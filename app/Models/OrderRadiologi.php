<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne};

class OrderRadiologi extends Model
{
    protected $table = 'radiology_requests';

    protected $fillable = [
        'visit_id',
        'requested_by',
        'no_permintaan',
        'jenis_pemeriksaan',
        'regio_anatomi',
        'modalitas',
        'persiapan_khusus',
        'catatan_dokter',
        'status',
        'tanggal_minta',
    ];

    protected $casts = [
        'tanggal_minta' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'visit_id');
    }

    public function hasilRadiologi(): HasOne
    {
        return $this->hasOne(HasilRadiologi::class, 'radiology_request_id');
    }
}
