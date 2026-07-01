<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmrRequest;
use App\Models\{Kunjungan, RekamMedis, RmDiagnosis};
use App\Jobs\PushSatuSehatJob;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class EmrController extends Controller
{
    /**
     * GET /api/emr/{kunjungan_id}
     */
    public function show(int $kunjunganId): JsonResponse
    {
        $kunjungan = Kunjungan::with([
            'pasien',
            'dokter',
            'poliklinik',
            'rekamMedis.diagnosa.icd',
            'rekamMedis.resep.items.obat',
            'rekamMedis.orderLab.hasil',
            'rekamMedis.orderRadiologi',
        ])->findOrFail($kunjunganId);

        return response()->json(['data' => $kunjungan]);
    }

    /**
     * POST /api/emr/{kunjungan_id}/vital — Input tanda vital (perawat)
     */
    public function simpanVital(Request $request, int $kunjunganId): JsonResponse
    {
        $data = $request->validate([
            'tekanan_darah_sistol'  => 'nullable|integer|between:50,300',
            'tekanan_darah_diastol' => 'nullable|integer|between:30,200',
            'suhu'                  => 'nullable|numeric|between:30,45',
            'nadi'                  => 'nullable|integer|between:20,300',
            'respirasi'             => 'nullable|integer|between:5,80',
            'berat_badan'           => 'nullable|numeric|between:1,500',
            'tinggi_badan'          => 'nullable|numeric|between:20,300',
            'spo2'                  => 'nullable|integer|between:50,100',
        ]);

        $rm = RekamMedis::firstOrCreate(
            ['kunjungan_id' => $kunjunganId],
            ['dokter_id' => Kunjungan::findOrFail($kunjunganId)->dokter_id, 'status' => RekamMedis::STATUS_DRAFT]
        );

        $rm->update($data);

        // Update status kunjungan
        Kunjungan::findOrFail($kunjunganId)->update(['status' => Kunjungan::STATUS_PEMERIKSAAN]);

        return response()->json(['message' => 'Tanda vital tersimpan.', 'data' => $rm]);
    }

    /**
     * POST /api/emr/{kunjungan_id} — Simpan anamnesis & pemeriksaan (dokter)
     */
    public function simpan(StoreEmrRequest $request, int $kunjunganId): JsonResponse
    {
        $rm = RekamMedis::where('kunjungan_id', $kunjunganId)->firstOrFail();

        if ($rm->isFinal()) {
            return response()->json(['message' => 'EMR sudah final. Gunakan fitur addendum.'], 422);
        }

        $rm->update($request->validated());

        return response()->json(['message' => 'EMR tersimpan.', 'data' => $rm]);
    }

    /**
     * POST /api/emr/{kunjungan_id}/diagnosis — Tambah diagnosis ICD-10
     */
    public function tambahDiagnosis(Request $request, int $rmId): JsonResponse
    {
        $data = $request->validate([
            'kode_icd10'      => 'required|string|max:10|exists:icd_diagnosis,kode',
            'nama_diagnosis'  => 'required|string|max:500',
            'jenis_diagnosis' => 'required|in:Primer,Sekunder,Komplikasi,Komorbid',
        ]);

        $rm = RekamMedis::findOrFail($rmId);

        if ($rm->isFinal()) {
            return response()->json(['message' => 'EMR sudah final.'], 422);
        }

        $dx = $rm->diagnosa()->create($data);

        return response()->json(['message' => 'Diagnosis ditambahkan.', 'data' => $dx], 201);
    }

    /**
     * DELETE /api/emr/{rm_id}/diagnosis/{dx_id}
     */
    public function hapusDiagnosis(int $rmId, int $dxId): JsonResponse
    {
        $rm = RekamMedis::findOrFail($rmId);

        if ($rm->isFinal()) {
            return response()->json(['message' => 'EMR sudah final.'], 422);
        }

        RmDiagnosis::where('rm_id', $rmId)->findOrFail($dxId)->delete();

        return response()->json(['message' => 'Diagnosis dihapus.']);
    }

    /**
     * POST /api/emr/{rm_id}/selesai — Finalkan EMR & trigger push SatuSehat
     */
    public function selesai(int $rmId): JsonResponse
    {
        return DB::transaction(function () use ($rmId) {
            $rm = RekamMedis::with('kunjungan')->findOrFail($rmId);

            if ($rm->isFinal()) {
                return response()->json(['message' => 'EMR sudah final.'], 422);
            }

            // Validasi: harus ada minimal 1 diagnosis primer
            if (! $rm->diagnosaPrimer()->exists()) {
                return response()->json(['message' => 'Minimal 1 diagnosis primer wajib diisi.'], 422);
            }

            $rm->update(['status' => RekamMedis::STATUS_FINAL]);

            // Update status kunjungan
            $rm->kunjungan->update(['status' => Kunjungan::STATUS_SELESAI]);

            // Dispatch job push ke SatuSehat (async, queue)
            PushSatuSehatJob::dispatch($rm->kunjungan)->onQueue('satusehat');

            return response()->json(['message' => 'EMR berhasil difinalisasi dan sedang dikirim ke SatuSehat.']);
        });
    }

    /**
     * PUT /api/emr/{rm_id}/addendum
     */
    public function addendum(Request $request, int $rmId): JsonResponse
    {
        $rm   = RekamMedis::findOrFail($rmId);
        $note = $request->validate(['catatan_addendum' => 'required|string|max:2000'])['catatan_addendum'];

        // Simpan addendum ke tabel audit / kolom addendum
        $rm->update([
            'addendum' => ($rm->addendum ? $rm->addendum . "\n\n---\n" : '') .
                          '[' . now()->format('d/m/Y H:i') . ' - ' . auth()->user()->name . "]\n{$note}",
        ]);

        return response()->json(['message' => 'Addendum berhasil ditambahkan.']);
    }

    /**
     * GET /api/emr/{rm_id}/pdf
     */
    public function pdf(int $rmId)
    {
        $rm = RekamMedis::with([
            'kunjungan.pasien', 'kunjungan.dokter', 'kunjungan.poliklinik',
            'diagnosa', 'resep.items.obat',
        ])->findOrFail($rmId);

        $pdf = Pdf::loadView('pdf.emr-ringkasan', ['rm' => $rm])
                  ->setPaper('a4');

        return $pdf->download("EMR-{$rm->kunjungan->no_kunjungan}.pdf");
    }
}
