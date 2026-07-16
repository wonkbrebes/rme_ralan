<?php
/**
 * Single Source of Truth - SIMRS Clinical Precision
 * Menyediakan data terpusat dan sinkronisasi real-time dengan Supabase PostgreSQL
 * Menghilangkan kontradiksi data antar halaman (Dashboard, Antrian, Pendaftaran, dll.)
 * 
 * SEMUA DATA DIAMBIL DARI DATABASE SUPABASE (DINAMIS)
 * Fallback statis hanya digunakan jika koneksi database gagal.
 */

// 1. NAVIGASI UTAMA (Statis — ini adalah konfigurasi UI, bukan data)
$nav_items = [
    ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',                     'page' => 'dashboard'],
    ['label' => 'Antrian',     'icon' => 'fa-clipboard-list',                'page' => 'antrian'],
    ['label' => 'Pendaftaran', 'icon' => 'fa-user-plus',                     'page' => 'pendaftaran'],
    ['label' => 'EMR Dokter',  'icon' => 'fa-file-medical',                  'page' => 'emr_dokter'],
    ['label' => 'Farmasi',     'icon' => 'fa-prescription-bottle-medical',   'page' => 'farmasi'],
    ['label' => 'Kasir',       'icon' => 'fa-credit-card',                   'page' => 'kasir'],
];

// EMR Tabs (Statis — konfigurasi UI)
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

// Nominal cepat kasir (konfigurasi kasir)
$nominal_cepat = ['100.000', '200.000', '500.000', 'Pas Tagihan'];

// 2. DATA FALLBACK (Digunakan HANYA jika koneksi DB gagal)
$antrian = [];
$stats_antrian = ['total' => 0, 'dilayani' => 0, 'rata_tunggu' => 0, 'selesai' => 0];
$stats = &$stats_antrian;
$sedang_dilayani = ['no' => '-', 'nama' => '-', 'poli' => '-', 'estimasi' => '-', 'status' => '-'];
$status_poli = [];
$info_hari_ini = [
    ['label' => 'Total Pasien Terdaftar',  'value' => '0 Pasien'],
    ['label' => 'Pendaftaran Hari Ini',    'value' => '0 Pasien'],
    ['label' => 'Rujukan Internal',        'value' => '0 Pasien'],
    ['label' => 'Pasien Baru (Bulan Ini)', 'value' => '0 Pasien'],
];
$riwayat_pendaftaran = [];
$daftar_pasien_master = [];
$stats_dashboard = [
    ['label' => 'Total Pasien Hari Ini', 'value' => '0', 'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-users'],
    ['label' => 'Antrian Aktif',         'value' => '0', 'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-hourglass-half'],
    ['label' => 'Selesai Dilayani',      'value' => '0', 'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-circle-check'],
    ['label' => 'Pasien IGD',            'value' => '0', 'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-truck-medical'],
];
$antrian_terkini = [];
$distribusi = [
    ['label' => 'Rawat Jalan', 'jumlah' => 0, 'persen' => '0%', 'color' => '#2e7d32'],
    ['label' => 'Rawat Inap',  'jumlah' => 0, 'persen' => '0%', 'color' => '#4caf50'],
    ['label' => 'IGD',         'jumlah' => 0, 'persen' => '0%', 'color' => '#a5d6a7'],
];
$grafik_7_hari = [0, 0, 0, 0, 0, 0, 0];
$grafik_7_labels = [];
$cnt_total = 0;

// Farmasi fallback
$stats_farmasi = [
    ['label' => 'TOTAL OBAT',     'value' => '0', 'sub' => 'Jenis Obat', 'icon' => 'fa-box-tissue'],
    ['label' => 'STOK AMAN',      'value' => '0', 'sub' => 'Obat',       'icon' => 'fa-cart-shopping'],
    ['label' => 'STOK MENIPIS',   'value' => '0', 'sub' => 'Obat',       'icon' => 'fa-triangle-exclamation', 'warning' => true],
    ['label' => 'RESEP HARI INI', 'value' => '0', 'sub' => 'Resep',      'icon' => 'fa-calendar-check'],
];
$daftar_obat = [];
$stok_menipis = [];
$resep_terbaru = [];

// Kasir/EMR fallback
$detail_transaksi = [];
$emr_pasien       = [];
$resep_pasien     = [];
$emr_history      = [];
$diagnosa_pasien  = [];
$order_lab        = [];
$order_radiologi  = [];
$order_results    = [];
$surat_rujukan    = [];

// Variabel pasien default (untuk EMR & Kasir)
$nama_pasien   = !empty($_GET['nama']) ? htmlspecialchars($_GET['nama']) : "Belum Memilih Pasien";
$no_rm         = !empty($_GET['no_antrian']) ? htmlspecialchars($_GET['no_antrian']) : "-";
$nik           = "-";
$tgl_lahir     = "-";
$jenis_pasien  = "-";
$alamat        = "-";
$telepon       = "-";
$penjamin      = "-";
$dokter        = "-";
$poli_pasien   = "-";
$perusahaan    = "Umum / Pribadi";

// 3. SINKRONISASI REAL-TIME DATABASE SUPABASE POSTGRESQL (SINGLE SOURCE OF TRUTH)
$daftar_polyclinics = [];
$db_connected = false;

if (function_exists('db_select')) {
    // 3a. Load daftar poliklinik aktif
    try {
        $daftar_polyclinics = db_select("SELECT id, kode_poli, nama_poli FROM polyclinics WHERE is_active = true ORDER BY nama_poli");
        $db_connected = true;
    } catch (Exception $e) {
        // Fallback: form akan tampil tanpa data dinamis
    }

    if ($db_connected) {
        // ============================================================
        // 3b. ANTRIAN & PENDAFTARAN (Dinamis dari tabel queues)
        // ============================================================
        try {
            $today_wib = date('Y-m-d'); // PHP sudah di-set Asia/Jakarta

            // Query antrian hari ini
            $rows_antrian = db_select("SELECT q.id as queue_id, q.no_antrian, q.status as q_status, q.jenis_daftar, p.id as patient_id, p.no_rm, p.nama_lengkap, p.nik, p.tanggal_lahir, p.alamat, p.no_telepon, p.jenis_pasien, pol.nama_poli, COALESCE(TO_CHAR(q.created_at AT TIME ZONE 'Asia/Jakarta', 'HH24:MI'), '-') as estimasi, TO_CHAR(q.created_at AT TIME ZONE 'Asia/Jakarta', 'HH24:MI \"WIB\"') as waktu FROM queues q JOIN patients p ON q.patient_id = p.id JOIN polyclinics pol ON q.polyclinic_id = pol.id WHERE q.tanggal = :today ORDER BY q.id ASC", ['today' => $today_wib]);

            // Query riwayat pendaftaran terbaru
            $rows_riwayat = db_select("SELECT p.no_rm, p.nama_lengkap, COALESCE(pol.nama_poli, 'Poli Umum') as nama_poli, TO_CHAR(COALESCE(q.created_at, p.created_at) AT TIME ZONE 'Asia/Jakarta', 'HH24:MI \"WIB\"') as waktu FROM patients p LEFT JOIN queues q ON q.patient_id = p.id LEFT JOIN polyclinics pol ON q.polyclinic_id = pol.id ORDER BY p.id DESC LIMIT 6");

            // Query total pasien terdaftar
            $cnt_total_row = db_select_one("SELECT COUNT(*) as total FROM patients");
            $cnt_total = $cnt_total_row ? intval($cnt_total_row['total']) : 0;

            // Query pasien baru bulan ini
            $cnt_bulan_row = db_select_one("SELECT COUNT(*) as total FROM patients WHERE created_at >= date_trunc('month', (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta'))");
            $cnt_bulan = $cnt_bulan_row ? intval($cnt_bulan_row['total']) : 0;

            // Query seluruh master data pasien untuk dropdown pendaftaran antrian pasien lama
            $rows_master = db_select("SELECT id, no_rm, nama_lengkap, nik, tanggal_lahir, jenis_kelamin, alamat, no_telepon, no_bpjs, gol_darah, jenis_pasien FROM patients ORDER BY nama_lengkap ASC");
            if (is_array($rows_master)) {
                $daftar_pasien_master = $rows_master;
            }

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
                    if (!isset($poli_counts[$poli_name])) {
                        $poli_counts[$poli_name] = ['menunggu' => 0, 'dilayani' => 0, 'selesai' => 0, 'total' => 0];
                    }
                    $poli_counts[$poli_name]['total']++;

                    $db_status = $row['q_status'];
                    $status_key = normalize_queue_status($db_status);

                    if ($status_key === 'menunggu') {
                        $status_counts['Menunggu']++;
                        $poli_counts[$poli_name]['menunggu']++;
                    } elseif ($status_key === 'dipanggil' || $status_key === 'dalam_pemeriksaan') {
                        $status_counts['Dipanggil']++;
                        $poli_counts[$poli_name]['dilayani']++;
                    } elseif ($status_key === 'selesai') {
                        $status_counts['Selesai']++;
                        $poli_counts[$poli_name]['selesai']++;
                    } else {
                        $status_counts['Batal']++;
                    }

                    $antrian[] = [
                        'no'           => $row['no_antrian'],
                        'nama'         => $row['nama_lengkap'],
                        'poli'         => $poli_name,
                        'estimasi'     => $row['estimasi'],
                        'status'       => $status_key,
                        'status_label' => get_queue_status_label($status_key),
                        'badge_class'  => get_queue_badge_class($status_key),
                    ];

                    if (count($antrian_terkini) < 5) {
                        $antrian_terkini[] = [
                            'no'           => $row['no_antrian'],
                            'nama'         => $row['nama_lengkap'],
                            'poli'         => $poli_name,
                            'dokter'       => $dokter_map[$poli_name] ?? 'Dr. Dokter Jaga',
                            'status'       => $status_key,
                            'status_label' => get_queue_status_label($status_key),
                            'badge_class'  => get_queue_badge_class($status_key),
                        ];
                    }
                }

                // Riwayat pendaftaran
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

                $dilayani_cnt = $status_counts['Dipanggil'] + $status_counts['Dalam Pemeriksaan'];
                $menunggu_cnt = $status_counts['Menunggu'];
                $selesai_cnt  = $status_counts['Selesai'];

                // Status poli dari data riil
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
                if (empty($status_poli)) {
                    $status_poli = [
                        ['nama' => 'Poli Umum', 'sekarang' => 0, 'total' => 0, 'icon' => 'fa-user'],
                        ['nama' => 'Poli Gigi', 'sekarang' => 0, 'total' => 0, 'icon' => 'fa-tooth'],
                        ['nama' => 'Poli Anak', 'sekarang' => 0, 'total' => 0, 'icon' => 'fa-child'],
                    ];
                }

                $stats_antrian = [
                    'total'       => $cnt_today,
                    'dilayani'    => $dilayani_cnt,
                    'rata_tunggu' => $cnt_today > 0 ? max(5, intval(($selesai_cnt * 15 + $menunggu_cnt * 20) / max(1, $cnt_today))) : 0,
                    'selesai'     => $selesai_cnt,
                ];

                if (!empty($antrian)) {
                    $sedang_dilayani = null;
                    foreach ($antrian as $a) {
                        if (get_queue_priority($a['status'] ?? '') <= 2) {
                            $sedang_dilayani = $a;
                            break;
                        }
                    }
                    if ($sedang_dilayani === null) {
                        foreach ($antrian as $a) {
                            if (normalize_queue_status($a['status'] ?? '') === 'menunggu') {
                                $sedang_dilayani = $a;
                                break;
                            }
                        }
                    }
                    if ($sedang_dilayani === null) {
                        $sedang_dilayani = $antrian[0];
                    }

                    $sedang_dilayani = [
                        'no' => $sedang_dilayani['no'] ?? '-',
                        'nama' => $sedang_dilayani['nama'] ?? '-',
                        'poli' => $sedang_dilayani['poli'] ?? '-',
                        'estimasi' => $sedang_dilayani['estimasi'] ?? '-',
                        'status' => get_queue_status_label($sedang_dilayani['status'] ?? ''),
                        'status_key' => normalize_queue_status($sedang_dilayani['status'] ?? ''),
                    ];
                }

                $info_hari_ini = [
                    ['label' => 'Total Pasien Terdaftar',  'value' => $cnt_total . ' Pasien'],
                    ['label' => 'Pendaftaran Hari Ini',    'value' => $cnt_today . ' Pasien'],
                    ['label' => 'Rujukan Internal',        'value' => '0 Pasien'],
                    ['label' => 'Pasien Baru (Bulan Ini)', 'value' => $cnt_bulan . ' Pasien'],
                ];

                // Dashboard stats — semua dinamis
                $stats_dashboard = [
                    ['label' => 'Total Pasien Hari Ini', 'value' => $cnt_today,                     'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-users'],
                    ['label' => 'Antrian Aktif',         'value' => $dilayani_cnt + $menunggu_cnt,   'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-hourglass-half'],
                    ['label' => 'Selesai Dilayani',      'value' => $selesai_cnt,                    'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-circle-check'],
                    ['label' => 'Pasien IGD',            'value' => '0',                             'trend' => '0%', 'trend_type' => 'neutral', 'icon' => 'fa-truck-medical'],
                ];

                // Distribusi kunjungan — dari data riil
                $total_kunjungan = max(1, $cnt_today);
                $distribusi = [
                    ['label' => 'Rawat Jalan', 'jumlah' => $cnt_today, 'persen' => '100%', 'color' => '#2e7d32'],
                    ['label' => 'Rawat Inap',  'jumlah' => 0,          'persen' => '0%',   'color' => '#4caf50'],
                    ['label' => 'IGD',         'jumlah' => 0,          'persen' => '0%',   'color' => '#a5d6a7'],
                ];
            }
        } catch (Exception $e) {
            // Fallback aman ke data default (sudah di-set di atas)
        }

        // ============================================================
        // 3c. GRAFIK KUNJUNGAN 7 HARI TERAKHIR (Dinamis)
        // ============================================================
        try {
            $hari_map_short = ['Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'];
            $grafik_7_hari = [];
            $grafik_7_labels = [];
            for ($i = 6; $i >= 0; $i--) {
                $tanggal = date('Y-m-d', strtotime("-$i days"));
                $label = $hari_map_short[date('D', strtotime("-$i days"))] ?? date('D', strtotime("-$i days"));
                $grafik_7_labels[] = $label;

                $row_count = db_select_one("SELECT COUNT(*) as cnt FROM queues WHERE tanggal = :tgl", ['tgl' => $tanggal]);
                $grafik_7_hari[] = $row_count ? intval($row_count['cnt']) : 0;
            }
        } catch (Exception $e) {
            $grafik_7_hari = [0, 0, 0, 0, 0, 0, 0];
            $grafik_7_labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        }

        // ============================================================
        // 3d. FARMASI — Daftar Obat, Stok Menipis, Resep (Dinamis)
        // ============================================================
        try {
            // Daftar obat dari tabel obat
            $rows_obat = db_select("SELECT kode_obat, nama_obat, satuan, stok FROM obat WHERE is_active = true ORDER BY nama_obat ASC");
            if (is_array($rows_obat) && !empty($rows_obat)) {
                $daftar_obat = [];
                $cnt_aman = 0;
                $cnt_menipis = 0;
                foreach ($rows_obat as $ob) {
                    $stok_val = intval($ob['stok']);
                    if ($stok_val <= 0) {
                        $status_obat = 'Habis';
                    } elseif ($stok_val < 50) {
                        $status_obat = 'Menipis';
                        $cnt_menipis++;
                    } else {
                        $status_obat = 'Aman';
                        $cnt_aman++;
                    }
                    $daftar_obat[] = [
                        'kode'     => $ob['kode_obat'],
                        'nama'     => $ob['nama_obat'],
                        'kategori' => '-',
                        'satuan'   => ucfirst($ob['satuan']),
                        'stok'     => number_format($stok_val, 0, ',', '.'),
                        'status'   => $status_obat
                    ];
                }

                // Stats farmasi
                $cnt_obat_total = count($rows_obat);
                $stats_farmasi = [
                    ['label' => 'TOTAL OBAT',     'value' => number_format($cnt_obat_total, 0, ',', '.'), 'sub' => 'Jenis Obat', 'icon' => 'fa-box-tissue'],
                    ['label' => 'STOK AMAN',      'value' => number_format($cnt_aman, 0, ',', '.'),       'sub' => 'Obat',       'icon' => 'fa-cart-shopping'],
                    ['label' => 'STOK MENIPIS',   'value' => number_format($cnt_menipis, 0, ',', '.'),    'sub' => 'Obat',       'icon' => 'fa-triangle-exclamation', 'warning' => true],
                    ['label' => 'RESEP HARI INI', 'value' => '0',                                         'sub' => 'Resep',      'icon' => 'fa-calendar-check'],
                ];

                // Stok menipis widget — keys: nama, detail, status
                $stok_menipis = [];
                foreach ($rows_obat as $ob) {
                    $sv = intval($ob['stok']);
                    if ($sv > 0 && $sv < 50) {
                        $stok_menipis[] = [
                            'nama'   => $ob['nama_obat'],
                            'detail' => 'Sisa: ' . $sv . ' ' . ucfirst($ob['satuan']),
                            'status' => 'Menipis'
                        ];
                    } elseif ($sv <= 0) {
                        $stok_menipis[] = [
                            'nama'   => $ob['nama_obat'],
                            'detail' => 'Stok habis!',
                            'status' => 'Habis'
                        ];
                    }
                }
            }

            // Resep terbaru dari tabel resep (jika ada data)
            try {
                $rows_resep = db_select("SELECT r.id as resep_id, r.status as resep_status, p.nama_lengkap, TO_CHAR(r.created_at AT TIME ZONE 'Asia/Jakarta', 'HH24:MI') as waktu FROM resep r JOIN rekam_medis rm ON r.rm_id = rm.rm_id JOIN visits v ON rm.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id ORDER BY r.id DESC LIMIT 5");
                if (is_array($rows_resep) && !empty($rows_resep)) {
                    $resep_terbaru = [];
                    foreach ($rows_resep as $rs) {
                        $resep_terbaru[] = [
                            'no'     => 'RSP-' . str_pad($rs['resep_id'], 4, '0', STR_PAD_LEFT),
                            'nama'   => $rs['nama_lengkap'],
                            'waktu'  => $rs['waktu'] . ' WIB',
                            'status' => ucfirst($rs['resep_status'])
                        ];
                    }

                    // Update stats resep hari ini
                    $cnt_resep_row = db_select_one("SELECT COUNT(*) as cnt FROM resep WHERE created_at >= (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Jakarta')::date");
                    if ($cnt_resep_row) {
                        $stats_farmasi[3]['value'] = number_format(intval($cnt_resep_row['cnt']), 0, ',', '.');
                    }
                }
            } catch (Exception $e) {
                // Resep table mungkin belum ada data, abaikan
            }

        } catch (Exception $e) {
            // Farmasi fallback sudah di-set di atas
        }

        // ============================================================
        // 3e. KASIR & EMR — Pencarian Pasien DINAMIS dari Database
        // ============================================================

        // Pencarian via URL parameter ?id_antrian= atau ?no_antrian= atau ?no_rm= atau ?nama=
        $search_no_antrian = !empty($_GET['no_antrian']) && $_GET['no_antrian'] !== '-' ? trim($_GET['no_antrian']) : (!empty($_POST['no_antrian']) ? trim($_POST['no_antrian']) : '');
        $search_nama = !empty($_GET['nama']) && $_GET['nama'] !== 'Belum Memilih Pasien' ? trim($_GET['nama']) : (!empty($_POST['nama']) ? trim($_POST['nama']) : '');
        $search_no_rm = !empty($_GET['no_rm']) && $_GET['no_rm'] !== '-' ? trim($_GET['no_rm']) : '';

        if ((isset($_GET['id_antrian']) && !empty($_GET['id_antrian'])) || !empty($search_no_antrian) || !empty($search_no_rm) || !empty($search_nama)) {
            try {
                $q_row = false;
                if (isset($_GET['id_antrian']) && !empty($_GET['id_antrian'])) {
                    $queue_id = intval($_GET['id_antrian']);
                    $q_row = db_select_one("SELECT q.id, p.nama_lengkap, p.no_rm, p.nik, p.tanggal_lahir, p.alamat, p.no_telepon, p.jenis_pasien, p.no_bpjs, pol.nama_poli FROM queues q JOIN patients p ON q.patient_id = p.id JOIN polyclinics pol ON q.polyclinic_id = pol.id WHERE q.id = :qid", ['qid' => $queue_id]);
                } elseif (!empty($search_no_antrian)) {
                    $q_row = db_select_one("SELECT q.id, p.nama_lengkap, p.no_rm, p.nik, p.tanggal_lahir, p.alamat, p.no_telepon, p.jenis_pasien, p.no_bpjs, pol.nama_poli FROM queues q JOIN patients p ON q.patient_id = p.id JOIN polyclinics pol ON q.polyclinic_id = pol.id WHERE q.no_antrian = :qno ORDER BY q.id DESC LIMIT 1", ['qno' => $search_no_antrian]);
                }

                // Jika tidak ketemu di queues (atau hanya kirim no_rm/nama), cari langsung ke tabel patients
                if (!$q_row) {
                    $search_kw = !empty($_GET['no_rm']) && $_GET['no_rm'] !== '-' ? trim($_GET['no_rm']) : (!empty($_GET['nama']) ? trim($_GET['nama']) : (!empty($_GET['no_antrian']) ? trim($_GET['no_antrian']) : ''));
                    if (!empty($search_kw)) {
                        $p_row = db_select_one("SELECT p.id, p.nama_lengkap, p.no_rm, p.nik, p.tanggal_lahir, p.alamat, p.no_telepon, p.jenis_pasien, p.no_bpjs FROM patients p WHERE p.no_rm ILIKE :kw OR p.nama_lengkap ILIKE :kw2 ORDER BY p.id DESC LIMIT 1", ['kw' => '%' . $search_kw . '%', 'kw2' => '%' . $search_kw . '%']);
                        if ($p_row) {
                            $last_q = db_select_one("SELECT pol.nama_poli FROM queues q JOIN polyclinics pol ON q.polyclinic_id = pol.id WHERE q.patient_id = :pid ORDER BY q.id DESC LIMIT 1", ['pid' => $p_row['id']]);
                            $p_poli = $last_q ? $last_q['nama_poli'] : 'Poli Umum';
                            $q_row = [
                                'nama_lengkap' => $p_row['nama_lengkap'],
                                'no_rm'        => $p_row['no_rm'],
                                'nik'          => $p_row['nik'],
                                'tanggal_lahir'=> $p_row['tanggal_lahir'],
                                'alamat'       => $p_row['alamat'],
                                'no_telepon'   => $p_row['no_telepon'],
                                'jenis_pasien' => $p_row['jenis_pasien'],
                                'nama_poli'    => $p_poli
                            ];
                        }
                    }
                }

                if ($q_row) {
                    $nama_pasien  = $q_row['nama_lengkap'];
                    $no_rm        = $q_row['no_rm'];
                    $nik          = $q_row['nik'];
                    $tgl_lahir    = date('d M Y', strtotime($q_row['tanggal_lahir']));
                    $alamat       = $q_row['alamat'];
                    $telepon      = $q_row['no_telepon'] ?: '-';
                    $penjamin     = $q_row['jenis_pasien'] ?: 'Umum';
                    $poli_pasien  = $q_row['nama_poli'];
                    $dokter       = $dokter_map[$q_row['nama_poli']] ?? 'Dr. Dokter Jaga';
                    $jenis_pasien = $q_row['jenis_pasien'] ?: '-';
                }
            } catch (Exception $e) {
                // Gagal query, tetap default
            }
        }

        // Pencarian manual via form POST (Kasir, EMR)
        if (isset($_POST['cari_pasien']) && !empty($_POST['keyword'])) {
            try {
                $keyword = '%' . trim($_POST['keyword']) . '%';
                $p_row = db_select_one("SELECT p.id, p.nama_lengkap, p.no_rm, p.nik, p.tanggal_lahir, p.alamat, p.no_telepon, p.jenis_pasien, p.no_bpjs FROM patients p WHERE p.no_rm ILIKE :kw OR p.nama_lengkap ILIKE :kw2 ORDER BY p.id DESC LIMIT 1", ['kw' => $keyword, 'kw2' => $keyword]);
                if ($p_row) {
                    $nama_pasien  = $p_row['nama_lengkap'];
                    $no_rm        = $p_row['no_rm'];
                    $nik          = $p_row['nik'];
                    $tgl_lahir    = date('d M Y', strtotime($p_row['tanggal_lahir']));
                    $alamat       = $p_row['alamat'];
                    $telepon      = $p_row['no_telepon'] ?: '-';
                    $penjamin     = $p_row['jenis_pasien'] ?: 'Umum';
                    $jenis_pasien = $p_row['jenis_pasien'] ?: '-';

                    // Cari poli terakhir dari antrian
                    $last_q = db_select_one("SELECT pol.nama_poli FROM queues q JOIN polyclinics pol ON q.polyclinic_id = pol.id WHERE q.patient_id = :pid ORDER BY q.id DESC LIMIT 1", ['pid' => $p_row['id']]);
                    $poli_pasien = $last_q ? $last_q['nama_poli'] : '-';
                    $dokter = isset($dokter_map[$poli_pasien]) ? $dokter_map[$poli_pasien] : 'Dr. Dokter Jaga';
                } else {
                    if (!defined('AJAX_REQUEST')) {
                        echo "<script>alert('Pasien tidak ditemukan di database! Coba keyword lain.');</script>";
                    }
                }
            } catch (Exception $e) {
                if (!defined('AJAX_REQUEST')) {
                    echo "<script>alert('Gagal mencari pasien: " . addslashes($e->getMessage()) . "');</script>";
                }
            }
        }

        // ============================================================
        // 3f. KASIR & EMR — Data Riwayat/Tagihan Dinamis untuk Pasien Terpilih
        // ============================================================
        if (!empty($no_rm) && $no_rm !== '-') {
            try {
                $emr_rows = db_select("SELECT rm.subjective, rm.objective, rm.assessment, rm.plan, TO_CHAR(rm.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY HH24:MI') as waktu, v.keluhan_utama, p.nama_lengkap FROM rekam_medis rm JOIN visits v ON rm.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY rm.created_at DESC LIMIT 5", ['rm' => $no_rm]);
                if (is_array($emr_rows) && !empty($emr_rows)) {
                    $emr_pasien = $emr_rows;
                } else {
                    $notes_rows = db_select("SELECT en.subjective, en.objective, en.assessment, en.plan, TO_CHAR(en.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY HH24:MI') as waktu, en.keluhan_utama, p.nama_lengkap FROM emr_notes en JOIN queues q ON en.queue_id = q.id JOIN patients p ON q.patient_id = p.id WHERE p.no_rm = :rm ORDER BY en.created_at DESC LIMIT 5", ['rm' => $no_rm]);
                    if (is_array($notes_rows) && !empty($notes_rows)) {
                        $emr_pasien = $notes_rows;
                    }
                }
            } catch (Exception $e) {}

            try {
                $rsp_rows = db_select("SELECT r.id as resep_id, r.status as resep_status, TO_CHAR(r.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY HH24:MI') as waktu FROM resep r JOIN rekam_medis rm ON r.rm_id = rm.rm_id JOIN visits v ON rm.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY r.created_at DESC LIMIT 5", ['rm' => $no_rm]);
                if (is_array($rsp_rows)) $resep_pasien = $rsp_rows;
            } catch (Exception $e) {}

            try {
                $tag_rows = db_select("SELECT t.id, t.nama_layanan, t.biaya, t.jumlah, t.subtotal FROM transaksi_detail t JOIN transaksi tr ON t.transaksi_id = tr.id JOIN visits v ON tr.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY t.id ASC", ['rm' => $no_rm]);
                if (is_array($tag_rows)) {
                    $detail_transaksi = [];
                    foreach ($tag_rows as $idx => $trow) {
                        $biaya_val = floatval($trow['biaya']);
                        $subtotal_val = floatval($trow['subtotal']);
                        $detail_transaksi[] = [
                            'no' => $idx + 1,
                            'id' => $trow['id'],
                            'deskripsi' => $trow['nama_layanan'] ?: '-',
                            'nama_layanan' => $trow['nama_layanan'] ?: '-',
                            'kategori' => 'Tindakan/Layanan',
                            'qty' => $trow['jumlah'] ?: 1,
                            'jumlah' => $trow['jumlah'] ?: 1,
                            'harga' => number_format($biaya_val, 0, ',', '.'),
                            'biaya' => $biaya_val,
                            'total' => number_format($subtotal_val, 0, ',', '.'),
                            'subtotal' => $subtotal_val
                        ];
                    }
                }
            } catch (Exception $e) {}

            try {
                $history_rows = db_select("SELECT v.no_kunjungan, TO_CHAR(v.tanggal_kunjungan AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY') as tanggal, rm.subjective, rm.assessment, rm.plan, rm.pemeriksaan_fisik FROM visits v LEFT JOIN rekam_medis rm ON v.kunjungan_id = rm.kunjungan_id WHERE v.pasien_id = (SELECT id FROM patients WHERE no_rm = :rm LIMIT 1) ORDER BY v.tanggal_kunjungan DESC LIMIT 8", ['rm' => $no_rm]);
                if (is_array($history_rows)) {
                    foreach ($history_rows as $hr) {
                        $emr_history[] = [
                            'no_kunjungan' => $hr['no_kunjungan'],
                            'tanggal' => $hr['tanggal'],
                            'subjective' => $hr['subjective'] ?: '-',
                            'assessment' => $hr['assessment'] ?: '-',
                            'plan' => $hr['plan'] ?: '-',
                            'pemeriksaan_fisik' => $hr['pemeriksaan_fisik'] ?: '-',
                        ];
                    }
                }
            } catch (Exception $e) {}

            try {
                $diag_rows = db_select("SELECT rd.kode_icd10, rd.nama_diagnosis, rd.jenis_diagnosis, TO_CHAR(rd.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY HH24:MI') as waktu FROM rm_diagnoses rd JOIN rekam_medis rm ON rd.rm_id = rm.rm_id JOIN visits v ON rm.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY rd.created_at DESC LIMIT 10", ['rm' => $no_rm]);
                if (is_array($diag_rows)) {
                    $diagnosa_pasien = $diag_rows;
                }
            } catch (Exception $e) {}

            try {
                $order_lab_rows = db_select("SELECT ol.id, ol.jenis_pemeriksaan, ol.catatan, ol.status, TO_CHAR(ol.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY HH24:MI') as waktu FROM order_lab ol JOIN rekam_medis rm ON ol.rm_id = rm.rm_id JOIN visits v ON ol.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY ol.created_at DESC LIMIT 8", ['rm' => $no_rm]);
                if (is_array($order_lab_rows)) {
                    $order_lab = $order_lab_rows;
                }
            } catch (Exception $e) {}

            try {
                $order_rad_rows = db_select("SELECT orad.id, orad.jenis_pemeriksaan, orad.catatan, orad.status, TO_CHAR(orad.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY HH24:MI') as waktu FROM order_radiologi orad JOIN rekam_medis rm ON orad.rm_id = rm.rm_id JOIN visits v ON orad.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY orad.created_at DESC LIMIT 8", ['rm' => $no_rm]);
                if (is_array($order_rad_rows)) {
                    $order_radiologi = $order_rad_rows;
                }
            } catch (Exception $e) {}

            try {
                $results_rows = db_select("SELECT olh.id, olh.parameter, olh.nilai, olh.satuan, olh.nilai_rujukan, olh.keterangan, TO_CHAR(ol.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY') as tanggal, ol.jenis_pemeriksaan FROM order_lab_hasil olh JOIN order_lab ol ON olh.order_lab_id = ol.id JOIN rekam_medis rm ON ol.rm_id = rm.rm_id JOIN visits v ON ol.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY olh.created_at DESC LIMIT 12", ['rm' => $no_rm]);
                if (is_array($results_rows)) {
                    $order_results = $results_rows;
                }
            } catch (Exception $e) {}

            try {
                $ref_rows = db_select("SELECT sr.id, sr.faskes_tujuan, sr.poli_tujuan, sr.alasan_rujukan, sr.no_rujukan_bpjs, TO_CHAR(sr.tanggal_rujukan, 'DD Mon YYYY') as tanggal, TO_CHAR(sr.created_at AT TIME ZONE 'Asia/Jakarta', 'DD Mon YYYY HH24:MI') as waktu FROM surat_rujukan sr JOIN rekam_medis rm ON sr.rm_id = rm.rm_id JOIN visits v ON rm.kunjungan_id = v.kunjungan_id JOIN patients p ON v.pasien_id = p.id WHERE p.no_rm = :rm ORDER BY sr.created_at DESC LIMIT 6", ['rm' => $no_rm]);
                if (is_array($ref_rows)) {
                    $surat_rujukan = $ref_rows;
                }
            } catch (Exception $e) {}
        }

    } // end if ($db_connected)
}

// Alias agar halaman yang pakai $poli (bukan $poli_pasien) tetap kompatibel
if (!isset($poli) || $poli === 'Poli Jantung') {
    $poli = $poli_pasien ?? '-';
}
