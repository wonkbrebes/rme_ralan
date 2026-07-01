<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Tagihan extends Model
{
    protected $table = 'billing';

    protected $fillable = [
        'visit_id',
        'no_tagihan',
        'total_amount',
        'diskon',
        'amount_bpjs',
        'amount_pasien',
        'status',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'diskon'        => 'decimal:2',
        'amount_bpjs'   => 'decimal:2',
        'amount_pasien' => 'decimal:2',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'visit_id');
    }

    public function tagihanItem(): HasMany
    {
        return $this->hasMany(TagihanItem::class, 'billing_id');
    }
}
