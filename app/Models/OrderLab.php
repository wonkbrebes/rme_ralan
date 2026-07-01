<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class OrderLab extends Model
{
    protected $table = 'lab_requests';

    protected $fillable = [
        'visit_id',
        'requested_by',
        'no_permintaan',
        'jenis_pemeriksaan',
        'sampel_dibutuhkan',
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

    public function hasilLab(): HasMany
    {
        return $this->hasMany(HasilLab::class, 'lab_request_id');
    }
}
