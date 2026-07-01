<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpjsClaim extends Model
{
    protected $table = 'bpjs_claims';

    protected $fillable = [
        'billing_id',
        'visit_id',
        'no_sep',
        'no_klaim',
        'nilai_klaim',
        'status_klaim',
        'alasan_penolakan',
        'tanggal_submit',
        'tanggal_respon',
    ];

    protected $casts = [
        'nilai_klaim'     => 'decimal:2',
        'tanggal_submit'  => 'date',
        'tanggal_respon'  => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'visit_id');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'billing_id');
    }
}
