<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Resep, ResepItem, Obat, StokObat, StokTransaksi, Kunjungan};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class FarmasiController extends Controller
{
    // GET /api/farmasi — Daftar resep hari ini
    public function index(Request $request): JsonResponse
    {
        $resep = Resep::with(['kunjungan.pasien', 'dokter', 'items.obat'])
            ->whereDate('tanggal_resep', today())
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(20);
        return response()->json($resep);
    }

    // GET /api/farmasi/resep/{id}
    public function showResep(int $id): JsonResponse
    {
        $resep = Resep::with(['kunjungan.pasien', 'dokter', 'items.obat.stokObat'])->findOrFail($id);
        return response()->json(['data' => $resep]);
    }

    // POST /api/farmasi/resep/{id}/proses — Apoteker mulai meracik
    public function prosesResep(Request $request, int $id): JsonResponse
    {
        $resep = Resep::findOrFail($id);
        if ($resep->status !== 'Menunggu') {
            return response()->json(['message' => 'Resep sudah diproses.'], 422);
        }
        // Check stock availability for all items
        foreach ($resep->items as $item) {
            $stok = StokObat::where('medicine_id', $item->medicine_id)->first();
            if (!$stok || $stok->stok_tersedia < $item->jumlah) {
                return response()->json([
                    'message' => 'Stok obat ' . ($item->obat->nama_obat ?? '') . ' tidak mencukupi.',
                    'stok_tersedia' => $stok->stok_tersedia ?? 0,
                    'dibutuhkan' => $item->jumlah,
                ], 422);
            }
        }
        $resep->update(['status' => 'Diracik', 'catatan_apoteker' => $request->catatan_apoteker]);
        return response()->json(['message' => 'Resep sedang diproses.', 'data' => $resep]);
    }

    // POST /api/farmasi/resep/{id}/serahkan — Serahkan obat & potong stok
    public function serahkanObat(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $resep = Resep::with('items.obat')->findOrFail($id);
            if ($resep->status !== 'Diracik') {
                return response()->json(['message' => 'Resep belum/sudah diproses.'], 422);
            }
            // Potong stok untuk setiap item
            foreach ($resep->items as $item) {
                $stok = StokObat::where('medicine_id', $item->medicine_id)->lockForUpdate()->first();
                if (!$stok || $stok->stok_tersedia < $item->jumlah) {
                    throw new \RuntimeException('Stok ' . ($item->obat->nama_obat ?? '') . ' tidak cukup.');
                }
                $stokSebelum = $stok->stok_tersedia;
                $stok->decrement('stok_tersedia', $item->jumlah);
                // Record stock transaction
                StokTransaksi::create([
                    'medicine_id' => $item->medicine_id,
                    'tipe_transaksi' => 'Keluar',
                    'jumlah' => $item->jumlah,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSebelum - $item->jumlah,
                    'referensi_id' => $resep->id,
                    'keterangan' => 'Penyerahan resep #' . $resep->no_resep,
                    'tanggal' => now(),
                ]);
            }
            $resep->update(['status' => 'Diserahkan']);
            return response()->json(['message' => 'Obat berhasil diserahkan dan stok dipotong.']);
        });
    }

    // GET /api/farmasi/stok
    public function stok(Request $request): JsonResponse
    {
        $stok = Obat::with('stokObat')
            ->where('is_active', true)
            ->when($request->search, fn($q, $s) => $q->where('nama_obat', 'ilike', "%{$s}%"))
            ->orderBy('nama_obat')
            ->paginate(30);
        return response()->json($stok);
    }

    // GET /api/farmasi/stok/kritis
    public function stokKritis(): JsonResponse
    {
        $kritis = StokObat::with('obat')
            ->whereColumn('stok_tersedia', '<=', 'stok_minimum')
            ->get();
        return response()->json(['data' => $kritis, 'total' => $kritis->count()]);
    }
}
