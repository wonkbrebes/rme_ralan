<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Antrian extends Model
{
    protected $table = 'queues';

    protected $fillable = [
        'patient_id',
        'polyclinic_id',
        'doctor_schedule_id',
        'no_antrian',
        'tanggal',
        'status',
        'jenis_daftar',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'patient_id');
    }

    public function poliklinik(): BelongsTo
    {
        return $this->belongsTo(Poliklinik::class, 'polyclinic_id');
    }

    public function jadwalDokter(): BelongsTo
    {
        return $this->belongsTo(JadwalDokter::class, 'doctor_schedule_id');
    }
}
