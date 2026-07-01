<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalDokter extends Model
{
    protected $table = 'doctor_schedules';

    protected $fillable = [
        'doctor_id',
        'polyclinic_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'kuota_harian',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'kuota_harian' => 'integer',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'doctor_id');
    }

    public function poliklinik(): BelongsTo
    {
        return $this->belongsTo(Poliklinik::class, 'polyclinic_id');
    }

    // ── SCOPE ─────────────────────────────────────────────────────

    public function scopeHariIni(Builder $query): Builder
    {
        $namaHari = match (now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };

        return $query->where('hari', $namaHari);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
