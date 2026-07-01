<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pasien extends Model
{
    use HasFactory;

    protected $table      = 'patients';
    protected $primaryKey = 'id';

    protected $fillable = [
        'no_rm', 'nik', 'no_bpjs', 'nama_lengkap',
        'tanggal_lahir', 'jenis_kelamin', 'gol_darah', 'alamat',
        'kota', 'kecamatan', 'no_telepon', 'jenis_pasien', 'alergi',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    // ── RELASI ────────────────────────────────────────────────────

    public function kunjungan()
    {
        return $this->hasMany(Kunjungan::class, 'patient_id');
    }

    public function kunjunganTerakhir()
    {
        return $this->hasOne(Kunjungan::class, 'patient_id')
                    ->latestOfMany('tanggal_kunjungan');
    }

    public function bpjsSep()
    {
        return $this->hasMany(BpjsSep::class, 'patient_id');
    }

    // ── ACCESSOR ──────────────────────────────────────────────────

    public function getUmurAttribute(): int
    {
        return $this->tanggal_lahir->age;
    }

    // ── SCOPE ─────────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query;
    }

    public function scopeCari($query, string $keyword)
    {
        return $query->where('nama_lengkap', 'like', "%{$keyword}%")
                     ->orWhere('no_rm', $keyword)
                     ->orWhere('nik', $keyword)
                     ->orWhere('no_bpjs', $keyword);
    }

    // ── STATIC ────────────────────────────────────────────────────

    /**
     * Generate No. RM otomatis: RM-YYYY-00001
     */
    public static function generateNoRm(): string
    {
        $tahun = now()->year;
        $last  = static::where('no_rm', 'like', "RM-{$tahun}-%")
                        ->orderByDesc('no_rm')
                        ->first();
        $urut  = $last ? (int) substr($last->no_rm, -5) + 1 : 1;
        return "RM-{$tahun}-" . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }
}
