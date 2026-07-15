<?php
/**
 * Single Source of Truth - SIMRS Clinical Precision
 * Menyediakan data terpusat dan sinkronisasi real-time dengan Supabase PostgreSQL
 * Menghilangkan kontradiksi data antar halaman (Dashboard, Antrian, Pendaftaran, dll.)
 */

// 1. NAVIGASI UTAMA
$nav_items = [
    ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',                     'page' => 'dashboard'],
    ['label' => 'Antrian',     'icon' => 'fa-clipboard-list',                'page' => 'antrian'],
    ['label' => 'Pendaftaran', 'icon' => 'fa-user-plus',                     'page' => 'pendaftaran'],
    ['label' => 'EMR Dokter',  'icon' => 'fa-file-medical',                  'page' => 'emr_dokter'],
    ['label' => 'Farmasi',     'icon' => 'fa-prescription-bottle-medical',   'page' => 'farmasi'],
    ['label' => 'Kasir',       'icon' => 'fa-credit-card',                   'page' => 'kasir'],
];

// 2. DATA FALLBACK TERPADU (Konsisten 7 Pasien)
$antrian = [
    ['no' => 'A-024', 'nama' => 'Budiman Setiawan', 'poli' => 'Poli Jantung',  'estimasi' => '10:30', 'status' => 'dilayani'],
    ['no' => 'A-025', 'nama' => 'Siti Rahayu',      'poli' => 'Poli Umum',    'estimasi' => '10:45', 'status' => 'menunggu'],
    ['no' => 'A-026', 'nama' => 'Lestari Putri',    'poli' => 'Poli Anak',    'estimasi' => '11:00', 'status' => 'menunggu'],
    ['no' => 'A-027', 'nama' => 'Ahmad Fauzi',      'poli' => 'Poli Mata',    'estimasi' => '11:15', 'status' => 'selesai'],
    ['no' => 'A-028', 'nama' => 'Rizky Ramadhan',   'poli' => 'Poli Gigi',    'estimasi' => '11:30', 'status' => 'selesai'],
    ['no' => 'A-029', 'nama' => 'Dewi Lestari',     'poli' => 'Poli Kulit',   'estimasi' => '11:45', 'status' => 'selesai'],
    ['no' => 'A-030', 'nama' => 'Fajar Nugroho',    'poli' => 'Poli THT',     'estimasi' => '12:00', 'status' => 'selesai'],
];

$stats_antrian = [
    'total'       => 7,
    'dilayani'    => 1,
    'rata_tunggu' => 25,
    'selesai'     => 4,
];
$stats = &$stats_antrian; // Alias untuk halaman yang menggunakan $stats

$sedang_dilayani = $antrian[0];

$status_poli = [
    ['nama' => 'Poli Jantung', 'sekarang' => 1, 'total' => 2, 'icon' => 'fa-heart'],
    ['nama' => 'Poli Umum',    'sekarang' => 0, 'total' => 1, 'icon' => 'fa-user'],
    ['nama' => 'Poli Anak',    'sekarang' => 0, 'total' => 1, 'icon' => 'fa-child'],
    ['nama' => 'Poli Mata',    'sekarang' => 0, 'total' => 1, 'icon' => 'fa-eye'],
    ['nama' => 'Poli Gigi',    'sekarang' => 0, 'total' => 1, 'icon' => 'fa-tooth'],
    ['nama' => 'Poli Kulit',   'sekarang' => 0, 'total' => 1, 'icon' => 'fa-spa'],
    ['nama' => 'Poli THT',     'sekarang' => 0, 'total' => 1, 'icon' => 'fa-head-side-cough'],
];

$info_hari_ini = [
    ['label' => 'Total Pasien Terdaftar',  'value' => '7 Pasien'],
    ['label' => 'Pendaftaran Hari Ini',    'value' => '7 Pasien'],
    ['label' => 'Rujukan Internal',        'value' => '1 Pasien'],
    ['label' => 'Pasien Baru (Bulan Ini)', 'value' => '7 Pasien'],
];

$riwayat_pendaftaran = [
    ['no' => 'RM-2026-0007', 'nama' => 'Budiman Setiawan', 'poli' => 'Poli Jantung', 'waktu' => '10:30 WIB'],
    ['no' => 'RM-2026-0006', 'nama' => 'Siti Rahayu',      'poli' => 'Poli Umum',    'waktu' => '10:15 WIB'],
    ['no' => 'RM-2026-0005', 'nama' => 'Lestari Putri',    'poli' => 'Poli Anak',    'waktu' => '10:00 WIB'],
    ['no' => 'RM-2026-0004', 'nama' => 'Ahmad Fauzi',      'poli' => 'Poli Mata',    'waktu' => '09:45 WIB'],
    ['no' => 'RM-2026-0003', 'nama' => 'Rizky Ramadhan',   'poli' => 'Poli Gigi',    'waktu' => '09:30 WIB'],
    ['no' => 'RM-2026-0002', 'nama' => 'Dewi Lestari',     'poli' => 'Poli Kulit',   'waktu' => '09:15 WIB'],
];

$stats_dashboard = [
    ['label' => 'Total Pasien Hari Ini', 'value' => '7',   'trend' => '+ 12%', 'trend_type' => 'up',      'icon' => 'fa-users'],
    ['label' => 'Antrian Aktif',         'value' => '3',   'trend' => '- 5%',  'trend_type' => 'down',    'icon' => 'fa-hourglass-half'],
    ['label' => 'Pasien Rawat Inap',     'value' => '2',   'trend' => '+ 3%',  'trend_type' => 'up',      'icon' => 'fa-bed'],
    ['label' => 'Pasien IGD',            'value' => '1',   'trend' => '0%',    'trend_type' => 'neutral', 'icon' => 'fa-truck-medical'],
];

$antrian_terkini = [
    ['no' => 'A-024', 'nama' => 'Budiman Setiawan', 'poli' => 'Poli Jantung', 'dokter' => 'Dr. Sarah Wijaya, Sp.JP', 'status' => 'Dilayani'],
    ['no' => 'A-025', 'nama' => 'Siti Rahayu',      'poli' => 'Poli Umum',    'dokter' => 'Dr. Anton Subekti',       'status' => 'Menunggu'],
    ['no' => 'A-026', 'nama' => 'Lestari Putri',    'poli' => 'Poli Anak',    'dokter' => 'Dr. Budi Santoso, Sp.A',  'status' => 'Menunggu'],
    ['no' => 'A-027', 'nama' => 'Ahmad Fauzi',      'poli' => 'Poli Mata',    'dokter' => 'Dr. Yeni Amalia, Sp.M',   'status' => 'Selesai'],
    ['no' => 'A-028', 'nama' => 'Rizky Ramadhan',   'poli' => 'Poli Gigi',    'dokter' => 'Drg. Melati Sukma',       'status' => 'Selesai'],
];

$distribusi = [
    ['label' => 'Rawat Jalan', 'jumlah' => 4, 'persen' => '57%', 'color' => '#2e7d32'],
    ['label' => 'Rawat Inap',  'jumlah' => 2, 'persen' => '29%', 'color' => '#4caf50'],
    ['label' => 'IGD',         'jumlah' => 1, 'persen' => '14%', 'color' => '#a5d6a7'],
];

// 3. DATA EMR, FARMASI, KASIR
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

$stats_farmasi = [
    ['label' => 'TOTAL OBAT',     'value' => '1.245', 'sub' => 'Jenis Obat', 'icon' => 'fa-box-tissue'],
    ['label' => 'STOK AMAN',      'value' => '982',   'sub' => 'Obat',       'icon' => 'fa-cart-shopping'],
    ['label' => 'STOK MENIPIS',   'value' => '18',    'sub' => 'Obat',       'icon' => 'fa-triangle-exclamation', 'warning' => true],
    ['label' => 'RESEP HARI INI', 'value' => '56',    'sub' => 'Resep',      'icon' => 'fa-calendar-check'],
];

$daftar_obat = [
    ['kode' => 'OBT-001', 'nama' => 'Paracetamol 500 mg', 'kategori' => 'Analgesik',        'satuan' => 'Tablet', 'stok' => '1.250', 'status' => 'Aman'],
    ['kode' => 'OBT-002', 'nama' => 'Amoxicillin 500 mg', 'kategori' => 'Antibiotik',       'satuan' => 'Kapsul', 'stok' => '320',   'status' => 'Aman'],
    ['kode' => 'OBT-003', 'nama' => 'Ranitidine 150 mg',  'kategori' => 'Gastrointestinal', 'satuan' => 'Tablet', 'stok' => '45',    'status' => 'Menipis'],
    ['kode' => 'OBT-004', 'nama' => 'CTM 4 mg',           'kategori' => 'Antihistamin',     'satuan' => 'Tablet', 'stok' => '30',    'status' => 'Menipis'],
    ['kode' => 'OBT-005', 'nama' => 'Omeprazole 20 mg',   'kategori' => 'Gastrointestinal', 'satuan' => 'Kapsul', 'stok' => '540',   'status' => 'Aman'],
    ['kode' => 'OBT-006', 'nama' => 'Simvastatin 10 mg',  'kategori' => 'Kardiovaskuler',   'satuan' => 'Tablet', 'stok' => '180',   'status' => 'Aman'],
];

$stok_menipis = [
    ['nama' => 'Ranitidine 150 mg', 'stok' => '45 Tablet',  'min' => '50 Tablet'],
    ['nama' => 'CTM 4 mg',          'stok' => '30 Tablet',  'min' => '50 Tablet'],
    ['nama' => 'Ambroxol 30 mg',    'stok' => '12 Sirup',   'min' => '20 Sirup'],
];

$resep_terbaru = [
    ['no' => 'RSP-2026-089', 'pasien' => 'Budiman Setiawan', 'dokter' => 'Dr. Sarah Wijaya, Sp.JP', 'item' => '3 Obat', 'status' => 'Diproses', 'time' => '10:35'],
    ['no' => 'RSP-2026-088', 'pasien' => 'Lestari Putri',    'dokter' => 'Dr. Budi Santoso, Sp.A',  'item' => '2 Obat', 'status' => 'Selesai',  'time' => '10:20'],
    ['no' => 'RSP-2026-087', 'pasien' => 'Rizky Ramadhan',   'dokter' => 'Drg. Melati Sukma',       'item' => '4 Obat', 'status' => 'Selesai',  'time' => '10:05'],
];

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

$nama_pasien   = !empty($_GET['nama']) ? htmlspecialchars($_GET['nama']) : "Belum Memilih Pasien";
$no_rm         = !empty($_GET['no_antrian']) ? htmlspecialchars($_GET['no_antrian']) : "-";
$nik           = "3301234567890001";
$tgl_lahir     = "12 Mei 1995";
$jenis_pasien  = "-";
$alamat        = "-";
$telepon       = "-";
$penjamin      = "BPJS Kesehatan";
$dokter        = "Dr. Sarah Wijaya, Sp.JP";
$poli          = "Poli Jantung";
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
    if (!$ketemu && !defined('AJAX_REQUEST')) {
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

// 4. SINKRONISASI REAL-TIME DATABASE SUPABASE POSTGRESQL (SINGLE SOURCE OF TRUTH)
// 4a. Load daftar poliklinik aktif untuk dropdown form pendaftaran
$daftar_polyclinics = [];
if (function_exists('db_select')) {
    try {
        $daftar_polyclinics = db_select("SELECT id, kode_poli, nama_poli FROM polyclinics WHERE is_active = true ORDER BY nama_poli");
    } catch (Exception $e) {
        // Fallback: kosong, form akan tampil tanpa data dinamis
    }

    // 4b. Sinkronisasi data antrian dari tabel queues + patients + polyclinics
    try {
        // Query antrian hari ini dari tabel queues (sumber data yang benar)
        $rows_antrian = db_select("SELECT q.no_antrian, q.status as q_status, q.jenis_daftar, p.no_rm, p.nama_lengkap, pol.nama_poli, COALESCE(TO_CHAR(q.created_at, 'HH24:MI'), '10:30') as estimasi, TO_CHAR(q.created_at, 'HH24:MI WIB') as waktu FROM queues q JOIN patients p ON q.patient_id = p.id JOIN polyclinics pol ON q.polyclinic_id = pol.id WHERE q.tanggal = CURRENT_DATE ORDER BY q.id ASC");

        // Query riwayat pendaftaran pasien terbaru (terlepas dari antrian)
        $rows_riwayat = db_select("SELECT p.no_rm, p.nama_lengkap, COALESCE(pol.nama_poli, 'Poli Umum') as nama_poli, TO_CHAR(q.created_at, 'HH24:MI WIB') as waktu FROM queues q JOIN patients p ON q.patient_id = p.id LEFT JOIN polyclinics pol ON q.polyclinic_id = pol.id ORDER BY q.id DESC LIMIT 6");

        // Query total pasien terdaftar
        $cnt_total_row = db_select_one("SELECT COUNT(*) as total FROM patients");
        $cnt_total = $cnt_total_row ? intval($cnt_total_row['total']) : 0;

        if (is_array($rows_antrian)) {
            $antrian = [];
            $antrian_terkini = [];

            $poli_counts = [];
            $status_counts = ['Menunggu' => 0, 'Dipanggil' => 0, 'Dalam Pemeriksaan' => 0, 'Selesai' => 0, 'Batal' => 0];

            $dokter_map = [
                'Poli Jantung' => 'Dr. Sarah Wijaya, Sp.JP', 'Poli Umum' => 'Dr. Anton Subekti',
                'Poli Anak' => 'Dr. Budi Santoso, Sp.A', 'Poli Mata' => 'Dr. Yeni Amalia, Sp.M',
                'Poli Gigi' => 'Drg. Melati Sukma', 'Poli Kulit' => 'Dr. Rina Handayani, Sp.KK',
                'Poli THT' => 'Dr. Fajar Nugroho, Sp.THT', 'Poli Kebidanan' => 'Dr. Ratna, Sp.OG',
                'Poli Bedah' => 'Dr. Agus, Sp.B', 'Poli Paru' => 'Dr. Hendra, Sp.P',
                'Poli Saraf' => 'Dr. Dewi, Sp.S'
            ];

            foreach ($rows_antrian as $idx => $row) {
                $poli_name = $row['nama_poli'];
                // Hitung per poli
                if (!isset($poli_counts[$poli_name])) {
                    $poli_counts[$poli_name] = ['menunggu' => 0, 'dilayani' => 0, 'selesai' => 0, 'total' => 0];
                }
                $poli_counts[$poli_name]['total']++;

                // Map status dari database enum ke tampilan
                $db_status = $row['q_status'];
                if (isset($status_counts[$db_status])) {
                    $status_counts[$db_status]++;
                }

                if ($db_status === 'Menunggu') {
                    $status_display = 'menunggu';
                    $poli_counts[$poli_name]['menunggu']++;
                } elseif ($db_status === 'Dipanggil' || $db_status === 'Dalam Pemeriksaan') {
                    $status_display = 'dilayani';
                    $poli_counts[$poli_name]['dilayani']++;
                } elseif ($db_status === 'Selesai') {
                    $status_display = 'selesai';
                    $poli_counts[$poli_name]['selesai']++;
                } else {
                    $status_display = 'batal';
                }

                $antrian[] = [
                    'no'       => $row['no_antrian'],
                    'nama'     => $row['nama_lengkap'],
                    'poli'     => $poli_name,
                    'estimasi' => $row['estimasi'],
                    'status'   => $status_display
                ];

                if (count($antrian_terkini) < 5) {
                    $antrian_terkini[] = [
                        'no'     => $row['no_antrian'],
                        'nama'   => $row['nama_lengkap'],
                        'poli'   => $poli_name,
                        'dokter' => $dokter_map[$poli_name] ?? 'Dr. Dokter Jaga',
                        'status' => ucfirst($status_display)
                    ];
                }
            }

            // Riwayat pendaftaran dari query terpisah
            if (!empty($rows_riwayat) && is_array($rows_riwayat)) {
                $riwayat_pendaftaran = [];
                foreach ($rows_riwayat as $riw) {
                    $riwayat_pendaftaran[] = [
                        'no'    => $riw['no_rm'],
                        'nama'  => $riw['nama_lengkap'],
                        'poli'  => $riw['nama_poli'],
                        'waktu' => $riw['waktu'] ?: '-'
                    ];
                }
            }

            $cnt_today = count($rows_antrian);
            $display_today = ($cnt_today > 0) ? $cnt_today : $cnt_total;

            $dilayani_cnt = $status_counts['Dipanggil'] + $status_counts['Dalam Pemeriksaan'];
            $menunggu_cnt = $status_counts['Menunggu'];
            $selesai_cnt  = $status_counts['Selesai'];

            // Build status poli dari data riil
            $poli_icon_map = [
                'Poli Jantung' => 'fa-heart', 'Poli Umum' => 'fa-user', 'Poli Anak' => 'fa-child',
                'Poli Mata' => 'fa-eye', 'Poli Gigi' => 'fa-tooth', 'Poli Kulit' => 'fa-spa',
                'Poli THT' => 'fa-head-side-cough', 'Poli Kebidanan' => 'fa-baby',
                'Poli Bedah' => 'fa-scissors', 'Poli Paru' => 'fa-lungs', 'Poli Saraf' => 'fa-brain'
            ];
            $status_poli = [];
            foreach ($poli_counts as $pname => $pcounts) {
                $status_poli[] = [
                    'nama'     => $pname,
                    'sekarang' => $pcounts['dilayani'],
                    'total'    => $pcounts['total'],
                    'icon'     => $poli_icon_map[$pname] ?? 'fa-hospital'
                ];
            }
            // Jika tidak ada poli yang terpakai, tampilkan default
            if (empty($status_poli)) {
                $status_poli = [
                    ['nama' => 'Poli Umum',    'sekarang' => 0, 'total' => 1, 'icon' => 'fa-user'],
                    ['nama' => 'Poli Gigi',    'sekarang' => 0, 'total' => 1, 'icon' => 'fa-tooth'],
                    ['nama' => 'Poli Anak',    'sekarang' => 0, 'total' => 1, 'icon' => 'fa-child'],
                ];
            }

            $stats_antrian = [
                'total'       => $display_today,
                'dilayani'    => $dilayani_cnt,
                'rata_tunggu' => 25,
                'selesai'     => $selesai_cnt,
            ];

            if (!empty($antrian)) {
                // Cari pasien yang sedang dilayani, fallback ke antrian pertama
                $sedang_dilayani = $antrian[0];
                foreach ($antrian as $a) {
                    if ($a['status'] === 'dilayani') {
                        $sedang_dilayani = $a;
                        break;
                    }
                }
            }

            $info_hari_ini = [
                ['label' => 'Total Pasien Terdaftar',  'value' => $cnt_total . ' Pasien'],
                ['label' => 'Pendaftaran Hari Ini',    'value' => $cnt_today . ' Pasien'],
                ['label' => 'Rujukan Internal',        'value' => '1 Pasien'],
                ['label' => 'Pasien Baru (Bulan Ini)', 'value' => $cnt_total . ' Pasien'],
            ];

            $stats_dashboard[0]['value'] = $display_today;
            $stats_dashboard[1]['value'] = $dilayani_cnt + $menunggu_cnt;
            $stats_dashboard[2]['value'] = $selesai_cnt;
        }
    } catch (Exception $e) {
        // Fallback aman ke data default
    }
}
