<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilLab extends Model
{
    protected $table = 'lab_results';

    public $timestamps = false;

    protected $fillable = [
        'lab_request_id',
        'processed_by',
        'parameter',
        'hasil',
        'nilai_normal',
        'satuan',
        'flag',
        'file_hasil',
        'tanggal_hasil',
    ];

    protected $casts = [
        'tanggal_hasil' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function orderLab(): BelongsTo
    {
        return $this->belongsTo(OrderLab::class, 'lab_request_id');
    }
}
