<?php
// File: api/search_obat.php
// Endpoint AJAX Server-Side Search untuk Katalog Obat dengan PostgreSQL / Supabase
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_helper.php';

try {
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    if ($keyword !== '') {
        $sql = "SELECT obat_id, kode_obat, nama_obat, satuan, stok 
                FROM obat 
                WHERE is_active = true AND (kode_obat ILIKE :kw1 OR nama_obat ILIKE :kw2) 
                ORDER BY nama_obat ASC 
                LIMIT 25";
        $params = [
            'kw1' => '%' . $keyword . '%',
            'kw2' => '%' . $keyword . '%'
        ];
        $rows = db_select($sql, $params);
    } else {
        $sql = "SELECT obat_id, kode_obat, nama_obat, satuan, stok 
                FROM obat 
                WHERE is_active = true 
                ORDER BY nama_obat ASC 
                LIMIT 25";
        $rows = db_select($sql);
    }

    $daftar_obat = [];
    if (is_array($rows) && !empty($rows)) {
        foreach ($rows as $ob) {
            $stok_val = intval($ob['stok']);
            if ($stok_val <= 0) {
                $status_obat = 'Habis';
            } elseif ($stok_val <= 15) {
                $status_obat = 'Menipis';
            } else {
                $status_obat = 'Aman';
            }
            $daftar_obat[] = [
                'obat_id'  => $ob['obat_id'] ?? null,
                'kode'     => $ob['kode_obat'],
                'nama'     => $ob['nama_obat'],
                'kategori' => '-',
                'satuan'   => ucfirst($ob['satuan']),
                'stok'     => number_format($stok_val, 0, ',', '.'),
                'stok_num' => $stok_val,
                'status'   => $status_obat
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'count'  => count($daftar_obat),
        'data'   => $daftar_obat
    ]);
} catch (Exception $e) {
    error_log("Error in api/search_obat.php: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan saat mencari data obat: ' . $e->getMessage(),
        'data'    => []
    ]);
}
