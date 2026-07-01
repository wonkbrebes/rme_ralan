<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    protected $table = 'medicines';

    protected $fillable = [
        'kode_obat',
        'nama_obat',
        'nama_generik',
        'satuan',
        'kategori',
        'bentuk_sediaan',
        'golongan_obat',
        'harga_jual',
        'is_active',
    ];

    protected $casts = [
        'harga_jual' => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function resepItem(): HasMany
    {
        return $this->hasMany(ResepItem::class, 'medicine_id');
    }

    public function stokObat(): HasMany
    {
        return $this->hasMany(StokObat::class, 'medicine_id');
    }

    // ── SCOPE ─────────────────────────────────────────────────────

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
