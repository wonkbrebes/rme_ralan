<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rujukan extends Model
{
    protected $table = 'referrals';

    protected $fillable = [
        'visit_id',
        'tipe_rujukan',
        'faskes_tujuan',
        'unit_tujuan',
        'alasan',
        'diagnosa_rujukan',
        'no_surat_rujukan',
        'tanggal_rujuk',
        'status',
    ];

    protected $casts = [
        'tanggal_rujuk' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'visit_id');
    }
}
