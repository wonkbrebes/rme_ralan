<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'billing_id',
        'kasir_id',
        'amount_paid',
        'metode_bayar',
        'no_transaksi',
        'tanggal_bayar',
        'bukti_pembayaran',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'amount_paid'   => 'decimal:2',
        'tanggal_bayar' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'billing_id');
    }
}
