<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokObat extends Model
{
    protected $table = 'medicine_stocks';

    const CREATED_AT = null;

    protected $fillable = [
        'medicine_id',
        'stok_tersedia',
        'stok_minimum',
        'tanggal_kadaluarsa',
        'no_batch',
    ];

    protected $casts = [
        'stok_tersedia'      => 'integer',
        'stok_minimum'       => 'integer',
        'tanggal_kadaluarsa' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'medicine_id');
    }

    // ── SCOPE ─────────────────────────────────────────────────────

    public function scopeKritis(Builder $query): Builder
    {
        return $query->whereColumn('stok_tersedia', '<=', 'stok_minimum');
    }
}
