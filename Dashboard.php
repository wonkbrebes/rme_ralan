<?php
// Data navigasi disamakan persis dengan halaman antrian
$nav_items = [
    ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'active' => true],
    ['label' => 'Antrian',     'icon' => 'fa-clipboard-list'],
    ['label' => 'Pendaftaran', 'icon' => 'fa-user-plus'],
    ['label' => 'EMR Dokter',  'icon' => 'fa-file-medical'],
    ['label' => 'Farmasi',     'icon' => 'fa-prescription-bottle-medical'],
    ['label' => 'Kasir',       'icon' => 'fa-credit-card'],
];

// Simulasi data statistik atas (sesuai gambar mockup)
$stats_dashboard = [
    ['label' => 'Total Pasien Hari Ini', 'value' => '128', 'trend' => '+ 12%', 'trend_type' => 'up',   'icon' => 'fa-users'],
    ['label' => 'Antrian Aktif',         'value' => '14',  'trend' => '- 5%',  'trend_type' => 'down', 'icon' => 'fa-hourglass-half'],
    ['label' => 'Pasien Rawat Inap',     'value' => '42',  'trend' => '+ 3%',  'trend_type' => 'up',   'icon' => 'fa-bed'],
    ['label' => 'Pasien IGD',            'value' => '9',   'trend' => '0%',    'trend_type' => 'neutral', 'icon' => 'fa-truck-medical'],
];

// Data tabel Antrian Terkini (sesuai gambar mockup)
$antrian_terkini = [
    ['no' => 'A-024', 'nama' => 'Budiman Setiawan', 'poli' => 'Poli Jantung', 'dokter' => 'Dr. Sarah Wijaya, Sp.JP', 'status' => 'Dipanggil'],
    ['no' => 'A-025', 'nama' => 'Siti Rahayu',      'poli' => 'Poli Umum',    'dokter' => 'Dr. Anton Subekti',       'status' => 'Menunggu'],
    ['no' => 'A-021', 'nama' => 'Lestari Putri',    'poli' => 'Poli Anak',    'dokter' => 'Dr. Budi Santoso, Sp.A',   'status' => 'Selesai'],
    ['no' => 'A-026', 'nama' => 'Ahmad Fauzi',      'poli' => 'Poli Mata',    'dokter' => 'Dr. Yeni Amalia, Sp.M',    'status' => 'Menunggu'],
    ['no' => 'A-022', 'nama' => 'Rizky Ramadhan',   'poli' => 'Poli Gigi',    'dokter' => 'Drg. Melati Sukma',       'status' => 'Selesai'],
];

// Data chart lingkaran Distribusi Kunjungan
$distribusi = [
    ['label' => 'Rawat Jalan', 'jumlah' => 72, 'persen' => '56%', 'color' => '#2e7d32'],
    ['label' => 'Rawat Inap',  'jumlah' => 31, 'persen' => '24%', 'color' => '#4caf50'],
    ['label' => 'IGD',         'jumlah' => 25, 'persen' => '20%', 'color' => '#a5d6a7'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – SIMRS Clinical Precision</title>
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
            --font:          'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            font-family: var(--font);
            background: var(--gray-100);
            color: var(--gray-800);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR (PERSIS SAMA) ── */
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
            text-decoration: none;
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

        .sidebar-footer { padding: 12px 0; border-top: 1px solid var(--gray-200); }
        .sidebar-footer a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 18px; font-size: 13px;
            color: var(--gray-600); text-decoration: none;
            transition: background .15s;
        }
        .sidebar-footer a:hover { background: var(--green-xpale); color: var(--green-primary); }

        /* ── MAIN CONTENT AREA ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ── TOPBAR (PERSIS SAMA) ── */
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
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--green-pale);
            display: flex; align-items: center; justify-content: center;
            color: var(--green-primary);
        }
        .user-text small { display: block; font-size: 10px; color: var(--gray-400); }
        .user-text strong { font-size: 13px; }

        .content { padding: 24px 28px; display: flex; flex-direction: column; gap: 22px; }

        /* ── STAT CARDS DENGAN TREND BADGE ── */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex; justify-content: space-between; align-items: flex-start;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .stat-left { display: flex; flex-direction: column; gap: 12px; }
        .stat-icon {
            width: 44px; height: 44px;
            background: var(--green-pale);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .stat-icon i { font-size: 18px; color: var(--green-primary); }
        .stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: var(--gray-400); font-weight: 600; }
        .stat-value { font-size: 32px; font-weight: 700; color: var(--gray-800); line-height: 1; }
        
        /* Trend badge style */
        .trend-badge {
            font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 6px;
            display: flex; align-items: center; gap: 4px;
        }
        .trend-up { background: #dcfce7; color: #15803d; }
        .trend-down { background: #fee2e2; color: #b91c1c; }
        .trend-neutral { background: var(--gray-100); color: var(--gray-600); }

        /* ── DASHBOARD CHARTS GRID ── */
        .chart-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; }
        .card {
            background: var(--white); border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden;
        }
        .card-header {
            padding: 18px 20px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--gray-200);
        }
        .card-header h2 { font-size: 15px; font-weight: 700; color: var(--gray-800); }
        
        .btn-group { display: flex; gap: 6px; }
        .btn-secondary {
            padding: 6px 12px; border: 1px solid var(--gray-200); border-radius: 6px;
            background: var(--white); font-size: 12px; font-weight: 600; color: var(--gray-600); cursor: pointer;
        }
        .btn-primary-sm {
            padding: 6px 12px; border: none; border-radius: 6px;
            background: var(--green-primary); font-size: 12px; font-weight: 600; color: var(--white); cursor: pointer;
        }

        /* Mockup area untuk grafik garis Kunjungan */
        .line-chart-placeholder {
            padding: 24px; position: relative; height: 260px;
            display: flex; align-items: flex-end; justify-content: space-between;
        }
        /* Background grid line simulasi */
        .chart-bg-lines {
            position: absolute; left: 24px; right: 24px; top: 24px; bottom: 50px;
            display: flex; flex-direction: column; justify-content: space-between;
            pointer-events: none;
        }
        .chart-bg-lines div { border-bottom: 1px dashed var(--gray-200); width: 100%; height: 0; }
        
        /* CSS Svg untuk render line path persis gambar */
        .svg-chart-container {
            position: absolute; left: 24px; right: 24px; top: 24px; bottom: 50px;
        }

        /* Lingkaran Donut Chart Simulasi */
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
        .donut-center strong { font-size: 24px; color: var(--gray-800); font-weight: 700; line-index: 1; }
        .donut-center span { font-size: 10px; color: var(--gray-400); font-weight: 600; letter-spacing: .5px; }

        .donut-legend { width: 100%; display: flex; flex-direction: column; gap: 8px; font-size: 12px; }
        .legend-item { display: flex; justify-content: space-between; align-items: center; }
        .legend-label { display: flex; align-items: center; gap: 8px; color: var(--gray-600); }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }

        /* ── TABLE ANTRIAN TERKINI ── */
        .search-container { position: relative; width: 240px; }
        .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 13px; }
        .search-input-dash {
            width: 100%; padding: 7px 12px 7px 34px; border: 1px solid var(--gray-200); border-radius: 7px;
            font-size: 13px; outline: none; background: var(--white);
        }
        .search-input-dash:focus { border-color: var(--green-light); }

        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px 20px; text-align: left; font-size: 12px;
            text-transform: uppercase; letter-spacing: .4px; color: var(--gray-400); font-weight: 600;
            background: var(--gray-50); border-bottom: 1px solid var(--gray-200);
        }
        td { padding: 14px 20px; font-size: 13px; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--gray-50); }

        .no-antrian { font-weight: 700; color: var(--green-primary); }
        
        /* Badge Status */
        .badge-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-dipanggil { background: #dcfce7; color: #15803d; }
        .badge-menunggu  { background: #fef9c3; color: #a16207; }
        .badge-selesai   { background: #e0e7ff; color: #3730a3; }

        .btn-detail {
            padding: 5px 14px; border: 1px solid var(--green-primary); border-radius: 6px;
            background: var(--white); color: var(--green-primary); font-size: 12px; cursor: pointer; transition: background .15s;
        }
        .btn-detail:hover { background: var(--green-pale); }

        .table-footer {
            padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;
            border-top: 1px solid var(--gray-200); background: var(--white);
        }
        .table-footer p { font-size: 12px; color: var(--gray-400); }
        .pagination-nav { display: flex; gap: 6px; }
        .nav-btn {
            width: 28px; height: 28px; border: 1px solid var(--gray-200); background: var(--white);
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            color: var(--gray-600); font-size: 11px; cursor: pointer;
        }
        .nav-btn:hover { background: var(--gray-50); }
        
        footer { font-size: 11px; color: var(--gray-400); text-align: center; margin-top: 10px; padding-bottom: 10px;}
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
            <a href="#" <?= !empty($item['active']) ? 'class="active"' : '' ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <?= htmlspecialchars($item['label']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <button class="btn-quick">
        <i class="fa-solid fa-plus"></i> Quick Admission
    </button>

    <div class="sidebar-footer">
        <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="#"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</aside>

<div class="main">

    <header class="topbar">
        <div class="topbar-left">
            <h1>Dashboard</h1>
            <p class="breadcrumb">Front Office &rsaquo; <span>Dashboard</span></p>
        </div>
        <div class="topbar-right">
            <button class="icon-btn"><i class="fa-solid fa-bell"></i></button>
            <button class="icon-btn"><i class="fa-solid fa-gear"></i></button>
            <div class="user-info">
                <div class="user-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="user-text">
                    <strong>Dr. Hendrawan</strong>
                    <small>Front Office Supervisor</small>
                </div>
            </div>
        </div>
    </header>

    <main class="content">

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
                        <button class="btn-secondary">Export PDF</button>
                        <button class="btn-primary-sm">Details</button>
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
                        <td><button class="btn-detail">Detail</button></td>
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

        <footer>
            &copy; 2026 SIMRS. All rights reserved.
        </footer>
    </main>
</div>

</body>
</html>