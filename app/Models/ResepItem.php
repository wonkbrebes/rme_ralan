<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResepItem extends Model
{
    protected $table = 'prescription_items';

    public $timestamps = false;

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'jumlah',
        'aturan_pakai',
        'catatan',
        'harga_satuan',
    ];

    protected $casts = [
        'jumlah'       => 'integer',
        'harga_satuan' => 'decimal:2',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'prescription_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'medicine_id');
    }
}
