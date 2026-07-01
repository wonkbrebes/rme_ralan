<?php

namespace App\Services;

use App\Models\{Antrian, Kunjungan, Poliklinik};
use Illuminate\Support\Facades\DB;
use Illuminate\Broadcasting\Channel;

class AntrianService
{
    /**
     * Generate nomor antrian baru: UMU-001, ANA-001, dst.
     */
    public function generateNoAntrian(int $poliklinikId): string
    {
        $poli   = Poliklinik::findOrFail($poliklinikId);
        $prefix = strtoupper(substr($poli->kode_poli, 0, 3));

        $last = Antrian::where('poliklinik_id', $poliklinikId)
                        ->whereDate('created_at', today())
                        ->max('urutan');

        $urutan = ($last ?? 0) + 1;
        return $prefix . '-' . str_pad($urutan, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Panggil pasien berikutnya di suatu poli
     */
    public function panggilBerikutnya(int $poliklinikId): ?Antrian
    {
        return DB::transaction(function () use ($poliklinikId) {
            $antrian = Antrian::where('poliklinik_id', $poliklinikId)
                               ->where('status', 'MENUNGGU')
                               ->orderBy('urutan')
                               ->lockForUpdate()
                               ->first();

            if ($antrian) {
                $antrian->update([
                    'status'      => 'DIPANGGIL',
                    'dipanggil_at'=> now(),
                ]);

                // Broadcast ke channel antrian publik
                broadcast(new \App\Events\AntrianDipanggil($antrian))->toOthers();
            }

            return $antrian;
        });
    }

    /**
     * Tandai antrian sedang dalam pemeriksaan
     */
    public function mulaiPemeriksaan(int $antrianId): void
    {
        $antrian = Antrian::findOrFail($antrianId);
        $antrian->update(['status' => 'DALAM_PEMERIKSAAN', 'mulai_at' => now()]);
        $antrian->kunjungan->update(['status' => Kunjungan::STATUS_PEMERIKSAAN]);
    }

    /**
     * Tandai antrian selesai
     */
    public function selesaikan(int $antrianId): void
    {
        $antrian = Antrian::findOrFail($antrianId);
        $antrian->update(['status' => 'SELESAI', 'selesai_at' => now()]);
    }

    /**
     * Ambil data monitor semua poli aktif hari ini
     */
    public function getMonitorData(): array
    {
        return Poliklinik::aktif()
            ->with([
                'antrian' => fn($q) => $q->hariIni()
                    ->whereIn('status', ['MENUNGGU', 'DIPANGGIL', 'DALAM_PEMERIKSAAN'])
                    ->orderBy('urutan')
                    ->with('kunjungan.pasien'),
            ])
            ->get()
            ->map(fn($poli) => [
                'poliklinik_id'   => $poli->poliklinik_id,
                'nama_poliklinik' => $poli->nama_poliklinik,
                'kode_poli'       => $poli->kode_poli,
                'sedang_dilayani' => $poli->antrian
                    ->whereIn('status', ['DIPANGGIL', 'DALAM_PEMERIKSAAN'])
                    ->first()?->only(['no_antrian', 'kunjungan']),
                'jumlah_menunggu' => $poli->antrian->where('status', 'MENUNGGU')->count(),
                'jumlah_selesai'  => Antrian::where('poliklinik_id', $poli->poliklinik_id)
                    ->hariIni()->where('status', 'SELESAI')->count(),
                'antrian_menunggu'=> $poli->antrian
                    ->where('status', 'MENUNGGU')
                    ->take(5)
                    ->values(),
            ])
            ->toArray();
    }

    /**
     * Estimasi waktu tunggu (menit)
     */
    public function estimasiWaktuTunggu(int $poliklinikId, int $urutan): int
    {
        $rata = Antrian::where('poliklinik_id', $poliklinikId)
            ->whereNotNull('mulai_at')
            ->whereNotNull('selesai_at')
            ->whereDate('created_at', today())
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, mulai_at, selesai_at)) as avg_menit')
            ->value('avg_menit') ?? 10;

        $sedangDilayani = Antrian::where('poliklinik_id', $poliklinikId)
            ->hariIni()
            ->where('status', 'DALAM_PEMERIKSAAN')
            ->max('urutan') ?? 0;

        return max(0, ($urutan - $sedangDilayani) * (int) $rata);
    }
}
