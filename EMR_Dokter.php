<?php
// Menu navigasi disamakan 100% persis dengan halaman antrian awal
$nav_items = [
    ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'page' => 'dashboard'],
    ['label' => 'Antrian',     'icon' => 'fa-clipboard-list', 'page' => 'antrian'],
    ['label' => 'Pendaftaran', 'icon' => 'fa-user-plus',      'page' => 'pendaftaran'],
    ['label' => 'EMR Dokter',  'icon' => 'fa-file-medical',   'page' => 'emr_dokter', 'active' => true],
    ['label' => 'Farmasi',     'icon' => 'fa-prescription-bottle-medical', 'page' => 'farmasi'],
    ['label' => 'Kasir',       'icon' => 'fa-credit-card',    'page' => 'kasir'],
];

// Data Tab Navigasi Internal EMR Dokter
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMR Dokter – SIMRS Clinical Precision</title>
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
            --gray-300:      #d1d5db;
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

        /* ── SIDEBAR (PERSIS SAMA DENGAN ANTRIAN) ── */
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
            margin: 10px 14px; padding: 11px 14px;
            background: var(--green-primary); color: var(--white);
            border: none; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer;
            text-decoration: none; transition: background .15s;
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

        /* ── MAIN AREA ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--white); border-bottom: 1px solid var(--gray-200);
            padding: 0 28px; height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-left h1 { font-size: 20px; font-weight: 700; color: var(--green-primary); }
        .breadcrumb { font-size: 12px; color: var(--gray-400); margin-top: 1px; }
        .breadcrumb span { color: var(--gray-600); }
        .breadcrumb .active-crumb { color: var(--green-primary); }

        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .icon-btn {
            position: relative; width: 36px; height: 36px;
            background: var(--gray-100); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: none; color: var(--gray-600); font-size: 15px;
        }
        .badge {
            position: absolute; top: -2px; right: -2px;
            background: var(--green-primary); color: var(--white);
            font-size: 9px; font-weight: 700; width: 16px; height: 16px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
        }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--green-pale); display: flex; align-items: center; justify-content: center;
            color: var(--green-primary);
        }
        .user-text small { display: block; font-size: 10px; color: var(--gray-400); }
        .user-text strong { font-size: 13px; }

        /* ── CONTENT LAYOUT GRID ── */
        .content { padding: 24px 28px; display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }

        /* ── PASIEN PROFILE HEADER CARD ── */
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

        /* ── INTERACTIVE TAB NAV BAR ── */
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

        /* TAB PANELS (SISTEM KLIK) */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── CARDS & BLOCKS ── */
        .card { background: var(--white); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden; margin-bottom: 16px; }
        .card-header { padding: 14px 18px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: 14px; font-weight: 700; color: var(--gray-800); }
        .header-meta { font-size: 11px; color: var(--gray-400); }

        /* Clinical Note Structure */
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

        /* Triple Card Grid di Bagian Bawah */
        .triple-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .med-list, .order-list { display: flex; flex-direction: column; gap: 10px; padding: 14px; }
        .med-item { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 10px; border-bottom: 1px dashed var(--gray-200); }
        .med-item:last-child { border-bottom: none; padding-bottom: 0; }
        .med-num { font-size: 11px; font-weight: 700; color: var(--white); background: var(--green-light); width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: 2px; }
        .med-details { flex: 1; margin-left: 10px; display: flex; flex-direction: column; gap: 2px; }
        .med-name { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .med-rule { font-size: 11px; color: var(--gray-400); }
        .med-qty { font-size: 11px; font-weight: 600; color: var(--gray-600); text-align: right; }

        /* Order & Hasil Status Badges */
        .mini-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
        .badge-warning { background: #fef9c3; color: #a16207; }
        .badge-normal { background: #e0f2fe; color: #0369a1; }
        
        .btn-card-action {
            width: 100%; border: none; border-top: 1px solid var(--gray-200); background: var(--white);
            padding: 10px; text-align: center; color: var(--green-primary); font-size: 12px; font-weight: 600;
            cursor: pointer; transition: background .15s;
        }
        .btn-card-action:hover { background: var(--green-xpale); }

        /* ── RIGHT COLUMN (WIDGETS) ── */
        .right-column { display: flex; flex-direction: column; gap: 16px; }
        .widget-title {
            display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700;
            color: var(--gray-800); padding: 14px 16px; border-bottom: 1px solid var(--gray-200);
        }
        .widget-title span { display: flex; align-items: center; gap: 8px; }
        .widget-title i { color: var(--green-primary); font-size: 14px; }
        .widget-link { font-size: 11px; color: var(--green-primary); text-decoration: none; font-weight: 600; }

        .quick-action-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 14px 16px; }
        .btn-qa {
            display: flex; align-items: center; gap: 8px; padding: 10px;
            border: 1px solid var(--gray-200); border-radius: 8px; background: var(--white);
            font-size: 11px; font-weight: 600; color: var(--gray-600); cursor: pointer;
        }
        .btn-qa:hover { background: var(--green-xpale); border-color: var(--green-light); color: var(--green-primary); }
        .btn-qa i { color: var(--green-primary); font-size: 13px; }

        /* History Widget Card */
        .history-box { padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; font-size: 12px; }
        .history-date-row { display: flex; justify-content: space-between; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
        .history-item-row { display: flex; margin-bottom: 4px; }
        .history-label { width: 80px; color: var(--gray-400); font-weight: 500; }
        .history-val { flex: 1; color: var(--gray-600); }

        /* Placeholder view untuk tab kosong */
        .empty-tab-view { padding: 40px; text-align: center; color: var(--gray-400); font-size: 13px; }
        .empty-tab-view i { font-size: 32px; color: var(--gray-300); margin-bottom: 10px; display: block; }

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
        <?php foreach ($nav_items as $item): ?>
        <li>
            <a href="?page=<?= isset($item['page']) ? $item['page'] : 'dashboard' ?>" <?= !empty($item['active']) ? 'class="active"' : '' ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <?= htmlspecialchars($item['label']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <button class="btn-quick" onclick="window.location.href='?page=pendaftaran'">
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
            <h1>EMR Dokter</h1>
            <p class="breadcrumb">Front Office &rsaquo; EMR Dokter &rsaquo; <span class="active-crumb">Detail Pasien</span></p>
        </div>
        <div class="topbar-right">
            <button class="icon-btn">
                <i class="fa-solid fa-bell"></i>
                <span class="badge">3</span>
            </button>
            <button class="icon-btn"><i class="fa-solid fa-gear"></i></button>
            <div class="user-info">
                <div class="user-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="user-text">
                    <strong>Dr. Hendrawan</strong>
                    <small>Dokter Umum</small>
                </div>
            </div>
        </div>
    </header>

    <main class="content">

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
                        <button class="btn-card-action">Lihat Resep</button>
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
                        <button class="btn-card-action" style="margin-top:22px;">Lihat Order</button>
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
                        <button class="btn-card-action" style="margin-top:42px;">Lihat Hasil</button>
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
                    <button class="btn-qa"><i class="fa-solid fa-file-lines"></i> Clinical Note</button>
                    <button class="btn-qa"><i class="fa-solid fa-receipt"></i> Resep Elektronik</button>
                    <button class="btn-qa"><i class="fa-solid fa-vial"></i> Order Lab</button>
                    <button class="btn-qa"><i class="fa-solid fa-x-ray"></i> Order Radiologi</button>
                    <button class="btn-qa"><i class="fa-solid fa-envelope-open-text"></i> Surat Keterangan</button>
                    <button class="btn-qa"><i class="fa-solid fa-copy"></i> Template Note</button>
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

        </div></main>
</div>

<script>
function switchTab(event, tabId) {
    // 1. Sembunyikan semua tab panel content
    const panels = document.querySelectorAll('.tab-panel');
    panels.forEach(panel => panel.classList.remove('active'));

    // 2. Hilangkan status class active dari semua tombol tab
    const tabs = document.querySelectorAll('.emr-tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));

    // 3. Tampilkan panel yang sedang dipilih dan set tombol jadi active
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>

</body>
</html>