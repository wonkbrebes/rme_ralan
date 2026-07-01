<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokTransaksi extends Model
{
    protected $table = 'stock_transactions';

    public $timestamps = false;

    protected $fillable = [
        'medicine_id',
        'user_id',
        'tipe_transaksi',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'referensi_id',
        'keterangan',
        'tanggal',
    ];

    protected $casts = [
        'jumlah'        => 'integer',
        'stok_sebelum'  => 'integer',
        'stok_sesudah'  => 'integer',
        'tanggal'       => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'medicine_id');
    }
}
