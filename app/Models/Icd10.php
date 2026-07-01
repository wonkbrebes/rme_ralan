<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icd10 extends Model
{
    protected $table = 'icd10_codes';

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama_penyakit',
        'kategori',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function diagnosis(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'icd10_id');
    }
}
