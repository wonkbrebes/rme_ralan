<?php
try {
    // Memuat koneksi database & sumber data terpusat
    require_once __DIR__ . '/config.php';
    if (isset($_GET['page']) && $_GET['page'] !== 'emr_dokter' && $_GET['page'] !== 'emr') {
        header('Location: index.php?page=' . urlencode($_GET['page']));
        exit;
    }
    require_once __DIR__ . '/simrs_data.php';

    // Menyesuaikan active nav untuk EMR Dokter
    foreach ($nav_items as &$item) {
        if ($item['page'] === 'emr_dokter') {
            $item['active'] = true;
        }
    }
    unset($item);
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
        .emr-grid-container { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; }
        .left-column-panels { width: 100%; min-width: 0; }
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
        .triple-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
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
            .chart-grid, .lower-grid, .pendaftaran-grid, .triple-grid, .poli-grid, .emr-grid-container { grid-template-columns: 1fr; gap: 16px; }
            .right-col, .right-column { order: -1; }
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
            <a href="<?= $item['url'] ?? ('index.php?page=' . $item['page']) ?>" <?= !empty($item['active']) ? 'class="active"' : '' ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <?= htmlspecialchars($item['label']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <button class="btn-quick" onclick="window.location.href='index.php?page=pendaftaran'">
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
            <div style="display:flex; align-items:center; gap:12px;">
                <a href="index.php?page=antrian" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:#e0f2fe; color:#0369a1; border-radius:8px; font-weight:600; text-decoration:none; font-size:13px;" title="Kembali ke Daftar Antrian"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
                <h1>EMR Dokter</h1>
            </div>
            <p class="breadcrumb">Front Office &rsaquo; EMR Dokter &rsaquo; <span class="active-crumb">Detail Pasien</span></p>
        </div>
        <div class="topbar-right">
            <button class="icon-btn">
                <i class="fa-solid fa-bell"></i>
                <span class="badge">3</span>
            </button>
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
        <?php if (!empty($db_conn_error)): ?>
        <div style="background: #fef3c7; color: #92400e; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f59e0b; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
            <span><strong>Mode Offline / Fallback:</strong> <?= htmlspecialchars($db_conn_error) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($msg_success)): ?>
        <div style="background: #dcfce7; color: #166534; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #22c55e; font-weight: 600; display: flex; align-items: center; gap: 10px;">
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

        <section class="patient-card">
            <div class="patient-left">
                <div class="patient-avatar" style="background: #e2e8f0; display:flex; align-items:center; justify-content:center; font-size:32px; color:#94a3b8;"><i class="fa-solid fa-user"></i></div>
                <div class="patient-meta">
                    <div class="patient-name-box">
                        <h2><?= htmlspecialchars($nama_pasien) ?></h2>
                        <span class="gender-badge"><?= htmlspecialchars($jenis_pasien !== '-' ? $jenis_pasien : 'Umum') ?></span>
                    </div>
                    <div class="info-grid-inline">
                        <span>No. RM / Antrian: <strong><?= htmlspecialchars($no_rm) ?></strong></span>
                        <span>Tanggal Lahir: <strong><?= htmlspecialchars($tgl_lahir) ?></strong></span>
                        <span>No. Identitas: <strong><?= htmlspecialchars($nik) ?></strong></span>
                    </div>
                    <div class="address-box">
                        <span>Alamat: <strong><?= htmlspecialchars($alamat) ?></strong></span>
                        <div style="display:flex; gap:20px; margin-top:2px;">
                            <span>Telepon: <strong><?= htmlspecialchars($telepon) ?></strong></span>
                            <span>Penjamin: <strong><?= htmlspecialchars($penjamin) ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="patient-right">
                <div class="vitals-box <?= $no_rm !== '-' ? 'alert-vitals' : '' ?>">
                    <span class="vitals-label alert-text"><i class="fa-solid fa-circle-exclamation"></i> Alergi</span>
                    <span class="vitals-val" style="color:#b91c1c; font-size:12px;"><?= $no_rm !== '-' ? 'Tidak Ada Alergi Tercatat' : '-' ?></span>
                </div>
                <div class="vitals-box">
                    <span class="vitals-label">Gol. Darah</span>
                    <span class="vitals-val" style="font-size:15px; color:var(--gray-800);"><?= $no_rm !== '-' ? '-' : '-' ?></span>
                </div>
                <div class="vitals-box">
                    <span class="vitals-label">Berat Badan</span>
                    <span class="vitals-val"><?= $no_rm !== '-' ? '-' : '-' ?></span>
                    <span class="vitals-sub">Belum diperiksa</span>
                </div>
                <div class="vitals-box">
                    <span class="vitals-label">Tinggi Badan</span>
                    <span class="vitals-val"><?= $no_rm !== '-' ? '-' : '-' ?></span>
                    <span class="vitals-sub">Belum diperiksa</span>
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

        <div class="emr-grid-container">
        <div class="left-column-panels">
            <div id="ringkasan" class="tab-panel active">
                <?php if (!empty($emr_pasien)): ?>
                <?php foreach ($emr_pasien as $idx => $rm_item): ?>
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header">
                        <h3>Clinical Note (SOAP)</h3>
                        <span class="header-meta"><?= htmlspecialchars($rm_item['waktu'] ?: '-') ?> &bull; <?= htmlspecialchars($rm_item['nama_lengkap'] ?? $nama_pasien) ?></span>
                    </div>
                    <div class="note-body">
                        <div class="note-row">
                            <div class="note-letter">S</div>
                            <div class="note-content">
                                <div class="note-title">Subjective</div>
                                <p><?= nl2br(htmlspecialchars($rm_item['subjective'] ?: ($rm_item['keluhan_utama'] ?: '-'))) ?></p>
                            </div>
                        </div>
                        <div class="note-row">
                            <div class="note-letter">O</div>
                            <div class="note-content">
                                <div class="note-title">Objective</div>
                                <p><?= nl2br(htmlspecialchars($rm_item['objective'] ?: '-')) ?></p>
                            </div>
                        </div>
                        <div class="note-row">
                            <div class="note-letter">A</div>
                            <div class="note-content">
                                <div class="note-title">Assessment</div>
                                <p><strong>Diagnosis Kerja:</strong> <?= nl2br(htmlspecialchars($rm_item['assessment'] ?: '-')) ?></p>
                            </div>
                        </div>
                        <div class="note-row">
                            <div class="note-letter">P</div>
                            <div class="note-content">
                                <div class="note-title">Plan</div>
                                <p><?= nl2br(htmlspecialchars($rm_item['plan'] ?: '-')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="card" style="margin-bottom:16px;">
                    <div style="text-align:center; padding:45px 20px; color:var(--gray-400);">
                        <i class="fa-solid fa-folder-open" style="font-size:38px; margin-bottom:12px; color:var(--gray-300);"></i>
                        <p style="font-weight:600; font-size:14px; color:var(--gray-600);">Belum ada catatan klinis (SOAP) di database untuk pasien ini.</p>
                        <p style="font-size:12px; margin-top:4px;">Silakan pilih pasien dari tabel Antrian dan input form pemeriksaan/SOAP untuk menyimpan rekam medis real-time ke Supabase.</p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="triple-grid">
                    <div class="card">
                        <div class="card-header"><h3>Resep Terbaru</h3></div>
                        <div class="med-list">
                            <?php if (!empty($resep_pasien)): ?>
                            <?php foreach ($resep_pasien as $ri => $rsp): ?>
                            <div class="med-item">
                                <span class="med-num"><?= $ri + 1 ?></span>
                                <div class="med-details">
                                    <span class="med-name">Resep #<?= htmlspecialchars($rsp['resep_id']) ?></span>
                                    <span class="med-rule"><?= htmlspecialchars($rsp['waktu']) ?></span>
                                </div>
                                <span class="mini-badge badge-normal"><?= htmlspecialchars($rsp['resep_status']) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <p style="text-align:center; color:var(--gray-400); font-size:12px; padding:20px 0;">Belum ada resep obat di database untuk pasien ini</p>
                            <?php endif; ?>
                        </div>
                        <button class="btn-card-action" onclick="switchTab(event, 'terapi_obat')">Lihat Resep</button>
                    </div>

                    <div class="card">
                        <div class="card-header"><h3>Order Terbaru</h3></div>
                        <div class="order-list" style="padding:20px; text-align:center;">
                            <p style="color:var(--gray-400); font-size:12px;">Belum ada order lab / radiologi di database</p>
                        </div>
                        <button class="btn-card-action" style="margin-top:22px;" onclick="switchTab(event, 'order')">Lihat Order</button>
                    </div>

                    <div class="card">
                        <div class="card-header"><h3>Hasil Pemeriksaan Terbaru</h3></div>
                        <div class="order-list" style="padding:20px; text-align:center;">
                            <p style="color:var(--gray-400); font-size:12px;">Belum ada hasil pemeriksaan di database</p>
                        </div>
                        <button class="btn-card-action" style="margin-top:42px;" onclick="switchTab(event, 'hasil_pemeriksaan')">Lihat Hasil</button>
                    </div>
                </div>
            </div>

            <div id="riwayat_kunjungan" class="tab-panel">
                <div class="card">
                    <div class="card-header"><h3>Riwayat Kunjungan & Pemeriksaan Pasien</h3></div>
                    <?php if (!empty($riwayat_pemeriksaan_lengkap)): ?>
                    <div style="padding:16px; display:grid; gap:14px;">
                        <?php foreach ($riwayat_pemeriksaan_lengkap as $riw): ?>
                        <div style="border:1px solid var(--gray-200); border-radius:10px; padding:14px; background:var(--white);">
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px; border-bottom:1px solid var(--gray-100); padding-bottom:8px;">
                                <strong><i class="fa-solid fa-calendar-check" style="color:var(--green-primary);"></i> <?= htmlspecialchars($riw['tanggal'] ?: '-') ?></strong>
                                <span class="gender-badge" style="background:#e0f2fe; color:#0369a1;"><?= htmlspecialchars($riw['nama_poli'] ?: 'Poli Umum') ?> &bull; <?= htmlspecialchars($riw['nama_dokter'] ?: 'Dokter') ?></span>
                            </div>
                            <div style="font-size:13px; margin-bottom:6px;"><strong>Keluhan:</strong> <?= htmlspecialchars($riw['subjective'] ?: ($riw['keluhan_utama'] ?: '-')) ?></div>
                            <div style="font-size:13px; margin-bottom:6px;"><strong>Pemeriksaan Fisik / Objektif:</strong> <?= htmlspecialchars($riw['pemeriksaan_fisik'] ?: ($riw['objective'] ?: '-')) ?></div>
                            <div style="font-size:13px; margin-bottom:6px; color:#15803d;"><strong>Diagnosa:</strong> <?= htmlspecialchars($riw['daftar_diagnosa'] ?: ($riw['assessment'] ?: '-')) ?></div>
                            <div style="font-size:13px; color:#4b5563;"><strong>Resep / Terapi:</strong> <?= htmlspecialchars($riw['daftar_resep'] ?: ($riw['plan'] ?: '-')) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-tab-view"><i class="fa-solid fa-clock-rotate-left"></i>Belum ada riwayat kunjungan terdahulu untuk pasien ini.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="pemeriksaan" class="tab-panel">
                <div class="card">
                    <div class="card-header"><h3>Form Catatan Pemeriksaan Fisik & Klinis</h3></div>
                    <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                        <input type="hidden" name="action" value="simpan_emr">
                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                        <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Keluhan Utama (Subjective)</label><textarea name="subjective" rows="3" placeholder="Keluhan pasien saat ini" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></textarea></div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">Tekanan Darah</label><input name="pemeriksaan_tekanan_darah" type="text" placeholder="120/80 mmHg" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></div>
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">Berat Badan (kg)</label><input name="pemeriksaan_bb" type="text" placeholder="kg" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></div>
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">Tinggi Badan (cm)</label><input name="pemeriksaan_tb" type="text" placeholder="cm" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></div>
                        </div>
                        <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Pemeriksaan Fisik & Objektif</label><textarea name="pemeriksaan_fisik" rows="4" placeholder="Temuan pemeriksaan fisik vital" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></textarea></div>
                        <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Assessment / Diagnosis Kerja</label><textarea name="assessment" rows="3" placeholder="Hasil penilaian klinis" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></textarea></div>
                        <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Plan / Rencana Tindak Lanjut</label><textarea name="plan" rows="3" placeholder="Rencana penatalaksanaan klinis" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></textarea></div>
                        <button type="submit" class="btn-submit" style="background:var(--green-primary); color:#fff; border:none; padding:12px; border-radius:8px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-save"></i> Simpan Catatan Pemeriksaan</button>
                    </form>
                </div>
            </div>

            <div id="diagnosa" class="tab-panel">
                <div class="card">
                    <div class="card-header"><h3>Kelola Kode Diagnosa ICD-10</h3></div>
                    <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                        <input type="hidden" name="action" value="simpan_emr">
                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                        <div style="display:grid; grid-template-columns: 180px 1fr; gap:12px;">
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">Kode ICD-10</label><input name="kode_icd10" type="text" placeholder="A09 / J00" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;" required></div>
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">Nama Diagnosis</label><input name="nama_diagnosa" type="text" placeholder="Misal: Acute gastroenteritis / Nasopharyngitis" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;" required></div>
                        </div>
                        <button type="submit" class="btn-submit" style="background:#2563eb; color:#fff; border:none; padding:12px; border-radius:8px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-plus-circle"></i> Tambah Diagnosa Aktif</button>
                    </form>
                    <?php if (!empty($diagnosa_pasien)): ?>
                    <div style="padding:16px; border-top:1px solid var(--gray-200);">
                        <h4 style="font-size:13px; margin-bottom:10px; color:var(--gray-600);">Daftar Diagnosa Tersimpan</h4>
                        <?php foreach ($diagnosa_pasien as $diag): ?>
                        <div style="margin-bottom:10px; font-size:13px; padding:10px; background:var(--gray-50); border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="color:var(--green-primary);"><?= htmlspecialchars($diag['kode_icd10']) ?></strong> &bull; <?= htmlspecialchars($diag['nama_diagnosis']) ?>
                                <div style="font-size:11px; color:var(--gray-400); margin-top:2px;"><?= htmlspecialchars($diag['waktu']) ?> (<?= htmlspecialchars($diag['jenis_diagnosis']) ?>)</div>
                            </div>
                            <span class="mini-badge badge-normal">Aktif</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="terapi_obat" class="tab-panel">
                <div class="card">
                    <div class="card-header" style="background:var(--green-pale);">
                        <h3 style="color:var(--green-primary);"><i class="fa-solid fa-prescription-bottle-medical"></i> Panel Resep Elektronik & Terapi Obat</h3>
                        <span class="header-meta">Sinkronisasi langsung ke Farmasi & Kasir</span>
                    </div>
                    <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" id="formResepDokter" style="padding:18px; display:grid; gap:16px;">
                        <input type="hidden" name="action" value="simpan_emr">
                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">

                        <div style="background:var(--gray-50); border:1px solid var(--gray-200); border-radius:10px; padding:14px;">
                            <label style="font-weight:700; font-size:13px; color:var(--gray-800); display:block; margin-bottom:10px;"><i class="fa-solid fa-pills" style="color:var(--green-primary);"></i> Cari Obat dari Database Stok SIMRS</label>
                            <div style="display:grid; grid-template-columns: minmax(200px, 2fr) minmax(110px, 1fr) minmax(110px, 1fr) minmax(180px, 1.5fr) 80px auto; gap:10px; align-items:end;">
                                <div>
                                    <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Nama Obat (Search / Auto-suggest)</label>
                                    <input type="text" id="inputObatSearch" list="daftarObatList" placeholder="Ketik nama / kode obat..." style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                    <datalist id="daftarObatList">
                                        <?php if (!empty($daftar_obat)): foreach ($daftar_obat as $ob): ?>
                                        <option data-id="<?= htmlspecialchars($ob['obat_id'] ?? '') ?>" data-satuan="<?= htmlspecialchars($ob['satuan']) ?>" value="<?= htmlspecialchars($ob['nama']) ?>">Stok: <?= htmlspecialchars($ob['stok']) ?> (<?= htmlspecialchars($ob['satuan']) ?>)</option>
                                        <?php endforeach; endif; ?>
                                    </datalist>
                                </div>
                                <div>
                                    <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Takaran/Dosis</label>
                                    <input type="number" step="0.5" id="inputDosis" value="1" placeholder="1" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Satuan</label>
                                    <input type="text" id="inputSatuan" placeholder="Tablet/Botol" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Aturan Minum / Pakai</label>
                                    <input type="text" id="inputAturan" placeholder="3x1 sesudah makan" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Jumlah</label>
                                    <input type="number" id="inputQty" value="10" min="1" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                </div>
                                <div>
                                    <button type="button" onclick="tambahObatKeResep()" style="background:var(--green-mid); color:#fff; border:none; padding:10px 14px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; height:37px;" title="Tambahkan ke Daftar Resep">+ Tambah</button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 style="font-size:13px; font-weight:700; color:var(--gray-800); margin-bottom:8px;"><i class="fa-solid fa-list-check"></i> Daftar Item Resep Elektronik (Akan dikirim)</h4>
                            <div style="overflow-x:auto; border:1px solid var(--gray-200); border-radius:8px;">
                                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                    <thead>
                                        <tr style="background:var(--gray-100); text-align:left; border-bottom:1px solid var(--gray-200);">
                                            <th style="padding:10px 12px;">Nama Obat</th>
                                            <th style="padding:10px 12px;">Takaran</th>
                                            <th style="padding:10px 12px;">Satuan</th>
                                            <th style="padding:10px 12px;">Aturan Minum</th>
                                            <th style="padding:10px 12px;">Jumlah</th>
                                            <th style="padding:10px 12px; width:60px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabelItemResepBody">
                                        <tr id="emptyResepRow">
                                            <td colspan="6" style="text-align:center; padding:20px; color:var(--gray-400);">Belum ada item obat ditambahkan. Gunakan form pencarian di atas atau ketik resep manual di bawah.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <label style="font-weight:700; font-size:13px; color:var(--gray-800); display:block; margin-bottom:6px;">Catatan Tambahan / Terapi Non-Farmakologi</label>
                            <textarea name="terapi_aturan" rows="2" placeholder="Saran istirahat, diet khusus, atau instruksi racikan khusus..." style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;"></textarea>
                        </div>

                        <div style="border-top:1px dashed var(--gray-300); padding-top:16px; margin-top:4px;">
                            <label style="font-weight:700; font-size:13px; color:var(--gray-800); display:block; margin-bottom:10px;"><i class="fa-solid fa-traffic-light"></i> Aksi Tegas Penyelesaian EMR Pasien</label>
                            <div style="display:flex; gap:14px; flex-wrap:wrap;">
                                <button type="submit" name="tujuan_selesai" value="farmasi" class="btn-submit" style="flex:1; background:var(--green-primary); color:#fff; border:none; padding:14px 20px; font-weight:700; border-radius:10px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:10px; font-size:14px; box-shadow:0 2px 6px rgba(46,125,50,0.25);">
                                    <i class="fa-solid fa-share-from-square" style="font-size:16px;"></i> Simpan & Teruskan ke Farmasi (Antrian Apotik)
                                </button>
                                <button type="submit" name="tujuan_selesai" value="kasir" class="btn-submit" style="flex:1; background:#2563eb; color:#fff; border:none; padding:14px 20px; font-weight:700; border-radius:10px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:10px; font-size:14px; box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                                    <i class="fa-solid fa-cash-register" style="font-size:16px;"></i> Simpan & Langsung ke Kasir (Tanpa Obat)
                                </button>
                            </div>
                            <p style="font-size:11px; color:var(--gray-400); margin-top:8px;">* Memilih <strong>Teruskan ke Farmasi</strong> akan mengirim resep ke daftar antrian apoteker. Memilih <strong>Langsung ke Kasir</strong> akan langsung memproses tagihan ke kasir.</p>
                        </div>
                    </form>
                    <?php if (!empty($resep_pasien)): ?>
                    <div style="padding:18px; border-top:1px solid var(--gray-200); background:var(--gray-50);">
                        <h4 style="font-size:13px; font-weight:700; color:var(--gray-800); margin-bottom:12px;"><i class="fa-solid fa-clock-rotate-left"></i> Resep Obat Terdahulu Pasien Ini</h4>
                        <div style="display:grid; gap:12px;">
                            <?php foreach ($resep_pasien as $rsp): ?>
                            <div style="background:var(--white); border:1px solid var(--gray-200); border-radius:8px; padding:12px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                    <strong style="color:var(--green-primary);">Resep #<?= htmlspecialchars($rsp['resep_id']) ?> (<?= htmlspecialchars($rsp['resep_status']) ?>)</strong>
                                    <span style="font-size:11px; color:var(--gray-400);"><?= htmlspecialchars($rsp['waktu']) ?></span>
                                </div>
                                <?php if (!empty($rsp['items']) && is_array($rsp['items'])): ?>
                                <ul style="margin-left:20px; font-size:12px; color:var(--gray-600);">
                                    <?php foreach ($rsp['items'] as $ritm): ?>
                                    <li><strong><?= htmlspecialchars($ritm['nama_obat']) ?></strong> &bull; Dosis: <?= htmlspecialchars($ritm['dosis']) ?> <?= htmlspecialchars($ritm['satuan']) ?> &bull; Aturan: <?= htmlspecialchars($ritm['aturan_pakai']) ?> (<?= htmlspecialchars($ritm['qty']) ?> <?= htmlspecialchars($ritm['satuan']) ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php elseif (!empty($rsp['catatan'])): ?>
                                <div style="font-size:12px; color:var(--gray-600);"><?= nl2br(htmlspecialchars($rsp['catatan'])) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function tambahObatKeResep() {
                const inputSearch = document.getElementById('inputObatSearch');
                const inputDosis = document.getElementById('inputDosis');
                const inputSatuan = document.getElementById('inputSatuan');
                const inputAturan = document.getElementById('inputAturan');
                const inputQty = document.getElementById('inputQty');

                const namaObat = inputSearch.value.trim();
                if (!namaObat) {
                    alert('Silakan pilih atau ketik nama obat terlebih dahulu.');
                    inputSearch.focus();
                    return;
                }

                let obatId = '';
                const datalist = document.getElementById('daftarObatList');
                if (datalist) {
                    const options = datalist.querySelectorAll('option');
                    options.forEach(opt => {
                        if (opt.value.toLowerCase() === namaObat.toLowerCase()) {
                            obatId = opt.getAttribute('data-id') || '';
                            if (!inputSatuan.value.trim() && opt.getAttribute('data-satuan')) {
                                inputSatuan.value = opt.getAttribute('data-satuan');
                            }
                        }
                    });
                }

                const dosis = inputDosis.value.trim() || '1';
                const satuan = inputSatuan.value.trim() || 'Tablet';
                const aturan = inputAturan.value.trim() || '3x1 sesudah makan';
                const qty = inputQty.value.trim() || '10';

                const tbody = document.getElementById('tabelItemResepBody');
                const emptyRow = document.getElementById('emptyResepRow');
                if (emptyRow) emptyRow.remove();

                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid var(--gray-200)';
                tr.innerHTML = `
                    <td style="padding:10px 12px;">
                        <input type="hidden" name="resep_obat_id[]" value="${obatId}">
                        <input type="hidden" name="resep_nama_obat[]" value="${namaObat.replace(/"/g, '&quot;')}">
                        <strong>${namaObat}</strong>
                    </td>
                    <td style="padding:10px 12px;"><input type="hidden" name="resep_dosis[]" value="${dosis}">${dosis}</td>
                    <td style="padding:10px 12px;"><input type="hidden" name="resep_satuan[]" value="${satuan}">${satuan}</td>
                    <td style="padding:10px 12px;"><input type="hidden" name="resep_aturan_pakai[]" value="${aturan.replace(/"/g, '&quot;')}">${aturan}</td>
                    <td style="padding:10px 12px;"><input type="hidden" name="resep_qty[]" value="${qty}">${qty}</td>
                    <td style="padding:10px 12px;"><button type="button" onclick="this.closest('tr').remove(); cekKosongResep();" style="background:#fee2e2; color:#dc2626; border:none; padding:4px 8px; border-radius:6px; cursor:pointer;" title="Hapus"><i class="fa-solid fa-trash"></i></button></td>
                `;
                tbody.appendChild(tr);

                inputSearch.value = '';
                inputDosis.value = '1';
                inputQty.value = '10';
                inputSearch.focus();
            }

            function cekKosongResep() {
                const tbody = document.getElementById('tabelItemResepBody');
                if (tbody && tbody.children.length === 0) {
                    tbody.innerHTML = `<tr id="emptyResepRow"><td colspan="6" style="text-align:center; padding:20px; color:var(--gray-400);">Belum ada item obat ditambahkan. Gunakan form pencarian di atas atau ketik resep manual di bawah.</td></tr>`;
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const inputSearch = document.getElementById('inputObatSearch');
                const inputSatuan = document.getElementById('inputSatuan');
                if (inputSearch && inputSatuan) {
                    inputSearch.addEventListener('input', function() {
                        const val = this.value.trim();
                        const datalist = document.getElementById('daftarObatList');
                        if (datalist) {
                            const options = datalist.querySelectorAll('option');
                            options.forEach(opt => {
                                if (opt.value.toLowerCase() === val.toLowerCase()) {
                                    if (opt.getAttribute('data-satuan')) {
                                        inputSatuan.value = opt.getAttribute('data-satuan');
                                    }
                                }
                            });
                        }
                    });
                }
            });
            </script>

            <div id="order" class="tab-panel">
                <div class="card">
                    <div class="card-header"><h3>Form Order Laboratorium & Radiologi</h3></div>
                    <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                        <input type="hidden" name="action" value="simpan_emr">
                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                        <div style="display:grid; grid-template-columns: 200px 1fr; gap:12px;">
                            <div>
                                <label style="font-weight:700; margin-bottom:6px; display:block;">Pilih Tipe Order</label>
                                <select name="order_type" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;">
                                    <option value="lab">Laboratorium</option>
                                    <option value="radiologi">Radiologi</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-weight:700; margin-bottom:6px; display:block;">Jenis Pemeriksaan</label>
                                <input name="order_lab_jenis" type="text" placeholder="Misal: Hematologi Lengkap / Rontgen Thorax / Gula Darah" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;" required>
                            </div>
                        </div>
                        <div>
                            <label style="font-weight:700; margin-bottom:6px; display:block;">Catatan Instruksi Klinis</label>
                            <textarea name="order_lab_catatan" rows="3" placeholder="Instruksi khusus atau indikasi klinis pemeriksaan..." style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></textarea>
                        </div>
                        <button type="submit" class="btn-submit" style="background:#2563eb; color:#fff; border:none; padding:12px; border-radius:8px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-paper-plane"></i> Kirim Order Penunjang</button>
                    </form>
                </div>
            </div>

            <div id="hasil_pemeriksaan" class="tab-panel">
                <div class="card">
                    <div class="card-header"><h3>Lembar Dokumen Hasil Laboratorium & Penunjang</h3></div>
                    <?php if (!empty($order_results)): ?>
                    <div style="padding:16px; display:grid; gap:12px;">
                        <?php foreach ($order_results as $res): ?>
                        <div style="border:1px solid var(--gray-200); border-radius:10px; padding:14px; background:var(--white);">
                            <div style="font-size:13px; font-weight:700; color:var(--gray-800); margin-bottom:4px;"><?= htmlspecialchars($res['parameter']) ?>: <span style="color:var(--green-primary);"><?= htmlspecialchars($res['nilai']) ?> <?= htmlspecialchars($res['satuan']) ?></span></div>
                            <div style="font-size:12px; color:var(--gray-600);">Referensi Normal: <?= htmlspecialchars($res['nilai_rujukan']) ?></div>
                            <div style="font-size:11px; color:var(--gray-400); margin-top:4px;">Pemeriksaan: <?= htmlspecialchars($res['jenis_pemeriksaan']) ?> &bull; <?= htmlspecialchars($res['tanggal']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-tab-view"><i class="fa-solid fa-square-poll-horizontal"></i>Belum ada hasil pemeriksaan laboratorium atau radiologi yang diunggah.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="dokumen" class="tab-panel">
                <div class="card">
                    <div class="card-header"><h3>Buat Surat Keterangan / Pengantar / Rujukan</h3></div>
                    <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                        <input type="hidden" name="action" value="simpan_emr">
                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">Faskes Rujukan Tujuan</label><input name="rujukan_faskes" type="text" placeholder="RS Tujuan Rujukan" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></div>
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">Poli Tujuan</label><input name="rujukan_poli" type="text" placeholder="Poli Spesialis Tujuan" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></div>
                            <div><label style="font-weight:700; margin-bottom:6px; display:block;">No. BPJS / Asuransi</label><input name="rujukan_bpjs" type="text" value="<?= htmlspecialchars($penjamin) ?>" style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></div>
                        </div>
                        <div><label style="font-weight:700; margin-bottom:6px; display:block;">Alasan Rujukan / Catatan Klinis</label><textarea name="rujukan_alasan" rows="3" placeholder="Indikasi rujukan / hasil pemeriksaan awal..." style="width:100%; padding:10px; border:1px solid var(--gray-300); border-radius:8px;"></textarea></div>
                        <button type="submit" class="btn-submit" style="background:#16a34a; color:#fff; border:none; padding:12px; border-radius:8px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-file-export"></i> Terbitkan Surat Rujukan</button>
                    </form>
                </div>
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
                <div class="widget-title"><span><i class="fa-solid fa-history"></i> Riwayat Kunjungan Terakhir</span> <a href="#" class="widget-link" onclick="switchTab(event, 'riwayat_kunjungan'); return false;">Lihat Semua</a></div>
                <div class="history-box" style="padding:16px; text-align:center;">
                    <?php if (!empty($emr_pasien)): ?>
                    <div class="history-date-row">
                        <span><?= htmlspecialchars($emr_pasien[0]['waktu']) ?></span>
                        <span style="background:#dcfce7; color:#15803d; font-size:10px; padding:1px 6px; border-radius:4px;">Tercatat</span>
                    </div>
                    <div class="history-item-row"><span class="history-label">Keluhan:</span><span class="history-val"><?= htmlspecialchars($emr_pasien[0]['subjective'] ?: '-') ?></span></div>
                    <div class="history-item-row"><span class="history-label">Diagnosis:</span><span class="history-val"><?= htmlspecialchars($emr_pasien[0]['assessment'] ?: '-') ?></span></div>
                    <?php else: ?>
                    <p style="color:var(--gray-400); font-size:12px;">Belum ada riwayat kunjungan di database</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="widget-title"><span><i class="fa-solid fa-star-of-life"></i> Diagnosa Aktif</span> <span style="color:var(--green-primary); cursor:pointer; font-size:11px;" onclick="switchTab(event, 'diagnosa'); return false;">+ Tambah</span></div>
                <div style="padding:16px; text-align:center; font-size:12px; color:var(--gray-400);">
                    <?php if (!empty($emr_pasien) && !empty($emr_pasien[0]['assessment'])): ?>
                    <div style="text-align:left; font-weight:600; color:var(--gray-800);"><?= htmlspecialchars($emr_pasien[0]['assessment']) ?></div>
                    <?php else: ?>
                    Belum ada diagnosa aktif tercatat
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="widget-title"><span><i class="fa-solid fa-pills"></i> Terapi Aktif</span> <a href="#" class="widget-link" onclick="switchTab(event, 'terapi_obat'); return false;">Lihat Semua</a></div>
                <div class="order-list" style="padding:16px; text-align:center; color:var(--gray-400); font-size:12px;">
                    <?php if (!empty($resep_pasien)): ?>
                    <div style="text-align:left; font-weight:600; color:var(--gray-800);">Resep Aktif #<?= htmlspecialchars($resep_pasien[0]['resep_id']) ?> (<?= htmlspecialchars($resep_pasien[0]['resep_status']) ?>)</div>
                    <?php else: ?>
                    Belum ada terapi obat aktif tercatat
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div></main>
</div>

<script>
function switchTab(event, tabId) {
    if (event && event.preventDefault) event.preventDefault();
    const panels = document.querySelectorAll('.tab-panel');
    panels.forEach(panel => panel.classList.remove('active'));

    const tabs = document.querySelectorAll('.emr-tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));

    const targetPanel = document.getElementById(tabId);
    if (targetPanel) targetPanel.classList.add('active');

    let matchedTab = null;
    if (event && event.currentTarget && event.currentTarget.classList.contains('emr-tab-btn')) {
        matchedTab = event.currentTarget;
    } else {
        tabs.forEach(tab => {
            const attr = tab.getAttribute('onclick') || '';
            if (attr.includes("'" + tabId + "'") || attr.includes('"' + tabId + '"')) {
                matchedTab = tab;
            }
        });
    }
    if (matchedTab) {
        matchedTab.classList.add('active');
        const crumb = document.querySelector('.active-crumb');
        if (crumb) crumb.textContent = matchedTab.textContent.trim();
    }
}
</script>

</body>
</html>
<?php
} catch (Throwable $e) {
    error_log('Unhandled exception in EMR_Dokter.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (function_exists('renderFriendlyErrorPage')) {
        renderFriendlyErrorPage('Terjadi Kesalahan pada Halaman', $e->getMessage());
    } else {
        echo '<div style="padding:20px;color:red;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>