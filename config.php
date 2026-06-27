<?php

date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'simrs_db');

// Membuat koneksi
$koneksi = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,

);

// Cek koneksi
if ($koneksi->connect_errno) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

// Menggunakan UTF-8
$koneksi->set_charset("utf8");

// Fungsi bantu
function query($sql)
{
    global $koneksi;

    $result = $koneksi->query($sql);

    if (!$result) {
        die("Query Error: " . $koneksi->error);
    }

    return $result;
}