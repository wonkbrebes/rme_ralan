<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Tagihan, TagihanItem, Pembayaran, Kunjungan, TarifLayanan, Resep};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    // GET /api/kasir — Daftar tagihan hari ini
    public function index(Request $request): JsonResponse
    {
        $tagihan = Tagihan::with(['kunjungan.pasien', 'kunjungan.poliklinik'])
            ->whereDate('created_at', today())
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(20);
        return response()->json($tagihan);
    }

    // GET /api/kasir/tagihan/{id}
    public function showTagihan(int $id): JsonResponse
    {
        $tagihan = Tagihan::with(['kunjungan.pasien', 'kunjungan.poliklinik', 'kunjungan.dokter', 'items'])->findOrFail($id);
        return response()->json(['data' => $tagihan]);
    }

    // POST /api/kasir/tagihan/{visit_id}/generate — Auto-generate tagihan dari kunjungan
    public function generateTagihan(int $visitId): JsonResponse
    {
        return DB::transaction(function () use ($visitId) {
            $kunjungan = Kunjungan::with(['poliklinik', 'dokter'])->findOrFail($visitId);
            
            // Check if billing already exists
            $existing = Tagihan::where('visit_id', $visitId)->first();
            if ($existing) {
                return response()->json(['message' => 'Tagihan sudah ada.', 'data' => $existing], 422);
            }

            $isBpjs = $kunjungan->jenis_pembayaran === 'BPJS';
            $items = [];
            $total = 0;

            // 1. Biaya konsultasi
            $tarifKonsul = $kunjungan->dokter && $kunjungan->dokter->spesialisasi !== 'Dokter Umum'
                ? TarifLayanan::where('kode_tarif', 'KON002')->first()
                : TarifLayanan::where('kode_tarif', 'KON001')->first();
            
            if ($tarifKonsul) {
                $harga = $isBpjs ? $tarifKonsul->harga_bpjs : $tarifKonsul->harga_umum;
                $items[] = [
                    'service_rate_id' => $tarifKonsul->id,
                    'nama_layanan' => $tarifKonsul->nama_layanan,
                    'kategori' => 'Konsultasi',
                    'jumlah' => 1,
                    'harga_satuan' => $harga,
                    'subtotal' => $harga,
                ];
                $total += $harga;
            }

            // 2. Biaya administrasi
            $tarifAdmin = TarifLayanan::where('kode_tarif', 'ADM001')->first();
            if ($tarifAdmin) {
                $harga = $isBpjs ? $tarifAdmin->harga_bpjs : $tarifAdmin->harga_umum;
                $items[] = [
                    'service_rate_id' => $tarifAdmin->id,
                    'nama_layanan' => $tarifAdmin->nama_layanan,
                    'kategori' => 'Administrasi',
                    'jumlah' => 1,
                    'harga_satuan' => $harga,
                    'subtotal' => $harga,
                ];
                $total += $harga;
            }

            // 3. Biaya obat dari resep
            $resep = Resep::with('items.obat')->where('visit_id', $visitId)->first();
            if ($resep) {
                foreach ($resep->items as $ri) {
                    $subtotal = $ri->jumlah * ($ri->harga_satuan ?? $ri->obat->harga_jual ?? 0);
                    $items[] = [
                        'service_rate_id' => null,
                        'nama_layanan' => $ri->obat->nama_obat ?? 'Obat',
                        'kategori' => 'Farmasi',
                        'jumlah' => $ri->jumlah,
                        'harga_satuan' => $ri->harga_satuan ?? $ri->obat->harga_jual ?? 0,
                        'subtotal' => $subtotal,
                    ];
                    $total += $subtotal;
                }
            }

            // Create billing record
            $noTagihan = 'INV-' . now()->format('Ymd') . '-' . str_pad(Tagihan::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            
            $tagihan = Tagihan::create([
                'visit_id' => $visitId,
                'no_tagihan' => $noTagihan,
                'total_amount' => $total,
                'diskon' => 0,
                'amount_bpjs' => $isBpjs ? $total : 0,
                'amount_pasien' => $isBpjs ? 0 : $total,
                'status' => 'Menunggu Bayar',
            ]);

            foreach ($items as $item) {
                $tagihan->items()->create($item);
            }

            return response()->json(['message' => 'Tagihan berhasil dibuat.', 'data' => $tagihan->load('items')], 201);
        });
    }

    // POST /api/kasir/tagihan/{id}/bayar
    public function bayar(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'metode_bayar' => 'required|in:Tunai,QRIS,Transfer,BPJS,Debit,Asuransi Lain,Gratis',
            'amount_paid'  => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($id, $data) {
            $tagihan = Tagihan::findOrFail($id);
            if ($tagihan->status === 'Lunas') {
                return response()->json(['message' => 'Tagihan sudah lunas.'], 422);
            }

            $noTransaksi = 'PAY-' . now()->format('YmdHis') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

            Pembayaran::create([
                'billing_id' => $tagihan->id,
                'kasir_id' => auth()->id() ?? 1,
                'amount_paid' => $data['amount_paid'],
                'metode_bayar' => $data['metode_bayar'],
                'no_transaksi' => $noTransaksi,
                'tanggal_bayar' => now(),
                'status' => 'Sukses',
            ]);

            $tagihan->update(['status' => 'Lunas']);

            // Create notification record for WA & Email
            \App\Models\Notifikasi::create([
                'user_id' => auth()->id() ?? 1,
                'tipe' => 'WhatsApp',
                'judul' => 'Bukti Pembayaran SIMRS (' . $noTransaksi . ')',
                'pesan' => "Terima kasih, pembayaran Tagihan {$tagihan->no_tagihan} sebesar Rp " . number_format($data['amount_paid'], 0, ',', '.') . " via {$data['metode_bayar']} telah LUNAS. Struk digital dikirim ke WA & Email.",
                'nomor_tujuan' => $tagihan->kunjungan->pasien->no_hp ?? env('WA_SENDER_NUMBER', '0882-1529-0459'),
                'referensi' => $noTransaksi,
                'status' => 'Terkirim',
                'sent_at' => now(),
            ]);

            return response()->json(['message' => 'Pembayaran berhasil.', 'no_transaksi' => $noTransaksi]);
        });
    }
}
