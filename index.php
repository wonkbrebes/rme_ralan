<?php
// ============================================================
// 1. AMBIL PARAMETER HALAMAN & KONEKSI SUPABASE
// ============================================================
require_once __DIR__ . '/config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$msg_success = '';
$msg_error = '';

// HANDLE FORM SUBMISSION (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'register_pasien') {
        try {
            $no_rm = 'RM-' . date('Y') . '-' . sprintf('%04d', rand(1, 9999));
            // Sanitasi Integer / Angka
            $nik_raw = !empty($_POST['nik']) ? $_POST['nik'] : '0000000000000000';
            $nik = preg_replace('/[^0-9]/', '', $nik_raw);
            if (empty($nik)) $nik = '0000000000000000';

            $bpjs_raw = !empty($_POST['no_bpjs']) ? $_POST['no_bpjs'] : null;
            $no_bpjs = $bpjs_raw ? preg_replace('/[^0-9]/', '', $bpjs_raw) : null;

            $phone_raw = !empty($_POST['no_telepon']) ? $_POST['no_telepon'] : '-';
            $phone = preg_replace('/[^0-9+]/', '', $phone_raw);
            if (empty($phone)) $phone = '-';

            // Sanitasi String / Teks
            $nama = !empty($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : 'Pasien Baru';
            $tgl_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '2000-01-01';
            $jk_input = !empty($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : 'Laki-laki';
            $jk = ($jk_input === 'P' || strcasecmp($jk_input, 'perempuan') === 0) ? 'Perempuan' : 'Laki-laki';
            $alamat = !empty($_POST['alamat']) ? trim($_POST['alamat']) : '-';
            
            db_insert('patients', [
                'no_rm' => $no_rm,
                'nik' => $nik,
                'nama_lengkap' => $nama,
                'tanggal_lahir' => $tgl_lahir,
                'jenis_kelamin' => $jk,
                'no_telepon' => $phone,
                'alamat' => $alamat,
                'no_bpjs' => !empty($no_bpjs) ? $no_bpjs : null,
                'gol_darah' => !empty($_POST['golongan_darah']) ? $_POST['golongan_darah'] : null
            ]);
            try {
                $last_p = db_select_one("SELECT id FROM patients WHERE no_rm = '$no_rm' LIMIT 1");
                if ($last_p && isset($last_p['id'])) {
                    db_insert('queues', [
                        'patient_id' => $last_p['id'],
                        'no_antrian' => 'A-' . rand(100, 999),
                        'tanggal' => date('Y-m-d'),
                        'status' => 'Menunggu',
                        'jenis_daftar' => 'Offline'
                    ]);
                }
            } catch (Exception $ex) {
                // Abaikan jika struktur queues berbeda
            }
            $msg_success = "Pendaftaran pasien & antrian berhasil disimpan ke Supabase! (No. RM: $no_rm - $nama)";
        } catch (Exception $e) {
            $err = $e->getMessage();
            if (strpos($err, 'patients_nik_key') !== false || strpos($err, '23505') !== false || strpos($err, 'Unique violation') !== false) {
                $msg_error = "Gagal menyimpan: Nomor KTP / NIK tersebut sudah terdaftar di database Supabase! Silakan gunakan NIK yang berbeda.";
            } else {
                $msg_error = "Gagal menyimpan ke database Supabase: " . $err;
            }
        }
    } elseif ($action === 'panggil_antrian') {
        $msg_success = "Antrian nomor " . htmlspecialchars($_POST['no_antrian'] ?? '') . " sedang dipanggil ke poli!";
    } elseif ($action === 'simpan_emr') {
        $msg_success = "Catatan EMR / SOAP untuk pasien berhasil disimpan ke Supabase!";
    } elseif ($action === 'bayar_kasir') {
        $msg_success = "Pembayaran sebesar Rp " . htmlspecialchars($_POST['nominal'] ?? '0') . " berhasil diproses!";
    }
}


// ============================================================
// 2. DATA NAVIGASI (dengan key 'page' untuk identifikasi)
// ============================================================
$nav_items = [
    ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',                     'page' => 'dashboard'],
    ['label' => 'Antrian',     'icon' => 'fa-clipboard-list',                'page' => 'antrian'],
    ['label' => 'Pendaftaran', 'icon' => 'fa-user-plus',                     'page' => 'pendaftaran'],
    ['label' => 'EMR Dokter',  'icon' => 'fa-file-medical',                  'page' => 'emr'],
    ['label' => 'Farmasi',     'icon' => 'fa-prescription-bottle-medical',   'page' => 'farmasi'],
    ['label' => 'Kasir',       'icon' => 'fa-credit-card',                   'page' => 'kasir'],
];

// ============================================================
// 3. DATA UNTUK DASHBOARD
// ============================================================
$stats_dashboard = [
    ['label' => 'Total Pasien Hari Ini', 'value' => '128', 'trend' => '+ 12%', 'trend_type' => 'up',   'icon' => 'fa-users'],
    ['label' => 'Antrian Aktif',         'value' => '14',  'trend' => '- 5%',  'trend_type' => 'down', 'icon' => 'fa-hourglass-half'],
    ['label' => 'Pasien Rawat Inap',     'value' => '42',  'trend' => '+ 3%',  'trend_type' => 'up',   'icon' => 'fa-bed'],
    ['label' => 'Pasien IGD',            'value' => '9',   'trend' => '0%',    'trend_type' => 'neutral', 'icon' => 'fa-truck-medical'],
];

$antrian_terkini = [
    ['no' => 'A-024', 'nama' => 'Budiman Setiawan', 'poli' => 'Poli Jantung', 'dokter' => 'Dr. Sarah Wijaya, Sp.JP', 'status' => 'Dipanggil'],
    ['no' => 'A-025', 'nama' => 'Siti Rahayu',      'poli' => 'Poli Umum',    'dokter' => 'Dr. Anton Subekti',        'status' => 'Menunggu'],
    ['no' => 'A-021', 'nama' => 'Lestari Putri',    'poli' => 'Poli Anak',    'dokter' => 'Dr. Budi Santoso, Sp.A',   'status' => 'Selesai'],
    ['no' => 'A-026', 'nama' => 'Ahmad Fauzi',      'poli' => 'Poli Mata',    'dokter' => 'Dr. Yeni Amalia, Sp.M',    'status' => 'Menunggu'],
    ['no' => 'A-022', 'nama' => 'Rizky Ramadhan',   'poli' => 'Poli Gigi',    'dokter' => 'Drg. Melati Sukma',        'status' => 'Selesai'],
];

$distribusi = [
    ['label' => 'Rawat Jalan', 'jumlah' => 72, 'persen' => '56%', 'color' => '#2e7d32'],
    ['label' => 'Rawat Inap',   'jumlah' => 31, 'persen' => '24%', 'color' => '#4caf50'],
    ['label' => 'IGD',         'jumlah' => 25, 'persen' => '20%', 'color' => '#a5d6a7'],
];

// ============================================================
// 4. DATA UNTUK HALAMAN ANTRIAN
// ============================================================
$stats_antrian = [
    'total'        => 56,
    'dilayani'     => 14,
    'rata_tunggu'  => 32,
    'selesai'      => 42,
];

$antrian = [
    ['no' => 'A-024', 'nama' => 'Budiman Setiawan', 'poli' => 'Poli Jantung',  'estimasi' => '10:30', 'status' => 'dilayani'],
    ['no' => 'A-025', 'nama' => 'Siti Rahayu',      'poli' => 'Poli Umum',    'estimasi' => '10:45', 'status' => 'menunggu'],
    ['no' => 'A-026', 'nama' => 'Lestari Putri',    'poli' => 'Poli Anak',    'estimasi' => '11:00', 'status' => 'menunggu'],
    ['no' => 'A-027', 'nama' => 'Ahmad Fauzi',      'poli' => 'Poli Mata',    'estimasi' => '11:15', 'status' => 'menunggu'],
    ['no' => 'A-028', 'nama' => 'Rizky Ramadhan',   'poli' => 'Poli Gigi',    'estimasi' => '11:30', 'status' => 'menunggu'],
    ['no' => 'A-029', 'nama' => 'Dewi Lestari',     'poli' => 'Poli Kulit',   'estimasi' => '11:45', 'status' => 'menunggu'],
    ['no' => 'A-030', 'nama' => 'Fajar Nugroho',    'poli' => 'Poli THT',     'estimasi' => '12:00', 'status' => 'menunggu'],
];

$sedang_dilayani = $antrian[0];

// PERBAIKAN & INTEGRASI SUPABASE: Ambil data pasien antrian langsung dari database Supabase
if (function_exists('db_select')) {
    try {
        $db_antrian = db_select("SELECT no_rm as no, nama_lengkap as nama, COALESCE(jenis_pasien, 'Poli Umum') as poli, COALESCE(TO_CHAR(created_at, 'HH24:MI'), '10:30') as estimasi FROM patients ORDER BY id DESC");
        if (!empty($db_antrian) && is_array($db_antrian)) {
            $antrian = [];
            foreach ($db_antrian as $idx => $row) {
                $no_antrian = 'A-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
                $status_antrian = ($idx === 0) ? 'dilayani' : 'menunggu';
                $antrian[] = [
                    'no' => $no_antrian,
                    'nama' => $row['nama'],
                    'poli' => (strpos(strtolower($row['poli']), 'poli') !== false) ? $row['poli'] : 'Poli ' . $row['poli'],
                    'estimasi' => $row['estimasi'],
                    'status' => $status_antrian
                ];
            }
            $stats_antrian['total'] = count($antrian);
            $stats_antrian['dilayani'] = 1;
            $stats_antrian['selesai'] = max(0, $stats_antrian['total'] - 1);
            $sedang_dilayani = $antrian[0];
        }
    } catch (Exception $e) {
        // Fallback ke data simulasi
    }
}

$status_poli = [
    ['nama' => 'Poli Jantung', 'sekarang' => 2,  'total' => 15, 'icon' => 'fa-heart'],
    ['nama' => 'Poli Umum',    'sekarang' => 4,  'total' => 12, 'icon' => 'fa-user'],
    ['nama' => 'Poli Anak',    'sekarang' => 3,  'total' => 10, 'icon' => 'fa-child'],
    ['nama' => 'Poli Mata',    'sekarang' => 1,  'total' => 8,  'icon' => 'fa-eye'],
    ['nama' => 'Poli Gigi',    'sekarang' => 2,  'total' => 7,  'icon' => 'fa-tooth'],
    ['nama' => 'Poli Kulit',   'sekarang' => 1,  'total' => 6,  'icon' => 'fa-spa'],
    ['nama' => 'Poli THT',     'sekarang' => 1,  'total' => 5,  'icon' => 'fa-head-side-cough'],
];

// ============================================================
// 5. DATA UNTUK HALAMAN PENDAFTARAN
// ============================================================
$info_hari_ini = [
    ['label' => 'Total Pasien Terdaftar', 'value' => '0 Pasien'],
    ['label' => 'Pendaftaran Hari Ini',   'value' => '0 Pasien'],
    ['label' => 'Rujukan Internal',       'value' => '0 Pasien'],
    ['label' => 'Pasien Baru (Bulan Ini)', 'value' => '0 Pasien'],
];

$riwayat_pendaftaran = [
    ['no' => 'RM-2026-0001', 'nama' => 'Belum ada data pasien', 'poli' => 'Poli Umum', 'waktu' => '-'],
];

// Sinkronisasi data real-time dari tabel patients di Supabase PostgreSQL
try {
    if (function_exists('get_db_connection') && get_db_connection()) {
        $cnt_total = db_select_one("SELECT COUNT(*) as total FROM patients")['total'] ?? 0;
        $cnt_today = db_select_one("SELECT COUNT(*) as total FROM patients WHERE DATE(created_at) = CURRENT_DATE")['total'] ?? 0;
        if ($cnt_total > 0) {
            $info_hari_ini = [
                ['label' => 'Total Pasien Terdaftar', 'value' => $cnt_total . ' Pasien'],
                ['label' => 'Pendaftaran Hari Ini',   'value' => $cnt_today . ' Pasien'],
                ['label' => 'Rujukan Internal',       'value' => '0 Pasien'],
                ['label' => 'Pasien Baru (Bulan Ini)', 'value' => $cnt_total . ' Pasien'],
            ];
        }
        $rows_riw = db_select("SELECT no_rm as no, nama_lengkap as nama, COALESCE(jenis_pasien, 'Poli Umum') as poli, TO_CHAR(created_at, 'HH24:MI WIB') as waktu FROM patients ORDER BY id DESC LIMIT 6");
        if (!empty($rows_riw)) {
            $riwayat_pendaftaran = $rows_riw;
        }
    }
} catch (Exception $e) {}

// ============================================================
// 6. DATA UNTUK HALAMAN EMR DOKTER
// ============================================================
$emr_tabs = [
    ['id' => 'ringkasan',        'label' => 'Ringkasan', 'active' => true],
    ['id' => 'riwayat_kunjungan', 'label' => 'Riwayat Kunjungan'],
    ['id' => 'pemeriksaan',      'label' => 'Pemeriksaan'],
    ['id' => 'diagnosa',         'label' => 'Diagnosa'],
    ['id' => 'terapi_obat',      'label' => 'Terapi & Obat'],
    ['id' => 'order',            'label' => 'Order'],
    ['id' => 'hasil_pemeriksaan','label' => 'Hasil Pemeriksaan'],
    ['id' => 'dokumen',          'label' => 'Dokumen'],
];

// ============================================================
// 7. DATA UNTUK HALAMAN FARMASI
// ============================================================
$stats_farmasi = [
    ['label' => 'TOTAL OBAT',    'value' => '1.245', 'sub' => 'Jenis Obat', 'icon' => 'fa-box-tissue'],
    ['label' => 'STOK AMAN',     'value' => '982',   'sub' => 'Obat',       'icon' => 'fa-cart-shopping'],
    ['label' => 'STOK MENIPIS',  'value' => '18',    'sub' => 'Obat',       'icon' => 'fa-triangle-exclamation', 'warning' => true],
    ['label' => 'RESEP HARI INI', 'value' => '56',    'sub' => 'Resep',      'icon' => 'fa-calendar-check'],
];

$daftar_obat = [
    ['kode' => 'OBT-001', 'nama' => 'Paracetamol 500 mg', 'kategori' => 'Analgesik',       'satuan' => 'Tablet', 'stok' => '1.250', 'status' => 'Aman'],
    ['kode' => 'OBT-002', 'nama' => 'Amoxicillin 500 mg', 'kategori' => 'Antibiotik',      'satuan' => 'Kapsul', 'stok' => '320',   'status' => 'Aman'],
    ['kode' => 'OBT-003', 'nama' => 'Ranitidine 150 mg',  'kategori' => 'Gastrointestinal','satuan' => 'Tablet', 'stok' => '45',    'status' => 'Menipis'],
    ['kode' => 'OBT-004', 'nama' => 'CTM 4 mg',           'kategori' => 'Antihistamin',    'satuan' => 'Tablet', 'stok' => '30',    'status' => 'Menipis'],
    ['kode' => 'OBT-005', 'nama' => 'Vitamin C 500 mg',   'kategori' => 'Vitamin',         'satuan' => 'Tablet', 'stok' => '850',   'status' => 'Aman'],
    ['kode' => 'OBT-006', 'nama' => 'Ibuprofen 400 mg',   'kategori' => 'Analgesik',       'satuan' => 'Tablet', 'stok' => '120',   'status' => 'Menipis'],
    ['kode' => 'OBT-007', 'nama' => 'Salbutamol Inhaler', 'kategori' => 'Respirasi',       'satuan' => 'Puff',   'stok' => '25',    'status' => 'Habis'],
    ['kode' => 'OBT-008', 'nama' => 'Omeprazole 20 mg',   'kategori' => 'Gastrointestinal','satuan' => 'Kapsul', 'stok' => '200',   'status' => 'Aman'],
];

$stok_menipis = [
    ['nama' => 'Salbutamol Inhaler', 'detail' => 'Stok tersisa: 25 Puff', 'status' => 'Habis'],
    ['nama' => 'CTM 4 mg',           'detail' => 'Stok tersisa: 30 Tablet', 'status' => 'Menipis'],
    ['nama' => 'Ranitidine 150 mg',  'detail' => 'Stok tersisa: 45 Tablet', 'status' => 'Menipis'],
    ['nama' => 'Ibuprofen 400 mg',   'detail' => 'Stok tersisa: 120 Tablet', 'status' => 'Menipis'],
];

$resep_terbaru = [
    ['no' => '#R-2025-00056', 'nama' => 'Siti Rahayu',    'waktu' => '10:15 WIB', 'status' => 'Selesai'],
    ['no' => '#R-2025-00055', 'nama' => 'Budiman Setiawan','waktu' => '09:50 WIB', 'status' => 'Selesai'],
    ['no' => '#R-2025-00054', 'nama' => 'Lestari Putri',   'waktu' => '09:30 WIB', 'status' => 'Selesai'],
];

// ============================================================
// 8. DATA UNTUK HALAMAN KASIR
// ============================================================
$daftar_pasien_statis = [
    '1' => [
        'nama_pasien'  => 'Bani Santoso',
        'no_rm'        => 'RM-001',
        'nik'          => '3301234567890001',
        'tgl_lahir'    => '12 Mei 1995',
        'alamat'       => 'Jl. Merdeka No. 10, Semarang',
        'telepon'      => '08123456789',
        'penjamin'     => 'BPJS Kesehatan',
        'nama_dokter'  => 'dr. Ahmad Spesialis Mata',
        'nama_poli'    => 'Poli Mata',
        'jenis_pasien' => 'Rawat Jalan (BPJS)'
    ],
    '2' => [
        'nama_pasien'  => 'Siti Aminah',
        'no_rm'        => 'RM-002',
        'nik'          => '3301234567890002',
        'tgl_lahir'    => '23 November 1998',
        'alamat'       => 'Jl. Mawar No. 45, Semarang',
        'telepon'      => '08571234567',
        'penjamin'     => 'Mandiri Inhealth',
        'nama_dokter'  => 'dr. Indah Spesialis Anak',
        'nama_poli'    => 'Poli Anak',
        'jenis_pasien' => 'Rawat Jalan (Asuransi)'
    ]
];

$nama_pasien   = "Belum Memilih Pasien";
$no_rm         = "-";
$nik           = "-";
$tgl_lahir     = "-";
$jenis_pasien  = "-";
$alamat        = "-";
$telepon       = "-";
$penjamin      = "-";
$dokter        = "-";
$poli          = "-";
$perusahaan    = "Umum / Pribadi";

if (isset($_GET['id_antrian'])) {
    $id = $_GET['id_antrian'];
    if (array_key_exists($id, $daftar_pasien_statis)) {
        $data = $daftar_pasien_statis[$id];
        $nama_pasien   = $data['nama_pasien'];
        $no_rm         = $data['no_rm'];
        $nik           = $data['nik'];
        $tgl_lahir     = $data['tgl_lahir'];
        $alamat        = $data['alamat'];
        $telepon       = $data['telepon'];
        $penjamin      = $data['penjamin'];
        $dokter        = $data['nama_dokter'];
        $poli          = $data['nama_poli'];
        $jenis_pasien  = $data['jenis_pasien'];
    }
}

if (isset($_POST['cari_pasien'])) {
    $keyword = strtolower($_POST['keyword']);
    $ketemu  = false;
    foreach ($daftar_pasien_statis as $id => $data) {
        if (strtolower($data['no_rm']) == $keyword || strpos(strtolower($data['nama_pasien']), $keyword) !== false) {
            $nama_pasien   = $data['nama_pasien'];
            $no_rm         = $data['no_rm'];
            $nik           = $data['nik'];
            $tgl_lahir     = $data['tgl_lahir'];
            $alamat        = $data['alamat'];
            $telepon       = $data['telepon'];
            $penjamin      = $data['penjamin'];
            $dokter        = $data['nama_dokter'];
            $poli          = $data['nama_poli'];
            $jenis_pasien  = $data['jenis_pasien'];
            $ketemu = true;
            break;
        }
    }
    if (!$ketemu) {
        echo "<script>alert('Pasien tidak ditemukan! Coba ketik: Budi atau RM-002');</script>";
    }
}

$detail_transaksi = [
    ['no' => 1, 'deskripsi' => 'Konsultasi Dokter Spesialis', 'kategori' => 'Jasa Medis',   'qty' => 1,  'harga' => '150.000', 'total' => '150.000'],
    ['no' => 2, 'deskripsi' => 'Pemeriksaan Darah Rutin',     'kategori' => 'Laboratorium', 'qty' => 1,  'harga' => '125.000', 'total' => '125.000'],
    ['no' => 3, 'deskripsi' => 'Rontgen Thorax',              'kategori' => 'Radiologi',    'qty' => 1,  'harga' => '250.000', 'total' => '250.000'],
    ['no' => 4, 'deskripsi' => 'Paracetamol 500 mg',          'kategori' => 'Farmasi',      'qty' => 10, 'harga' => '2.500',   'total' => '25.000'],
    ['no' => 5, 'deskripsi' => 'Vitamin C 500 mg',            'kategori' => 'Farmasi',      'qty' => 10, 'harga' => '3.000',   'total' => '30.000'],
];

$nominal_cepat = ['580.000', '600.000', '1.000.000', 'Pas Tagihan'];

// ============================================================
// 9. FUNGSI RENDER KONTEN
// ============================================================
function renderContent($page, $stats_dashboard, $antrian_terkini, $distribusi, $stats_antrian, $antrian, $sedang_dilayani, $status_poli, $info_hari_ini, $riwayat_pendaftaran, $emr_tabs, $stats_farmasi, $daftar_obat, $stok_menipis, $resep_terbaru, $detail_transaksi, $nominal_cepat, $nama_pasien, $no_rm, $nik, $tgl_lahir, $alamat, $telepon, $penjamin, $dokter, $poli) {
    switch ($page) {
        case 'dashboard':
            // ---------- DASHBOARD ----------
            ?>
            <div class="stat-grid">
                <?php foreach ($stats_dashboard as $st): ?>
                <div class="stat-card">
                    <div class="stat-left">
                        <div class="stat-icon"><i class="fa-solid <?= $st['icon'] ?>"></i></div>
                        <div class="stat-label"><?= $st['label'] ?></div>
                        <div class="stat-value"><?= $st['value'] ?></div>
                    </div>
                    <div class="trend-badge trend-<?= $st['trend_type'] ?>">
                        <?php if($st['trend_type'] == 'up') echo '<i class="fa-solid fa-arrow-trend-up"></i>'; ?>
                        <?php if($st['trend_type'] == 'down') echo '<i class="fa-solid fa-arrow-trend-down"></i>'; ?>
                        <?= $st['trend'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="chart-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Kunjungan Pasien 7 Hari Terakhir</h2>
                        <div class="btn-group">
                            <button class="btn-secondary" onclick="alert('Mengekspor laporan ke PDF...')">Export PDF</button>
                            <button class="btn-primary-sm" onclick="window.location.href='?page=antrian'">Details</button>
                        </div>
                    </div>
                    <div class="line-chart-placeholder">
                        <div class="chart-bg-lines">
                            <div></div><div></div><div></div><div></div><div></div>
                        </div>
                        <div class="svg-chart-container">
                            <svg width="100%" height="100%" viewBox="0 0 700 200" preserveAspectRatio="none">
                                <path d="M 20 150 L 130 90 L 240 120 L 350 80 L 460 30 L 570 70 L 680 15" 
                                      fill="none" stroke="#2e7d32" stroke-width="3" stroke-linecap="round"/>
                                <circle cx="20" cy="150" r="5" fill="#2e7d32"/>
                                <circle cx="130" cy="90" r="5" fill="#2e7d32"/>
                                <circle cx="240" cy="120" r="5" fill="#2e7d32"/>
                                <circle cx="350" cy="80" r="5" fill="#2e7d32"/>
                                <circle cx="460" cy="30" r="5" fill="#2e7d32"/>
                                <circle cx="570" cy="70" r="5" fill="#2e7d32"/>
                                <circle cx="680" cy="15" r="5" fill="#2e7d32"/>
                            </svg>
                        </div>
                        <span style="font-size: 11px; color:#9ca3af; position:absolute; bottom:15px; left:35px;">Mon</span>
                        <span style="font-size: 11px; color:#9ca3af; position:absolute; bottom:15px; left:145px;">Tue</span>
                        <span style="font-size: 11px; color:#9ca3af; position:absolute; bottom:15px; left:255px;">Wed</span>
                        <span style="font-size: 11px; color:#9ca3af; position:absolute; bottom:15px; left:365px;">Thu</span>
                        <span style="font-size: 11px; color:#9ca3af; position:absolute; bottom:15px; left:475px;">Fri</span>
                        <span style="font-size: 11px; color:#9ca3af; position:absolute; bottom:15px; left:585px;">Sat</span>
                        <span style="font-size: 11px; color:#9ca3af; position:absolute; bottom:15px; right:35px;">Sun</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Distribusi Jenis Kunjungan</h2>
                    </div>
                    <div class="donut-wrapper">
                        <div class="donut-chart">
                            <div class="donut-center">
                                <strong>128</strong>
                                <span>PASIEN</span>
                            </div>
                        </div>
                        <div class="donut-legend">
                            <?php foreach ($distribusi as $ds): ?>
                            <div class="legend-item">
                                <div class="legend-label">
                                    <div class="legend-dot" style="background: <?= $ds['color'] ?>;"></div>
                                    <?= $ds['label'] ?>
                                </div>
                                <strong style="color: var(--gray-800); font-weight:600;"><?= $ds['jumlah'] ?> (<?= $ds['persen'] ?>)</strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Antrian Terkini</h2>
                        <p style="font-size: 12px; color: var(--gray-400); font-weight: normal; margin-top: 2px;">Daftar antrian pelayanan yang sedang berlangsung</p>
                    </div>
                    <div class="search-container">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="search-input-dash" placeholder="Cari nama pasien...">
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No Antrian</th>
                            <th>Nama Pasien</th>
                            <th>Poli</th>
                            <th>Dokter</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($antrian_terkini as $row): ?>
                        <tr>
                            <td class="no-antrian"><?= htmlspecialchars($row['no']) ?></td>
                            <td style="font-weight: 500; color: var(--gray-800);"><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['poli']) ?></td>
                            <td><?= htmlspecialchars($row['dokter']) ?></td>
                            <td>
                                <?php 
                                $status_class = 'badge-menunggu';
                                if (strtolower($row['status']) === 'dipanggil') $status_class = 'badge-dipanggil';
                                if (strtolower($row['status']) === 'selesai') $status_class = 'badge-selesai';
                                ?>
                                <span class="badge-status <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                            </td>
                            <td><a href="?page=emr_dokter&no_antrian=<?= urlencode($row['no']) ?>&nama=<?= urlencode($row['nama']) ?>" class="btn-detail" style="text-decoration:none; display:inline-block;">Detail</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="table-footer">
                    <p>Menampilkan 5 dari 14 antrian aktif</p>
                    <div class="pagination-nav">
                        <button class="nav-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="nav-btn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'antrian':
            // ---------- HALAMAN ANTRIAN ----------
            ?>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <div class="stat-label">Total Antrian Hari Ini</div>
                        <div class="stat-value"><?= $stats_antrian['total'] ?></div>
                        <div class="stat-unit">Pasien</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div>
                        <div class="stat-label">Sedang Dilayani</div>
                        <div class="stat-value"><?= $stats_antrian['dilayani'] ?></div>
                        <div class="stat-unit">Pasien</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="stat-label">Rata-rata Waktu Tunggu</div>
                        <div class="stat-value"><?= $stats_antrian['rata_tunggu'] ?></div>
                        <div class="stat-unit">Menit</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="stat-label">Selesai Hari Ini</div>
                        <div class="stat-value"><?= $stats_antrian['selesai'] ?></div>
                        <div class="stat-unit">Pasien</div>
                    </div>
                </div>
            </div>

            <div class="lower-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Daftar Antrian Hari Ini</h2>
                    </div>
                    <div class="table-filters">
                        <select class="filter-select">
                            <option>Semua Poli</option>
                            <option>Poli Jantung</option>
                            <option>Poli Umum</option>
                            <option>Poli Anak</option>
                            <option>Poli Mata</option>
                            <option>Poli Gigi</option>
                            <option>Poli Kulit</option>
                            <option>Poli THT</option>
                        </select>
                        <select class="filter-select">
                            <option>Semua Status</option>
                            <option>Sedang Dilayani</option>
                            <option>Menunggu</option>
                            <option>Selesai</option>
                        </select>
                        <div class="search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" class="search-input" placeholder="Cari nama / no. antrian...">
                        </div>
                        <button class="btn-refresh" onclick="location.reload()">
                            <i class="fa-solid fa-rotate-right"></i> Refresh
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>No. Antrian</th>
                                <th>Nama Pasien</th>
                                <th>Poli</th>
                                <th>Estimasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($antrian as $row): ?>
                            <tr>
                                <td class="no-antrian"><?= htmlspecialchars($row['no']) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['poli']) ?></td>
                                <td><?= htmlspecialchars($row['estimasi']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'dilayani'): ?>
                                        <span class="badge-status badge-dilayani">Sedang Dilayani</span>
                                    <?php elseif ($row['status'] === 'selesai'): ?>
                                        <span class="badge-status badge-selesai">Selesai</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-menunggu">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                                <td><a href="?page=emr_dokter&no_antrian=<?= urlencode($row['no']) ?>&nama=<?= urlencode($row['nama']) ?>" class="btn-detail" style="text-decoration:none; display:inline-block;">Detail / Dilayani</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <p>Menampilkan 1 - <?= count($antrian) ?> dari <?= $stats_antrian['total'] ?> antrian</p>
                        <div class="pagination">
                            <button class="page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn">...</button>
                            <button class="page-btn">8</button>
                            <button class="page-btn"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>

                <div class="right-col">
                    <div class="dilayani-card">
                        <div class="dilayani-header">
                            <div>
                                <h3>Sedang Dilayani</h3>
                                <p><?= htmlspecialchars($sedang_dilayani['poli']) ?></p>
                            </div>
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div class="antrian-box">
                            <div class="label">No. Antrian</div>
                            <div class="no"><?= htmlspecialchars($sedang_dilayani['no']) ?></div>
                            <div class="nama"><?= htmlspecialchars($sedang_dilayani['nama']) ?></div>
                        </div>
                        <div class="dilayani-meta">
                            <div class="meta-row">
                                <span>Dipanggil pada</span>
                                <span>10:15</span>
                            </div>
                            <div class="meta-row">
                                <span>Estimasi selesai</span>
                                <strong><?= htmlspecialchars($sedang_dilayani['estimasi']) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="poli-card">
                        <div class="poli-card-header">Status per Poli</div>
                        <div class="poli-grid">
                            <?php foreach ($status_poli as $poli): ?>
                            <div class="poli-item">
                                <div class="poli-top">
                                    <div class="poli-icon"><i class="fa-solid <?= htmlspecialchars($poli['icon']) ?>"></i></div>
                                    <div>
                                        <div class="poli-name"><?= htmlspecialchars($poli['nama']) ?></div>
                                        <div class="poli-count"><?= $poli['sekarang'] ?> / <?= $poli['total'] ?></div>
                                    </div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:<?= round($poli['sekarang']/$poli['total']*100) ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.querySelector('.search-input');
                const filterSelects = document.querySelectorAll('.filter-select');
                const tableRows = document.querySelectorAll('table tbody tr');
                const pageBtns = document.querySelectorAll('.page-btn');

                function filterTable() {
                    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
                    const poliFilter = (filterSelects.length > 0 && filterSelects[0].value !== 'Semua Poli') ? filterSelects[0].value.toLowerCase() : '';
                    const statusFilter = (filterSelects.length > 1 && filterSelects[1].value !== 'Semua Status') ? filterSelects[1].value.toLowerCase() : '';

                    tableRows.forEach(row => {
                        const noAntrian = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
                        const nama = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
                        const poli = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
                        const status = row.cells[4] ? row.cells[4].textContent.toLowerCase() : '';

                        const matchQuery = !query || noAntrian.includes(query) || nama.includes(query);
                        const matchPoli = !poliFilter || poli.includes(poliFilter);
                        const matchStatus = !statusFilter || status.includes(statusFilter);

                        if (matchQuery && matchPoli && matchStatus) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }

                if (searchInput) {
                    searchInput.addEventListener('input', filterTable);
                }
                filterSelects.forEach(select => {
                    select.addEventListener('change', filterTable);
                });

                pageBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        if (this.textContent.trim() === '...' || this.querySelector('i')) return;
                        pageBtns.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                    });
                });
            });
            </script>
            <?php
            break;

        case 'pendaftaran':
            // ---------- HALAMAN PENDAFTARAN ----------
            ?>
            <div class="pendaftaran-grid">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>Formulir Pendaftaran Pasien</h2>
                            <p>Lengkapi data pasien dengan benar</p>
                        </div>
                        <button type="button" class="btn-reset" onclick="this.closest('.card').querySelector('form').reset()">
                            <i class="fa-solid fa-rotate-right"></i> Reset Form
                        </button>
                    </div>
                    <form action="?page=pendaftaran" method="POST">
                        <input type="hidden" name="action" value="register_pasien">
                        <div class="form-body">
                            <div class="form-section-title">
                                <i class="fa-solid fa-user"></i> Data Pasien
                            </div>
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>No. Rekam Medis</label>
                                    <input type="text" class="form-input form-input-locked" value="RM-2026-07-<?= rand(1000,9999) ?>" readonly>
                                    <i class="fa-solid fa-lock lock-icon"></i>
                                </div>
                                <div class="form-group">
                                    <label>Nama Lengkap<span>*</span></label>
                                    <input type="text" name="nama_lengkap" class="form-input" placeholder="Masukkan nama lengkap (Hanya huruf)" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,']/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>Jenis Kelamin<span>*</span></label>
                                    <select name="jenis_kelamin" class="form-select" required>
                                        <option value="" disabled selected>Pilih jenis kelamin</option>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>Tempat Lahir<span>*</span></label>
                                    <input type="text" name="tempat_lahir" class="form-input" placeholder="Masukkan tempat lahir (Hanya huruf)" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,'-]/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Lahir<span>*</span></label>
                                    <input type="date" name="tanggal_lahir" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label>Status Perkawinan</label>
                                    <select name="status_perkawinan" class="form-select">
                                        <option value="" disabled selected>Pilih status</option>
                                        <option value="Belum Kawin">Belum Kawin</option>
                                        <option value="Kawin">Kawin</option>
                                        <option value="Cerai">Cerai</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>No. KTP / NIK<span>*</span> (16 Angka)</label>
                                    <input type="text" name="nik" class="form-input" placeholder="16 digit angka NIK" maxlength="16" pattern="[0-9]{16}" title="NIK wajib 16 digit angka" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>No. BPJS (Jika ada - Angka)</label>
                                    <input type="text" name="no_bpjs" class="form-input" placeholder="13 digit angka BPJS" maxlength="13" pattern="[0-9]*" title="No BPJS hanya boleh angka" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                <div class="form-group">
                                    <label>Golongan Darah</label>
                                    <select name="golongan_darah" class="form-select">
                                        <option value="" disabled selected>Pilih golongan darah</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="AB">AB</option>
                                        <option value="O">O</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section-title">
                                <i class="fa-solid fa-location-dot"></i> Kontak & Alamat
                            </div>

                            <div class="form-group">
                                <label>Alamat Lengkap<span>*</span></label>
                                <textarea name="alamat" class="form-textarea" placeholder="Masukkan alamat lengkap pasien" required></textarea>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>No. Telepon<span>*</span> (Angka / HP)</label>
                                    <input type="tel" name="no_telepon" class="form-input" placeholder="08xxxxxxxxxx" maxlength="15" pattern="[\+0-9]*" title="No Telepon hanya boleh angka" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-input" placeholder="Masukkan email (opsional)">
                                </div>
                            </div>

                            <div class="form-section-title">
                                <i class="fa-solid fa-users-viewfinder"></i> Penanggung Jawab / Kontak Darurat
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>Nama Lengkap (Hanya huruf)</label>
                                    <input type="text" name="pj_nama" class="form-input" placeholder="Nama penanggung jawab" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,']/g, '')">
                                </div>
                                <div class="form-group">
                                    <label>Hubungan</label>
                                    <select name="pj_hubungan" class="form-select">
                                        <option value="" disabled selected>Pilih hubungan</option>
                                        <option value="Orang Tua">Orang Tua</option>
                                        <option value="Suami/Istri">Suami/Istri</option>
                                        <option value="Anak">Anak</option>
                                        <option value="Saudara">Saudara Kandung</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>No. Telepon (Angka / HP)</label>
                                    <input type="tel" name="pj_telepon" class="form-input" placeholder="08xxxxxxxxxx" maxlength="15" pattern="[\+0-9]*" oninput="this.value = this.value.replace(/[^0-9+]/g, '')">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="window.location.href='?page=dashboard'">Batal</button>
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Pendaftaran
                            </button>
                        </div>
                    </form>
                </div>

                <div class="right-column">
                    <div class="card">
                        <div class="widget-title">
                            <i class="fa-solid fa-calendar-days"></i> Informasi Hari Ini
                        </div>
                        <div class="info-header">
                            <span class="info-date">Kamis, 29 Mei 2025</span>
                            <span class="info-time">09:15 WIB</span>
                        </div>
                        <div class="info-list">
                            <?php foreach ($info_hari_ini as $inf): ?>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fa-solid fa-circle-chevron-right" style="font-size: 10px; color: var(--green-light);"></i>
                                    <?= $inf['label'] ?>
                                </div>
                                <strong><?= $inf['value'] ?></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="guide-box">
                        <div class="widget-title" style="border-bottom: 1px solid #bbf7d0; color: #14532d;">
                            <i class="fa-solid fa-book-open" style="color: #16a34a;"></i> Petunjuk
                        </div>
                        <div class="guide-body">
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Pastikan semua data terisi dengan benar</span>
                            </div>
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Field bertanda <span style="color:#b91c1c;">*</span> wajib diisi</span>
                            </div>
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Periksa kembali data sebelum disimpan</span>
                            </div>
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Gunakan NIK yang valid (16 digit)</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="widget-title">
                            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pendaftaran Terakhir
                        </div>
                        <div class="history-list">
                            <?php foreach ($riwayat_pendaftaran as $riw): ?>
                            <div class="history-item">
                                <div class="history-badge"><?= $riw['no'] ?></div>
                                <div class="history-details">
                                    <div class="history-name"><?= htmlspecialchars($riw['nama']) ?></div>
                                    <div class="history-sub"><?= htmlspecialchars($riw['poli']) ?> &bull; <?= $riw['waktu'] ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn-view-all">Lihat Semua</button>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'emr':
            // ---------- HALAMAN EMR DOKTER ----------
            ?>
            <section class="patient-card">
                <div class="patient-left">
                    <div class="patient-avatar" style="background: #e2e8f0; display:flex; align-items:center; justify-content:center; font-size:32px; color:#94a3b8;"><i class="fa-solid fa-user"></i></div>
                    <div class="patient-meta">
                        <div class="patient-name-box">
                            <h2>Budi Santoso</h2>
                            <span class="gender-badge">Laki-laki</span>
                        </div>
                        <div class="info-grid-inline">
                            <span>No. RM: <strong>RM00012345</strong></span>
                            <span>Tanggal Lahir: <strong>12 Mei 1990 (34 th)</strong></span>
                            <span>No. Identitas: <strong>3275011205900001</strong></span>
                        </div>
                        <div class="address-box">
                            <span>Alamat: <strong>Jl. Merdeka No. 10, Jakarta Pusat</strong></span>
                            <div style="display:flex; gap:20px; margin-top:2px;">
                                <span>Telepon: <strong>0812-3456-7890</strong></span>
                                <span>Penjamin: <strong>BPJS Kesehatan</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="patient-right">
                    <div class="vitals-box alert-vitals">
                        <span class="vitals-label alert-text"><i class="fa-solid fa-circle-exclamation"></i> Alergi</span>
                        <span class="vitals-val" style="color:#b91c1c; font-size:12px;">Penisilin, Seafood</span>
                        <span class="vitals-sub" style="color:var(--green-primary); font-weight:600; cursor:pointer;">Lihat Detail</span>
                    </div>
                    <div class="vitals-box">
                        <span class="vitals-label">Gol. Darah</span>
                        <span class="vitals-val" style="font-size:15px; color:var(--gray-800);">O+</span>
                    </div>
                    <div class="vitals-box">
                        <span class="vitals-label">Berat Badan</span>
                        <span class="vitals-val">72 kg</span>
                        <span class="vitals-sub">Terakhir: 10/05/2025</span>
                    </div>
                    <div class="vitals-box">
                        <span class="vitals-label">Tinggi Badan</span>
                        <span class="vitals-val">170 cm</span>
                        <span class="vitals-sub">Terakhir: 10/05/2025</span>
                    </div>
                </div>
            </section>

            <nav class="emr-nav-bar">
                <?php foreach ($emr_tabs as $tab): ?>
                <button class="emr-tab-btn <?= !empty($tab['active']) ? 'active' : '' ?>" onclick="switchTab(event, '<?= $tab['id'] ?>')">
                    <?= $tab['label'] ?>
                </button>
                <?php endforeach; ?>
            </nav>

            <div class="left-column-panels">
                <div id="ringkasan" class="tab-panel active">
                    <div class="card">
                        <div class="card-header">
                            <h3>Clinical Note</h3>
                            <span class="header-meta">10 Mei 2025 10:15 WIB &bull; Dr. Hendrawan</span>
                        </div>
                        <div class="note-body">
                            <div class="note-row">
                                <div class="note-letter">S</div>
                                <div class="note-content">
                                    <div class="note-title">Subjective</div>
                                    <p>Keluhan utama pasien: Pasien mengeluh demam sejak 2 hari yang lalu disertai batuk kering dan nyeri tenggorokan.</p>
                                </div>
                            </div>
                            <div class="note-row">
                                <div class="note-letter">O</div>
                                <div class="note-content">
                                    <div class="note-title">Objective</div>
                                    <p>Keadaan umum cukup, kesadaran compos mentis, tenggorokan hiperemis, tidak ada ronki.</p>
                                    <div class="vitals-tag-group">
                                        <span class="vital-tag">TD: 120/80 mmHg</span>
                                        <span class="vital-tag success">Nadi: 88 x/menit</span>
                                        <span class="vital-tag">RR: 20 x/menit</span>
                                        <span class="vital-tag">Suhu: 37.8 °C</span>
                                        <span class="vital-tag">SpO2: 98%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="note-row">
                                <div class="note-letter">A</div>
                                <div class="note-content">
                                    <div class="note-title">Assessment</div>
                                    <p><strong>Diagnosis Kerja:</strong> ISPA (Infeksi Saluran Pernapasan Akut)</p>
                                </div>
                            </div>
                            <div class="note-row">
                                <div class="note-letter">P</div>
                                <div class="note-content">
                                    <div class="note-title">Plan</div>
                                    <p>Terapi obat, istirahat cukup, kontrol ulang 3 hari jika keluhan tidak membaik.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="triple-grid">
                        <div class="card">
                            <div class="card-header"><h3>Resep Terbaru</h3></div>
                            <div class="med-list">
                                <div style="font-size:11px; color:var(--gray-400); margin-bottom:4px; display:flex; justify-content:space-between;">
                                    <span>Resep R/2025-00056</span>
                                    <span>10 Mei 2025 10:15 WIB</span>
                                </div>
                                <div class="med-item">
                                    <span class="med-num">1</span>
                                    <div class="med-details">
                                        <span class="med-name">Paracetamol 500 mg</span>
                                        <span class="med-rule">3x sehari setelah makan</span>
                                    </div>
                                    <span class="med-qty">10 Tablet</span>
                                </div>
                                <div class="med-item">
                                    <span class="med-num">2</span>
                                    <div class="med-details">
                                        <span class="med-name">Ambroxol 30 mg</span>
                                        <span class="med-rule">3x sehari setelah makan</span>
                                    </div>
                                    <span class="med-qty">10 Tablet</span>
                                </div>
                                <div class="med-item">
                                    <span class="med-num">3</span>
                                    <div class="med-details">
                                        <span class="med-name">Vitamin C 500 mg</span>
                                        <span class="med-rule">1x sehari setelah makan</span>
                                    </div>
                                    <span class="med-qty">10 Tablet</span>
                                </div>
                            </div>
                            <button class="btn-card-action" onclick="switchTab(event, 'terapi_obat')">Lihat Resep</button>
                        </div>

                        <div class="card">
                            <div class="card-header"><h3>Order Terbaru</h3></div>
                            <div class="order-list">
                                <div style="font-size:11px; color:var(--gray-400); margin-bottom:6px;">Order #O/2025-00089 &bull; 10 Mei 2025 10:20 WIB</div>
                                <div class="med-item" style="border:none; align-items:center;">
                                    <i class="fa-solid fa-square-heart" style="color:#b91c1c; font-size:16px;"></i>
                                    <div class="med-details" style="margin-left:12px;">
                                        <span class="med-name">Darah Rutin</span>
                                        <span class="med-rule">Hematologi</span>
                                    </div>
                                    <span class="mini-badge badge-warning">Menunggu</span>
                                </div>
                                <div class="med-item" style="border:none; align-items:center;">
                                    <i class="fa-solid fa-circle-radiation" style="color:#a16207; font-size:16px;"></i>
                                    <div class="med-details" style="margin-left:12px;">
                                        <span class="med-name">Foto Thorax</span>
                                        <span class="med-rule">Radiologi</span>
                                    </div>
                                    <span class="mini-badge badge-warning">Menunggu</span>
                                </div>
                            </div>
                            <button class="btn-card-action" style="margin-top:22px;" onclick="switchTab(event, 'order')">Lihat Order</button>
                        </div>

                        <div class="card">
                            <div class="card-header"><h3>Hasil Pemeriksaan Terbaru</h3></div>
                            <div class="order-list">
                                <div class="med-item" style="border:none; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--gray-100)">
                                    <i class="fa-solid fa-file-invoice" style="color:var(--green-primary); font-size:16px;"></i>
                                    <div class="med-details" style="margin-left:12px;">
                                        <span class="med-name">Darah Rutin</span>
                                        <span class="med-rule">10 Mei 2025</span>
                                    </div>
                                    <span class="mini-badge badge-normal">Normal</span>
                                </div>
                                <div class="med-item" style="border:none; align-items:center; padding-top:4px;">
                                    <i class="fa-solid fa-file-invoice" style="color:var(--green-primary); font-size:16px;"></i>
                                    <div class="med-details" style="margin-left:12px;">
                                        <span class="med-name">Foto Thorax</span>
                                        <span class="med-rule">10 Mei 2025</span>
                                    </div>
                                    <span class="mini-badge badge-normal">Normal</span>
                                </div>
                            </div>
                            <button class="btn-card-action" style="margin-top:42px;" onclick="switchTab(event, 'hasil_pemeriksaan')">Lihat Hasil</button>
                        </div>
                    </div>
                </div>

                <div id="riwayat_kunjungan" class="tab-panel">
                    <div class="card"><div class="empty-tab-view"><i class="fa-solid fa-clock-rotate-left"></i>Data Riwayat Kunjungan Pasien</div></div>
                </div>
                <div id="pemeriksaan" class="tab-panel">
                    <div class="card"><div class="empty-tab-view"><i class="fa-solid fa-stethoscope"></i>Form & Data Pemeriksaan Fisik</div></div>
                </div>
                <div id="diagnosa" class="tab-panel">
                    <div class="card"><div class="empty-tab-view"><i class="fa-solid fa-kit-medical"></i>Kelola Kode Diagnosa ICD-10</div></div>
                </div>
                <div id="terapi_obat" class="tab-panel">
                    <div class="card"><div class="empty-tab-view"><i class="fa-solid fa-pills"></i>Input Terapi dan Resep Elektronik</div></div>
                </div>
                <div id="order" class="tab-panel">
                    <div class="card"><div class="empty-tab-view"><i class="fa-solid fa-basket-shopping"></i>Form Order Lab / Radiologi</div></div>
                </div>
                <div id="hasil_pemeriksaan" class="tab-panel">
                    <div class="card"><div class="empty-tab-view"><i class="fa-solid fa-square-poll-horizontal"></i>Lembar Dokumen Hasil Laboratorium</div></div>
                </div>
                <div id="dokumen" class="tab-panel">
                    <div class="card"><div class="empty-tab-view"><i class="fa-solid fa-folder-open"></i>Berkas Lampiran Penjamin / Surat Pengantar</div></div>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <div class="widget-title"><span><i class="fa-solid fa-bolt"></i> Quick Action</span></div>
                    <div class="quick-action-grid">
                        <button class="btn-qa" onclick="switchTab(event, 'ringkasan')"><i class="fa-solid fa-file-lines"></i> Clinical Note</button>
                        <button class="btn-qa" onclick="switchTab(event, 'terapi_obat')"><i class="fa-solid fa-receipt"></i> Resep Elektronik</button>
                        <button class="btn-qa" onclick="switchTab(event, 'order')"><i class="fa-solid fa-vial"></i> Order Lab</button>
                        <button class="btn-qa" onclick="switchTab(event, 'order')"><i class="fa-solid fa-x-ray"></i> Order Radiologi</button>
                        <button class="btn-qa" onclick="switchTab(event, 'dokumen')"><i class="fa-solid fa-envelope-open-text"></i> Surat Keterangan</button>
                        <button class="btn-qa" onclick="switchTab(event, 'ringkasan')"><i class="fa-solid fa-copy"></i> Template Note</button>
                    </div>
                </div>

                <div class="card">
                    <div class="widget-title"><span><i class="fa-solid fa-history"></i> Riwayat Kunjungan Terakhir</span> <a href="#" class="widget-link">Lihat Semua</a></div>
                    <div class="history-box">
                        <div class="history-date-row">
                            <span>10 Mei 2025 &bull; 10:15 WIB</span>
                            <span style="background:#dcfce7; color:#15803d; font-size:10px; padding:1px 6px; border-radius:4px;">Selesai</span>
                        </div>
                        <div class="history-item-row"><span class="history-label">Keluhan:</span><span class="history-val">Demam, batuk, nyeri tenggorokan</span></div>
                        <div class="history-item-row"><span class="history-label">Diagnosis:</span><span class="history-val">ISPA</span></div>
                        <div class="history-item-row"><span class="history-label">Dokter:</span><span class="history-val">Dr. Hendrawan</span></div>
                        <button class="btn-card-action" style="border:1px solid var(--gray-200); border-radius:6px; margin-top:8px; padding:6px;">Lihat Detail</button>
                    </div>
                </div>

                <div class="card">
                    <div class="widget-title"><span><i class="fa-solid fa-star-of-life"></i> Diagnosa Aktif</span> <span style="color:var(--green-primary); cursor:pointer; font-size:11px;">+ Tambah</span></div>
                    <div style="padding:14px 16px; display:flex; align-items:center; justify-content:between; font-size:12px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="background:var(--green-pale); color:var(--green-primary); font-weight:700; padding:3px 6px; border-radius:4px; font-size:11px;">J00</span>
                            <div>
                                <div style="font-weight:700; color:var(--gray-800);">Acute nasopharyngitis [common cold]</div>
                                <div style="font-size:10px; color:var(--gray-400); margin-top:1px;">Sejak: 10 Mei 2025</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="widget-title"><span><i class="fa-solid fa-pills"></i> Terapi Aktif</span> <a href="#" class="widget-link">Lihat Semua</a></div>
                    <div class="order-list" style="padding:12px 16px;">
                        <div class="med-item" style="border:none; padding:4px 0;">
                            <i class="fa-solid fa-droplet" style="color:var(--green-light); font-size:13px; margin-top:3px;"></i>
                            <div class="med-details" style="margin-left:10px;">
                                <span class="med-name">Paracetamol 500 mg</span>
                                <span class="med-rule">3x sehari setelah makan</span>
                            </div>
                            <span class="med-qty" style="font-size:11px; color:var(--gray-400);">10 Tablet</span>
                        </div>
                        <div class="med-item" style="border:none; padding:4px 0;">
                            <i class="fa-solid fa-droplet" style="color:var(--green-light); font-size:13px; margin-top:3px;"></i>
                            <div class="med-details" style="margin-left:10px;">
                                <span class="med-name">Ambroxol 30 mg</span>
                                <span class="med-rule">3x sehari setelah makan</span>
                            </div>
                            <span class="med-qty" style="font-size:11px; color:var(--gray-400);">10 Tablet</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'farmasi':
            // ---------- HALAMAN FARMASI ----------
            ?>
            <div class="stat-grid">
                <?php foreach ($stats_farmasi as $st): ?>
                <div class="stat-card <?= !empty($st['warning']) ? 'card-warning' : '' ?>">
                    <div class="stat-icon"><i class="fa-solid <?= $st['icon'] ?>"></i></div>
                    <div class="stat-info">
                        <span class="stat-label"><?= $st['label'] ?></span>
                        <div class="stat-value-group">
                            <strong><?= $st['value'] ?></strong>
                            <span><?= $st['sub'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Daftar Obat</h2>
                    <p>Kelola data obat dan stok di gudang farmasi</p>
                </div>
                <div class="table-filter-bar">
                    <div class="filter-left">
                        <select class="select-filter">
                            <option>Semua Kategori</option>
                        </select>
                        <select class="select-filter">
                            <option>Semua Status</option>
                        </select>
                        <div class="search-container">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" class="search-input" placeholder="Cari nama obat / kode...">
                        </div>
                    </div>
                    <button class="btn-add-obat">
                        <i class="fa-solid fa-plus"></i> Tambah Obat
                    </button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Kode Obat</th>
                            <th>Nama Obat</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_obat as $row): ?>
                        <tr>
                            <td class="obat-code"><?= $row['kode'] ?></td>
                            <td class="obat-name"><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td><?= htmlspecialchars($row['satuan']) ?></td>
                            <td style="font-weight: 600; color: var(--gray-800);"><?= $row['stok'] ?></td>
                            <td>
                                <?php 
                                $badge_class = 'badge-aman';
                                if ($row['status'] === 'Menipis') $badge_class = 'badge-menipis';
                                if ($row['status'] === 'Habis') $badge_class = 'badge-habis';
                                ?>
                                <span class="status-badge <?= $badge_class ?>"><?= $row['status'] ?></span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn-action edit"><i class="fa-solid fa-pencil"></i></button>
                                    <button class="btn-action delete"><i class="fa-solid fa-trash-can"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="table-footer">
                    <p>Menampilkan 1 - 8 dari 1.245 data</p>
                    <div class="pagination-nav">
                        <button class="page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">3</button>
                        <button class="page-btn" style="border:none; cursor:default; background:none;">...</button>
                        <button class="page-btn">156</button>
                        <button class="page-btn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <div class="widget-title">
                        <span><i class="fa-solid fa-bolt"></i> Aksi Cepat</span>
                    </div>
                    <div class="quick-actions-grid">
                        <button class="btn-qa" onclick="alert('Membuka form Resep Baru...')"><i class="fa-solid fa-file-medical"></i> Resep Baru</button>
                        <button class="btn-qa" onclick="alert('Membuka antrian Penyerahan Obat...')"><i class="fa-solid fa-hand-holding-medical"></i> Penyerahan Obat</button>
                        <button class="btn-qa" onclick="alert('Membuka form Stok Masuk (Restock)...')"><i class="fa-solid fa-boxes-stacked"></i> Stok Masuk</button>
                        <button class="btn-qa" onclick="alert('Mengekspor Laporan Farmasi...')"><i class="fa-solid fa-file-lines"></i> Laporan Farmasi</button>
                    </div>
                </div>

                <div class="card">
                    <div class="widget-title">
                        <span><i class="fa-solid fa-triangle-exclamation"></i> Stok Menipis</span>
                        <a href="#" class="widget-link">Lihat Semua</a>
                    </div>
                    <div class="list-widget">
                        <?php foreach ($stok_menipis as $sm): ?>
                        <div class="list-item">
                            <div class="item-left <?= strtolower($sm['status']) === 'habis' ? 'habis' : '' ?>">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <div class="item-text">
                                    <span class="item-title"><?= htmlspecialchars($sm['nama']) ?></span>
                                    <span class="item-sub"><?= htmlspecialchars($sm['detail']) ?></span>
                                </div>
                            </div>
                            <span class="mini-badge <?= strtolower($sm['status']) === 'habis' ? 'badge-habis' : 'badge-menipis' ?>">
                                <?= $sm['status'] ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="widget-title">
                        <span><i class="fa-solid fa-receipt"></i> Resep Terbaru</span>
                        <a href="#" class="widget-link">Lihat Semua</a>
                    </div>
                    <div class="list-widget">
                        <?php foreach ($resep_terbaru as $rs): ?>
                        <div class="resep-item">
                            <div class="resep-icon-box"><i class="fa-solid fa-file-prescription"></i></div>
                            <div class="resep-details">
                                <span class="resep-no"><?= $rs['no'] ?></span>
                                <span class="resep-patient"><?= htmlspecialchars($rs['nama']) ?></span>
                                <span class="resep-time"><?= $rs['waktu'] ?></span>
                            </div>
                            <span class="badge-done"><?= $rs['status'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'kasir':
            // ---------- HALAMAN KASIR ----------
            ?>
            <section class="simulasi-box">
                <span>💡 Jalur Otomatis URL:</span>
                <a href="?page=kasir&id_antrian=1" class="btn-sim">Panggil Pasien: Budi</a>
                <a href="?page=kasir&id_antrian=2" class="btn-sim">Panggil Pasien: Siti</a>
                <a href="?page=kasir" class="btn-sim" style="background:#6b7280;">Kosongkan Layar</a>
            </section>

            <section class="search-patient-card">
                <h3>Cari Pasien (Manual):</h3>
                <form action="" method="POST" class="search-form-flex">
                    <input type="text" name="keyword" class="input-search-pasien" placeholder="Masukkan Nama Pasien / No. RM (Contoh: Budi)..." required autocomplete="off">
                    <button type="submit" name="cari_pasien" class="btn-search-submit"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                </form>
            </section>

            <section class="patient-profile-bar">
                <div class="patient-avatar-box"><i class="fa-solid fa-user"></i></div>
                <div class="patient-details-grid">
                    <div class="p-group"><label>Nama Pasien</label><strong><?= $nama_pasien ?></strong></div>
                    <div class="p-group"><label>No. RM</label><span><?= $no_rm ?></span></div>
                    <div class="p-group"><label>NIK</label><span><?= $nik ?></span></div>
                    <div class="p-group"><label>Tanggal Lahir</label><span><?= $tgl_lahir ?></span></div>
                    <div class="p-group"><label>Alamat Pasien</label><span><?= $alamat ?></span></div>
                    <div class="p-group"><label>No. Telepon</label><span><?= $telepon ?></span></div>
                    <div class="p-group"><label>Dokter & Poli Penanggung</label><span><?= $dokter ?> / <?= $poli ?></span></div>
                    <div class="p-group"><label>Jenis Penjamin</label><span style="font-weight:700; color:var(--green-primary);"><?= $penjamin ?></span></div>
                </div>
            </section>

            <div class="left-column">
                <div class="card">
                    <div class="card-header"><h2>Rincian Transaksi Tindakan & Obat</h2></div>
                    <div class="table-action-bar">
                        <input type="text" class="search-input" placeholder="Cari tindakan tambahan...">
                        <button class="btn-add-item">+ Tambah Tindakan</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Deskripsi Tindakan / Layanan</th>
                                <th>Kategori</th>
                                <th style="text-align: center; width: 60px;">Qty</th>
                                <th style="text-align: right;">Harga</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail_transaksi as $row): ?>
                            <tr>
                                <td style="text-align: center; color: var(--gray-400);"><?= $row['no'] ?></td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($row['deskripsi']) ?></td>
                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                <td style="text-align: center; font-weight: 600;"><?= $row['qty'] ?></td>
                                <td style="text-align: right;">Rp <?= $row['harga'] ?></td>
                                <td style="text-align: right; font-weight: 600;">Rp <?= $row['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <div class="card-header"><h2>Ringkasan Biaya</h2></div>
                    <div class="billing-summary-list">
                        <div class="summary-row"><span>Subtotal Layanan</span><strong>Rp 580.000</strong></div>
                        <div class="summary-row"><span>Diskon Medis</span><strong>Rp 0</strong></div>
                        <div class="summary-row"><span>Pajak / Admin RS</span><strong>Rp 0</strong></div>
                    </div>
                    <div class="total-pay-box">
                        <span>Total Tagihan:</span>
                        <strong>Rp 580.000</strong>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2>Form Transaksi Pembayaran</h2></div>
                    <div class="pay-form">
                        <div class="form-group">
                            <label>Metode Pembayaran</label>
                            <div class="select-container">
                                <i class="fa-solid fa-money-bill-wave" id="pay-icon"></i>
                                <select class="select-pay" id="payment-method" onchange="updatePaymentIcon()">
                                    <option value="Tunai">Tunai / Cash</option>
                                    <option value="QRIS">QRIS / Digital Payment</option>
                                    <option value="Debit">Debit Card / Transfer Bank</option>
                                    <option value="BPJS">Jaminan BPJS Kesehatan</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Jumlah Uang Diterima</label>
                            <div class="input-money-container">
                                <span class="currency-prefix">Rp</span>
                                <input type="text" class="money-input" value="580.000">
                            </div>
                        </div>

                        <div class="nominal-grid">
                            <?php foreach ($nominal_cepat as $nom): ?>
                                <button class="btn-nom"><?= $nom ?></button>
                            <?php endforeach; ?>
                        </div>

                        <div class="change-box">
                            <span>Uang Kembalian:</span>
                            <strong>Rp 0</strong>
                        </div>
                    </div>
                    
                    <button class="btn-submit-pay" onclick="alert('Transaksi Berhasil Disimpan & Struk Dicetak!')">
                        <i class="fa-solid fa-print"></i> Proses & Cetak Struk
                    </button>
                </div>
            </div>
            <?php
            break;

        default:
            echo '<h2>Halaman tidak ditemukan</h2>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMRS Clinical Precision</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ===== RESET & VARIABEL ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-primary: #2e7d32;
            --green-mid:     #388e3c;
            --green-light:   #4caf50;
            --green-pale:    #e8f5e9;
            --green-xpale:   #f1f8f2;
            --sidebar-w:     230px;
            --white:         #ffffff;
            --gray-50:       #f9fafb;
            --gray-100:      #f3f4f6;
            --gray-200:      #e5e7eb;
            --gray-300:      #d1d5db;
            --gray-400:      #9ca3af;
            --gray-600:      #4b5563;
            --gray-800:      #1f2937;
            --orange:        #f59e0b;
            --font:          'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            font-family: var(--font);
            background: var(--gray-100);
            color: var(--gray-800);
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--white);
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--gray-200);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 18px 18px;
            border-bottom: 1px solid var(--gray-200);
        }

        .brand-icon {
            width: 40px; height: 40px;
            background: var(--green-primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-icon i { color: var(--white); font-size: 18px; }

        .brand-text h2 { font-size: 15px; font-weight: 700; color: var(--green-primary); line-height: 1.2; }
        .brand-text p  { font-size: 11px; color: var(--gray-400); }

        .nav-list { list-style: none; padding: 10px 0; flex: 1; }
        .nav-list li a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 18px;
            font-size: 13px; color: var(--gray-600);
            text-decoration: none; border-radius: 0;
            transition: background .15s, color .15s;
            border-left: 3px solid transparent;
        }
        .nav-list li a:hover { background: var(--green-xpale); color: var(--green-primary); }
        .nav-list li a.active {
            background: var(--green-xpale);
            color: var(--green-primary);
            border-left: 3px solid var(--green-primary);
            font-weight: 600;
        }
        .nav-list li a i { width: 16px; text-align: center; font-size: 14px; }

        .sidebar-footer { padding: 12px 0; border-top: 1px solid var(--gray-200); }
        .sidebar-footer a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 18px; font-size: 13px;
            color: var(--gray-600); text-decoration: none;
            transition: background .15s;
        }
        .sidebar-footer a:hover { background: var(--green-xpale); color: var(--green-primary); }

        .btn-quick {
            display: flex; align-items: center; gap: 8px;
            margin: 10px 14px;
            padding: 11px 14px;
            background: var(--green-primary);
            color: var(--white);
            border: none; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-quick:hover { background: var(--green-mid); }

        /* ===== MAIN ===== */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 0 28px;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }

        .topbar-left h1 { font-size: 20px; font-weight: 700; color: var(--green-primary); }
        .breadcrumb { font-size: 12px; color: var(--gray-400); margin-top: 1px; }
        .breadcrumb span { color: var(--green-primary); }

        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .icon-btn {
            position: relative;
            width: 36px; height: 36px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: none; color: var(--gray-600);
            font-size: 15px;
        }
        .badge {
            position: absolute; top: -2px; right: -2px;
            background: var(--green-primary); color: var(--white);
            font-size: 9px; font-weight: 700;
            width: 16px; height: 16px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--green-pale);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .user-avatar i { font-size: 18px; color: var(--green-primary); }
        .user-text small { display: block; font-size: 10px; color: var(--gray-400); }
        .user-text strong { font-size: 13px; }

        /* ===== CONTENT ===== */
        .content { padding: 24px 28px; display: flex; flex-direction: column; gap: 22px; }

        /* ===== STAT CARDS ===== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .stat-card .stat-left {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            background: var(--green-pale);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-card .stat-icon i { font-size: 20px; color: var(--green-primary); }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-400); margin-bottom: 4px; }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--gray-800); line-height: 1; }
        .stat-unit  { font-size: 12px; color: var(--gray-400); margin-top: 3px; }

        .trend-badge {
            font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 6px;
            display: flex; align-items: center; gap: 4px;
            margin-left: auto;
        }
        .trend-up { background: #dcfce7; color: #15803d; }
        .trend-down { background: #fee2e2; color: #b91c1c; }
        .trend-neutral { background: var(--gray-100); color: var(--gray-600); }

        /* ===== CHART (Dashboard) ===== */
        .chart-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; }
        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px 14px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--gray-200);
        }
        .card-header h2 { font-size: 15px; font-weight: 600; }

        .btn-group { display: flex; gap: 6px; }
        .btn-secondary {
            padding: 6px 12px; border: 1px solid var(--gray-200); border-radius: 6px;
            background: var(--white); font-size: 12px; font-weight: 600; color: var(--gray-600); cursor: pointer;
        }
        .btn-primary-sm {
            padding: 6px 12px; border: none; border-radius: 6px;
            background: var(--green-primary); font-size: 12px; font-weight: 600; color: var(--white); cursor: pointer;
        }

        .line-chart-placeholder {
            padding: 24px; position: relative; height: 260px;
            display: flex; align-items: flex-end; justify-content: space-between;
        }
        .chart-bg-lines {
            position: absolute; left: 24px; right: 24px; top: 24px; bottom: 50px;
            display: flex; flex-direction: column; justify-content: space-between;
            pointer-events: none;
        }
        .chart-bg-lines div { border-bottom: 1px dashed var(--gray-200); width: 100%; height: 0; }
        .svg-chart-container {
            position: absolute; left: 24px; right: 24px; top: 24px; bottom: 50px;
        }

        .donut-wrapper {
            display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; gap: 20px;
        }
        .donut-chart {
            position: relative; width: 160px; height: 160px;
            border-radius: 50%; background: conic-gradient(var(--green-primary) 0% 56%, var(--green-light) 56% 80%, #a5d6a7 80% 100%);
            display: flex; align-items: center; justify-content: center;
        }
        .donut-center {
            width: 110px; height: 110px; background: var(--white); border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .donut-center strong { font-size: 24px; color: var(--gray-800); font-weight: 700; }
        .donut-center span { font-size: 10px; color: var(--gray-400); font-weight: 600; letter-spacing: .5px; }
        .donut-legend { width: 100%; display: flex; flex-direction: column; gap: 8px; font-size: 12px; }
        .legend-item { display: flex; justify-content: space-between; align-items: center; }
        .legend-label { display: flex; align-items: center; gap: 8px; color: var(--gray-600); }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }

        /* ===== TABLE ===== */
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 11px 20px;
            text-align: left; font-size: 12px;
            text-transform: uppercase; letter-spacing: .4px;
            color: var(--gray-400); font-weight: 600;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        td {
            padding: 13px 20px;
            font-size: 13px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--gray-50); }

        .no-antrian { font-weight: 700; color: var(--green-primary); }

        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px; font-weight: 500;
        }
        .badge-dipanggil { background: #dcfce7; color: #15803d; }
        .badge-dilayani  { background: #dcfce7; color: #15803d; }
        .badge-menunggu  { background: #fef9c3; color: #a16207; }
        .badge-selesai   { background: #e0e7ff; color: #3730a3; }

        .btn-detail {
            padding: 5px 14px;
            border: 1px solid var(--green-primary);
            border-radius: 6px;
            background: var(--white);
            color: var(--green-primary);
            font-size: 12px; cursor: pointer;
            transition: background .15s;
        }
        .btn-detail:hover { background: var(--green-pale); }

        .table-footer {
            padding: 14px 20px;
            display: flex; align-items: center; justify-content: space-between;
            border-top: 1px solid var(--gray-200);
        }
        .table-footer p { font-size: 12px; color: var(--gray-400); }
        .pagination-nav { display: flex; gap: 6px; }
        .nav-btn {
            width: 28px; height: 28px; border: 1px solid var(--gray-200); background: var(--white);
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            color: var(--gray-600); font-size: 11px; cursor: pointer;
        }
        .nav-btn:hover { background: var(--gray-50); }

        .pagination { display: flex; gap: 4px; }
        .page-btn {
            width: 30px; height: 30px;
            border: 1px solid var(--gray-200);
            background: var(--white);
            border-radius: 6px;
            font-size: 12px; cursor: pointer; color: var(--gray-600);
            display: flex; align-items: center; justify-content: center;
            transition: background .15s;
        }
        .page-btn:hover { background: var(--green-pale); color: var(--green-primary); }
        .page-btn.active { background: var(--green-primary); color: var(--white); border-color: var(--green-primary); }

        /* ===== FILTER (untuk antrian) ===== */
        .table-filters {
            padding: 14px 20px;
            display: flex; gap: 10px; align-items: center;
            border-bottom: 1px solid var(--gray-200);
            flex-wrap: wrap;
        }
        .filter-select, .search-input {
            padding: 7px 12px;
            border: 1px solid var(--gray-200);
            border-radius: 7px;
            font-size: 13px; color: var(--gray-800);
            background: var(--white);
            outline: none;
        }
        .filter-select { min-width: 130px; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
            padding-right: 30px;
        }
        .filter-select:focus, .search-input:focus { border-color: var(--green-light); }
        .search-wrap { position: relative; flex: 1; min-width: 150px; }
        .search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 13px; }
        .search-input { width: 100%; padding-left: 32px; }

        .btn-refresh {
            padding: 7px 14px;
            border: 1px solid var(--green-primary);
            border-radius: 7px;
            background: var(--white);
            color: var(--green-primary);
            font-size: 13px; font-weight: 500; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .15s;
        }
        .btn-refresh:hover { background: var(--green-xpale); }

        /* ===== KOMPONEN ANTRIAN ===== */
        .lower-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }
        .right-col { display: flex; flex-direction: column; gap: 16px; }

        .dilayani-card {
            background: var(--green-primary);
            border-radius: 12px;
            padding: 18px;
            color: var(--white);
        }
        .dilayani-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px;
        }
        .dilayani-header h3 { font-size: 16px; font-weight: 700; }
        .dilayani-header p  { font-size: 12px; opacity: .85; }
        .dilayani-header i  { font-size: 18px; opacity: .8; }

        .antrian-box {
            background: var(--white);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin-bottom: 12px;
        }
        .antrian-box .label { font-size: 11px; color: var(--gray-400); text-transform: uppercase; letter-spacing: .5px; }
        .antrian-box .no    { font-size: 36px; font-weight: 800; color: var(--green-primary); line-height: 1; margin: 4px 0; }
        .antrian-box .nama  { font-size: 13px; color: var(--gray-600); }

        .dilayani-meta { display: flex; flex-direction: column; gap: 6px; }
        .meta-row { display: flex; justify-content: space-between; font-size: 12px; opacity: .9; }
        .meta-row strong { opacity: 1; font-size: 13px; }

        .poli-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            overflow: hidden;
        }
        .poli-card-header {
            padding: 14px 18px 12px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px; font-weight: 600;
        }
        .poli-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .poli-item {
            padding: 12px 14px;
            border-bottom: 1px solid var(--gray-100);
            border-right: 1px solid var(--gray-100);
        }
        .poli-item:nth-child(even)  { border-right: none; }
        .poli-item:nth-last-child(-n+2) { border-bottom: none; }

        .poli-top { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .poli-icon {
            width: 28px; height: 28px;
            background: var(--green-pale);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
        }
        .poli-icon i { font-size: 12px; color: var(--green-primary); }
        .poli-name   { font-size: 12px; font-weight: 600; }
        .poli-count  { font-size: 11px; color: var(--gray-400); margin-top: 2px; }

        .progress-bar {
            height: 4px; background: var(--gray-200);
            border-radius: 2px; overflow: hidden;
        }
        .progress-fill { height: 100%; background: var(--green-primary); border-radius: 2px; }

        /* ===== UTILITY DASHBOARD ===== */
        .search-container { position: relative; width: 240px; }
        .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 13px; }
        .search-input-dash {
            width: 100%; padding: 7px 12px 7px 34px; border: 1px solid var(--gray-200); border-radius: 7px;
            font-size: 13px; outline: none; background: var(--white);
        }
        .search-input-dash:focus { border-color: var(--green-light); }

        /* ===== PENDAFTARAN ===== */
        .pendaftaran-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            align-items: start;
        }

        .form-body { padding: 24px 20px; display: flex; flex-direction: column; gap: 24px; }
        .form-section-title {
            display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700;
            color: var(--gray-800); border-bottom: 1px dashed var(--gray-200); padding-bottom: 10px;
        }
        .form-section-title i { color: var(--green-primary); font-size: 15px; }
        
        .form-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .form-row-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        
        .form-group { display: flex; flex-direction: column; gap: 6px; position: relative; }
        .form-group label { font-size: 12px; font-weight: 600; color: var(--gray-800); }
        .form-group label span { color: #b91c1c; margin-left: 2px; }

        .form-input, .form-select, .form-textarea {
            width: 100%; padding: 10px 12px; border: 1px solid var(--gray-200); border-radius: 8px;
            font-size: 13px; font-family: var(--font); color: var(--gray-800); outline: none; background: var(--white);
            transition: border-color .15s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--green-light); }
        .form-input::placeholder, .form-textarea::placeholder { color: var(--gray-400); }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
        }
        
        .form-input-locked { background: var(--gray-50); color: var(--gray-600); padding-right: 36px; font-weight: 500; }
        .lock-icon { position: absolute; right: 12px; bottom: 12px; color: var(--gray-400); font-size: 13px; }
        .form-textarea { resize: vertical; min-height: 70px; }

        .form-actions {
            padding: 16px 20px; background: var(--gray-50); border-top: 1px solid var(--gray-200);
            display: flex; justify-content: flex-end; gap: 10px;
        }
        .btn-cancel {
            padding: 10px 24px; border: 1px solid var(--gray-200); border-radius: 8px;
            background: var(--white); color: var(--gray-600); font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn-cancel:hover { background: var(--gray-100); }
        .btn-submit {
            padding: 10px 24px; border: none; border-radius: 8px;
            background: var(--green-primary); color: var(--white); font-size: 13px; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
        }
        .btn-submit:hover { background: var(--green-mid); }
        .btn-reset {
            padding: 7px 14px; border: 1px solid var(--gray-200); border-radius: 7px;
            background: var(--white); color: var(--gray-600); font-size: 12px; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background .15s;
        }
        .btn-reset:hover { background: var(--gray-50); }

        .widget-title {
            display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700;
            color: var(--gray-800); padding: 14px 16px; border-bottom: 1px solid var(--gray-200);
        }
        .widget-title i { color: var(--green-primary); font-size: 14px; }

        .info-header { padding: 12px 16px; background: var(--gray-50); display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
        .info-date { font-weight: 600; color: var(--gray-600); }
        .info-time { background: var(--green-pale); color: var(--green-primary); font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
        .info-list { padding: 8px 16px 14px; display: flex; flex-direction: column; gap: 12px; }
        .info-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
        .info-label { color: var(--gray-400); display: flex; align-items: center; gap: 8px; }
        .info-row strong { font-size: 14px; color: var(--gray-800); }

        .guide-box { background: #f0fdf4; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden; }
        .guide-body { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 10px; }
        .guide-item { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #166534; line-height: 1.4; }
        .guide-item i { color: var(--green-primary); margin-top: 2px; font-size: 11px; }

        .history-list { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
        .history-item {
            display: flex; align-items: center; gap: 12px; padding: 10px;
            border: 1px solid var(--gray-100); border-radius: 8px; background: var(--gray-50);
        }
        .history-badge {
            background: var(--green-pale); color: var(--green-primary); font-weight: 700;
            font-size: 12px; padding: 6px 8px; border-radius: 6px; min-width: 50px; text-align: center;
        }
        .history-details { display: flex; flex-direction: column; gap: 1px; flex: 1; }
        .history-name { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .history-sub { font-size: 11px; color: var(--gray-400); }
        
        .btn-view-all {
            width: calc(100% - 32px); margin: 4px 16px 16px; padding: 8px;
            border: 1px solid var(--green-primary); border-radius: 8px; background: var(--white);
            color: var(--green-primary); font-size: 12px; font-weight: 600; cursor: pointer; text-align: center;
            transition: background .15s;
        }
        .btn-view-all:hover { background: var(--green-xpale); }

        /* ===== EMR DOKTER ===== */
        .patient-card {
            background: var(--white); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
            padding: 20px; display: flex; justify-content: space-between; gap: 20px; grid-column: 1 / -1;
        }
        .patient-left { display: flex; gap: 20px; }
        .patient-avatar { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; background: var(--gray-200); }
        
        .patient-meta { display: flex; flex-direction: column; gap: 4px; }
        .patient-name-box { display: flex; align-items: center; gap: 10px; }
        .patient-name-box h2 { font-size: 18px; font-weight: 700; color: var(--gray-800); }
        .gender-badge { background: #dcfce7; color: #166534; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
        
        .info-grid-inline { display: flex; gap: 24px; font-size: 12px; color: var(--gray-600); margin-top: 4px; }
        .info-grid-inline strong { color: var(--gray-800); font-weight: 600; }
        
        .address-box { font-size: 12px; color: var(--gray-600); margin-top: 6px; display: flex; flex-direction: column; gap: 2px; }

        .patient-right { display: flex; gap: 12px; align-items: flex-start; }
        .vitals-box {
            border: 1px solid var(--gray-200); border-radius: 8px; padding: 10px 14px;
            font-size: 12px; display: flex; flex-direction: column; gap: 4px; min-width: 110px;
        }
        .vitals-box.alert-vitals { border-color: #fca5a5; background: #fff5f5; }
        .vitals-label { font-size: 10px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; }
        .vitals-label.alert-text { color: #b91c1c; }
        .vitals-val { font-size: 13px; font-weight: 700; color: var(--gray-800); }
        .vitals-sub { font-size: 11px; color: var(--gray-400); }

        .emr-nav-bar {
            grid-column: 1 / -1; display: flex; border-bottom: 1px solid var(--gray-200); gap: 4px; overflow-x: auto;
        }
        .emr-tab-btn {
            background: none; border: none; padding: 10px 16px; font-size: 13px; font-weight: 600;
            color: var(--gray-600); cursor: pointer; position: relative; white-space: nowrap; transition: color .15s;
        }
        .emr-tab-btn:hover { color: var(--green-primary); }
        .emr-tab-btn.active { color: var(--green-primary); }
        .emr-tab-btn.active::after {
            content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
            height: 3px; background: var(--green-primary); border-radius: 3px 3px 0 0;
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .left-column-panels {
            grid-column: 1 / -1;
        }

        .note-body { padding: 18px; display: flex; flex-direction: column; gap: 16px; }
        .note-row { display: flex; align-items: flex-start; gap: 16px; }
        .note-letter {
            width: 24px; height: 24px; border-radius: 50%; background: var(--green-pale);
            color: var(--green-primary); display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; flex-shrink: 0;
        }
        .note-content { flex: 1; font-size: 13px; line-height: 1.5; color: var(--gray-800); }
        .note-title { font-weight: 700; color: var(--gray-600); margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; }
        
        .vitals-tag-group { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
        .vital-tag { background: var(--gray-100); padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; color: var(--gray-600); }
        .vital-tag.success { background: #dcfce7; color: #15803d; }

        .triple-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .med-list, .order-list { display: flex; flex-direction: column; gap: 10px; padding: 14px; }
        .med-item { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 10px; border-bottom: 1px dashed var(--gray-200); }
        .med-item:last-child { border-bottom: none; padding-bottom: 0; }
        .med-num { font-size: 11px; font-weight: 700; color: var(--white); background: var(--green-light); width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: 2px; }
        .med-details { flex: 1; margin-left: 10px; display: flex; flex-direction: column; gap: 2px; }
        .med-name { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .med-rule { font-size: 11px; color: var(--gray-400); }
        .med-qty { font-size: 11px; font-weight: 600; color: var(--gray-600); text-align: right; }

        .mini-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
        .badge-warning { background: #fef9c3; color: #a16207; }
        .badge-normal { background: #e0f2fe; color: #0369a1; }
        
        .btn-card-action {
            width: 100%; border: none; border-top: 1px solid var(--gray-200); background: var(--white);
            padding: 10px; text-align: center; color: var(--green-primary); font-size: 12px; font-weight: 600;
            cursor: pointer; transition: background .15s;
        }
        .btn-card-action:hover { background: var(--green-xpale); }

        .quick-action-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 14px 16px; }
        .btn-qa {
            display: flex; align-items: center; gap: 8px; padding: 10px;
            border: 1px solid var(--gray-200); border-radius: 8px; background: var(--white);
            font-size: 11px; font-weight: 600; color: var(--gray-600); cursor: pointer;
        }
        .btn-qa:hover { background: var(--green-xpale); border-color: var(--green-light); color: var(--green-primary); }
        .btn-qa i { color: var(--green-primary); font-size: 13px; }

        .history-box { padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; font-size: 12px; }
        .history-date-row { display: flex; justify-content: space-between; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
        .history-item-row { display: flex; margin-bottom: 4px; }
        .history-label { width: 80px; color: var(--gray-400); font-weight: 500; }
        .history-val { flex: 1; color: var(--gray-600); }

        .empty-tab-view { padding: 40px; text-align: center; color: var(--gray-400); font-size: 13px; }
        .empty-tab-view i { font-size: 32px; color: var(--gray-300); margin-bottom: 10px; display: block; }

        /* ===== FARMASI ===== */
        .stat-card.card-warning .stat-icon { background: #fef9c3; color: #a16207; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-value-group { display: flex; align-items: baseline; gap: 4px; margin-top: 2px; }
        .stat-value-group strong { font-size: 24px; font-weight: 700; color: var(--gray-800); }
        .stat-value-group span { font-size: 11px; color: var(--gray-400); }

        .table-filter-bar {
            padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;
            background: var(--white);
        }
        .filter-left { display: flex; gap: 10px; }
        .select-filter {
            padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px;
            font-size: 13px; color: var(--gray-600); outline: none; background: var(--white);
            appearance: none; min-width: 130px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
        }
        .btn-add-obat {
            padding: 8px 14px; border: none; border-radius: 8px; background: var(--green-primary);
            color: var(--white); font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;
        }
        .btn-add-obat:hover { background: var(--green-mid); }

        .obat-code { font-weight: 700; color: var(--green-primary); }
        .obat-name { font-weight: 500; color: var(--gray-800); }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-aman { background: #dcfce7; color: #15803d; }
        .badge-menipis { background: #ffedd5; color: #c2410c; }
        .badge-habis { background: #fee2e2; color: #b91c1c; }

        .actions-cell { display: flex; gap: 6px; }
        .btn-action {
            width: 28px; height: 28px; border: 1px solid var(--gray-200); border-radius: 6px;
            background: var(--white); display: flex; align-items: center; justify-content: center;
            font-size: 12px; cursor: pointer; transition: background .15s;
        }
        .btn-action.edit { color: var(--green-primary); }
        .btn-action.edit:hover { background: var(--green-pale); border-color: var(--green-light); }
        .btn-action.delete { color: #b91c1c; }
        .btn-action.delete:hover { background: #fee2e2; border-color: #fca5a5; }

        .list-widget { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
        .list-item {
            display: flex; align-items: center; justify-content: space-between; padding: 10px;
            border: 1px solid var(--gray-100); border-radius: 8px; background: var(--gray-50);
        }
        .item-left { display: flex; align-items: center; gap: 10px; }
        .item-left i { font-size: 14px; color: #c2410c; }
        .item-left.habis i { color: #b91c1c; }
        .item-text { display: flex; flex-direction: column; gap: 1px; }
        .item-title { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .item-sub { font-size: 11px; color: var(--gray-400); }

        .resep-item { display: flex; align-items: center; gap: 12px; padding: 10px; border: 1px solid var(--gray-100); border-radius: 8px; background: var(--gray-50); }
        .resep-icon-box { width: 32px; height: 32px; background: var(--green-pale); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-size: 14px; }
        .resep-details { flex: 1; display: flex; flex-direction: column; gap: 1px; }
        .resep-no { font-size: 11px; font-weight: 600; color: var(--gray-400); }
        .resep-patient { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .resep-time { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
        .badge-done { background: #dcfce7; color: #15803d; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }

        /* ===== KASIR ===== */
        .simulasi-box { grid-column: 1 / -1; background: #fffbeb; border: 1px dashed #f59e0b; padding: 15px; border-radius: 10px; display: flex; gap: 15px; align-items: center; }
        .simulasi-box span { font-size: 12px; font-weight: bold; color: #b45309; }
        .btn-sim { padding: 6px 12px; background: #f59e0b; color: white; text-decoration: none; font-size: 12px; font-weight: 600; border-radius: 6px; }
        .btn-sim:hover { background: #d97706; }

        .search-patient-card { grid-column: 1 / -1; background: var(--white); border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06); display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--green-primary); }
        .search-form-flex { display: flex; gap: 10px; width: 450px; }
        .input-search-pasien { flex: 1; padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 13px; outline: none; }
        .btn-search-submit { padding: 8px 16px; background: var(--green-primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }

        .patient-profile-bar { display: flex; gap: 20px; padding: 20px; grid-column: 1 / -1; background: var(--white); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .patient-avatar-box { width: 56px; height: 56px; border-radius: 50%; background: var(--green-pale); display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-size: 24px; }
        .patient-details-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; flex: 1; }
        .p-group { display: flex; flex-direction: column; }
        .p-group label { font-size: 10px; color: var(--gray-400); text-transform: uppercase; font-weight: 600; }
        .p-group span, .p-group strong { font-size: 13px; color: var(--gray-800); }

        .table-action-bar { padding: 14px 20px; display: flex; justify-content: space-between; background: var(--white); border-bottom: 1px solid var(--gray-100); }
        .btn-add-item { padding: 6px 12px; background: var(--green-primary); color: var(--white); border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }

        .billing-summary-list { padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 13px; color: var(--gray-600); }
        
        .total-pay-box { background: var(--green-xpale); padding: 14px 16px; border-top: 1px dashed var(--gray-200); border-bottom: 1px dashed var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .total-pay-box strong { font-size: 18px; color: var(--green-primary); font-weight: 700; }

        .pay-form { padding: 16px; display: flex; flex-direction: column; gap: 14px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 11px; font-weight: 600; color: var(--gray-400); text-transform: uppercase; }
        
        .select-container { position: relative; display: flex; align-items: center; }
        .select-container i { position: absolute; left: 12px; color: var(--gray-600); font-size: 14px; }
        .select-pay { width: 100%; padding: 10px 10px 10px 36px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 13px; outline: none; background: var(--white); font-weight: 600; color: var(--gray-800); -webkit-appearance: none; appearance: none; }
        .select-container::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 12px; color: var(--gray-400); pointer-events: none; }

        .input-money-container { position: relative; display: flex; align-items: center; }
        .currency-prefix { position: absolute; left: 12px; font-size: 13px; font-weight: 600; color: var(--gray-400); }
        .money-input { width: 100%; padding: 10px 12px 10px 38px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 14px; font-weight: 700; text-align: right; color: var(--gray-800); outline: none; }
        
        .nominal-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .btn-nom { padding: 10px; border: 1px solid var(--gray-200); border-radius: 6px; background: var(--white); font-size: 12px; font-weight: 600; color: var(--gray-600); cursor: pointer; text-align: center; }
        .btn-nom:hover { border-color: var(--green-light); color: var(--green-primary); }

        .change-box { background: var(--green-xpale); padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .change-box strong { color: var(--green-primary); font-size: 14px; }

        .btn-submit-pay { width: calc(100% - 32px); margin: 0 16px 16px; padding: 12px; border: none; border-radius: 8px; background: var(--green-primary); color: var(--white); font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit-pay:hover { background: var(--green-mid); }

        /* ===== FOOTER ===== */
        footer { font-size: 11px; color: var(--gray-400); text-align: center; margin-top: 10px; padding-bottom: 10px; }

        /* ===== RESPONSIF TOTAL (DESKTOP, TABLET, MOBILE) ===== */
        @media (max-width: 1024px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .chart-grid, .lower-grid, .pendaftaran-grid, .triple-grid, .poli-grid { grid-template-columns: 1fr; gap: 16px; }
            .right-col { order: -1; }
            .patient-card { flex-direction: column; gap: 16px; }
            .patient-right { flex-wrap: wrap; }
            .patient-details-grid { grid-template-columns: repeat(2, 1fr); }
            .search-form-flex { width: 100%; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 60px; }
            .sidebar-brand .brand-text, .nav-list li a span, .btn-quick span, .sidebar-footer a span { display: none; }
            .sidebar-brand { justify-content: center; padding: 16px 0; }
            .nav-list li a { justify-content: center; padding: 12px 0; }
            .nav-list li a i { font-size: 18px; }
            .main { margin-left: 60px; }
            .topbar { padding: 12px 16px; height: auto; flex-wrap: wrap; gap: 10px; }
            .topbar-left h1 { font-size: 16px; }
            .user-profile span { display: none; }
            .search-wrap { width: 100%; order: 3; min-width: 100%; margin-top: 4px; }
            .content { padding: 16px; }
            .stat-grid { grid-template-columns: 1fr; }
            .form-row-3, .form-row-2, .patient-details-grid { grid-template-columns: 1fr; }
            .table-filter-bar, .table-filters, .search-form-flex { flex-direction: column; align-items: stretch; gap: 10px; }
            .filter-left { flex-direction: column; width: 100%; gap: 10px; }
            .select-filter, .search-container, .input-money-container, .select-container { width: 100%; min-width: 100%; }
            .search-container input, .select-filter { width: 100%; }
            .btn-add-obat, .btn-submit, .btn-cancel, .btn-submit-pay { width: 100%; justify-content: center; }
            .form-actions { flex-direction: column; gap: 10px; }
            table { display: block; overflow-x: auto; white-space: nowrap; width: 100%; -webkit-overflow-scrolling: touch; }
            .patient-left { flex-direction: column; align-items: center; text-align: center; gap: 12px; }
            .patient-right { width: 100%; justify-content: center; }
            .vitals-box { flex: 1 1 40%; min-width: 130px; }
            .emr-nav-bar { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
            .emr-tab-btn { flex-shrink: 0; }
            .note-row, .info-grid-inline { flex-direction: column; gap: 8px; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 50px; }
            .main { margin-left: 50px; }
            .topbar { padding: 10px 12px; }
            .content { padding: 12px; }
            .quick-action-grid, .nominal-grid { grid-template-columns: 1fr; }
            .vitals-box { flex: 1 1 100%; }
            .modal-content { width: 95%; margin: 10px; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-heart-pulse"></i></div>
        <div class="brand-text">
            <h2>SIMRS</h2>
            <p>Clinical Precision</p>
        </div>
    </div>

    <ul class="nav-list">
        <?php foreach ($nav_items as $item): 
            $is_active = ($page == $item['page']) ? 'class="active"' : '';
        ?>
        <li>
            <a href="?page=<?= $item['page'] ?>" <?= $is_active ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <a href="?page=pendaftaran" class="btn-quick">
        <i class="fa-solid fa-plus"></i> <span>Quick Admission</span>
    </a>

    <div class="sidebar-footer">
        <a href="#"><i class="fa-solid fa-gear"></i> <span>Settings</span></a>
        <a href="#"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<div class="main">

    <header class="topbar">
        <div class="topbar-left">
            <h1><?= ucfirst($page) ?></h1>
            <p class="breadcrumb">Front Office &rsaquo; <span><?= ucfirst($page) ?></span></p>
        </div>
        <div class="topbar-right">
            <button class="icon-btn">
                <i class="fa-solid fa-bell"></i>
                <span class="badge">3</span>
            </button>
            <button class="icon-btn"><i class="fa-solid fa-gear"></i></button>
            <div class="user-info">
                <div class="user-avatar"><i class="fa-solid fa-user-tie"></i></div>
                <div class="user-text">
                    <strong>Dr. Hendrawan</strong>
                    <small>Front Office Supervisor</small>
                </div>
            </div>
        </div>
    </header>

    <main class="content">
        <?php if (!empty($db_conn_error)): ?>
        <div style="background: #fef3c7; color: #92400e; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f59e0b; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px; color: #d97706;"></i>
            <div>
                <strong>Peringatan Koneksi Cloud Supabase:</strong> <?= htmlspecialchars($db_conn_error) ?><br>
                <small style="font-weight: normal; color: #78350f;">Untuk mengaktifkan koneksi database PostgreSQL di terminal Anda saat ini, matikan server lokal (Ctrl+C) lalu jalankan dengan perintah: <b><code>php -d extension=pdo_pgsql -d extension=pgsql -S localhost:8000</code></b></small>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($msg_success)): ?>
        <div style="background: #dcfce7; color: #15803d; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #16a34a; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i>
            <?= $msg_success ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($msg_error)): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc2626; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 18px;"></i>
            <?= $msg_error ?>
        </div>
        <?php endif; ?>
        <?php renderContent(
            $page,
            $stats_dashboard,
            $antrian_terkini,
            $distribusi,
            $stats_antrian,
            $antrian,
            $sedang_dilayani,
            $status_poli,
            $info_hari_ini,
            $riwayat_pendaftaran,
            $emr_tabs,
            $stats_farmasi,
            $daftar_obat,
            $stok_menipis,
            $resep_terbaru,
            $detail_transaksi,
            $nominal_cepat,
            $nama_pasien,
            $no_rm,
            $nik,
            $tgl_lahir,
            $alamat,
            $telepon,
            $penjamin,
            $dokter,
            $poli
        ); ?>
        <footer>
            &copy; 2026 SIMRS. All rights reserved.
        </footer>
    </main>
</div>

<script>
function switchTab(event, tabId) {
    const panels = document.querySelectorAll('.tab-panel');
    panels.forEach(panel => panel.classList.remove('active'));

    const tabs = document.querySelectorAll('.emr-tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}

function updatePaymentIcon() {
    const method = document.getElementById('payment-method').value;
    const icon = document.getElementById('pay-icon');
    
    if (method === 'Tunai') {
        icon.className = 'fa-solid fa-money-bill-wave';
    } else if (method === 'QRIS') {
        icon.className = 'fa-solid fa-qrcode';
    } else if (method === 'Debit') {
        icon.className = 'fa-solid fa-credit-card';
    } else if (method === 'BPJS') {
        icon.className = 'fa-solid fa-hospital';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const moneyInput = document.querySelector('.money-input');
    const changeVal = document.querySelector('.change-box strong');
    const nomBtns = document.querySelectorAll('.btn-nom');
    const totalTagihan = 580000;

    function calcChange(val) {
        let numericVal = parseInt(val.replace(/\D/g, '')) || 0;
        let kembalian = numericVal - totalTagihan;
        if (kembalian < 0) kembalian = 0;
        if (changeVal) changeVal.textContent = 'Rp ' + kembalian.toLocaleString('id-ID');
    }

    nomBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            let txt = this.textContent.trim();
            if (txt === 'Pas Tagihan') {
                if (moneyInput) moneyInput.value = '580.000';
                calcChange('580000');
            } else {
                if (moneyInput) moneyInput.value = txt;
                calcChange(txt);
            }
        });
    });

    if (moneyInput) {
        moneyInput.addEventListener('input', function() {
            calcChange(this.value);
        });
    }
});
</script>

</body>
</html>