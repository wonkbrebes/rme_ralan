<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Dokter, Poliklinik, Obat, Icd10, TarifLayanan};
use Illuminate\Http\{Request, JsonResponse};

class MasterDataController extends Controller
{
    // GET /api/master/dokter
    public function index(Request $request): JsonResponse
    {
        $data = Dokter::with(['user', 'poliklinik'])
            ->when($request->search, fn($q, $s) => $q->whereHas('user', fn($u) => $u->where('nama', 'ilike', "%{$s}%")))
            ->orderBy('id')
            ->paginate(20);
        return response()->json($data);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Dokter::with(['user', 'poliklinik', 'jadwal'])->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'polyclinic_id' => 'required|exists:polyclinics,id',
            'no_str' => 'required|string|max:50',
            'spesialisasi' => 'required|string|max:100',
            'gelar' => 'required|string|max:20',
        ]);
        $dokter = Dokter::create($data);
        return response()->json(['message' => 'Dokter berhasil ditambahkan.', 'data' => $dokter], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dokter = Dokter::findOrFail($id);
        $dokter->update($request->only(['polyclinic_id', 'no_str', 'spesialisasi', 'gelar']));
        return response()->json(['message' => 'Data dokter berhasil diperbarui.', 'data' => $dokter]);
    }

    public function destroy(int $id): JsonResponse
    {
        Dokter::findOrFail($id)->delete();
        return response()->json(['message' => 'Dokter berhasil dihapus.']);
    }

    // Obat
    public function listObat(Request $request): JsonResponse
    {
        $obat = Obat::with('stokObat')
            ->when($request->search, fn($q, $s) => $q->where('nama_obat', 'ilike', "%{$s}%"))
            ->when($request->kategori, fn($q, $k) => $q->where('kategori', $k))
            ->orderBy('nama_obat')
            ->paginate(30);
        return response()->json($obat);
    }

    // ICD-10
    public function listIcd10(Request $request): JsonResponse
    {
        $icd = Icd10::when($request->search, fn($q, $s) => $q->where('kode', 'ilike', "%{$s}%")->orWhere('nama_penyakit', 'ilike', "%{$s}%"))
            ->orderBy('kode')
            ->paginate(30);
        return response()->json($icd);
    }

    // Tarif
    public function listTarif(Request $request): JsonResponse
    {
        $tarif = TarifLayanan::when($request->kategori, fn($q, $k) => $q->where('kategori', $k))
            ->where('is_active', true)
            ->orderBy('nama_layanan')
            ->paginate(30);
        return response()->json($tarif);
    }
}
