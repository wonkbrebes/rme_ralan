<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends Model
{
    protected $table = 'diagnoses';

    const UPDATED_AT = null;

    protected $fillable = [
        'visit_id',
        'icd10_id',
        'tipe',
        'keterangan_tambahan',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'visit_id');
    }

    public function icd10(): BelongsTo
    {
        return $this->belongsTo(Icd10::class, 'icd10_id');
    }
}
