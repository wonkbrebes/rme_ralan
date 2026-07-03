<?php
// Menu navigasi disamakan 100% persis urutan dan strukturnya
// Status 'active' dipindahkan ke menu Farmasi
$nav_items = [
    ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'page' => 'dashboard'],
    ['label' => 'Antrian',     'icon' => 'fa-clipboard-list', 'page' => 'antrian'],
    ['label' => 'Pendaftaran', 'icon' => 'fa-user-plus',      'page' => 'pendaftaran'],
    ['label' => 'EMR Dokter',  'icon' => 'fa-file-medical',   'page' => 'emr_dokter'],
    ['label' => 'Farmasi',     'icon' => 'fa-prescription-bottle-medical', 'page' => 'farmasi', 'active' => true],
    ['label' => 'Kasir',       'icon' => 'fa-credit-card',    'page' => 'kasir'],
];

// Data 4 Card Statistik Atas
$stats_farmasi = [
    ['label' => 'TOTAL OBAT',    'value' => '1.245', 'sub' => 'Jenis Obat', 'icon' => 'fa-box-tissue'],
    ['label' => 'STOK AMAN',     'value' => '982',   'sub' => 'Obat',       'icon' => 'fa-cart-shopping'],
    ['label' => 'STOK MENIPIS',  'value' => '18',    'sub' => 'Obat',       'icon' => 'fa-triangle-exclamation', 'warning' => true],
    ['label' => 'RESEP HARI INI', 'value' => '56',    'sub' => 'Resep',      'icon' => 'fa-calendar-check'],
];

// Data Tabel Daftar Obat
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

// Data Widget Samping: Stok Menipis
$stok_menipis = [
    ['nama' => 'Salbutamol Inhaler', 'detail' => 'Stok tersisa: 25 Puff', 'status' => 'Habis'],
    ['nama' => 'CTM 4 mg',           'detail' => 'Stok tersisa: 30 Tablet', 'status' => 'Menipis'],
    ['nama' => 'Ranitidine 150 mg',  'detail' => 'Stok tersisa: 45 Tablet', 'status' => 'Menipis'],
    ['nama' => 'Ibuprofen 400 mg',   'detail' => 'Stok tersisa: 120 Tablet', 'status' => 'Menipis'],
];

// Data Widget Samping: Resep Terbaru
$resep_terbaru = [
    ['no' => '#R-2025-00056', 'nama' => 'Siti Rahayu',    'waktu' => '10:15 WIB', 'status' => 'Selesai'],
    ['no' => '#R-2025-00055', 'nama' => 'Budiman Setiawan','waktu' => '09:50 WIB', 'status' => 'Selesai'],
    ['no' => '#R-2025-00054', 'nama' => 'Lestari Putri',   'waktu' => '09:30 WIB', 'status' => 'Selesai'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmasi – SIMRS Clinical Precision</title>
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

        /* ── MAIN AREA ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 0 28px; height: 64px;
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

        /* ── LAYOUT GRID ── */
        .content { padding: 24px 28px; display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }

        /* ── STAT CARDS ── */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; grid-column: 1 / -1; }
        .stat-card {
            background: var(--white); border-radius: 12px; padding: 16px 20px;
            display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .stat-icon {
            width: 46px; height: 46px; background: var(--green-pale); border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--green-primary);
        }
        .stat-card.card-warning .stat-icon { background: #fef9c3; color: #a16207; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-label { font-size: 10px; font-weight: 700; color: var(--gray-400); letter-spacing: .4px; }
        .stat-value-group { display: flex; align-items: baseline; gap: 4px; margin-top: 2px; }
        .stat-value-group strong { font-size: 24px; font-weight: 700; color: var(--gray-800); }
        .stat-value-group span { font-size: 11px; color: var(--gray-400); }

        /* ── MAIN CARD LAYOUT ── */
        .card { background: var(--white); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden; }
        .card-header { padding: 18px 20px; border-bottom: 1px solid var(--gray-200); }
        .card-header h2 { font-size: 15px; font-weight: 700; color: var(--gray-800); }
        .card-header p { font-size: 12px; color: var(--gray-400); margin-top: 2px; }

        /* FILTER & SEARCH BAR */
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
        .search-container { position: relative; width: 240px; }
        .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 13px; }
        .search-input { width: 100%; padding: 8px 12px 8px 34px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 13px; outline: none; }
        .search-input:focus { border-color: var(--green-light); }

        .btn-add-obat {
            padding: 8px 14px; border: none; border-radius: 8px; background: var(--green-primary);
            color: var(--white); font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;
        }
        .btn-add-obat:hover { background: var(--green-mid); }

        /* ── TABLE STYLING ── */
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .4px; color: var(--gray-400);
            background: var(--gray-50); border-bottom: 1px solid var(--gray-200);
        }
        td { padding: 13px 20px; font-size: 13px; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--gray-50); }

        .obat-code { font-weight: 700; color: var(--green-primary); }
        .obat-name { font-weight: 500; color: var(--gray-800); }

        /* Status Badges */
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-aman { background: #dcfce7; color: #15803d; }
        .badge-menipis { background: #ffedd5; color: #c2410c; }
        .badge-habis { background: #fee2e2; color: #b91c1c; }

        /* Action Buttons Row */
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

        /* Pagination */
        .table-footer {
            padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;
            border-top: 1px solid var(--gray-200); background: var(--white);
        }
        .table-footer p { font-size: 12px; color: var(--gray-400); }
        .pagination-nav { display: flex; gap: 4px; }
        .page-btn {
            min-width: 28px; height: 28px; padding: 0 6px; border: 1px solid var(--gray-200); background: var(--white);
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            color: var(--gray-600); font-size: 12px; cursor: pointer; font-weight: 500;
        }
        .page-btn:hover { background: var(--gray-50); }
        .page-btn.active { background: var(--green-primary); color: var(--white); border-color: var(--green-primary); font-weight: 600; }

        /* ── RIGHT COLUMN WIDGETS ── */
        .farmasi-grid-container { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; }
        .right-column { display: flex; flex-direction: column; gap: 16px; width: 100%; min-width: 0; }
        .widget-title {
            display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700;
            color: var(--gray-800); padding: 14px 16px; border-bottom: 1px solid var(--gray-200);
        }
        .widget-title span { display: flex; align-items: center; gap: 8px; }
        .widget-title i { color: var(--green-primary); font-size: 14px; }
        .widget-link { font-size: 11px; color: var(--green-primary); text-decoration: none; font-weight: 600; }
        .widget-link:hover { text-decoration: underline; }

        /* Widget Aksi Cepat Grid */
        .quick-actions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 14px 16px; }
        .btn-qa {
            display: flex; align-items: center; gap: 8px; padding: 10px 12px;
            border: 1px solid var(--gray-200); border-radius: 8px; background: var(--white);
            font-size: 12px; font-weight: 600; color: var(--gray-600); cursor: pointer; transition: background .15s;
        }
        .btn-qa:hover { background: var(--green-xpale); border-color: var(--green-light); color: var(--green-primary); }
        .btn-qa i { font-size: 14px; color: var(--green-primary); }

        /* Widget Stok Menipis */
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
        .mini-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }

        /* Widget Resep Terbaru */
        .resep-item { display: flex; align-items: center; gap: 12px; padding: 10px; border: 1px solid var(--gray-100); border-radius: 8px; background: var(--gray-50); }
        .resep-icon-box { width: 32px; height: 32px; background: var(--green-pale); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-size: 14px; }
        .resep-details { flex: 1; display: flex; flex-direction: column; gap: 1px; }
        .resep-no { font-size: 11px; font-weight: 600; color: var(--gray-400); }
        .resep-patient { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .resep-time { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
        .badge-done { background: #dcfce7; color: #15803d; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }

        footer { font-size: 11px; color: var(--gray-400); text-align: center; margin-top: 14px; padding-bottom: 10px; grid-column: 1 / -1; }

        /* ===== RESPONSIF TOTAL (DESKTOP, TABLET, MOBILE) ===== */
        @media (max-width: 1024px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .chart-grid, .lower-grid, .pendaftaran-grid, .triple-grid, .poli-grid, .farmasi-grid-container { grid-template-columns: 1fr; gap: 16px; }
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
            <h1>Farmasi</h1>
            <p class="breadcrumb">Front Office &rsaquo; <span>Farmasi</span></p>
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
                    <small>Front Office Supervisor</small>
                </div>
            </div>
        </div>
    </header>

    <main class="content">

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

        <div class="farmasi-grid-container">
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
                    <button class="btn-qa"><i class="fa-solid fa-file-medical"></i> Resep Baru</button>
                    <button class="btn-qa"><i class="fa-solid fa-hand-holding-medical"></i> Penyerahan Obat</button>
                    <button class="btn-qa"><i class="fa-solid fa-boxes-stacked"></i> Stok Masuk</button>
                    <button class="btn-qa"><i class="fa-solid fa-file-lines"></i> Laporan Farmasi</button>
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
        </div><footer>
            &copy; 2026 SIMRS. All rights reserved.
        </footer>
    </main>
</div>

</body>
</html>