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