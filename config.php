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

// Memuat sumber data terpusat (Single Source of Truth)
require_once __DIR__ . '/simrs_data.php';