<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Kunjungan, Diagnosis, Tagihan, Pembayaran};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    // GET /api/laporan/dashboard — Dashboard Eksekutif
    public function dashboard(): JsonResponse
    {
        $today = today();
        return response()->json([
            'kunjungan_hari_ini' => Kunjungan::whereDate('tanggal_kunjungan', $today)->count(),
            'kunjungan_bulan_ini' => Kunjungan::whereMonth('tanggal_kunjungan', $today->month)->whereYear('tanggal_kunjungan', $today->year)->count(),
            'pendapatan_hari_ini' => Pembayaran::whereDate('tanggal_bayar', $today)->where('status', 'BERHASIL')->sum('amount_paid'),
            'pendapatan_bulan_ini' => Pembayaran::whereMonth('tanggal_bayar', $today->month)->whereYear('tanggal_bayar', $today->year)->where('status', 'BERHASIL')->sum('amount_paid'),
            'total_pasien' => DB::table('patients')->count(),
            'total_dokter' => DB::table('doctors')->count(),
            'antrian_aktif' => DB::table('queues')->whereDate('tanggal', $today)->whereIn('status', ['MENUNGGU', 'DIPANGGIL'])->count(),
            'stok_kritis' => DB::table('medicine_stocks')->whereColumn('stok_tersedia', '<=', 'stok_minimum')->count(),
        ]);
    }

    // GET /api/laporan/kunjungan-harian
    public function kunjunganHarian(Request $request): JsonResponse
    {
        $tanggal = $request->tanggal ?? today()->format('Y-m-d');
        $kunjungan = Kunjungan::with(['pasien', 'poliklinik', 'dokter'])
            ->whereDate('tanggal_kunjungan', $tanggal)
            ->orderBy('created_at')
            ->get();
        return response()->json(['tanggal' => $tanggal, 'total' => $kunjungan->count(), 'data' => $kunjungan]);
    }

    // GET /api/laporan/10-besar-penyakit
    public function sepuluhBesarPenyakit(Request $request): JsonResponse
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;

        $data = Diagnosis::join('icd10_codes', 'diagnoses.icd10_id', '=', 'icd10_codes.id')
            ->join('visits', 'diagnoses.visit_id', '=', 'visits.id')
            ->whereMonth('visits.tanggal_kunjungan', $bulan)
            ->whereYear('visits.tanggal_kunjungan', $tahun)
            ->select('icd10_codes.kode', 'icd10_codes.nama_penyakit', DB::raw('count(*) as jumlah'))
            ->groupBy('icd10_codes.kode', 'icd10_codes.nama_penyakit')
            ->orderByDesc('jumlah')
            ->limit(10)
            ->get();

        return response()->json(['bulan' => $bulan, 'tahun' => $tahun, 'data' => $data]);
    }

    // GET /api/laporan/pendapatan
    public function pendapatan(Request $request): JsonResponse
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;

        $harian = Pembayaran::whereMonth('tanggal_bayar', $bulan)
            ->whereYear('tanggal_bayar', $tahun)
            ->where('status', 'BERHASIL')
            ->select(DB::raw('DATE(tanggal_bayar) as tanggal'), DB::raw('SUM(amount_paid) as total'), DB::raw('COUNT(*) as jumlah_transaksi'))
            ->groupBy(DB::raw('DATE(tanggal_bayar)'))
            ->orderBy('tanggal')
            ->get();

        return response()->json([
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_bulan' => $harian->sum('total'),
            'data_harian' => $harian,
        ]);
    }
}
