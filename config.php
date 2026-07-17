<?php
/**
 * File Konfigurasi SIMRS & RME
 * Terhubung otomatis ke Supabase PostgreSQL (wonkbrebes.web.id)
 */
date_default_timezone_set('Asia/Jakarta');

// Memanggil helper database Supabase
require_once __DIR__ . '/db_helper.php';

$db_conn_error = '';
try {
    $pdo_koneksi = get_db_connection();
} catch (Exception $e) {
    $db_conn_error = $e->getMessage();
}

function renderFriendlyErrorPage($title = 'Terjadi Kesalahan', $message = 'Maaf, sistem sedang mengalami gangguan. Silakan muat ulang halaman atau hubungi admin jika masalah berlanjut.') {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($title) . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f8fafc;color:#111827;padding:40px;} .error-box{max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #d1d5db;border-radius:16px;box-shadow:0 18px 40px rgba(15,23,42,.08);padding:32px;} .error-title{font-size:24px;font-weight:700;margin-bottom:12px;} .error-message{font-size:16px;line-height:1.7;color:#374151;} .error-help{margin-top:24px;padding:16px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:12px;color:#92400e;}</style></head><body><div class="error-box"><div class="error-title">' . htmlspecialchars($title) . '</div><div class="error-message">' . htmlspecialchars($message) . '</div><div class="error-help">Silakan coba muat ulang atau buka halaman lain. Jika ini terus terjadi, hubungi tim TI / admin sistem.</div></div></body></html>';
    exit;
}

set_exception_handler(function ($exception) {
    error_log('Unhandled exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    renderFriendlyErrorPage('Terjadi Kesalahan Tidak Terduga');
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('Shutdown error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        renderFriendlyErrorPage('Terjadi Kesalahan Sistem');
    }
});

/**
 * Fungsi bantu kompatibilitas query lama
 */
function query($sql, $params = [])
{
    return db_query($sql, $params);
}

function normalize_queue_status($status)
{
    $value = trim(strtolower((string) $status));
    $value = preg_replace('/[_-]+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = trim($value);

    $map = [
        'menunggu' => 'menunggu',
        'dipanggil' => 'dipanggil',
        'dalam pemeriksaan' => 'dalam_pemeriksaan',
        'dalam_pemeriksaan' => 'dalam_pemeriksaan',
        'sedang dilayani' => 'dipanggil',
        'dilayani' => 'dipanggil',
        'selesai' => 'selesai',
        'batal' => 'batal',
        'dibatalkan' => 'batal',
        'cancelled' => 'batal',
    ];

    return $map[$value] ?? 'menunggu';
}

function get_queue_status_label($status)
{
    switch (normalize_queue_status($status)) {
        case 'dipanggil':
            return 'Dipanggil';
        case 'dalam_pemeriksaan':
            return 'Sedang Dilayani';
        case 'selesai':
            return 'Selesai';
        case 'batal':
            return 'Batal';
        default:
            return 'Menunggu';
    }
}

function get_queue_badge_class($status)
{
    switch (normalize_queue_status($status)) {
        case 'dipanggil':
        case 'dalam_pemeriksaan':
            return 'badge-dipanggil';
        case 'selesai':
            return 'badge-selesai';
        case 'batal':
            return 'badge-menunggu';
        default:
            return 'badge-menunggu';
    }
}

function get_queue_db_status($status)
{
    switch (normalize_queue_status($status)) {
        case 'dipanggil':
            return 'Dipanggil';
        case 'dalam_pemeriksaan':
            return 'Dalam Pemeriksaan';
        case 'selesai':
            return 'Selesai';
        case 'batal':
            return 'Batal';
        default:
            return 'Menunggu';
    }
}

function get_queue_priority($status)
{
    switch (normalize_queue_status($status)) {
        case 'dipanggil':
            return 1;
        case 'dalam_pemeriksaan':
            return 2;
        case 'menunggu':
            return 3;
        case 'selesai':
            return 4;
        case 'batal':
            return 5;
        default:
            return 3;
    }
}

// Catatan: simrs_data.php TIDAK di-include di sini.
// Setiap halaman memiliki require_once simrs_data.php sendiri,
// ditempatkan SETELAH POST handler agar data selalu fresh.