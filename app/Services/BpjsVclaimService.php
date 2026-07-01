<?php

namespace App\Services;

use Illuminate\Support\Facades\{Http, Cache, Log};
use App\Exceptions\BpjsApiException;

class BpjsVclaimService
{
    private string $baseUrl;
    private string $consId;
    private string $secretKey;
    private string $userKey;

    public function __construct()
    {
        $this->baseUrl   = config('bpjs.base_url');
        $this->consId    = config('bpjs.cons_id');
        $this->secretKey = config('bpjs.secret_key');
        $this->userKey   = config('bpjs.user_key');
    }

    // ─── PUBLIC API ───────────────────────────────────────────────────────

    /**
     * Cek eligibilitas peserta BPJS
     */
    public function cekEligibilitas(string $noBpjs, string $tanggalPelayanan): array
    {
        $cacheKey = "bpjs:elig:{$noBpjs}:" . date('Ymd', strtotime($tanggalPelayanan));

        return Cache::remember($cacheKey, config('bpjs.cache_ttl', 3600), function () use ($noBpjs, $tanggalPelayanan) {
            return $this->request('GET', "/peserta/nokartu/{$noBpjs}/tanggal/{$tanggalPelayanan}");
        });
    }

    /**
     * Generate SEP (Surat Eligibilitas Peserta)
     */
    public function generateSep(array $data): array
    {
        return $this->request('POST', '/sep/2.0/insert', [
            'request' => [
                'sep' => [
                    'noKartu'      => $data['no_bpjs'],
                    'tglSep'       => $data['tanggal'],
                    'ppkPelayanan' => config('bpjs.app_code'),
                    'jnsPelayanan' => '1',             // 1=Rawat Jalan
                    'klsRawat'     => $data['kelas_rawat'] ?? '3',
                    'noMR'         => $data['no_rm'],
                    'rujukan'      => $data['rujukan'] ?? null,
                    'diagnosa'     => $data['diagnosa'],
                    'poliTujuan'   => $data['kode_poli'],
                    'keterangan'   => '',
                    'user'         => $data['petugas'],
                ],
            ],
        ]);
    }

    /**
     * Submit klaim BPJS
     */
    public function submitKlaim(array $data): array
    {
        return $this->request('POST', '/klaim/2.0/insert', ['request' => $data]);
    }

    /**
     * Cek status klaim
     */
    public function cekStatusKlaim(string $noSep): array
    {
        return $this->request('GET', "/klaim/cari/{$noSep}");
    }

    /**
     * Cari data faskes tujuan rujukan
     */
    public function cariFaskes(string $namFaskes, string $jenisFaskes = '1'): array
    {
        return $this->request('GET', "/faskes/cari/{$jenisFaskes}/{$namFaskes}");
    }

    // ─── PRIVATE ──────────────────────────────────────────────────────────

    private function request(string $method, string $endpoint, array $body = []): array
    {
        $timestamp = now()->timestamp;
        $signature = $this->generateSignature($timestamp);

        try {
            $http = Http::withHeaders([
                'X-cons-id'    => $this->consId,
                'X-timestamp'  => (string) $timestamp,
                'X-signature'  => $signature,
                'user_key'     => $this->userKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(10);

            $response = strtoupper($method) === 'GET'
                ? $http->get($this->baseUrl . $endpoint)
                : $http->post($this->baseUrl . $endpoint, $body);

            if ($response->failed()) {
                Log::error('BPJS VClaim Error', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                throw new BpjsApiException("BPJS API gagal [{$response->status()}]: " . $response->body());
            }

            return $response->json();

        } catch (BpjsApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::critical('BPJS VClaim Exception', [
                'endpoint' => $endpoint,
                'message'  => $e->getMessage(),
            ]);
            throw new BpjsApiException('Koneksi ke BPJS VClaim gagal: ' . $e->getMessage());
        }
    }

    /**
     * Generate HMAC-SHA256 signature sesuai spesifikasi BPJS
     */
    private function generateSignature(int $timestamp): string
    {
        $payload = $this->consId . '&' . $timestamp;
        $hmac    = hash_hmac('sha256', $payload, $this->secretKey, true);
        return base64_encode($hmac);
    }
}
