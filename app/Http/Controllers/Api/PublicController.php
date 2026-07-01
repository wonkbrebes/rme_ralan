<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Poliklinik, JadwalDokter, Dokter, Antrian, Kunjungan};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    /**
     * GET /api/public/profil-rs — Info Rumah Sakit
     */
    public function profilRs(): JsonResponse
    {
        return response()->json([
            'nama'     => 'RSUD Puruk Cahu',
            'alamat'   => 'Jl. Jend. Sudirman No. 1, Puruk Cahu, Kabupaten Murung Raya, Kalimantan Tengah 73911',
            'telepon'  => '0882-1529-0459',
            'email'    => 'denynz17@gmail.com',
            'igd'      => '24 Jam',
            'visi'     => 'Menjadi Rumah Sakit Daerah yang Bermutu dan Terjangkau oleh Seluruh Lapisan Masyarakat',
            'misi'     => [
                'Memberikan pelayanan kesehatan yang paripurna, bermutu, dan terjangkau',
                'Meningkatkan kualitas Sumber Daya Manusia melalui pendidikan dan pelatihan',
                'Meningkatkan sarana dan prasarana rumah sakit',
                'Meningkatkan kesejahteraan pegawai',
            ],
            'fasilitas' => [
                'IGD 24 Jam',
                'Rawat Jalan (7 Poliklinik Spesialis)',
                'Rawat Inap',
                'Laboratorium',
                'Radiologi (Rontgen & USG)',
                'Farmasi / Apotek',
                'Ambulance 24 Jam',
            ],
        ]);
    }

    /**
     * GET /api/public/jadwal-dokter — Jadwal dokter (hari ini atau semua)
     */
    public function jadwalDokter(Request $request): JsonResponse
    {
        $hari = $request->hari ?? $this->getNamaHari();

        $jadwal = JadwalDokter::with(['dokter', 'poliklinik'])
            ->where('is_active', true)
            ->when($hari !== 'semua', fn($q) => $q->where('hari', $hari))
            ->orderBy('polyclinic_id')
            ->orderBy('jam_mulai')
            ->get()
            ->map(function ($j) {
                return [
                    'id'            => $j->id,
                    'hari'          => $j->hari,
                    'poli'          => $j->poliklinik->nama_poli ?? '-',
                    'kode_poli'     => $j->poliklinik->kode_poli ?? '-',
                    'dokter'        => ($j->dokter->gelar ?? '') . ' ' . ($j->dokter->user->nama ?? 'TBD'),
                    'spesialisasi'  => $j->dokter->spesialisasi ?? '-',
                    'jam'           => substr($j->jam_mulai, 0, 5) . ' - ' . substr($j->jam_selesai, 0, 5),
                    'kuota_harian'  => $j->kuota_harian,
                    'sisa_kuota'    => $this->hitungSisaKuota($j),
                ];
            });

        return response()->json([
            'hari'    => $hari,
            'tanggal' => now()->format('d F Y'),
            'data'    => $jadwal,
        ]);
    }

    /**
     * GET /api/public/antrian-live — Status antrian real-time per poli
     */
    public function antrianLive(): JsonResponse
    {
        $poliklinik = Poliklinik::where('is_active', true)
            ->get()
            ->map(function ($poli) {
                $antrianHariIni = Antrian::where('polyclinic_id', $poli->id)
                    ->whereDate('tanggal', today())
                    ->get();

                $sedangDilayani = $antrianHariIni->where('status', 'Dipanggil')->first();
                $menunggu       = $antrianHariIni->where('status', 'Menunggu')->count();
                $selesai        = $antrianHariIni->where('status', 'Selesai')->count();
                $total          = $antrianHariIni->count();

                return [
                    'poli'             => $poli->nama_poli,
                    'kode_poli'        => $poli->kode_poli,
                    'sedang_dilayani'  => $sedangDilayani->no_antrian ?? '-',
                    'jumlah_menunggu'  => $menunggu,
                    'jumlah_selesai'   => $selesai,
                    'total_hari_ini'   => $total,
                ];
            });

        return response()->json([
            'tanggal' => now()->format('d F Y'),
            'waktu'   => now()->format('H:i:s'),
            'data'    => $poliklinik,
        ]);
    }

    /**
     * GET /api/public/statistik — Statistik ringkas untuk landing page
     */
    public function statistik(): JsonResponse
    {
        $hariIni    = Kunjungan::whereDate('tanggal_kunjungan', today())->count();
        $bulanIni   = Kunjungan::whereMonth('tanggal_kunjungan', now()->month)
                                ->whereYear('tanggal_kunjungan', now()->year)->count();
        $totalPasien = DB::table('patients')->count();
        $totalDokter = DB::table('doctors')->count();

        return response()->json([
            'kunjungan_hari_ini'  => $hariIni,
            'kunjungan_bulan_ini' => $bulanIni,
            'total_pasien'        => $totalPasien,
            'total_dokter'        => $totalDokter,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function getNamaHari(): string
    {
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        return $days[now()->format('l')] ?? 'Senin';
    }

    private function hitungSisaKuota(JadwalDokter $jadwal): int
    {
        $terpakai = Antrian::where('doctor_schedule_id', $jadwal->id)
            ->whereDate('tanggal', today())
            ->whereNotIn('status', ['Batal'])
            ->count();

        return max(0, $jadwal->kuota_harian - $terpakai);
    }
}
