<?php

namespace App\Services;

use App\Models\{RekamMedis, Kunjungan};
use App\Exceptions\SatuSehatApiException;
use Illuminate\Support\Facades\{Http, Cache, Log};

class SatuSehatService
{
    private string $baseUrl;
    private string $orgId;

    public function __construct()
    {
        $this->baseUrl = config('satusehat.base_url');
        $this->orgId   = config('satusehat.org_id');
    }

    // ─── PUSH DATA KUNJUNGAN ──────────────────────────────────────────────

    /**
     * Push seluruh data kunjungan ke SatuSehat (FHIR R4)
     * Dipanggil dari PushSatuSehatJob setelah EMR difinalisasi
     */
    public function pushKunjungan(Kunjungan $kunjungan): void
    {
        $rm    = $kunjungan->rekamMedis()->with(['diagnosa', 'resep.items.obat'])->first();
        $token = $this->getAccessToken();

        // 1. Encounter (kunjungan)
        $encounterId = $this->pushEncounter($kunjungan, $token);
        $kunjungan->update(['satusehat_encounter_id' => $encounterId]);

        // 2. Observation (vital sign)
        $this->pushObservations($kunjungan, $rm, $encounterId, $token);

        // 3. Condition (diagnosa ICD-10)
        foreach ($rm->diagnosa as $dx) {
            $this->pushCondition($kunjungan, $dx, $encounterId, $token);
        }

        // 4. MedicationRequest (resep)
        if ($rm->resep) {
            foreach ($rm->resep->items as $item) {
                $this->pushMedicationRequest($kunjungan, $item, $encounterId, $token);
            }
        }

        // Tandai sudah di-push
        $rm->update(['satusehat_pushed_at' => now()]);
    }

    // ─── FHIR RESOURCES ───────────────────────────────────────────────────

    private function pushEncounter(Kunjungan $k, string $token): string
    {
        $payload = [
            'resourceType' => 'Encounter',
            'status'       => 'finished',
            'class'        => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => [
                'reference' => "Patient/{$k->pasien->satusehat_patient_id}",
                'display'   => $k->pasien->nama_lengkap,
            ],
            'participant' => [[
                'type'       => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType', 'code' => 'ATND', 'display' => 'attender']]]],
                'individual' => [
                    'reference' => "Practitioner/{$k->dokter->satusehat_practitioner_id}",
                    'display'   => $k->dokter->nama_lengkap,
                ],
            ]],
            'period' => [
                'start' => $k->created_at->toIso8601String(),
                'end'   => now()->toIso8601String(),
            ],
            'location' => [[
                'location' => [
                    'reference' => "Location/{$k->poliklinik->satusehat_location_id}",
                    'display'   => $k->poliklinik->nama_poliklinik,
                ],
            ]],
            'serviceProvider' => [
                'reference' => "Organization/{$this->orgId}",
            ],
        ];

        $response = $this->request('POST', '/fhir-r4/Encounter', $payload, $token);
        return $response['id'];
    }

    private function pushObservations(Kunjungan $k, RekamMedis $rm, string $encId, string $token): void
    {
        $vitals = [
            ['code' => '8480-6', 'display' => 'Systolic blood pressure', 'value' => $rm->tekanan_darah_sistol,   'unit' => 'mmHg', 'system' => 'http://unitsofmeasure.org', 'ucum' => 'mm[Hg]'],
            ['code' => '8462-4', 'display' => 'Diastolic blood pressure','value' => $rm->tekanan_darah_diastol,  'unit' => 'mmHg', 'system' => 'http://unitsofmeasure.org', 'ucum' => 'mm[Hg]'],
            ['code' => '8310-5', 'display' => 'Body temperature',        'value' => $rm->suhu,                   'unit' => 'C',    'system' => 'http://unitsofmeasure.org', 'ucum' => 'Cel'],
            ['code' => '8867-4', 'display' => 'Heart rate',              'value' => $rm->nadi,                   'unit' => '/min', 'system' => 'http://unitsofmeasure.org', 'ucum' => '/min'],
            ['code' => '59408-5','display' => 'Oxygen saturation',       'value' => $rm->spo2,                   'unit' => '%',    'system' => 'http://unitsofmeasure.org', 'ucum' => '%'],
            ['code' => '29463-7','display' => 'Body weight',             'value' => $rm->berat_badan,            'unit' => 'kg',   'system' => 'http://unitsofmeasure.org', 'ucum' => 'kg'],
        ];

        foreach ($vitals as $v) {
            if (! $v['value']) continue;

            $this->request('POST', '/fhir-r4/Observation', [
                'resourceType' => 'Observation',
                'status'       => 'final',
                'category'     => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/observation-category', 'code' => 'vital-signs']]]],
                'code'         => ['coding' => [['system' => 'http://loinc.org', 'code' => $v['code'], 'display' => $v['display']]]],
                'subject'      => ['reference' => "Patient/{$k->pasien->satusehat_patient_id}"],
                'encounter'    => ['reference' => "Encounter/{$encId}"],
                'effectiveDateTime' => now()->toIso8601String(),
                'valueQuantity'=> ['value' => (float) $v['value'], 'unit' => $v['unit'], 'system' => $v['system'], 'code' => $v['ucum']],
            ], $token);
        }
    }

    private function pushCondition(Kunjungan $k, $dx, string $encId, string $token): void
    {
        $this->request('POST', '/fhir-r4/Condition', [
            'resourceType'   => 'Condition',
            'clinicalStatus' => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical', 'code' => 'active']]],
            'category'       => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-category', 'code' => 'encounter-diagnosis']]]],
            'code'           => ['coding' => [['system' => 'http://hl7.org/fhir/sid/icd-10', 'code' => $dx->kode_icd10, 'display' => $dx->nama_diagnosis]]],
            'subject'        => ['reference' => "Patient/{$k->pasien->satusehat_patient_id}"],
            'encounter'      => ['reference' => "Encounter/{$encId}"],
        ], $token);
    }

    private function pushMedicationRequest(Kunjungan $k, $item, string $encId, string $token): void
    {
        $this->request('POST', '/fhir-r4/MedicationRequest', [
            'resourceType'  => 'MedicationRequest',
            'status'        => 'completed',
            'intent'        => 'order',
            'medicationCodeableConcept' => [
                'coding' => [['display' => $item->obat->nama_obat ?? $item->nama_obat]],
                'text'   => $item->obat->nama_obat ?? $item->nama_obat,
            ],
            'subject'       => ['reference' => "Patient/{$k->pasien->satusehat_patient_id}"],
            'encounter'     => ['reference' => "Encounter/{$encId}"],
            'authoredOn'    => now()->toIso8601String(),
            'dosageInstruction' => [[
                'text' => $item->aturan_pakai,
                'doseAndRate' => [[
                    'doseQuantity' => [
                        'value' => (float) $item->dosis,
                        'unit'  => $item->satuan ?? 'tablet',
                    ],
                ]],
            ]],
        ], $token);
    }

    // ─── AUTH & HTTP ──────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        return Cache::remember(
            config('satusehat.token_cache_key', 'satusehat_token'),
            config('satusehat.token_ttl', 3500),
            function () {
                $response = Http::asForm()
                    ->timeout(15)
                    ->post($this->baseUrl . '/oauth2/token', [
                        'client_id'     => config('satusehat.client_id'),
                        'client_secret' => config('satusehat.client_secret'),
                        'grant_type'    => 'client_credentials',
                    ]);

                if ($response->failed()) {
                    throw new SatuSehatApiException('Gagal mendapatkan token SatuSehat: ' . $response->body());
                }

                return $response->json('access_token');
            }
        );
    }

    private function request(string $method, string $endpoint, array $body, string $token): array
    {
        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->{strtolower($method)}($this->baseUrl . $endpoint, $body);

            if ($response->failed()) {
                Log::error('SatuSehat Error', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                throw new SatuSehatApiException("SatuSehat error [{$response->status()}]");
            }

            return $response->json();

        } catch (SatuSehatApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::critical('SatuSehat Exception', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
            throw new SatuSehatApiException('Koneksi ke SatuSehat gagal: ' . $e->getMessage());
        }
    }
}
