<?php
// Simulasi data antrian (dalam implementasi nyata, ini dari database)
$stats = [
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
if (file_exists(__DIR__ . '/db_helper.php')) {
    require_once __DIR__ . '/db_helper.php';
}
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
            $stats['total'] = count($antrian);
            $stats['dilayani'] = 1;
            $stats['selesai'] = max(0, $stats['total'] - 1);
            $sedang_dilayani = $antrian[0];
        }
    } catch (Exception $e) {
        // Fallback ke data simulasi jika query gagal
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

// PERBAIKAN: Menggunakan class icon Font Awesome v6 yang valid
$nav_items = [
    ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'page' => 'dashboard'],
    ['label' => 'Antrian',     'icon' => 'fa-clipboard-list', 'page' => 'antrian', 'active' => true],
    ['label' => 'Pendaftaran', 'icon' => 'fa-user-plus',      'page' => 'pendaftaran'],
    ['label' => 'EMR Dokter',  'icon' => 'fa-file-medical',   'page' => 'emr_dokter'],
    ['label' => 'Farmasi',     'icon' => 'fa-prescription-bottle-medical', 'page' => 'farmasi'],
    ['label' => 'Kasir',       'icon' => 'fa-credit-card',    'page' => 'kasir'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrian Pasien – SIMRS Clinical Precision</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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

        /* ── SIDEBAR ── */
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

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ── TOPBAR ── */
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
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-avatar i { font-size: 18px; color: var(--green-primary); }
        .user-text small { display: block; font-size: 10px; color: var(--gray-400); }
        .user-text strong { font-size: 13px; }

        /* ── CONTENT ── */
        .content { padding: 24px 28px; display: flex; flex-direction: column; gap: 22px; }

        /* ── STAT CARDS ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .stat-icon {
            width: 48px; height: 48px;
            background: var(--green-pale);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon i { font-size: 20px; color: var(--green-primary); }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-400); margin-bottom: 4px; }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--gray-800); line-height: 1; }
        .stat-unit  { font-size: 12px; color: var(--gray-400); margin-top: 3px; }

        /* ── LOWER GRID ── */
        .lower-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }

        /* ── TABLE CARD ── */
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

        .table-filters {
            padding: 14px 20px;
            display: flex; gap: 10px; align-items: center;
            border-bottom: 1px solid var(--gray-200);
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
        .search-wrap { position: relative; flex: 1; }
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
        .badge-dilayani { background: #dcfce7; color: #15803d; }
        .badge-menunggu { background: #fef9c3; color: #a16207; }
        .badge-selesai  { background: #e0e7ff; color: #3730a3; }

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

        /* ── RIGHT COLUMN ── */
        .right-col { display: flex; flex-direction: column; gap: 16px; }

        /* ── SEDANG DILAYANI CARD ── */
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

        /* ── STATUS POLI ── */
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
        <?php foreach ($nav_items as $item): ?>
        <li>
            <a href="?page=<?= $item['page'] ?>" <?= !empty($item['active']) ? 'class="active"' : '' ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <?= htmlspecialchars($item['label']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <a href="?page=pendaftaran" class="btn-quick">
        <i class="fa-solid fa-plus"></i> Quick Admission
    </a>

    <div class="sidebar-footer">
        <a href="?page=settings"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="?page=logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</aside>

<div class="main">

    <header class="topbar">
        <div class="topbar-left">
            <h1>Antrian Pasien</h1>
            <p class="breadcrumb">Front Office &rsaquo; <span>Antrian Pasien</span></p>
        </div>
        <div class="topbar-right">
            <button class="icon-btn">
                <i class="fa-solid fa-bell"></i>
                <span class="badge">3</span>
            </button>
            <button class="icon-btn">
                <i class="fa-solid fa-gear"></i>
            </button>
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

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="stat-label">Total Antrian Hari Ini</div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-unit">Pasien</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-label">Sedang Dilayani</div>
                    <div class="stat-value"><?= $stats['dilayani'] ?></div>
                    <div class="stat-unit">Pasien</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <div class="stat-label">Rata-rata Waktu Tunggu</div>
                    <div class="stat-value"><?= $stats['rata_tunggu'] ?></div>
                    <div class="stat-unit">Menit</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="stat-label">Selesai Hari Ini</div>
                    <div class="stat-value"><?= $stats['selesai'] ?></div>
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
                    <p>Menampilkan 1 - <?= count($antrian) ?> dari <?= $stats['total'] ?> antrian</p>
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

            </div></div></main>
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
</body>
</html>