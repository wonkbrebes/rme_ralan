<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekamMedis extends Model
{
    protected $table      = 'rekam_medis';
    protected $primaryKey = 'rm_id';

    const STATUS_DRAFT = 'Draft';
    const STATUS_FINAL = 'Final';

    protected $fillable = [
        'kunjungan_id', 'dokter_id',
        // Anamnesis
        'keluhan_utama', 'riwayat_penyakit', 'riwayat_alergi',
        // Vital sign
        'tekanan_darah_sistol', 'tekanan_darah_diastol',
        'suhu', 'nadi', 'respirasi', 'berat_badan', 'tinggi_badan', 'spo2',
        // Pemeriksaan
        'pemeriksaan_fisik', 'pemeriksaan_penunjang_catatan',
        // Rencana
        'catatan_dokter', 'tindak_lanjut',
        'status', 'satusehat_pushed_at',
    ];

    protected $casts = [
        'satusehat_pushed_at' => 'datetime',
        'suhu'                => 'decimal:1',
        'berat_badan'         => 'decimal:1',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function diagnosa()
    {
        return $this->hasMany(RmDiagnosis::class, 'rm_id');
    }

    public function diagnosaPrimer()
    {
        return $this->hasOne(RmDiagnosis::class, 'rm_id')
                    ->where('jenis_diagnosis', 'Primer');
    }

    public function resep()
    {
        return $this->hasOne(Resep::class, 'rm_id');
    }

    public function orderLab()
    {
        return $this->hasMany(OrderLab::class, 'rm_id');
    }

    public function orderRadiologi()
    {
        return $this->hasMany(OrderRadiologi::class, 'rm_id');
    }

    public function suratRujukan()
    {
        return $this->hasMany(SuratRujukan::class, 'rm_id');
    }

    // ── ACCESSOR ──────────────────────────────────────────────────

    public function getImtAttribute(): ?float
    {
        if ($this->berat_badan && $this->tinggi_badan) {
            $tb = $this->tinggi_badan / 100;
            return round($this->berat_badan / ($tb * $tb), 1);
        }
        return null;
    }

    public function getTekananDarahAttribute(): string
    {
        if ($this->tekanan_darah_sistol && $this->tekanan_darah_diastol) {
            return "{$this->tekanan_darah_sistol}/{$this->tekanan_darah_diastol}";
        }
        return '-';
    }

    // ── HELPERS ───────────────────────────────────────────────────

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function sudahDipushSatuSehat(): bool
    {
        return ! is_null($this->satusehat_pushed_at);
    }
}
