<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PasienController;
use App\Http\Controllers\Api\PendaftaranController;
use App\Http\Controllers\Api\EmrController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\FarmasiController;
use App\Http\Controllers\Api\KasirController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\SettingsController;

// ─── Endpoint Cek Kesehatan Sistem ───────────────────────────────
Route::get('/ping', function () {
    return response()->json([
        'status'    => 'ok',
        'message'   => 'SIMRS Rawat Jalan RSUD Puruk Cahu API is active and running',
        'timestamp' => now(),
        'database_connection' => config('database.default')
    ]);
});

// ─── MODUL PUBLIK (Tanpa Login) ─────────────────────────────────
Route::prefix('public')->group(function () {
    Route::get('profil-rs', [PublicController::class, 'profilRs']);
    Route::get('jadwal-dokter', [PublicController::class, 'jadwalDokter']);
    Route::get('antrian-live', [PublicController::class, 'antrianLive']);
    Route::get('statistik', [PublicController::class, 'statistik']);
});

// ─── MODUL 1: Manajemen Pasien & RM ─────────────────────────────
Route::apiResource('pasien', PasienController::class);
Route::get('pasien/cari', [PasienController::class, 'cari']);

// ─── MODUL 2: Admisi & Pendaftaran Rawat Jalan ──────────────────
Route::apiResource('pendaftaran', PendaftaranController::class);
Route::post('pendaftaran/{id}/cek-bpjs', [PendaftaranController::class, 'cekBpjs']);
Route::post('pendaftaran/{id}/generate-sep', [PendaftaranController::class, 'generateSep']);

// ─── MODUL 3: Rekam Medis Elektronik (EMR) ──────────────────────
Route::get('emr/{kunjungan_id}', [EmrController::class, 'show']);
Route::post('emr/{kunjungan_id}/vital', [EmrController::class, 'storeVital']);
Route::post('emr/{kunjungan_id}', [EmrController::class, 'storeAnamnesis']);
Route::post('emr/{rm_id}/diagnosis', [EmrController::class, 'storeDiagnosis']);
Route::post('emr/{rm_id}/resep', [EmrController::class, 'storeResep']);
Route::post('emr/{rm_id}/selesai', [EmrController::class, 'finalisasi']);

// ─── MODUL 4: Farmasi & Apotek ──────────────────────────────────
Route::prefix('farmasi')->group(function () {
    Route::get('/', [FarmasiController::class, 'index']);
    Route::get('resep/{id}', [FarmasiController::class, 'showResep']);
    Route::post('resep/{id}/proses', [FarmasiController::class, 'prosesResep']);
    Route::post('resep/{id}/serahkan', [FarmasiController::class, 'serahkanObat']);
    Route::get('stok', [FarmasiController::class, 'stok']);
    Route::get('stok/kritis', [FarmasiController::class, 'stokKritis']);
});

// ─── MODUL 5: Kasir & Billing ───────────────────────────────────
Route::prefix('kasir')->group(function () {
    Route::get('/', [KasirController::class, 'index']);
    Route::get('tagihan/{id}', [KasirController::class, 'showTagihan']);
    Route::post('tagihan/{visit_id}/generate', [KasirController::class, 'generateTagihan']);
    Route::post('tagihan/{id}/bayar', [KasirController::class, 'bayar']);
});

// ─── MODUL 6: Master Data ───────────────────────────────────────
Route::prefix('master')->group(function () {
    Route::apiResource('dokter', MasterDataController::class)->parameters(['dokter' => 'id'])->names('master.dokter');
    Route::apiResource('poliklinik', MasterDataController::class)->parameters(['poliklinik' => 'id'])->names('master.poliklinik');
    Route::get('obat', [MasterDataController::class, 'listObat']);
    Route::get('icd10', [MasterDataController::class, 'listIcd10']);
    Route::get('tarif', [MasterDataController::class, 'listTarif']);
});

// ─── MODUL 7: Laporan ───────────────────────────────────────────
Route::prefix('laporan')->group(function () {
    Route::get('dashboard', [LaporanController::class, 'dashboard']);
    Route::get('kunjungan-harian', [LaporanController::class, 'kunjunganHarian']);
    Route::get('10-besar-penyakit', [LaporanController::class, 'sepuluhBesarPenyakit']);
    Route::get('pendapatan', [LaporanController::class, 'pendapatan']);
});

// ─── MODUL 8: Notifikasi ────────────────────────────────────────
Route::prefix('notifikasi')->group(function () {
    Route::get('/', [NotifikasiController::class, 'index']);
    Route::post('/', [NotifikasiController::class, 'store']);
    Route::get('unread-count', [NotifikasiController::class, 'unreadCount']);
    Route::post('{id}/read', [NotifikasiController::class, 'markAsRead']);
});

// ─── MODUL 9: Pengaturan Sistem ─────────────────────────────────
Route::prefix('settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index']);
    Route::put('/', [SettingsController::class, 'update']);
});

// ─── Autentikasi Pengguna ───────────────────────────────────────
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
