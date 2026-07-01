<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanItem extends Model
{
    protected $table = 'billing_items';

    public $timestamps = false;

    protected $fillable = [
        'billing_id',
        'service_rate_id',
        'nama_layanan',
        'kategori',
        'jumlah',
        'harga_satuan',
        'subtotal',
    ];

    protected $casts = [
        'jumlah'       => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal'     => 'decimal:2',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'billing_id');
    }
}
