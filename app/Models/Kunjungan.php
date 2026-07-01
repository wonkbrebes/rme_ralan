<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne, HasMany};

class Kunjungan extends Model
{
    protected $table      = 'visits';
    protected $primaryKey = 'id';

    const STATUS_TERDAFTAR   = 'Terdaftar';
    const STATUS_MENUNGGU    = 'Menunggu';
    const STATUS_PEMERIKSAAN = 'Dalam Pemeriksaan';
    const STATUS_SELESAI     = 'Selesai';
    const STATUS_BATAL       = 'Batal';
    const STATUS_DIRUJUK     = 'Dirujuk';

    protected $fillable = [
        'queue_id', 'patient_id', 'polyclinic_id', 'doctor_id',
        'no_kunjungan', 'tanggal_kunjungan', 'status',
        'jenis_pembayaran', 'no_sep', 'keluhan_utama',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'patient_id');
    }

    public function poliklinik(): BelongsTo
    {
        return $this->belongsTo(Poliklinik::class, 'polyclinic_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'doctor_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalDokter::class, 'jadwal_id');
    }

    public function rekamMedis(): HasOne
    {
        return $this->hasOne(RekamMedis::class, 'kunjungan_id');
    }

    public function antrian(): HasOne
    {
        return $this->hasOne(Antrian::class, 'kunjungan_id');
    }

    public function tagihan(): HasOne
    {
        return $this->hasOne(Tagihan::class, 'kunjungan_id');
    }

    public function bpjsSep(): HasOne
    {
        return $this->hasOne(BpjsSep::class, 'kunjungan_id');
    }

    public function orderLab(): HasMany
    {
        return $this->hasMany(OrderLab::class, 'kunjungan_id');
    }

    public function orderRadiologi(): HasMany
    {
        return $this->hasMany(OrderRadiologi::class, 'kunjungan_id');
    }

    // ── SCOPE ─────────────────────────────────────────────────────

    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal_kunjungan', today());
    }

    public function scopeAktif($query)
    {
        return $query->whereIn('status', [
            self::STATUS_TERDAFTAR,
            self::STATUS_MENUNGGU,
            self::STATUS_PEMERIKSAAN,
        ]);
    }

    // ── STATIC ────────────────────────────────────────────────────

    /**
     * Generate No. Kunjungan: KUN-YYYYMMDD-0001
     */
    public static function generateNoKunjungan(): string
    {
        $tanggal = now()->format('Ymd');
        $last    = static::where('no_kunjungan', 'like', "KUN-{$tanggal}-%")
                          ->orderByDesc('no_kunjungan')->first();
        $urut    = $last ? (int) substr($last->no_kunjungan, -4) + 1 : 1;
        return "KUN-{$tanggal}-" . str_pad($urut, 4, '0', STR_PAD_LEFT);
    }
}
