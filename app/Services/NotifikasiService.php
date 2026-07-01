<?php

namespace App\Services;

use App\Models\Pasien;
use Illuminate\Support\Facades\{Http, Log};

class NotifikasiService
{
    private string $url;
    private string $token;
    private string $sender;

    public function __construct()
    {
        $this->url    = config('services.wa_gateway.url', env('WA_GATEWAY_URL'));
        $this->token  = config('services.wa_gateway.token', env('WA_GATEWAY_TOKEN'));
        $this->sender = config('services.wa_gateway.sender', env('WA_SENDER_NUMBER'));
    }

    /**
     * Kirim info antrian ke pasien via WhatsApp
     */
    public function kirimAntrianWa(Pasien $pasien, $kunjungan): void
    {
        $pesan = "🏥 *RSUD Puruk Cahu*\n\n"
            . "Yth. Bapak/Ibu *{$pasien->nama_lengkap}*,\n\n"
            . "Pendaftaran Anda telah berhasil:\n"
            . "📋 No. Antrian : *{$kunjungan->no_antrian}*\n"
            . "🏨 Poli          : *{$kunjungan->poliklinik->nama_poliklinik}*\n"
            . "👨‍⚕️ Dokter       : *{$kunjungan->dokter->nama_lengkap}*\n"
            . "📅 Tanggal     : *" . $kunjungan->tanggal_kunjungan->format('d/m/Y') . "*\n\n"
            . "Harap hadir sebelum nomor antrian Anda dipanggil.\n\n"
            . "_Terima kasih._";

        $this->kirimWa($pasien->no_telepon, $pesan);
    }

    /**
     * Kirim notifikasi antrian dipanggil
     */
    public function kirimPanggilanWa(Pasien $pasien, string $noAntrian, string $namaPoli): void
    {
        $pesan = "🔔 *Panggilan Antrian*\n\n"
            . "Yth. *{$pasien->nama_lengkap}*,\n"
            . "Nomor antrian Anda *{$noAntrian}* sedang dipanggil.\n"
            . "Segera menuju *{$namaPoli}*.\n\n"
            . "_RSUD Puruk Cahu_";

        $this->kirimWa($pasien->no_telepon, $pesan);
    }

    /**
     * Kirim notifikasi stok kritis ke admin farmasi
     */
    public function kirimAlertStokKritis(string $namaObat, int $sisaStok, string $noAdmin): void
    {
        $pesan = "⚠️ *Alert Stok Kritis*\n\n"
            . "Obat: *{$namaObat}*\n"
            . "Sisa stok: *{$sisaStok}* unit\n\n"
            . "Segera lakukan pengadaan.\n_SIMRS RSUD Puruk Cahu_";

        $this->kirimWa($noAdmin, $pesan);
    }

    // ─── PRIVATE ──────────────────────────────────────────────────────────

    private function kirimWa(string $noTelepon, string $pesan): void
    {
        // Normalisasi nomor telepon (0882 → 62882)
        $no = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $noTelepon));

        try {
            Http::withToken($this->token)
                ->timeout(10)
                ->post($this->url, [
                    'target'  => $no,
                    'message' => $pesan,
                    'sender'  => $this->sender,
                ]);
        } catch (\Exception $e) {
            Log::warning('Gagal kirim WhatsApp', [
                'nomor'   => $no,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
