<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poliklinik extends Model
{
    protected $table = 'polyclinics';

    protected $fillable = [
        'kode_poli',
        'nama_poli',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'polyclinic_id');
    }

    public function jadwalDokter(): HasMany
    {
        return $this->hasMany(JadwalDokter::class, 'polyclinic_id');
    }

    public function dokter(): HasMany
    {
        return $this->hasMany(Dokter::class, 'polyclinic_id');
    }

    // ── SCOPE ─────────────────────────────────────────────────────

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
