<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TarifLayanan extends Model
{
    protected $table = 'service_rates';

    protected $fillable = [
        'kode_tarif',
        'nama_layanan',
        'kategori',
        'harga_umum',
        'harga_bpjs',
        'is_active',
    ];

    protected $casts = [
        'harga_umum' => 'decimal:2',
        'harga_bpjs' => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    // ── SCOPE ─────────────────────────────────────────────────────

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
