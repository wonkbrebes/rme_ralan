<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Dokter extends Model
{
    protected $table = 'doctors';

    protected $fillable = [
        'user_id',
        'polyclinic_id',
        'no_str',
        'spesialisasi',
        'gelar',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function poliklinik(): BelongsTo
    {
        return $this->belongsTo(Poliklinik::class, 'polyclinic_id');
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'doctor_id');
    }

    public function jadwalDokter(): HasMany
    {
        return $this->hasMany(JadwalDokter::class, 'doctor_id');
    }
}
