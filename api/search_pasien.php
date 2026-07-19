<?php
// File: api/search_pasien.php
// Endpoint AJAX Server-Side Search untuk data Pasien dengan PostgreSQL / Supabase
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_helper.php';

try {
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    if ($keyword !== '') {
        // Cari berdasarkan No RM, NIK, Nama Lengkap, atau No Telepon (maksimal 15 baris)
        $sql = "SELECT id, no_rm, nama_lengkap, nik, tanggal_lahir, jenis_kelamin, alamat, no_telepon, no_bpjs, gol_darah, jenis_pasien 
                FROM patients 
                WHERE no_rm ILIKE :kw1 OR nama_lengkap ILIKE :kw2 OR nik ILIKE :kw3 OR no_telepon ILIKE :kw4 
                ORDER BY id DESC 
                LIMIT 15";
        $params = [
            'kw1' => '%' . $keyword . '%',
            'kw2' => '%' . $keyword . '%',
            'kw3' => '%' . $keyword . '%',
            'kw4' => '%' . $keyword . '%'
        ];
        $results = db_select($sql, $params);
    } else {
        // Jika kotak pencarian kosong, ambil 15 pasien terbaru saja
        $sql = "SELECT id, no_rm, nama_lengkap, nik, tanggal_lahir, jenis_kelamin, alamat, no_telepon, no_bpjs, gol_darah, jenis_pasien 
                FROM patients 
                ORDER BY id DESC 
                LIMIT 15";
        $results = db_select($sql);
    }

    echo json_encode([
        'status' => 'success',
        'count'  => is_array($results) ? count($results) : 0,
        'data'   => is_array($results) ? $results : []
    ]);
} catch (Exception $e) {
    error_log("Error in api/search_pasien.php: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan saat mencari data pasien: ' . $e->getMessage(),
        'data'    => []
    ]);
}
