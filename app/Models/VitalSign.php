<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    protected $table = 'vital_signs';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'recorded_by',
        'td_sistolik',
        'td_diastolik',
        'suhu',
        'nadi',
        'pernapasan',
        'berat_badan',
        'tinggi_badan',
        'saturasi_o2',
        'gcs',
        'recorded_at',
    ];

    protected $casts = [
        'td_sistolik'  => 'integer',
        'td_diastolik' => 'integer',
        'suhu'         => 'decimal:1',
        'nadi'         => 'integer',
        'pernapasan'   => 'integer',
        'berat_badan'  => 'decimal:1',
        'tinggi_badan' => 'decimal:1',
        'saturasi_o2'  => 'integer',
        'gcs'          => 'integer',
        'recorded_at'  => 'datetime',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'visit_id');
    }
}
