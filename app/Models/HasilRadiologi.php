<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilRadiologi extends Model
{
    protected $table = 'radiology_results';

    public $timestamps = false;

    protected $fillable = [
        'radiology_request_id',
        'processed_by',
        'deskripsi',
        'kesimpulan',
        'file_gambar',
        'tanggal_hasil',
    ];

    protected $casts = [
        'tanggal_hasil' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function orderRadiologi(): BelongsTo
    {
        return $this->belongsTo(OrderRadiologi::class, 'radiology_request_id');
    }
}
