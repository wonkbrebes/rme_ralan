<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use Illuminate\Http\{Request, JsonResponse};

class PasienController extends Controller
{
    /**
     * Cari pasien (GET /api/pasien/cari?q=nama|NIK|noRM)
     */
    public function cari(Request $request): JsonResponse
    {
        $q = $request->validate(['q' => 'required|string|min:3'])['q'];

        $pasien = Pasien::cari($q)
            ->aktif()
            ->with('kunjunganTerakhir')
            ->limit(10)
            ->get(['id', 'no_rm', 'nama_lengkap', 'tanggal_lahir', 'jenis_kelamin', 'jenis_pasien', 'no_bpjs']);

        return response()->json(['data' => $pasien]);
    }

    /**
     * GET /api/pasien
     */
    public function index(Request $request): JsonResponse
    {
        $pasien = Pasien::aktif()
            ->when($request->q, fn($q, $keyword) => $q->cari($keyword))
            ->paginate(20);

        return response()->json($pasien);
    }

    /**
     * POST /api/pasien
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nik'           => 'required|string|max:16|unique:patients,nik',
            'no_bpjs'       => 'nullable|string|max:20',
            'nama_lengkap'  => 'required|string|max:200',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'gol_darah'     => 'nullable|string|max:5',
            'alamat'        => 'required|string|max:500',
            'kota'          => 'nullable|string|max:100',
            'kecamatan'     => 'nullable|string|max:100',
            'no_telepon'    => 'nullable|string|max:20',
            'jenis_pasien'  => 'required|in:BPJS,Umum,Asuransi Lain,Gratis',
            'alergi'        => 'nullable|string|max:500',
        ]);

        $data['no_rm'] = Pasien::generateNoRm();

        $pasien = Pasien::create($data);

        return response()->json([
            'message' => 'Pasien berhasil didaftarkan.',
            'data'    => $pasien,
        ], 201);
    }

    /**
     * GET /api/pasien/{id}
     */
    public function show(int $id): JsonResponse
    {
        $pasien = Pasien::with('kunjungan.rekamMedis')->findOrFail($id);
        return response()->json(['data' => $pasien]);
    }

    /**
     * PUT /api/pasien/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $pasien = Pasien::findOrFail($id);

        $data = $request->validate([
            'nama_lengkap' => 'sometimes|string|max:200',
            'no_telepon'   => 'sometimes|string|max:20',
            'alamat'       => 'sometimes|string|max:500',
            'alergi'       => 'sometimes|string|max:500',
        ]);

        $pasien->update($data);

        return response()->json([
            'message' => 'Data pasien berhasil diperbarui.',
            'data'    => $pasien,
        ]);
    }

    /**
     * GET /api/pasien/{id}/riwayat
     */
    public function riwayat(int $id): JsonResponse
    {
        $pasien = Pasien::findOrFail($id);

        $riwayat = $pasien->kunjungan()
            ->with(['poliklinik', 'dokter'])
            ->orderByDesc('tanggal_kunjungan')
            ->paginate(10);

        return response()->json(['data' => $riwayat]);
    }

    /**
     * DELETE /api/pasien/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('admin');
        Pasien::findOrFail($id)->delete();
        return response()->json(['message' => 'Pasien berhasil dihapus.']);
    }
}
