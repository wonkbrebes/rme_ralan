<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePendaftaranRequest;
use App\Models\{Kunjungan, Antrian, Pasien};
use App\Services\{BpjsVclaimService, AntrianService, NotifikasiService};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class PendaftaranController extends Controller
{
    public function __construct(
        private BpjsVclaimService $bpjsService,
        private AntrianService    $antrianService,
        private NotifikasiService $notifService,
    ) {}

    /**
     * GET /api/pendaftaran — Daftar kunjungan hari ini
     */
    public function index(Request $request): JsonResponse
    {
        $kunjungan = Kunjungan::hariIni()
            ->with(['pasien', 'poliklinik', 'dokter', 'antrian'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->poliklinik_id, fn($q, $p) => $q->where('poliklinik_id', $p))
            ->orderBy('no_antrian')
            ->paginate(30);

        return response()->json($kunjungan);
    }

    /**
     * POST /api/pendaftaran — Daftarkan pasien ke poli (loket)
     */
    public function store(StorePendaftaranRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            // Cek duplikasi (pasien sudah daftar di poli yang sama hari ini)
            $sudahDaftar = Kunjungan::hariIni()
                ->where('pasien_id', $data['pasien_id'])
                ->where('poliklinik_id', $data['poliklinik_id'])
                ->whereNotIn('status', [Kunjungan::STATUS_BATAL])
                ->exists();

            if ($sudahDaftar) {
                return response()->json([
                    'message' => 'Pasien sudah terdaftar di poli ini hari ini.',
                ], 422);
            }

            // Generate identifiers
            $noAntrian   = $this->antrianService->generateNoAntrian($data['poliklinik_id']);
            $noKunjungan = Kunjungan::generateNoKunjungan();

            // Buat kunjungan
            $kunjungan = Kunjungan::create([
                ...$data,
                'no_kunjungan'      => $noKunjungan,
                'no_antrian'        => $noAntrian,
                'status'            => Kunjungan::STATUS_MENUNGGU,
                'registered_by'     => auth()->id(),
                'tanggal_kunjungan' => today(),
            ]);

            // Buat record antrian
            Antrian::create([
                'kunjungan_id'   => $kunjungan->kunjungan_id,
                'poliklinik_id'  => $data['poliklinik_id'],
                'no_antrian'     => $noAntrian,
                'urutan'         => (int) substr($noAntrian, -3),
                'status'         => 'Menunggu',
            ]);

            // Kirim notifikasi WA ke pasien
            $pasien = Pasien::find($data['pasien_id']);
            if ($pasien->no_telepon) {
                $this->notifService->kirimAntrianWa($pasien, $kunjungan);
            }

            return response()->json([
                'message'    => 'Pendaftaran berhasil.',
                'data'       => $kunjungan->load(['pasien', 'poliklinik', 'dokter']),
                'no_antrian' => $noAntrian,
            ], 201);
        });
    }

    /**
     * POST /api/pendaftaran/{id}/cek-bpjs
     */
    public function cekBpjs(Request $request, int $id): JsonResponse
    {
        $kunjungan = Kunjungan::with('pasien')->findOrFail($id);
        $pasien    = $kunjungan->pasien;

        if (! $pasien->no_bpjs) {
            return response()->json(['message' => 'Pasien tidak memiliki nomor BPJS.'], 422);
        }

        try {
            $result = $this->bpjsService->cekEligibilitas(
                $pasien->no_bpjs,
                $kunjungan->tanggal_kunjungan->format('Y-m-d')
            );

            return response()->json([
                'eligible'  => true,
                'data'      => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'eligible' => false,
                'message'  => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/pendaftaran/{id}/generate-sep
     */
    public function generateSep(Request $request, int $id): JsonResponse
    {
        $kunjungan = Kunjungan::with(['pasien', 'poliklinik'])->findOrFail($id);

        $sep = $this->bpjsService->generateSep([
            'no_bpjs'     => $kunjungan->pasien->no_bpjs,
            'tanggal'     => $kunjungan->tanggal_kunjungan->format('Y-m-d'),
            'no_rm'       => $kunjungan->pasien->no_rm,
            'kode_poli'   => $kunjungan->poliklinik->kode_bpjs_poli,
            'diagnosa'    => $request->diagnosa ?? 'Z00.0',
            'petugas'     => auth()->user()->name,
        ]);

        // Simpan SEP ke database
        $kunjungan->bpjsSep()->create([
            'pasien_id'   => $kunjungan->pasien_id,
            'no_sep'      => $sep['response']['sep']['noSep'] ?? null,
            'tanggal_sep' => $kunjungan->tanggal_kunjungan,
            'data_resp'   => json_encode($sep),
        ]);

        return response()->json([
            'message' => 'SEP berhasil dibuat.',
            'data'    => $sep,
        ]);
    }

    /**
     * POST /api/pendaftaran/{id}/batal
     */
    public function batal(Request $request, int $id): JsonResponse
    {
        $kunjungan = Kunjungan::findOrFail($id);

        if ($kunjungan->status === Kunjungan::STATUS_SELESAI) {
            return response()->json(['message' => 'Kunjungan yang sudah selesai tidak dapat dibatalkan.'], 422);
        }

        $kunjungan->update([
            'status'         => Kunjungan::STATUS_BATAL,
            'alasan_batal'   => $request->alasan ?? 'Dibatalkan oleh petugas.',
        ]);

        $kunjungan->antrian?->update(['status' => 'Batal']);

        return response()->json(['message' => 'Pendaftaran berhasil dibatalkan.']);
    }
}
