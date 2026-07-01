<?php

namespace App\Jobs;

use App\Models\Kunjungan;
use App\Services\SatuSehatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushSatuSehatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Kunjungan $kunjungan,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SatuSehatService $satuSehatService): void
    {
        try {
            $satuSehatService->pushKunjungan($this->kunjungan);

            Log::info('SatuSehat push berhasil', [
                'kunjungan_id'  => $this->kunjungan->id,
                'no_kunjungan'  => $this->kunjungan->no_kunjungan,
            ]);
        } catch (\Throwable $e) {
            Log::error('SatuSehat push gagal', [
                'kunjungan_id' => $this->kunjungan->id,
                'error'        => $e->getMessage(),
                'attempt'      => $this->attempts(),
            ]);

            throw $e;
        }
    }
}
