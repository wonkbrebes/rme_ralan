<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Resep extends Model
{
    protected $table = 'prescriptions';

    const CREATED_AT = null;

    protected $fillable = [
        'visit_id',
        'doctor_id',
        'no_resep',
        'tanggal_resep',
        'status',
        'catatan_apoteker',
    ];

    protected $casts = [
        'tanggal_resep' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'visit_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'doctor_id');
    }

    public function resepItem(): HasMany
    {
        return $this->hasMany(ResepItem::class, 'prescription_id');
    }
}
