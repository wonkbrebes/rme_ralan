<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
if (isset($_GET['page']) && $_GET['page'] !== 'igd') {
    header('Location: index.php?page=' . urlencode($_GET['page']));
    exit;
}
require_once __DIR__ . '/post_handler.php';
require_once __DIR__ . '/simrs_data.php';

// Menyesuaikan active nav untuk IGD
foreach ($nav_items as &$item) {
    if ($item['page'] === 'igd') {
        $item['active'] = true;
    } else {
        unset($item['active']);
    }
}
unset($item);

// Hitung statistik khusus pasien IGD dari antrian aktif
$igd_list = [];
$triase_stats = [
    'merah' => 0,
    'kuning' => 0,
    'hijau' => 0,
    'hitam' => 0,
    'total' => 0
];

if (!empty($antrian)) {
    foreach ($antrian as $a) {
        $poli = $a['poli'] ?? '';
        $jenis = $a['jenis_daftar'] ?? '';
        if (stripos($poli, 'IGD') !== false || stripos($poli, 'Darurat') !== false || strcasecmp($jenis, 'IGD') === 0) {
            $triase_color = 'Kuning'; // default
            $keluhan = 'Observasi darurat';
            $vitals = '-';
            
            // Cek jika ada catatan EMR/Triase
            if (!empty($a['no'])) {
                try {
                    $note = db_select_one("SELECT keluhan_utama, objective, assessment FROM emr_notes WHERE no_antrian = :no ORDER BY id DESC LIMIT 1", ['no' => $a['no']]);
                    if ($note) {
                        $ku = $note['keluhan_utama'] ?? '';
                        if (preg_match('/\[TRIASE:\s*([^\]]+)\]/i', $ku, $m)) {
                            $triase_color = ucfirst(strtolower(trim($m[1])));
                        }
                        $keluhan = preg_replace('/\[TRIASE:\s*[^\]]+\]\s*/i', '', $ku) ?: $keluhan;
                        if (!empty($note['objective'])) {
                            $vitals = $note['objective'];
                        }
                    }
                } catch (Exception $e) {}
            }
            
            $triase_key = strtolower($triase_color);
            if (isset($triase_stats[$triase_key])) {
                $triase_stats[$triase_key]++;
            } else {
                $triase_stats['kuning']++;
            }
            $triase_stats['total']++;
            
            $a['triase_color'] = $triase_color;
            $a['keluhan'] = $keluhan;
            $a['vitals'] = $vitals;
            $igd_list[] = $a;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modul IGD & Triase – SIMRS Clinical Precision</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green-primary: #1b5e20;
            --green-mid: #2e7d32;
            --green-light: #4caf50;
            --green-bg: #e8f5e9;
            --green-surface: #c8e6c9;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-600: #475569;
            --gray-800: #1e293b;
            --red-primary: #dc2626;
            --red-bg: #fee2e2;
            --yellow-primary: #d97706;
            --yellow-bg: #fef3c7;
            --green-triase: #16a34a;
            --green-triase-bg: #dcfce7;
            --black-triase: #1e293b;
            --black-triase-bg: #f1f5f9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', -apple-system, sans-serif; }
        body { background: var(--gray-100); color: var(--gray-800); display: flex; min-height: 100vh; }

        /* ===== SIDEBAR ===== */
        .sidebar { width: 260px; background: var(--green-primary); color: var(--white); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; box-shadow: 2px 0 10px rgba(0,0,0,0.15); }
        .sidebar-brand { padding: 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .brand-icon { width: 38px; height: 38px; background: var(--white); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-size: 20px; font-weight: bold; }
        .brand-text h2 { font-size: 18px; font-weight: 700; letter-spacing: -0.5px; }
        .brand-text span { font-size: 11px; color: var(--green-surface); text-transform: uppercase; letter-spacing: 1px; }

        .sidebar-menu { flex: 1; padding: 16px 0; overflow-y: auto; }
        .menu-label { padding: 0 20px 8px; font-size: 11px; font-weight: 600; color: var(--green-surface); text-transform: uppercase; letter-spacing: 1px; }
        .nav-list { list-style: none; }
        .nav-list li a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; border-left: 4px solid transparent; }
        .nav-list li a:hover, .nav-list li a.active { background: rgba(255,255,255,0.1); color: var(--white); border-left-color: var(--white); }
        .nav-list li a i { width: 20px; text-align: center; font-size: 16px; }

        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; gap: 12px; }
        .btn-quick { background: var(--green-light); color: var(--white); border: none; padding: 10px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s; text-decoration: none; }
        .btn-quick:hover { background: #43a047; }
        .sidebar-footer a.logout { color: var(--green-surface); text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .sidebar-footer a.logout:hover { color: var(--white); }

        /* ===== MAIN & TOPBAR ===== */
        .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar { height: 64px; background: var(--white); border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 90; }
        .topbar-left h1 { font-size: 18px; font-weight: 700; color: var(--gray-800); }
        .breadcrumb { font-size: 12px; color: var(--gray-600); margin-top: 2px; }
        .breadcrumb span { color: var(--green-primary); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .icon-btn { background: none; border: none; font-size: 18px; color: var(--gray-600); cursor: pointer; position: relative; }
        .icon-btn .badge { position: absolute; top: -6px; right: -8px; background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .user-text { line-height: 1.2; }
        .user-text strong { display: block; font-size: 13px; color: var(--gray-800); }
        .user-text small { font-size: 11px; color: var(--gray-600); }

        /* ===== CONTENT & STATS ===== */
        .content { padding: 24px; flex: 1; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: 12px; padding: 18px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
        .stat-left .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px; }
        .stat-left .stat-label { font-size: 12px; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-left .stat-value { font-size: 24px; font-weight: 700; color: var(--gray-800); margin-top: 4px; }

        /* ===== FORM TRIASE CARD ===== */
        .card-triage { background: var(--white); border-radius: 12px; border: 1px solid var(--gray-200); box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 24px; margin-bottom: 24px; border-top: 4px solid #dc2626; }
        .card-triage-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--gray-200); }
        .card-triage-header h2 { font-size: 16px; font-weight: 700; color: var(--gray-800); display: flex; align-items: center; gap: 10px; }
        .card-triage-header h2 i { color: #dc2626; font-size: 20px; }

        .triage-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--gray-800); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--gray-300); border-radius: 8px; font-size: 13.5px; color: var(--gray-800); transition: border 0.2s; }
        .form-control:focus { outline: none; border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.1); }
        
        .triage-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 6px; }
        .triage-option { border: 2px solid var(--gray-200); border-radius: 10px; padding: 12px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 10px; }
        .triage-option input[type="radio"] { cursor: pointer; }
        .triage-option.merah:hover, .triage-option input:checked + .merah { border-color: #dc2626; background: #fef2f2; }
        .triage-option.kuning:hover, .triage-option input:checked + .kuning { border-color: #d97706; background: #fffbeb; }
        .triage-option.hijau:hover, .triage-option input:checked + .hijau { border-color: #16a34a; background: #f0fdf4; }
        .triage-option.hitam:hover, .triage-option input:checked + .hitam { border-color: #475569; background: #f8fafc; }

        .vitals-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .btn-submit-triage { background: #dc2626; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; margin-top: 10px; transition: background 0.2s; box-shadow: 0 2px 4px rgba(220,38,38,0.2); }
        .btn-submit-triage:hover { background: #b91c1c; }

        /* ===== TABLE BOARD ===== */
        .card-board { background: var(--white); border-radius: 12px; border: 1px solid var(--gray-200); box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px; }
        .table-responsive { overflow-x: auto; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: var(--gray-50); padding: 12px 16px; font-size: 12px; font-weight: 700; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gray-200); }
        td { padding: 14px 16px; font-size: 13.5px; color: var(--gray-800); border-bottom: 1px solid var(--gray-200); vertical-align: middle; }
        tr:hover td { background: var(--gray-50); }

        .badge-triase { padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .badge-triase.Merah { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .badge-triase.Kuning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-triase.Hijau { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-triase.Hitam { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }

        .btn-action-sm { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; transition: all 0.2s; }
        .btn-action-sm:hover { background: #dbeafe; }

        @media (max-width: 1024px) {
            .triage-form-grid { grid-template-columns: 1fr; gap: 16px; }
            .vitals-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { width: 60px; }
            .sidebar-brand .brand-text, .nav-list li a span, .btn-quick span, .sidebar-footer a span { display: none; }
            .main { margin-left: 60px; }
            .topbar { padding: 12px 16px; height: auto; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="fa-solid fa-hospital"></i></div>
            <div class="brand-text">
                <h2>SIMRS Ralan</h2>
                <span>Clinical Precision</span>
            </div>
        </div>

        <div class="sidebar-menu">
            <div class="menu-label">Menu Utama</div>
            <ul class="nav-list">
                <?php foreach ($nav_items as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?? ('index.php?page=' . $item['page']) ?>" class="<?= !empty($item['active']) ? 'active' : '' ?>">
                        <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="sidebar-footer">
            <a href="?page=pendaftaran" class="btn-quick">
                <i class="fa-solid fa-plus"></i>
                <span>Quick Admission</span>
            </a>
            <a href="?page=kasir" class="logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Kembali / Keluar</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <h1>Modul IGD & Triase Darurat</h1>
                <p class="breadcrumb">Front Office › <span>Instalasi Gawat Darurat (IGD)</span></p>
            </div>
            <div class="topbar-right">
                <button class="icon-btn" title="Total Pasien IGD Aktif">
                    <i class="fa-solid fa-bell <?= $triase_stats['merah'] > 0 ? 'fa-shake' : '' ?>" style="<?= $triase_stats['merah'] > 0 ? 'color: #dc2626;' : '' ?>"></i>
                    <?php if ($triase_stats['total'] > 0): ?>
                    <span class="badge" style="background: <?= $triase_stats['merah'] > 0 ? '#dc2626' : '#2563eb' ?>;"><?= $triase_stats['total'] ?></span>
                    <?php endif; ?>
                </button>
                <div class="user-info">
                    <div class="user-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                    <div class="user-text">
                        <strong>Dr. Hendra Saputra, Sp.EM</strong>
                        <small>Dokter Jaga IGD & Triase</small>
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

            <!-- STATS TRIASE GRID -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #eff6ff; color: #2563eb;"><i class="fa-solid fa-truck-medical"></i></div>
                        <div class="stat-label">Total Pasien IGD</div>
                        <div class="stat-value"><?= $triase_stats['total'] ?> <span style="font-size: 14px; font-weight: 500; color: var(--gray-400);">Pasien</span></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #fee2e2; color: #dc2626;"><i class="fa-solid fa-heart-pulse"></i></div>
                        <div class="stat-label">Triase Merah (Resusitasi)</div>
                        <div class="stat-value" style="color: #dc2626;"><?= $triase_stats['merah'] ?> <span style="font-size: 14px; font-weight: 500; color: var(--gray-400);">Darurat</span></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="stat-label">Triase Kuning (Urgent)</div>
                        <div class="stat-value" style="color: #d97706;"><?= $triase_stats['kuning'] ?> <span style="font-size: 14px; font-weight: 500; color: var(--gray-400);">Pasien</span></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #dcfce7; color: #16a34a;"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-label">Triase Hijau (Non-Darurat)</div>
                        <div class="stat-value" style="color: #16a34a;"><?= $triase_stats['hijau'] ?> <span style="font-size: 14px; font-weight: 500; color: var(--gray-400);">Pasien</span></div>
                    </div>
                </div>
            </div>

            <!-- FORM TRIASE CEPAT -->
            <section class="card-triage">
                <div class="card-triage-header">
                    <h2><i class="fa-solid fa-notes-medical"></i> Registrasi & Triase Cepat Pasien IGD</h2>
                    <span style="font-size: 12px; font-weight: 600; color: #dc2626; background: #fee2e2; padding: 4px 12px; border-radius: 12px;">Fast Admission</span>
                </div>

                <form action="" method="POST">
                    <input type="hidden" name="action" value="daftar_igd">
                    
                    <div class="triage-form-grid">
                        <!-- Kolom Kiri: Identitas -->
                        <div>
                            <div class="form-group">
                                <label>Nama Pasien / No. RM (Isi 'Mr. X' jika belum ada identitas):</label>
                                <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Santoso / Mr. X (Korban KLL)" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div class="form-group">
                                    <label>No. KTP / NIK / ID Darurat:</label>
                                    <input type="text" name="nik" class="form-control" placeholder="3329xxxxxxxx / Kosongkan">
                                </div>
                                <div class="form-group">
                                    <label>Jenis Kelamin:</label>
                                    <select name="jenis_kelamin" class="form-control">
                                        <option value="L">Laki-laki (L)</option>
                                        <option value="P">Perempuan (P)</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div class="form-group">
                                    <label>Cara Bayar / Jenis Pasien:</label>
                                    <select name="jenis_pasien" class="form-control">
                                        <option value="BPJS">BPJS Kesehatan</option>
                                        <option value="Umum">Umum / Mandiri</option>
                                        <option value="Asuransi">Asuransi Swasta</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>No. Telepon / Pengantar:</label>
                                    <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxx">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Keluhan Utama & Mekanisme Datang:</label>
                                <input type="text" name="keluhan" class="form-control" placeholder="Contoh: Sesak napas berat, diantar Ambulans / KLL motor" required>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Triase & Tanda Vital -->
                        <div>
                            <div class="form-group">
                                <label>Kategori Triase (Prioritas Kegawatdaruratan):</label>
                                <div class="triage-options">
                                    <label class="triage-option merah">
                                        <input type="radio" name="triase" value="Merah" required>
                                        <div>
                                            <strong style="color: #dc2626; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Merah (Resusitasi)</strong>
                                            <span style="font-size: 11px; color: var(--gray-600);">Mengancam nyawa, butuh penanganan instan</span>
                                        </div>
                                    </label>
                                    <label class="triage-option kuning">
                                        <input type="radio" name="triase" value="Kuning" checked required>
                                        <div>
                                            <strong style="color: #d97706; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Kuning (Urgent)</strong>
                                            <span style="font-size: 11px; color: var(--gray-600);">Darurat, berpotensi mengancam jika ditunda</span>
                                        </div>
                                    </label>
                                    <label class="triage-option hijau">
                                        <input type="radio" name="triase" value="Hijau" required>
                                        <div>
                                            <strong style="color: #16a34a; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Hijau (Non-Darurat)</strong>
                                            <span style="font-size: 11px; color: var(--gray-600);">Luka ringan / kondisi stabil</span>
                                        </div>
                                    </label>
                                    <label class="triage-option hitam">
                                        <input type="radio" name="triase" value="Hitam" required>
                                        <div>
                                            <strong style="color: #334155; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Hitam (Ekspektasi)</strong>
                                            <span style="font-size: 11px; color: var(--gray-600);">Meninggal dunia (DOA) / tidak dapat diselamatkan</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 18px;">
                                <label>Tanda Vital Cepat (Initial Vitals):</label>
                                <div class="vitals-row">
                                    <div>
                                        <input type="text" name="td" class="form-control" placeholder="TD (mmHg)" title="Tekanan Darah">
                                    </div>
                                    <div>
                                        <input type="text" name="nadi" class="form-control" placeholder="Nadi (x/m)" title="Denyut Nadi">
                                    </div>
                                    <div>
                                        <input type="text" name="suhu" class="form-control" placeholder="Suhu (°C)" title="Suhu Tubuh">
                                    </div>
                                    <div>
                                        <input type="text" name="spo2" class="form-control" placeholder="SpO2 (%)" title="Saturasi Oksigen">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn-submit-triage">
                                <i class="fa-solid fa-file-circle-plus"></i> Daftarkan & Masukkan ke Ruang IGD
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- BOARD PASIEN IGD AKTIF -->
            <section class="card-board">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--gray-800); margin: 0;"><i class="fa-solid fa-clipboard-list" style="color: #2563eb;"></i> Daftar Pasien IGD Aktif & Status Triase</h3>
                        <p style="font-size: 12px; color: var(--gray-600); margin: 2px 0 0;">Daftar pasien gawat darurat yang sedang dalam pengawasan medis hari ini</p>
                    </div>
                    <span style="font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px;"><i class="fa-solid fa-users"></i> <?= count($igd_list) ?> Pasien IGD</span>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Antrian / Waktu</th>
                                <th>Nama Pasien / Identitas</th>
                                <th>Status Triase</th>
                                <th>Keluhan Utama & Tanda Vital</th>
                                <th>Status Pelayanan</th>
                                <th style="text-align: right;">Aksi Medis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($igd_list)): ?>
                                <?php foreach ($igd_list as $row): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #2563eb; font-size: 14px;"><?= htmlspecialchars($row['no'] ?? '-') ?></strong><br>
                                        <span style="font-size: 11px; color: var(--gray-400);"><?= htmlspecialchars($row['estimasi'] ?? '-') ?> WIB</span>
                                    </td>
                                    <td>
                                        <strong style="font-size: 14px; color: var(--gray-800); display: block;"><?= htmlspecialchars($row['nama'] ?? '-') ?></strong>
                                        <span style="font-size: 12px; color: var(--gray-600);"><i class="fa-solid fa-hospital-user"></i> <?= htmlspecialchars($row['poli'] ?? 'Instalasi Gawat Darurat (IGD)') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-triase <?= htmlspecialchars($row['triase_color'] ?? 'Kuning') ?>">
                                            <i class="fa-solid fa-circle"></i> <?= htmlspecialchars($row['triase_color'] ?? 'Kuning') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--gray-800);"><?= htmlspecialchars($row['keluhan'] ?? '-') ?></div>
                                        <div style="font-size: 11.5px; color: var(--gray-600); margin-top: 2px;"><i class="fa-solid fa-heart-pulse" style="color: #dc2626;"></i> Vitals: <?= htmlspecialchars($row['vitals'] ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid #bfdbfe;">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Dalam Pemeriksaan
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="?page=emr_dokter&search=<?= urlencode($row['no'] ?? '') ?>" class="btn-action-sm" title="Buka EMR Dokter / SOAP Cepat">
                                            <i class="fa-solid fa-stethoscope"></i> SOAP EMR
                                        </a>
                                        <a href="?page=kasir&no_antrian=<?= urlencode($row['no'] ?? '') ?>&nama=<?= urlencode($row['nama'] ?? '') ?>" class="btn-action-sm" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;" title="Proses Tagihan Kasir">
                                            <i class="fa-solid fa-credit-card"></i> Kasir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 36px; color: var(--gray-400);">
                                        <i class="fa-solid fa-truck-medical" style="font-size: 32px; color: var(--gray-300); margin-bottom: 10px; display: block;"></i>
                                        <span style="font-weight: 600; font-size: 14px;">Belum Ada Pasien IGD Aktif Hari Ini</span><br>
                                        <span style="font-size: 12px;">Gunakan form di atas untuk mendaftarkan pasien gawat darurat baru ke IGD.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
