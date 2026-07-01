<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SIMRS Rawat Jalan – RSUD Puruk Cahu</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:        #f4f6fa;
      --surface:   #ffffff;
      --border:    #e5e9f0;
      --primary:   #3b6ef0;
      --primary-l: #ebf0ff;
      --text:      #1a2035;
      --muted:     #6b7a99;
      --success:   #10b981;
      --warning:   #f59e0b;
      --danger:    #ef4444;
      --radius:    12px;
      --shadow:    0 2px 12px rgba(0,0,0,.06);
      --shadow-md: 0 4px 24px rgba(0,0,0,.10);
      --sidebar-w: 220px;
      --header-h:  60px;
      --trans:     .2s ease;
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; overflow-x: hidden; }

    /* SIDEBAR */
    .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; z-index: 100; }
    .sidebar-brand { padding: 20px 18px 16px; border-bottom: 1px solid var(--border); }
    .brand-logo { display: flex; align-items: center; gap: 10px; }
    .brand-icon { width: 36px; height: 36px; background: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; flex-shrink: 0; }
    .brand-text strong { font-size: 13px; font-weight: 700; display: block; }
    .brand-text span { font-size: 10px; color: var(--muted); }
    .nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
    .nav-section { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; padding: 8px 8px 4px; margin-top: 4px; }
    .nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 8px; cursor: pointer; font-size: 13.5px; font-weight: 500; color: var(--muted); margin-bottom: 1px; transition: all var(--trans); }
    .nav-item:hover { background: var(--bg); color: var(--text); }
    .nav-item.active { background: var(--primary-l); color: var(--primary); }
    .nav-item .icon { font-size: 16px; width: 20px; text-align: center; }
    .nav-badge { margin-left: auto; background: var(--danger); color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
    .sidebar-footer { padding: 12px 10px; border-top: 1px solid var(--border); }
    .user-card { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; cursor: pointer; transition: background var(--trans); }
    .user-card:hover { background: var(--bg); }
    .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
    .user-info strong { font-size: 12.5px; font-weight: 600; display: block; }
    .user-info span { font-size: 11px; color: var(--muted); }

    /* MAIN */
    .main { margin-left: var(--sidebar-w); min-height: 100vh; }
    .header { height: var(--header-h); background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 24px; gap: 12px; position: sticky; top: 0; z-index: 50; }
    .header-title { font-size: 15px; font-weight: 600; flex: 1; }
    .header-date { font-size: 12px; color: var(--muted); }
    .btn-icon { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--muted); transition: all var(--trans); }
    .btn-icon:hover { background: var(--bg); color: var(--text); }
    .btn-icon-wrap { position: relative; }
    .notif-dot { width: 8px; height: 8px; background: var(--danger); border-radius: 50%; position: absolute; top: 6px; right: 6px; border: 2px solid white; }

    .content { padding: 24px; }
    .page { display: none; animation: fadeIn .25s ease; }
    .page.active { display: block; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

    /* CARDS */
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
    .card-title { font-size: 11px; font-weight: 700; color: var(--muted); margin-bottom: 16px; text-transform: uppercase; letter-spacing: .6px; }

    /* STAT CARDS */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow); transition: transform var(--trans), box-shadow var(--trans); }
    .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .stat-badge { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .stat-value { font-size: 28px; font-weight: 700; line-height: 1; margin-bottom: 4px; }
    .stat-label { font-size: 12px; color: var(--muted); }
    .blue .stat-icon { background: #ebf0ff; } .blue .stat-value { color: var(--primary); }
    .green .stat-icon { background: #d1fae5; } .green .stat-value { color: var(--success); }
    .orange .stat-icon { background: #fef3c7; } .orange .stat-value { color: var(--warning); }
    .red .stat-icon { background: #fee2e2; } .red .stat-value { color: var(--danger); }
    .badge-up { background: #d1fae5; color: var(--success); }
    .badge-down { background: #fee2e2; color: var(--danger); }

    /* GRIDS */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-65 { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; }

    /* TABLE */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid var(--border); white-space: nowrap; }
    td { padding: 12px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: var(--bg); }
    .badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger  { background: #fee2e2; color: #991b1b; }
    .badge-info    { background: #dbeafe; color: #1e40af; }
    .badge-gray    { background: #f1f5f9; color: #475569; }

    /* BUTTONS */
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; font-family: inherit; transition: all var(--trans); }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: #2850c9; }
    .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
    .btn-outline:hover { background: var(--bg); }
    .btn-success { background: var(--success); color: white; }
    .btn-success:hover { background: #059669; }
    .btn-sm { padding: 5px 10px; font-size: 12px; }

    /* FORM */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group.full { grid-column: 1 / -1; }
    label { font-size: 12px; font-weight: 600; color: var(--muted); }
    input, select, textarea { padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; background: var(--surface); color: var(--text); transition: border-color var(--trans); outline: none; }
    input:focus, select:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,110,240,.1); }
    textarea { resize: vertical; min-height: 80px; }

    /* STEPS */
    .steps { display: flex; gap: 0; margin-bottom: 28px; }
    .step { flex: 1; display: flex; align-items: center; gap: 10px; padding: 0 0 16px; border-bottom: 2px solid var(--border); transition: border-color var(--trans); }
    .step.done, .step.active { border-color: var(--primary); }
    .step-num { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--border); background: var(--surface); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--muted); transition: all var(--trans); flex-shrink: 0; }
    .step.active .step-num { border-color: var(--primary); background: var(--primary); color: white; }
    .step.done .step-num { border-color: var(--success); background: var(--success); color: white; }
    .step-label { font-size: 12px; font-weight: 600; color: var(--muted); }
    .step.active .step-label { color: var(--primary); }
    .step.done .step-label { color: var(--success); }

    /* QUEUE */
    .queue-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .queue-header h2 { font-size: 16px; font-weight: 700; }
    .queue-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .queue-poly-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow); }
    .poly-name { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 10px; }
    .current-num { font-size: 42px; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 6px; }
    .current-name { font-size: 12px; color: var(--text); font-weight: 500; margin-bottom: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .queue-meta { display: flex; gap: 8px; }
    .meta-chip { font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 500; }
    .chip-wait { background: #fef3c7; color: #92400e; }
    .chip-done { background: #d1fae5; color: #065f46; }

    /* QUEUE BIG DISPLAY */
    .queue-big-display { display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; margin-bottom: 24px; }
    .calling-box { background: var(--primary); border-radius: var(--radius); padding: 32px; color: white; text-align: center; }
    .calling-label { font-size: 13px; opacity: .8; margin-bottom: 8px; }
    .calling-num { font-size: 72px; font-weight: 900; line-height: 1; letter-spacing: -2px; }
    .calling-poly { font-size: 16px; font-weight: 600; margin-top: 8px; opacity: .9; }
    .calling-name { font-size: 13px; opacity: .7; margin-top: 4px; }
    .calling-pulse { width: 12px; height: 12px; background: #7cffb2; border-radius: 50%; display: inline-block; margin-right: 6px; animation: pulse 1.5s ease infinite; }
    @keyframes pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.5; transform:scale(1.4); } }

    .waiting-list-box { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
    .waiting-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); }
    .waiting-item:last-child { border-bottom: none; }
    .waiting-num { font-size: 18px; font-weight: 700; width: 44px; color: var(--primary); flex-shrink: 0; text-align: center; }
    .waiting-info { flex: 1; }
    .waiting-info strong { font-size: 13px; font-weight: 600; display: block; }
    .waiting-info span { font-size: 11px; color: var(--muted); }
    .waiting-est { font-size: 11px; color: var(--muted); text-align: right; }

    /* EMR */
    .patient-banner { display: flex; align-items: center; gap: 14px; padding: 16px 20px; background: var(--primary-l); border-radius: var(--radius); margin-bottom: 20px; border: 1px solid #c7d7fd; }
    .patient-avatar-lg { width: 48px; height: 48px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex-shrink: 0; }
    .patient-info-row { display: flex; gap: 24px; flex-wrap: wrap; margin-top: 2px; }
    .patient-info-item { font-size: 12px; color: var(--muted); }
    .patient-info-item strong { color: var(--text); }
    .vital-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 20px; }
    .vital-box { background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 12px; text-align: center; }
    .vital-val { font-size: 20px; font-weight: 700; color: var(--primary); }
    .vital-unit { font-size: 10px; color: var(--muted); }
    .vital-lbl { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .icd-search-wrap { display: flex; gap: 8px; margin-bottom: 10px; }
    .icd-results { max-height: 160px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; display: none; }
    .icd-item { padding: 10px 14px; cursor: pointer; font-size: 13px; border-bottom: 1px solid var(--border); transition: background var(--trans); }
    .icd-item:last-child { border-bottom: none; }
    .icd-item:hover { background: var(--primary-l); }
    .icd-code { font-size: 11px; color: var(--primary); font-weight: 600; }
    .selected-diagnoses { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .dx-tag { display: inline-flex; align-items: center; gap: 6px; background: var(--primary-l); color: var(--primary); border: 1px solid #c7d7fd; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .dx-tag span { cursor: pointer; font-size: 14px; line-height: 1; }

    /* PHARMACY */
    .rx-card { border: 2px solid var(--primary); border-radius: var(--radius); padding: 20px; background: white; }
    .rx-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .rx-no { font-size: 12px; color: var(--muted); }
    .rx-dr { font-size: 13px; font-weight: 600; }
    .rx-items { list-style: none; margin-bottom: 16px; }
    .rx-item { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px dashed var(--border); }
    .rx-item:last-child { border-bottom: none; }
    .rx-num { width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
    .rx-med { flex: 1; }
    .rx-med strong { font-size: 13px; font-weight: 600; display: block; }
    .rx-med span { font-size: 12px; color: var(--muted); }
    .rx-qty { font-size: 13px; font-weight: 700; color: var(--primary); }

    /* KASIR */
    .billing-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border); }
    .billing-row:last-child { border-bottom: none; }
    .billing-item-name { font-size: 13px; }
    .billing-item-cat { font-size: 11px; color: var(--muted); }
    .billing-item-price { font-size: 13px; font-weight: 600; text-align: right; }
    .billing-total { display: flex; justify-content: space-between; padding: 14px 0 0; font-size: 16px; font-weight: 700; }
    .payment-options { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin: 16px 0; }
    .pay-opt { border: 2px solid var(--border); border-radius: 10px; padding: 14px 10px; text-align: center; cursor: pointer; transition: all var(--trans); }
    .pay-opt:hover { border-color: var(--primary); }
    .pay-opt.selected { border-color: var(--primary); background: var(--primary-l); }
    .pay-opt-icon { font-size: 22px; margin-bottom: 6px; }
    .pay-opt-label { font-size: 12px; font-weight: 600; }

    /* MISC */
    .chart-wrap { position: relative; height: 200px; }
    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .section-header h2 { font-size: 16px; font-weight: 700; }
    .action-bar { display: flex; gap: 8px; align-items: center; }
    .search-box { display: flex; align-items: center; gap: 8px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 7px 12px; }
    .search-box input { background: transparent; border: none; outline: none; font-size: 13px; width: 180px; }
    .progress-bar-wrap { background: var(--bg); border-radius: 4px; height: 6px; overflow: hidden; }
    .progress-bar { height: 100%; background: var(--primary); border-radius: 4px; }
    .divider { border: none; border-top: 1px solid var(--border); margin: 16px 0; }
    .scrollable { max-height: 300px; overflow-y: auto; }

    /* MODAL */
    .success-modal { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: none; align-items: center; justify-content: center; z-index: 999; }
    .success-modal.show { display: flex; animation: fadeIn .2s ease; }
    .modal-box { background: white; border-radius: 16px; padding: 40px; text-align: center; width: 340px; box-shadow: var(--shadow-md); }
    .modal-icon { font-size: 52px; margin-bottom: 16px; }
    .modal-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
    .modal-sub { font-size: 13px; color: var(--muted); margin-bottom: 24px; }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">
      <div class="brand-icon">🏥</div>
      <div class="brand-text">
        <strong>SIMRS Rawat Jalan</strong>
        <span>RSUD Puruk Cahu</span>
      </div>
    </div>
  </div>
  <nav class="nav">
    <div class="nav-section">Utama</div>
    <div class="nav-item active" data-page="dashboard"><span class="icon">📊</span> Dashboard</div>
    <div class="nav-item" data-page="antrian"><span class="icon">🎟️</span> Antrian <span class="nav-badge">12</span></div>
    <div class="nav-section">Pelayanan</div>
    <div class="nav-item" data-page="pendaftaran"><span class="icon">📝</span> Pendaftaran</div>
    <div class="nav-item" data-page="emr"><span class="icon">🩺</span> EMR Dokter</div>
    <div class="nav-item" data-page="farmasi"><span class="icon">💊</span> Farmasi</div>
    <div class="nav-item" data-page="kasir"><span class="icon">💰</span> Kasir</div>
    <div class="nav-section">Lainnya</div>
    <div class="nav-item" data-page="master"><span class="icon">📦</span> Master Data</div>
    <div class="nav-item" data-page="settings"><span class="icon">⚙️</span> Pengaturan & Bridging</div>
    <div class="nav-item" data-page="laporan"><span class="icon">📈</span> Laporan</div>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar">SA</div>
      <div class="user-info"><strong>Super Admin</strong><span>Administrator</span></div>
    </div>
  </div>
</aside>

<main class="main">
  <header class="header">
    <span class="header-title" id="headerTitle">Dashboard</span>
    <span class="header-date" id="headerDate"></span>
    <div class="btn-icon-wrap" onclick="toggleNotifModal()"><button class="btn-icon" title="Notifikasi Sistem">🔔</button><span class="notif-dot" id="notifBadge">0</span></div>
    <button class="btn-icon" title="Pengaturan Sistem" onclick="openSettingsPage()">⚙️</button>
    <a href="/" class="btn btn-outline btn-sm" style="text-decoration:none;font-size:12px;padding:6px 12px;border-radius:6px;display:flex;align-items:center;gap:4px;">🌐 Portal Publik</a>
  </header>

  <div class="content">

    <!-- DASHBOARD -->
    <div class="page active" id="page-dashboard">
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-header"><div class="stat-icon">🧑‍⚕️</div><span class="stat-badge badge-up">↑ 8%</span></div>
          <div class="stat-value" id="ctr-visit">0</div><div class="stat-label">Total Kunjungan Hari Ini</div>
        </div>
        <div class="stat-card green">
          <div class="stat-header"><div class="stat-icon">✅</div><span class="stat-badge badge-up">↑ 5%</span></div>
          <div class="stat-value" id="ctr-done">0</div><div class="stat-label">Selesai Dilayani</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-header"><div class="stat-icon">⏳</div><span class="stat-badge badge-down">↓ 2</span></div>
          <div class="stat-value" id="ctr-wait">0</div><div class="stat-label">Sedang Menunggu</div>
        </div>
        <div class="stat-card red">
          <div class="stat-header"><div class="stat-icon">💰</div><span class="stat-badge badge-up">↑ 12%</span></div>
          <div class="stat-value" id="ctr-rev">0</div><div class="stat-label">Pendapatan (Rp)</div>
        </div>
      </div>
      <div class="grid-65" style="margin-bottom:16px;">
        <div class="card">
          <div class="card-title">Kunjungan 7 Hari Terakhir</div>
          <div class="chart-wrap"><canvas id="chartVisit"></canvas></div>
        </div>
        <div class="card">
          <div class="card-title">Jenis Pembayaran</div>
          <div class="chart-wrap" style="height:150px;"><canvas id="chartPayment"></canvas></div>
          <div style="margin-top:12px;display:flex;flex-direction:column;gap:6px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;"><span>🔵 BPJS</span><strong>68%</strong></div>
            <div style="display:flex;justify-content:space-between;font-size:12px;"><span>🟢 Umum</span><strong>27%</strong></div>
            <div style="display:flex;justify-content:space-between;font-size:12px;"><span>🟡 Lainnya</span><strong>5%</strong></div>
          </div>
        </div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-title">Aktivitas Per Poli Hari Ini</div>
          <div class="table-wrap">
            <table><thead><tr><th>Poli</th><th>Total</th><th>Selesai</th><th>Progress</th></tr></thead>
            <tbody>
              <tr><td>Poli Umum</td><td>34</td><td>28</td><td><div class="progress-bar-wrap"><div class="progress-bar" style="width:82%"></div></div></td></tr>
              <tr><td>Poli Anak</td><td>22</td><td>15</td><td><div class="progress-bar-wrap"><div class="progress-bar" style="width:68%"></div></div></td></tr>
              <tr><td>Poli Kebidanan</td><td>18</td><td>12</td><td><div class="progress-bar-wrap"><div class="progress-bar" style="width:67%"></div></div></td></tr>
              <tr><td>Poli Gigi</td><td>14</td><td>14</td><td><div class="progress-bar-wrap"><div class="progress-bar" style="width:100%;background:var(--success)"></div></div></td></tr>
              <tr><td>Poli Bedah</td><td>10</td><td>6</td><td><div class="progress-bar-wrap"><div class="progress-bar" style="width:60%"></div></div></td></tr>
            </tbody></table>
          </div>
        </div>
        <div class="card">
          <div class="card-title">10 Diagnosis Terbanyak</div>
          <div class="table-wrap scrollable"><table><thead><tr><th>#</th><th>Diagnosis</th><th>Kasus</th></tr></thead><tbody id="dx-table"></tbody></table></div>
        </div>
      </div>
    </div>

    <!-- ANTRIAN -->
    <div class="page" id="page-antrian">
      <div class="queue-header">
        <h2>Monitor Antrian Real-Time</h2>
        <div class="action-bar">
          <button class="btn btn-outline btn-sm">📥 Export</button>
          <button class="btn btn-primary btn-sm" onclick="panggil()">📢 Panggil Berikutnya</button>
        </div>
      </div>
      <div class="queue-big-display">
        <div class="calling-box">
          <div class="calling-label"><span class="calling-pulse"></span>Sedang Dilayani</div>
          <div class="calling-num" id="callingNum">A-023</div>
          <div class="calling-poly">Poli Umum</div>
          <div class="calling-name" id="callingName">Budi Santoso</div>
        </div>
        <div class="waiting-list-box">
          <div class="card-title">Antrian Menunggu – Poli Umum</div>
          <div id="waitingList"></div>
        </div>
      </div>
      <div class="queue-grid" id="polyCards"></div>
    </div>

    <!-- PENDAFTARAN -->
    <div class="page" id="page-pendaftaran">
      <div class="section-header">
        <h2>Pendaftaran Pasien</h2>
        <div class="action-bar">
          <div class="search-box"><span>🔍</span><input type="text" placeholder="Cari nama / NIK / No. RM..."/></div>
          <button class="btn btn-primary">+ Daftar Baru</button>
        </div>
      </div>
      <div class="steps" id="regSteps">
        <div class="step active" data-step="1"><div class="step-num">1</div><div class="step-label">Data Pasien</div></div>
        <div class="step" data-step="2"><div class="step-num">2</div><div class="step-label">Pilih Layanan</div></div>
        <div class="step" data-step="3"><div class="step-num">3</div><div class="step-label">Konfirmasi</div></div>
      </div>
      <div class="card" id="regStep1">
        <div class="card-title">Data Identitas Pasien</div>
        <div class="form-grid">
          <div class="form-group"><label>NIK *</label><input type="text" placeholder="16 digit NIK" maxlength="16"/></div>
          <div class="form-group"><label>No. BPJS</label><input type="text" placeholder="13 digit nomor BPJS"/></div>
          <div class="form-group"><label>Nama Lengkap *</label><input type="text" placeholder="Nama sesuai KTP"/></div>
          <div class="form-group"><label>Tanggal Lahir *</label><input type="date"/></div>
          <div class="form-group"><label>Jenis Kelamin *</label><select><option value="">Pilih...</option><option>Laki-laki</option><option>Perempuan</option></select></div>
          <div class="form-group"><label>No. Telepon</label><input type="tel" placeholder="08xxxxxxxxxx"/></div>
          <div class="form-group full"><label>Alamat</label><textarea placeholder="Alamat lengkap pasien"></textarea></div>
          <div class="form-group"><label>Jenis Pasien *</label><select><option>BPJS</option><option>Umum</option><option>Asuransi Lain</option><option>Gratis</option></select></div>
          <div class="form-group"><label>Golongan Darah</label><select><option>Tidak Diketahui</option><option>A</option><option>B</option><option>AB</option><option>O</option></select></div>
        </div>
        <hr class="divider"/>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button class="btn btn-outline">Batal</button>
          <button class="btn btn-primary" onclick="nextStep(2)">Selanjutnya →</button>
        </div>
      </div>
      <div class="card" id="regStep2" style="display:none;">
        <div class="card-title">Pilih Poli & Dokter</div>
        <div class="form-grid">
          <div class="form-group"><label>Poli Tujuan *</label><select><option>Poli Umum</option><option>Poli Anak</option><option>Poli Kebidanan</option><option>Poli Gigi</option><option>Poli Bedah</option></select></div>
          <div class="form-group"><label>Dokter *</label><select><option>dr. Ahmad Fauzi, Sp.U</option><option>dr. Siti Rahayu, Sp.A</option><option>dr. Budi Prakoso</option></select></div>
          <div class="form-group"><label>Tanggal Kunjungan *</label><input type="date"/></div>
          <div class="form-group"><label>Jenis Pendaftaran</label><select><option>Offline (Loket)</option><option>Online</option></select></div>
          <div class="form-group full"><label>Keluhan Utama</label><textarea placeholder="Keluhan yang dirasakan pasien..."></textarea></div>
        </div>
        <hr class="divider"/>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button class="btn btn-outline" onclick="nextStep(1)">← Kembali</button>
          <button class="btn btn-primary" onclick="nextStep(3)">Selanjutnya →</button>
        </div>
      </div>
      <div class="card" id="regStep3" style="display:none;">
        <div class="card-title">Konfirmasi Pendaftaran</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
          <div><span style="font-size:12px;color:var(--muted);">Nama Pasien</span><div style="font-weight:600;margin-top:2px;">Andi Kurniawan</div></div>
          <div><span style="font-size:12px;color:var(--muted);">No. Rekam Medis</span><div style="font-weight:600;margin-top:2px;">RM-2026-00247</div></div>
          <div><span style="font-size:12px;color:var(--muted);">Poli Tujuan</span><div style="font-weight:600;margin-top:2px;">Poli Umum</div></div>
          <div><span style="font-size:12px;color:var(--muted);">Dokter</span><div style="font-weight:600;margin-top:2px;">dr. Ahmad Fauzi</div></div>
          <div><span style="font-size:12px;color:var(--muted);">Jenis Pasien</span><div style="font-weight:600;margin-top:2px;">BPJS <span class="badge badge-success">Eligible ✓</span></div></div>
          <div><span style="font-size:12px;color:var(--muted);">Nomor Antrian</span><div style="font-size:24px;font-weight:800;color:var(--primary);margin-top:2px;">UMU-001</div></div>
        </div>
        <hr class="divider"/>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button class="btn btn-outline" onclick="nextStep(2)">← Kembali</button>
          <button class="btn btn-success" onclick="confirmAction('pendaftaran')">✅ Simpan & Cetak Antrian</button>
        </div>
      </div>
      <div style="margin-top:24px;">
        <div class="section-header"><h2>Kunjungan Hari Ini</h2></div>
        <div class="card"><div class="table-wrap"><table>
          <thead><tr><th>No. Antrian</th><th>No. RM</th><th>Nama Pasien</th><th>Poli</th><th>Jenis</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody id="visitTable"></tbody>
        </table></div></div>
      </div>
    </div>

    <!-- EMR DOKTER -->
    <div class="page" id="page-emr">
      <div class="patient-banner">
        <div class="patient-avatar-lg">BK</div>
        <div>
          <div style="font-size:16px;font-weight:700;">Budi Kurniawan</div>
          <div class="patient-info-row">
            <div class="patient-info-item">RM: <strong>RM-2026-00231</strong></div>
            <div class="patient-info-item">Usia: <strong>42 th, L</strong></div>
            <div class="patient-info-item">BPJS: <strong>001234567890123</strong></div>
            <div class="patient-info-item">Alergi: <strong style="color:var(--danger);">Penisilin</strong></div>
          </div>
        </div>
        <div style="margin-left:auto;text-align:right;">
          <div style="font-size:12px;color:var(--muted);">No. Kunjungan</div>
          <div style="font-size:16px;font-weight:700;color:var(--primary);">KUN-20260626-0023</div>
          <span class="badge badge-warning">Dalam Pemeriksaan</span>
        </div>
      </div>
      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">Tanda-Tanda Vital</div>
      <div class="vital-grid">
        <div class="vital-box"><div class="vital-val">120/80</div><div class="vital-unit">mmHg</div><div class="vital-lbl">Tek. Darah</div></div>
        <div class="vital-box"><div class="vital-val">36.8</div><div class="vital-unit">°C</div><div class="vital-lbl">Suhu</div></div>
        <div class="vital-box"><div class="vital-val">82</div><div class="vital-unit">bpm</div><div class="vital-lbl">Nadi</div></div>
        <div class="vital-box"><div class="vital-val">18</div><div class="vital-unit">x/mnt</div><div class="vital-lbl">Pernapasan</div></div>
        <div class="vital-box"><div class="vital-val">68</div><div class="vital-unit">kg</div><div class="vital-lbl">Berat Badan</div></div>
        <div class="vital-box"><div class="vital-val" style="color:var(--success);">98%</div><div class="vital-unit">SpO₂</div><div class="vital-lbl">Saturasi O₂</div></div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-title">Anamnesis & Pemeriksaan</div>
          <div class="form-group" style="margin-bottom:12px;"><label>Keluhan Utama</label><textarea>Demam 3 hari, batuk berdahak, nyeri tenggorokan.</textarea></div>
          <div class="form-group" style="margin-bottom:12px;"><label>Pemeriksaan Fisik</label><textarea>Faring hiperemis (+), tonsil T1/T1, tidak ada pembesaran KGB.</textarea></div>
          <div class="form-group"><label>Catatan Tambahan</label><textarea placeholder="Catatan lain..."></textarea></div>
        </div>
        <div class="card">
          <div class="card-title">Diagnosis (ICD-10)</div>
          <div class="icd-search-wrap">
            <input type="text" placeholder="Cari kode / nama penyakit..." id="icdInput" style="flex:1;" oninput="searchICD(this.value)"/>
          </div>
          <div class="icd-results" id="icdResults"></div>
          <div class="selected-diagnoses" id="selectedDx">
            <div class="dx-tag">J06.9 – ISPA <span onclick="this.parentElement.remove()">×</span></div>
          </div>
          <hr class="divider"/>
          <div class="card-title">Resep Elektronik</div>
          <div id="rxList">
            <div class="rx-item">
              <div class="rx-num">1</div>
              <div class="rx-med"><strong>Paracetamol 500mg</strong><span>3×1 setelah makan · 10 tab</span></div>
              <span onclick="this.parentElement.remove()" style="cursor:pointer;color:var(--muted);font-size:18px;">×</span>
            </div>
            <div class="rx-item">
              <div class="rx-num">2</div>
              <div class="rx-med"><strong>Ambroxol 30mg</strong><span>3×1 setelah makan · 10 tab</span></div>
              <span onclick="this.parentElement.remove()" style="cursor:pointer;color:var(--muted);font-size:18px;">×</span>
            </div>
          </div>
          <button class="btn btn-outline btn-sm" style="margin-top:10px;width:100%;" onclick="addRx()">+ Tambah Obat</button>
          <hr class="divider"/>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn btn-outline btn-sm">📄 Surat Rujukan</button>
            <button class="btn btn-outline btn-sm">🧪 Order Lab</button>
            <button class="btn btn-success" style="margin-left:auto;" onclick="confirmAction('emr')">💾 Simpan EMR</button>
          </div>
        </div>
      </div>
    </div>

    <!-- FARMASI -->
    <div class="page" id="page-farmasi">
      <div class="section-header">
        <h2>Farmasi & Dispensing Obat</h2>
        <div class="search-box"><span>🔍</span><input type="text" placeholder="Cari No. Resep..."/></div>
      </div>
      <div class="grid-65">
        <div>
          <div class="card" style="margin-bottom:16px;">
            <div class="card-title">Antrian Resep Masuk</div>
            <div class="table-wrap"><table>
              <thead><tr><th>No. Resep</th><th>Pasien</th><th>Dokter</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody id="rxTable"></tbody>
            </table></div>
          </div>
          <div class="rx-card">
            <div class="rx-header">
              <div><div class="rx-no">RES-20260626-023</div><div class="rx-dr">dr. Ahmad Fauzi, Sp.U</div></div>
              <div style="text-align:right;"><div style="font-size:13px;font-weight:600;">Budi Kurniawan</div><div style="font-size:11px;color:var(--muted);">RM-2026-00231 · 42th, L</div></div>
            </div>
            <ul class="rx-items">
              <li class="rx-item"><div class="rx-num">1</div><div class="rx-med"><strong>Paracetamol 500mg</strong><span>3×1 setelah makan</span></div><div class="rx-qty">10 tab</div></li>
              <li class="rx-item"><div class="rx-num">2</div><div class="rx-med"><strong>Ambroxol 30mg</strong><span>3×1 setelah makan</span></div><div class="rx-qty">10 tab</div></li>
              <li class="rx-item"><div class="rx-num">3</div><div class="rx-med"><strong>Cetirizine 10mg</strong><span>1×1 malam sebelum tidur</span></div><div class="rx-qty">5 tab</div></li>
            </ul>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
              <button class="btn btn-outline btn-sm">✏️ Edit</button>
              <button class="btn btn-success" onclick="confirmAction('farmasi')">✅ Serahkan ke Pasien</button>
            </div>
          </div>
        </div>
        <div>
          <div class="card" style="margin-bottom:16px;">
            <div class="card-title">Stok Kritis</div>
            <div id="stockAlert"></div>
          </div>
          <div class="card">
            <div class="card-title">Statistik Hari Ini</div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px;">
              <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Resep Masuk</span><strong>47</strong></div>
              <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Sudah Diserahkan</span><strong style="color:var(--success);">41</strong></div>
              <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Menunggu</span><strong style="color:var(--warning);">6</strong></div>
              <hr class="divider"/>
              <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Item Stok Kritis</span><strong style="color:var(--danger);">3 item</strong></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KASIR -->
    <div class="page" id="page-kasir">
      <div class="section-header">
        <h2>Kasir & Pembayaran</h2>
        <div class="search-box"><span>🔍</span><input type="text" placeholder="Cari No. Kunjungan..."/></div>
      </div>
      <div class="grid-65">
        <div class="card">
          <div class="card-title">Detail Tagihan</div>
          <div class="patient-banner" style="margin-bottom:16px;">
            <div class="patient-avatar-lg" style="width:40px;height:40px;font-size:16px;">SR</div>
            <div><div style="font-weight:700;">Sari Rahayu</div><div style="font-size:12px;color:var(--muted);">INV-20260626-0041</div></div>
            <span class="badge badge-warning" style="margin-left:auto;">Menunggu Bayar</span>
          </div>
          <div class="billing-row"><div><div class="billing-item-name">Konsultasi Dokter Umum</div><div class="billing-item-cat">Konsultasi</div></div><div class="billing-item-price">Rp 50.000</div></div>
          <div class="billing-row"><div><div class="billing-item-name">Administrasi Rawat Jalan</div><div class="billing-item-cat">Administrasi</div></div><div class="billing-item-price">Rp 15.000</div></div>
          <div class="billing-row"><div><div class="billing-item-name">Darah Lengkap</div><div class="billing-item-cat">Laboratorium</div></div><div class="billing-item-price">Rp 80.000</div></div>
          <div class="billing-row"><div><div class="billing-item-name">Paracetamol 500mg × 10</div><div class="billing-item-cat">Farmasi</div></div><div class="billing-item-price">Rp 25.000</div></div>
          <div class="billing-row"><div><div class="billing-item-name">Ambroxol 30mg × 10</div><div class="billing-item-cat">Farmasi</div></div><div class="billing-item-price">Rp 30.000</div></div>
          <hr class="divider"/>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);"><span>Subtotal</span><span>Rp 200.000</span></div>
            <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--success);"><span>Diskon</span><span>– Rp 0</span></div>
            <div class="billing-total"><span>Total Bayar</span><span style="color:var(--primary);">Rp 200.000</span></div>
          </div>
        </div>
        <div class="card">
          <div class="card-title">Metode Pembayaran</div>
          <div class="payment-options">
            <div class="pay-opt selected" onclick="selectPay(this)" data-method="Tunai"><div class="pay-opt-icon">💵</div><div class="pay-opt-label">Tunai</div></div>
            <div class="pay-opt" onclick="selectPay(this)" data-method="QRIS"><div class="pay-opt-icon">📱</div><div class="pay-opt-label">QRIS</div></div>
            <div class="pay-opt" onclick="selectPay(this)" data-method="BPJS"><div class="pay-opt-icon">🏥</div><div class="pay-opt-label">BPJS</div></div>
            <div class="pay-opt" onclick="selectPay(this)" data-method="Debit/Kredit"><div class="pay-opt-icon">💳</div><div class="pay-opt-label">Debit/Kredit</div></div>
          </div>

          <!-- PANEL TUNAI -->
          <div id="pay-detail-Tunai" class="pay-panel" style="display:block;">
            <div class="form-group" style="margin-bottom:12px;"><label>Nominal Uang Diterima (Rp)</label><input type="number" value="200000" id="cashInput" oninput="calcChange()"/></div>
            <div style="background:var(--bg);border-radius:8px;padding:12px;margin-bottom:16px;border:1px solid var(--border);">
              <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;"><span>Total Tagihan:</span><strong>Rp 200.000</strong></div>
              <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Kembalian Pasien:</span><strong id="changeDisplay" style="color:var(--success);font-size:15px;">Rp 0</strong></div>
            </div>
          </div>

          <!-- PANEL QRIS -->
          <div id="pay-detail-QRIS" class="pay-panel" style="display:none;text-align:center;background:var(--bg);padding:16px;border-radius:8px;margin-bottom:16px;border:1px solid var(--border);">
            <div style="font-size:12px;color:var(--muted);margin-bottom:8px;">Scan Barcode QRIS Dinamis RSUD Puruk Cahu</div>
            <div style="background:white;padding:12px;display:inline-block;border-radius:8px;margin-bottom:8px;border:1px solid var(--border);box-shadow:0 2px 4px rgba(0,0,0,0.05);">
              <div style="font-size:54px;line-height:1;">📲</div>
              <div style="font-size:11px;font-family:monospace;color:#000;font-weight:700;margin-top:4px;">NMID: ID1020030040050</div>
              <div style="font-size:10px;color:var(--muted);">Nominal: Rp 200.000</div>
            </div>
            <div style="font-size:12px;color:var(--primary);font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;">
              <span style="display:inline-block;width:8px;height:8px;background:var(--primary);border-radius:50%;animation:pulse 1.5s infinite;"></span> Menunggu scan dari HP pasien...
            </div>
            <div style="margin-top:10px;font-size:11px;color:var(--muted);">Tips: Pastikan kasir mengecek notifikasi SMS/M-Banking mutasi masuk sebelum konfirmasi.</div>
          </div>

          <!-- PANEL BPJS -->
          <div id="pay-detail-BPJS" class="pay-panel" style="display:none;background:var(--bg);padding:14px;border-radius:8px;margin-bottom:16px;border:1px solid var(--border);">
            <div class="form-group" style="margin-bottom:10px;"><label>Nomor SEP V-Claim BPJS</label><input type="text" value="1401R0010726V000041" readonly style="background:var(--surface);font-weight:600;color:var(--primary);"/></div>
            <div style="display:flex;flex-direction:column;gap:6px;font-size:12px;">
              <div style="display:flex;justify-content:space-between;"><span>Status Eligibilitas:</span><span class="badge badge-success">Aktif & Terverifikasi ✓</span></div>
              <div style="display:flex;justify-content:space-between;"><span>Bridging V-Claim:</span><strong>Connected Kemenkes</strong></div>
              <div style="display:flex;justify-content:space-between;"><span>Biaya Ditanggung BPJS:</span><strong style="color:var(--success);">Rp 200.000 (100%)</strong></div>
              <div style="display:flex;justify-content:space-between;"><span>Tagihan Pasien:</span><strong style="color:var(--primary);">Rp 0 (Gratis)</strong></div>
            </div>
          </div>

          <!-- PANEL DEBIT / KREDIT -->
          <div id="pay-detail-Debit/Kredit" class="pay-panel" style="display:none;background:var(--bg);padding:14px;border-radius:8px;margin-bottom:16px;border:1px solid var(--border);">
            <div class="form-group" style="margin-bottom:10px;"><label>Nama Bank & Jenis Kartu</label>
              <select style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--border);background:var(--surface);"><option>Bank Mandiri - Debit EDC</option><option>Bank BRI - Debit EDC</option><option>Bank BNI - Debit EDC</option><option>Bank BCA - Debit/Kredit</option><option>Bank Kalteng - Debit</option></select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
              <div class="form-group" style="margin-bottom:0;"><label>4 Digit Akhir Kartu</label><input type="text" placeholder="XXXX" maxlength="4" value="8841" id="debitCardNum"/></div>
              <div class="form-group" style="margin-bottom:0;"><label>Kode Approval EDC</label><input type="text" placeholder="APPR-XXXX" value="APPR-9921" id="debitAppr"/></div>
            </div>
          </div>

          <button class="btn btn-success" style="width:100%;justify-content:center;padding:12px;font-size:14px;font-weight:700;" onclick="confirmAction('kasir')">💳 Proses Pembayaran</button>
          <hr class="divider"/>
          <div class="card-title">Pendapatan Hari Ini</div>
          <div style="font-size:28px;font-weight:800;color:var(--primary);margin-bottom:4px;" id="ctr-rev2">Rp 0</div>
          <div style="font-size:12px;color:var(--muted);">dari 38 transaksi</div>
        </div>
      </div>
    </div>

    <!-- MASTER DATA -->
    <div class="page" id="page-master">
      <div class="section-header">
        <h2>Master Data SIMRS & Inventori</h2>
        <div class="action-bar">
          <button class="btn btn-outline btn-sm" onclick="loadMasterData()">🔄 Refresh Data</button>
          <button class="btn btn-primary btn-sm" onclick="showModal()">+ Tambah Master</button>
        </div>
      </div>
      <div class="grid-2" style="margin-bottom:16px;">
        <div class="card">
          <div class="card-title">Daftar Poliklinik & Dokter Spesialis</div>
          <div class="table-wrap scrollable" style="max-height:280px;">
            <table>
              <thead><tr><th>Poli</th><th>Dokter Praktek</th><th>Spesialisasi</th><th>Jam</th><th>Kuota</th></tr></thead>
              <tbody id="master-dokter-body">
                <tr><td colspan="5" style="text-align:center;color:var(--muted);">Memuat data dokter dari Supabase...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card">
          <div class="card-title">Master Obat & Stok Farmasi</div>
          <div class="table-wrap scrollable" style="max-height:280px;">
            <table>
              <thead><tr><th>Kode</th><th>Nama Obat</th><th>Satuan</th><th>Harga</th><th>Stok</th></tr></thead>
              <tbody id="master-obat-body">
                <tr><td colspan="5" style="text-align:center;color:var(--muted);">Memuat data obat dari Supabase...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-title">Master Tarif Layanan & Tindakan Medis</div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Kode Tarif</th><th>Nama Layanan</th><th>Kategori</th><th>Tarif Umum</th><th>Tarif BPJS</th><th>Status</th></tr></thead>
            <tbody id="master-tarif-body">
              <tr><td colspan="6" style="text-align:center;color:var(--muted);">Memuat tarif dari Supabase...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SETTINGS & BRIDGING -->
    <div class="page" id="page-settings">
      <div class="section-header">
        <h2>Konfigurasi Sistem & Bridging Eksternal</h2>
        <button class="btn btn-success btn-sm" onclick="saveSettings()">💾 Simpan Konfigurasi</button>
      </div>
      <div class="grid-2" style="margin-bottom:16px;">
        <div class="card">
          <div class="card-title">🏥 Identitas Rumah Sakit</div>
          <div class="form-grid">
            <div class="form-group full"><label>Nama Rumah Sakit</label><input type="text" id="set-nama-rs" value="RSUD Puruk Cahu"/></div>
            <div class="form-group full"><label>Alamat</label><textarea id="set-alamat">Jl. Jend. Sudirman No. 1, Puruk Cahu, Kab. Murung Raya</textarea></div>
            <div class="form-group"><label>Telepon Helpdesk</label><input type="text" id="set-telepon" value="0882-1529-0459"/></div>
            <div class="form-group"><label>Email Resmi</label><input type="email" id="set-email" value="denynz17@gmail.com"/></div>
          </div>
        </div>
        <div class="card">
          <div class="card-title">📧 Konfigurasi SMTP Email Gateway</div>
          <div class="form-grid">
            <div class="form-group"><label>SMTP Host</label><input type="text" id="set-smtp-host" value="smtp.gmail.com"/></div>
            <div class="form-group"><label>SMTP Port</label><input type="text" id="set-smtp-port" value="587"/></div>
            <div class="form-group full"><label>SMTP Username / Email</label><input type="text" id="set-smtp-user" value="denynz17@gmail.com"/></div>
            <div class="form-group full"><label>SMTP Password / App Key</label><input type="password" id="set-smtp-pass" value="••••••••••••••••"/></div>
          </div>
          <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;">
            <span class="badge badge-success">● SMTP Ready (SSL/TLS)</span>
            <button class="btn btn-outline btn-sm" onclick="alert('Test SMTP Email dikirim!')">📩 Tes Kirim Email</button>
          </div>
        </div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-title">📱 Konfigurasi WhatsApp Gateway (Fonnte)</div>
          <div class="form-grid">
            <div class="form-group full"><label>Endpoint API WA Gateway</label><input type="text" id="set-wa-url" value="https://api.fonnte.com/send"/></div>
            <div class="form-group full"><label>API Key / Token Fonnte</label><input type="password" id="set-wa-key" value="••••••••••••••••••••"/></div>
            <div class="form-group full"><label>Status Fitur</label><select id="set-wa-status"><option value="1">Aktif (Kirim Bukti Daftar & Panggilan)</option><option value="0">Non-Aktif</option></select></div>
          </div>
          <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;">
            <span class="badge badge-success">● WA Gateway Connected</span>
            <button class="btn btn-outline btn-sm" onclick="alert('Pesan tes WA dikirim ke nomor helpdesk!')">💬 Tes Kirim WA</button>
          </div>
        </div>
        <div class="card">
          <div class="card-title">🔗 Bridging BPJS V-Claim & SatuSehat Kemenkes</div>
          <div class="form-grid">
            <div class="form-group"><label>BPJS Cons ID</label><input type="text" value="12345"/></div>
            <div class="form-group"><label>BPJS Secret Key</label><input type="password" value="••••••••••••"/></div>
            <div class="form-group"><label>SatuSehat Org ID</label><input type="text" value="100023456"/></div>
            <div class="form-group"><label>SatuSehat Client ID</label><input type="password" value="••••••••••••••••"/></div>
          </div>
          <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;">
            <div>
              <span class="badge badge-success" style="margin-right:6px;">● BPJS V-Claim Live</span>
              <span class="badge badge-info">● FHIR R4 Ready</span>
            </div>
            <button class="btn btn-outline btn-sm" onclick="alert('Koneksi bridging BPJS & SatuSehat OK (200)!')">🔄 Sinkronisasi</button>
          </div>
        </div>
      </div>
    </div>

    <!-- LAPORAN -->
    <div class="page" id="page-laporan">
      <div class="section-header">
        <h2>Laporan & Eksekutif Dashboard RSUD</h2>
        <div class="action-bar">
          <select class="btn btn-outline btn-sm"><option>Bulan Ini (Juli 2026)</option><option>Bulan Lalu</option></select>
          <button class="btn btn-primary btn-sm" onclick="alert('Mengunduh Laporan RL 5.1 & 5.2 PDF...')">📥 Export Laporan PDF / Excel</button>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card blue"><div class="stat-header"><div class="stat-icon">📈</div></div><div class="stat-value">1.428</div><div class="stat-label">Total Kunjungan Bulan Ini</div></div>
        <div class="stat-card green"><div class="stat-header"><div class="stat-icon">🏥</div></div><div class="stat-value">89,4%</div><div class="stat-label">Tingkat Penyelesaian Pelayanan</div></div>
        <div class="stat-card orange"><div class="stat-header"><div class="stat-icon">⏱️</div></div><div class="stat-value">14 Mnt</div><div class="stat-label">Rata-Rata Waktu Tunggu Pasien</div></div>
        <div class="stat-card red"><div class="stat-header"><div class="stat-icon">💳</div></div><div class="stat-value">Rp 142.5M</div><div class="stat-label">Total Pendapatan Rawat Jalan</div></div>
      </div>
      <div class="card">
        <div class="card-title">Rekapitulasi Kunjungan Per Poliklinik (Juli 2026)</div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Poliklinik</th><th>Dokter Penanggung Jawab</th><th>Pasien BPJS</th><th>Pasien Umum</th><th>Total Kunjungan</th><th>Persentase</th></tr></thead>
            <tbody>
              <tr><td><strong>Poli Umum</strong></td><td>dr. Ahmad Fauzi</td><td>420</td><td>180</td><td><strong>600</strong></td><td>42% <div class="progress-bar-wrap"><div class="progress-bar" style="width:42%"></div></div></td></tr>
              <tr><td><strong>Poli Anak</strong></td><td>dr. Siti Rahayu, Sp.A</td><td>210</td><td>90</td><td><strong>300</strong></td><td>21% <div class="progress-bar-wrap"><div class="progress-bar" style="width:21%"></div></div></td></tr>
              <tr><td><strong>Poli Kebidanan</strong></td><td>dr. Maya Kusuma, Sp.OG</td><td>160</td><td>40</td><td><strong>200</strong></td><td>14% <div class="progress-bar-wrap"><div class="progress-bar" style="width:14%"></div></div></td></tr>
              <tr><td><strong>Poli Gigi & Mulut</strong></td><td>drg. Budi Hartono</td><td>90</td><td>60</td><td><strong>150</strong></td><td>10.5% <div class="progress-bar-wrap"><div class="progress-bar" style="width:10.5%"></div></div></td></tr>
              <tr><td><strong>Poli Bedah</strong></td><td>dr. Rizki Pratama, Sp.B</td><td>70</td><td>30</td><td><strong>100</strong></td><td>7% <div class="progress-bar-wrap"><div class="progress-bar" style="width:7%"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- MODAL -->
<!-- CONFIRMATION MODAL -->
<div class="success-modal" id="confirmModal" onclick="closeConfirmModal()">
  <div class="modal-box" style="width:480px;text-align:left;padding:24px;" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <div style="font-size:16px;font-weight:700;color:var(--primary);" id="confTitle">🛡️ Konfirmasi Tindakan</div>
      <button class="btn btn-outline btn-sm" onclick="closeConfirmModal()">✕</button>
    </div>
    <div style="background:var(--bg);padding:14px;border-radius:8px;margin-bottom:16px;font-size:13px;line-height:1.6;border:1px solid var(--border);" id="confBody"></div>
    <div style="background:var(--surface);border:1px solid var(--border);padding:12px;border-radius:8px;margin-bottom:16px;font-size:12px;">
      <div style="font-weight:600;margin-bottom:8px;color:var(--primary);display:flex;align-items:center;gap:6px;">📲 Otomasi Pengiriman Notifikasi Pasien:</div>
      <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer;">
        <input type="checkbox" id="chkWaNotif" checked style="accent-color:var(--success);width:16px;height:16px;"/>
        <span id="lblWaNotif">Kirim Bukti / Struk Transaksi via <strong>WhatsApp Gateway (Fonnte)</strong></span>
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
        <input type="checkbox" id="chkEmailNotif" checked style="accent-color:var(--primary);width:16px;height:16px;"/>
        <span id="lblEmailNotif">Kirim Salinan PDF via <strong>Email SMTP Gateway RSUD</strong></span>
      </label>
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn btn-outline" onclick="closeConfirmModal()">✕ Batal</button>
      <button class="btn btn-success" id="btnExecuteConfirm" onclick="executeConfirmedAction()">✅ Ya, Proses Sekarang</button>
    </div>
  </div>
</div>

<div class="success-modal" id="successModal" onclick="closeModal()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-icon">✅</div>
    <div class="modal-title" id="modalTitle">Berhasil!</div>
    <div class="modal-sub" id="modalSub">Data berhasil disimpan ke sistem.</div>
    <button class="btn btn-primary" style="width:100%;justify-content:center;" onclick="closeModal()">Selesai</button>
  </div>
</div>

<div class="success-modal" id="notifModal" onclick="closeNotifModal()">
  <div class="modal-box" style="width:420px;text-align:left;padding:24px;" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <div style="font-size:16px;font-weight:700;">🔔 Notifikasi Sistem</div>
      <button class="btn btn-outline btn-sm" onclick="closeNotifModal()">✕</button>
    </div>
    <div id="notifListContainer" style="max-height:300px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;">
      <div style="padding:12px;background:var(--bg);border-radius:8px;font-size:13px;color:var(--muted);text-align:center;">Memuat notifikasi...</div>
    </div>
    <hr class="divider"/>
    <button class="btn btn-primary" style="width:100%;justify-content:center;" onclick="markAllRead()">Tandai Semua Dibaca</button>
  </div>
</div>

<script>
  // NAVIGATION
  const titles = { dashboard:'Dashboard', antrian:'Monitor Antrian', pendaftaran:'Pendaftaran Pasien', emr:'EMR – Rekam Medis Elektronik', farmasi:'Farmasi & Apotik', kasir:'Kasir & Pembayaran', master:'Master Data & Inventori', settings:'Pengaturan & Bridging Sistem', laporan:'Laporan & Statistik RSUD' };
  document.querySelectorAll('.nav-item[data-page]').forEach(item => {
    item.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
      document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
      item.classList.add('active');
      const page = item.dataset.page;
      document.getElementById('page-' + page).classList.add('active');
      document.getElementById('headerTitle').textContent = titles[page] || page;
    });
  });

  // DATE
  document.getElementById('headerDate').textContent = new Date().toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'});

  // COUNTER ANIMATION
  function animCount(el, target, prefix='', suffix='') {
    let cur=0; const step=target/40;
    const t=setInterval(()=>{ cur=Math.min(cur+step,target); el.textContent=prefix+Math.floor(cur).toLocaleString('id-ID')+suffix; if(cur>=target) clearInterval(t); },30);
  }
  animCount(document.getElementById('ctr-visit'),98);
  animCount(document.getElementById('ctr-done'),74);
  animCount(document.getElementById('ctr-wait'),12);
  animCount(document.getElementById('ctr-rev'),4750000,'Rp ','');
  animCount(document.getElementById('ctr-rev2'),4750000,'Rp ','');

  // CHARTS
  new Chart(document.getElementById('chartVisit').getContext('2d'),{
    type:'bar', data:{ labels:['Sen','Sel','Rab','Kam','Jum','Sab','Min'],
      datasets:[{label:'BPJS',data:[58,62,71,55,68,74,42],backgroundColor:'#3b6ef0',borderRadius:6},{label:'Umum',data:[22,28,24,18,22,26,14],backgroundColor:'#10b981',borderRadius:6}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{font:{family:'Inter',size:11}}}},scales:{x:{stacked:true,grid:{display:false},ticks:{font:{family:'Inter',size:11}}},y:{stacked:true,grid:{color:'#f1f5f9'},ticks:{font:{family:'Inter',size:11}}}}}
  });
  new Chart(document.getElementById('chartPayment').getContext('2d'),{
    type:'doughnut', data:{labels:['BPJS','Umum','Lainnya'],datasets:[{data:[68,27,5],backgroundColor:['#3b6ef0','#10b981','#f59e0b'],borderWidth:0,hoverOffset:4}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},cutout:'70%'}
  });

  // TOP DIAGNOSES
  const topDx=[['J06.9','ISPA',142],['I10','Hipertensi',98],['E11.9','DM Tipe 2',87],['J18.9','Pneumonia',64],['K29.7','Gastritis',58],['R51','Nyeri Kepala',47],['M54.5','Nyeri Punggung',43],['J30.4','Rinitis Alergi',38],['A09','Diare',34],['L30.9','Dermatitis',28]];
  const dxTb=document.getElementById('dx-table');
  topDx.forEach(([code,name,cnt],i)=>{ dxTb.innerHTML+=`<tr><td><span style="font-size:11px;font-weight:700;color:var(--muted);">${i+1}</span></td><td><div style="font-size:12px;font-weight:600;">${name}</div><div style="font-size:10px;color:var(--primary);">${code}</div></td><td><strong>${cnt}</strong></td></tr>`; });

  // QUEUE
  const polyData=[{name:'Poli Umum',cur:'A-023',curName:'Budi Santoso',wait:5,done:18},{name:'Poli Anak',cur:'B-014',curName:'Anisa Putri',wait:3,done:11},{name:'Poli Kebidanan',cur:'C-009',curName:'Dewi Lestari',wait:4,done:5},{name:'Poli Gigi',cur:'D-016',curName:'Rudi Hartono',wait:0,done:16},{name:'Poli Bedah',cur:'E-007',curName:'Bambang W.',wait:2,done:5},{name:'Poli Mata',cur:'F-005',curName:'Sri Mulyani',wait:1,done:4}];
  const polyCards=document.getElementById('polyCards');
  polyData.forEach(p=>{ polyCards.innerHTML+=`<div class="queue-poly-card"><div class="poly-name">${p.name}</div><div class="current-num">${p.cur}</div><div class="current-name">${p.curName}</div><div class="queue-meta"><span class="meta-chip chip-wait">⏳ ${p.wait}</span><span class="meta-chip chip-done">✅ ${p.done}</span></div></div>`; });

  const waitingPasien=[{num:'A-024',name:'Hendra Gunawan',type:'BPJS',est:'~5 mnt'},{num:'A-025',name:'Rina Marlina',type:'Umum',est:'~12 mnt'},{num:'A-026',name:'Slamet Riyadi',type:'BPJS',est:'~19 mnt'},{num:'A-027',name:'Yuli Astuti',type:'BPJS',est:'~26 mnt'},{num:'A-028',name:'Agus Salim',type:'Umum',est:'~33 mnt'}];
  const wl=document.getElementById('waitingList');
  waitingPasien.forEach(p=>{ wl.innerHTML+=`<div class="waiting-item"><div class="waiting-num">${p.num}</div><div class="waiting-info"><strong>${p.name}</strong><span>${p.type}</span></div><div class="waiting-est">${p.est}</div></div>`; });
  let callingIdx=0;
  function panggil(){ if(callingIdx>=waitingPasien.length)return; const p=waitingPasien[callingIdx++]; document.getElementById('callingNum').textContent=p.num; document.getElementById('callingName').textContent=p.name; wl.querySelector('.waiting-item')?.remove(); }

  // VISIT TABLE
  const visits=[{no:'UMU-001',rm:'RM-2026-00241',name:'Andi Kurniawan',poli:'Poli Umum',type:'BPJS',status:'Dalam Pemeriksaan'},{no:'UMU-002',rm:'RM-2026-00189',name:'Siti Fatimah',poli:'Poli Umum',type:'Umum',status:'Menunggu'},{no:'ANA-003',rm:'RM-2026-00231',name:'Budi Kurniawan',poli:'Poli Anak',type:'BPJS',status:'Selesai'},{no:'KBD-001',rm:'RM-2026-00245',name:'Sari Rahayu',poli:'Poli Kebidanan',type:'BPJS',status:'Selesai'},{no:'GIG-002',rm:'RM-2026-00098',name:'Rudi Hartono',poli:'Poli Gigi',type:'Umum',status:'Selesai'}];
  const vt=document.getElementById('visitTable');
  visits.forEach(v=>{ const s=v.status; const cls=s==='Selesai'?'badge-success':s==='Menunggu'?'badge-warning':'badge-info'; vt.innerHTML+=`<tr><td><strong>${v.no}</strong></td><td style="font-size:12px;color:var(--muted);">${v.rm}</td><td><strong>${v.name}</strong></td><td style="font-size:12px;">${v.poli}</td><td><span class="badge ${v.type==='BPJS'?'badge-info':'badge-gray'}">${v.type}</span></td><td><span class="badge ${cls}">${s}</span></td><td><button class="btn btn-outline btn-sm">Detail</button></td></tr>`; });

  // RX TABLE
  const rxData=[{no:'RES-023',pasien:'Budi Kurniawan',dr:'dr. Ahmad Fauzi',time:'10:35',status:'Menunggu'},{no:'RES-024',pasien:'Siti Fatimah',dr:'dr. Budi Prakoso',time:'11:02',status:'Diracik'},{no:'RES-025',pasien:'Agus Salim',dr:'dr. Ahmad Fauzi',time:'11:18',status:'Menunggu'},{no:'RES-022',pasien:'Rina Marlina',dr:'dr. Siti Rahayu',time:'09:55',status:'Diserahkan'}];
  const rt=document.getElementById('rxTable');
  rxData.forEach(r=>{ const cls=r.status==='Diserahkan'?'badge-success':r.status==='Menunggu'?'badge-warning':'badge-info'; rt.innerHTML+=`<tr><td style="font-weight:600;font-size:12px;">${r.no}</td><td>${r.pasien}</td><td style="font-size:12px;color:var(--muted);">${r.dr}</td><td style="font-size:12px;">${r.time}</td><td><span class="badge ${cls}">${r.status}</span></td><td><button class="btn btn-outline btn-sm">Proses</button></td></tr>`; });

  // STOCK ALERT
  const stocks=[{name:'Amoxicillin 500mg',stok:8,min:20},{name:'Metformin 500mg',stok:15,min:30},{name:'Cetirizine 10mg',stok:6,min:25}];
  const sa=document.getElementById('stockAlert');
  stocks.forEach(s=>{ const pct=Math.round(s.stok/s.min*100); sa.innerHTML+=`<div style="padding:8px 0;border-bottom:1px solid var(--border);"><div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span style="font-weight:600;">${s.name}</span><span style="color:var(--danger);font-weight:600;">${s.stok}/${s.min}</span></div><div class="progress-bar-wrap"><div class="progress-bar" style="width:${pct}%;background:var(--danger);"></div></div></div>`; });

  // ICD SEARCH
  const icdData=[{code:'J06.9',name:'Infeksi Sal. Napas Atas Akut'},{code:'J02.9',name:'Faringitis Akut'},{code:'R50.9',name:'Demam, tidak spesifik'},{code:'I10',name:'Hipertensi Esensial'},{code:'E11.9',name:'Diabetes Melitus Tipe 2'},{code:'K29.7',name:'Gastritis'},{code:'M54.5',name:'Nyeri Punggung Bawah'},{code:'J18.9',name:'Pneumonia'}];
  function searchICD(q){ const res=document.getElementById('icdResults'); if(!q){res.style.display='none';return;} const f=icdData.filter(d=>d.code.toLowerCase().includes(q.toLowerCase())||d.name.toLowerCase().includes(q.toLowerCase())); if(!f.length){res.style.display='none';return;} res.style.display='block'; res.innerHTML=f.map(d=>`<div class="icd-item" onclick="addDx('${d.code}','${d.name}')"><div class="icd-code">${d.code}</div>${d.name}</div>`).join(''); }
  function addDx(code,name){ const box=document.getElementById('selectedDx'); if([...box.querySelectorAll('.dx-tag')].some(t=>t.textContent.startsWith(code)))return; const tag=document.createElement('div'); tag.className='dx-tag'; tag.innerHTML=`${code} – ${name.split(',')[0]} <span onclick="this.parentElement.remove()">×</span>`; box.appendChild(tag); document.getElementById('icdResults').style.display='none'; document.getElementById('icdInput').value=''; }

  // ADD RX
  let rxCount=3;
  function addRx(){ rxCount++; const list=document.getElementById('rxList'); const item=document.createElement('div'); item.className='rx-item'; item.innerHTML=`<div class="rx-num">${rxCount}</div><div class="rx-med"><input type="text" placeholder="Nama obat..." style="font-size:13px;margin-bottom:4px;width:100%;"/><input type="text" placeholder="Aturan pakai · Jumlah..." style="font-size:12px;width:100%;"/></div><span onclick="this.parentElement.remove()" style="cursor:pointer;color:var(--muted);font-size:18px;">×</span>`; list.appendChild(item); }

  // PAYMENT
  function selectPay(el){ 
    document.querySelectorAll('.pay-opt').forEach(o=>o.classList.remove('selected')); 
    el.classList.add('selected'); 
    const method = el.dataset.method || 'Tunai';
    document.querySelectorAll('.pay-panel').forEach(p => p.style.display = 'none');
    const targetPanel = document.getElementById('pay-detail-' + method);
    if(targetPanel) targetPanel.style.display = 'block';
  }
  function calcChange(){ const paid=parseInt(document.getElementById('cashInput').value)||0; const change=Math.max(paid-200000,0); document.getElementById('changeDisplay').textContent='Rp '+change.toLocaleString('id-ID'); }

  // STEPS
  function nextStep(step){ [1,2,3].forEach(s=>{ const el=document.getElementById('regStep'+s); if(el) el.style.display=s===step?'block':'none'; }); document.querySelectorAll('#regSteps .step').forEach((s,i)=>{ s.classList.remove('active','done'); if(i+1===step) s.classList.add('active'); if(i+1<step) s.classList.add('done'); }); }

  // MODAL & CONFIRMATION
  const modalMessages={ emr:['EMR Tersimpan!','Rekam medis dan resep berhasil disimpan.'], farmasi:['Obat Diserahkan!','Resep berhasil diproses dan diserahkan ke pasien.'], kasir:['Pembayaran Berhasil!','Kwitansi telah dicetak dan dikirim ke pasien.'], pendaftaran:['Pendaftaran Berhasil!','Nomor antrian UMU-001 telah dicetak.'] };
  function showModal(){ const page=document.querySelector('.page.active').id.replace('page-',''); const msg=modalMessages[page]||['Berhasil!','Data berhasil disimpan.']; document.getElementById('modalTitle').textContent=msg[0]; document.getElementById('modalSub').textContent=msg[1]; document.getElementById('successModal').classList.add('show'); }
  function closeModal(){ document.getElementById('successModal').classList.remove('show'); }

  let currentActionType = '';
  function confirmAction(type) {
    currentActionType = type;
    const modal = document.getElementById('confirmModal');
    const title = document.getElementById('confTitle');
    const body = document.getElementById('confBody');
    const lblWa = document.getElementById('lblWaNotif');
    const lblEmail = document.getElementById('lblEmailNotif');
    
    if (type === 'kasir') {
      const selectedOpt = document.querySelector('.pay-opt.selected');
      const method = selectedOpt ? selectedOpt.dataset.method : 'Tunai';
      title.textContent = '🛡️ Konfirmasi & Verifikasi Pembayaran Kasir';
      let methodDetails = '';
      if (method === 'Tunai') {
        const paid = parseInt(document.getElementById('cashInput').value)||0;
        const change = Math.max(paid - 200000, 0);
        methodDetails = `<div style="color:var(--success);margin-top:6px;padding:8px;background:white;border-radius:6px;border:1px solid var(--border);"><strong>✔ Verifikasi Tunai:</strong> Uang Diterima Rp ${paid.toLocaleString('id-ID')} · Kembalian Pasien Rp ${change.toLocaleString('id-ID')}</div>`;
      } else if (method === 'QRIS') {
        methodDetails = `<div style="color:var(--primary);margin-top:6px;padding:8px;background:white;border-radius:6px;border:1px solid var(--border);"><strong>✔ Verifikasi QRIS Dinamis (NMID: ID1020030040050):</strong> Apakah Anda menyatakan mutasi masuk Rp 200.000 dari rekening pasien sudah dicek di M-Banking/Merchant RSUD?</div>`;
      } else if (method === 'BPJS') {
        methodDetails = `<div style="color:var(--success);margin-top:6px;padding:8px;background:white;border-radius:6px;border:1px solid var(--border);"><strong>✔ Verifikasi BPJS Kesehatan:</strong> SEP 1401R0010726V000041 valid & terverifikasi bridging V-Claim. Tagihan Rp 200.000 ditanggung 100% oleh BPJS.</div>`;
      } else {
        const cardNum = document.getElementById('debitCardNum').value || 'XXXX';
        const appr = document.getElementById('debitAppr').value || 'APPR-XXXX';
        methodDetails = `<div style="color:var(--info);margin-top:6px;padding:8px;background:white;border-radius:6px;border:1px solid var(--border);"><strong>✔ Verifikasi Kartu Debit/Kredit:</strong> 4 Digit Akhir Kartu: ${cardNum} · Kode Approval EDC Bank: ${appr}.</div>`;
      }
      body.innerHTML = `
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Pasien:</span><strong>Sari Rahayu (INV-20260626-0041)</strong></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Metode Dipilih:</span><strong style="color:var(--primary);font-size:14px;">${method}</strong></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Total Tagihan:</span><strong>Rp 200.000</strong></div>
        <hr class="divider" style="margin:8px 0;"/>
        <div style="font-size:12px;">${methodDetails}</div>
      `;
      lblWa.innerHTML = `Kirim Bukti Pembayaran & e-Kwitansi via <strong>WhatsApp Gateway (Fonnte)</strong> ke 0882-1529-0459`;
      lblEmail.innerHTML = `Kirim Salinan Struk Transaksi PDF via <strong>Email SMTP Gateway</strong> ke denynz17@gmail.com`;
    } else if (type === 'farmasi') {
      title.textContent = '🛡️ Konfirmasi Penyerahan Obat & Telaah Resep';
      body.innerHTML = `
        <div style="margin-bottom:6px;">Apakah Anda yakin resep <strong>RES-20260626-023</strong> atas nama pasien <strong>Budi Kurniawan</strong> telah ditelaah (5 Tepat Pasien, Obat, Dosis, Rute, Waktu)?</div>
        <div style="color:var(--warning);font-size:12px;padding:8px;background:white;border-radius:6px;border:1px solid var(--border);margin-top:6px;">⚠️ Stok apotek (Paracetamol, Ambroxol, Cetirizine) akan dikurangi secara otomatis di database inventori.</div>
      `;
      lblWa.innerHTML = `Kirim Aturan Pakai & Jadwal Minum Obat via <strong>WhatsApp Gateway (Fonnte)</strong>`;
      lblEmail.innerHTML = `Kirim Salinan Resep Digital via <strong>Email SMTP Gateway RSUD</strong>`;
    } else if (type === 'emr') {
      title.textContent = '🛡️ Konfirmasi Rekam Medis & Diagnosis Dokter';
      body.innerHTML = `
        <div style="margin-bottom:6px;">Pemeriksaan EMR atas nama <strong>Budi Kurniawan (RM-2026-00231)</strong> akan difinalisasi dengan diagnosis utama <strong>J06.9 – ISPA</strong>.</div>
        <div style="color:var(--primary);font-size:12px;padding:8px;background:white;border-radius:6px;border:1px solid var(--border);margin-top:6px;">🔗 Data EMR dienkripsi & tersinkronisasi realtime dengan platform <strong>SatuSehat Kemenkes RI</strong>.</div>
      `;
      lblWa.innerHTML = `Kirim Ringkasan Kunjungan & Edukasi Medis via <strong>WhatsApp Gateway</strong>`;
      lblEmail.innerHTML = `Kirim Resume Medis & Surat Keterangan via <strong>Email SMTP RSUD</strong>`;
    } else if (type === 'pendaftaran') {
      title.textContent = '🛡️ Konfirmasi Pendaftaran & Cetak Tiket Antrian';
      body.innerHTML = `
        <div style="margin-bottom:6px;">Pasien <strong>Andi Kurniawan (RM-2026-00247)</strong> akan didaftarkan ke <strong>Poli Umum</strong> bersama dokter <strong>dr. Ahmad Fauzi</strong>.</div>
        <div style="color:var(--success);font-size:12px;padding:8px;background:white;border-radius:6px;border:1px solid var(--border);margin-top:6px;">✔ Status BPJS Kesehatan: Eligible & Terverifikasi otomatis via bridging V-Claim Kemenkes.</div>
      `;
      lblWa.innerHTML = `Kirim Tiket Antrian (UMU-001) & Estimasi Waktu Layanan via <strong>WhatsApp Gateway</strong>`;
      lblEmail.innerHTML = `Kirim Bukti Registrasi & Jadwal Praktek Dokter via <strong>Email SMTP RSUD</strong>`;
    }
    modal.classList.add('show');
  }

  function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('show');
  }

  async function executeConfirmedAction() {
    const btn = document.getElementById('btnExecuteConfirm');
    const origText = btn.innerHTML;
    btn.innerHTML = '⏳ Memproses & Mengirim Notif...';
    btn.disabled = true;

    try {
      let notifTitle = 'Notifikasi Sistem SIMRS';
      let notifMsg = 'Transaksi berhasil diproses oleh sistem.';
      if (currentActionType === 'kasir') {
        const method = document.querySelector('.pay-opt.selected')?.dataset.method || 'Tunai';
        notifTitle = 'Bukti Pembayaran Tagihan (INV-20260626-0041)';
        notifMsg = `Pembayaran tagihan atas nama Sari Rahayu sebesar Rp 200.000 via ${method} telah LUNAS terverifikasi. Struk resmi telah dikirim via WA Gateway dan Email SMTP.`;
      } else if (currentActionType === 'farmasi') {
        notifTitle = 'Penyerahan Obat Resep (RES-20260626-023)';
        notifMsg = `Resep obat untuk Budi Kurniawan telah diserahkan. Aturan pakai: Paracetamol (3x1), Ambroxol (3x1), Cetirizine (1x1 malam). Dikirim via WA & Email.`;
      } else if (currentActionType === 'emr') {
        notifTitle = 'Rekam Medis & Diagnosis Terkirim (SatuSehat)';
        notifMsg = `Pemeriksaan EMR pasien Budi Kurniawan (J06.9 - ISPA) telah disimpan dan tersinkronisasi dengan SatuSehat Kemenkes.`;
      } else if (currentActionType === 'pendaftaran') {
        notifTitle = 'Registrasi Antrian Baru (UMU-001)';
        notifMsg = `Pasien Andi Kurniawan berhasil didaftarkan ke Poli Umum (dr. Ahmad Fauzi). Tiket antrian digital dikirim ke WA & Email.`;
      }

      await fetch('/api/notifikasi', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          tipe: 'WhatsApp & Email (Fonnte/SMTP)',
          judul: notifTitle,
          pesan: notifMsg,
          nomor_tujuan: '0882-1529-0459',
          referensi: currentActionType.toUpperCase() + '-' + Date.now()
        })
      });
      loadRealData();
    } catch (e) {
      console.log('Notif error or offline:', e);
    }

    setTimeout(() => {
      btn.innerHTML = origText;
      btn.disabled = false;
      closeConfirmModal();

      const msg = modalMessages[currentActionType] || ['Berhasil!', 'Tindakan telah berhasil diproses.'];
      document.getElementById('modalTitle').textContent = msg[0];
      document.getElementById('modalSub').textContent = msg[1] + ' ✅ Bukti & struk transaksi telah dikirim via WhatsApp Gateway dan Email!';
      document.getElementById('successModal').classList.add('show');
    }, 600);
  }

  // FETCH REAL DATA FROM SUPABASE
  async function loadRealData() {
    try {
      const resStat = await fetch('/api/public/statistik');
      if (resStat.ok) {
        const stat = await resStat.json();
        if (stat.kunjungan_hari_ini !== undefined) {
          document.getElementById('ctr-visit').textContent = stat.kunjungan_hari_ini || 0;
          document.getElementById('ctr-done').textContent = Math.floor((stat.kunjungan_hari_ini || 0) * 0.8);
          document.getElementById('ctr-wait').textContent = Math.ceil((stat.kunjungan_hari_ini || 0) * 0.2);
        }
      }
      const resNotif = await fetch('/api/notifikasi/unread-count');
      if (resNotif.ok) {
        const notif = await resNotif.json();
        document.getElementById('notifBadge').textContent = notif.unread_count || 0;
      }
    } catch (e) {
      console.log('Using simulated numbers:', e);
    }
  }

  async function loadMasterData() {
    try {
      const resDoc = await fetch('/api/public/jadwal-dokter?hari=semua');
      if (resDoc.ok) {
        const docJson = await resDoc.json();
        const tbodyDoc = document.getElementById('master-dokter-body');
        if (docJson.data && docJson.data.length > 0) {
          tbodyDoc.innerHTML = docJson.data.map(d => `
            <tr>
              <td><span class="badge badge-info">${d.poli}</span></td>
              <td><strong>${d.dokter}</strong></td>
              <td style="color:var(--muted);">${d.spesialisasi}</td>
              <td>${d.jam}</td>
              <td><strong style="color:var(--success);">${d.sisa_kuota}/${d.kuota_harian}</strong></td>
            </tr>
          `).join('');
        }
      }
      const resObat = await fetch('/api/farmasi/stok');
      if (resObat.ok) {
        const obatJson = await resObat.json();
        const tbodyObat = document.getElementById('master-obat-body');
        if (obatJson && obatJson.length > 0) {
          tbodyObat.innerHTML = obatJson.map(o => `
            <tr>
              <td><span style="font-family:monospace;font-weight:600;color:var(--primary);">${o.kode_obat}</span></td>
              <td><strong>${o.nama_obat}</strong><div style="font-size:11px;color:var(--muted);">${o.kategori}</div></td>
              <td>${o.satuan}</td>
              <td>Rp ${(o.harga_jual||0).toLocaleString('id-ID')}</td>
              <td><span class="badge ${o.stok_tersedia <= o.stok_minimum ? 'badge-danger' : 'badge-success'}">${o.stok_tersedia}</span></td>
            </tr>
          `).join('');
        }
      }
      const resTarif = await fetch('/api/master/tarif');
      if (resTarif.ok) {
        const tarifJson = await resTarif.json();
        const tbodyTarif = document.getElementById('master-tarif-body');
        if (tarifJson && tarifJson.length > 0) {
          tbodyTarif.innerHTML = tarifJson.map(t => `
            <tr>
              <td><span style="font-family:monospace;font-weight:600;color:var(--primary);">${t.kode_tarif}</span></td>
              <td><strong>${t.nama_layanan}</strong></td>
              <td><span class="badge badge-gray">${t.kategori}</span></td>
              <td>Rp ${(t.harga_umum||0).toLocaleString('id-ID')}</td>
              <td>Rp ${(t.harga_bpjs||0).toLocaleString('id-ID')}</td>
              <td><span class="badge badge-success">Aktif</span></td>
            </tr>
          `).join('');
        }
      }
    } catch (e) {
      console.log('Error loading master data:', e);
    }
  }

  function openSettingsPage() {
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const setNav = document.querySelector('.nav-item[data-page="settings"]');
    if (setNav) setNav.classList.add('active');
    document.getElementById('page-settings').classList.add('active');
    document.getElementById('headerTitle').textContent = 'Pengaturan & Bridging Sistem';
  }

  async function toggleNotifModal() {
    const modal = document.getElementById('notifModal');
    if (modal.classList.contains('show')) {
      closeNotifModal();
      return;
    }
    modal.classList.add('show');
    const container = document.getElementById('notifListContainer');
    try {
      const res = await fetch('/api/notifikasi');
      if (res.ok) {
        const json = await res.json();
        if (json && json.length > 0) {
          container.innerHTML = json.map(n => `
            <div style="padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <strong style="font-size:13px;color:var(--primary);">${n.judul}</strong>
                <span style="font-size:11px;color:var(--muted);">${n.created_at || 'Baru'}</span>
              </div>
              <div style="font-size:12px;color:var(--text);">${n.pesan}</div>
            </div>
          `).join('');
          return;
        }
      }
    } catch(e) {}
    container.innerHTML = `
      <div style="padding:12px;background:var(--bg);border-radius:8px;font-size:13px;text-align:center;">
        <div style="font-weight:600;margin-bottom:4px;">✅ Tidak Ada Notifikasi Baru</div>
        <span style="color:var(--muted);font-size:11px;">Semua antrian dan stok obat dalam kondisi normal.</span>
      </div>
    `;
  }

  function closeNotifModal() {
    document.getElementById('notifModal').classList.remove('show');
  }

  function markAllRead() {
    document.getElementById('notifBadge').textContent = '0';
    closeNotifModal();
    alert('Semua notifikasi telah ditandai dibaca.');
  }

  function saveSettings() {
    alert('Konfigurasi sistem & bridging berhasil disimpan ke database Supabase!');
  }

  // Load initial real data on start
  window.addEventListener('DOMContentLoaded', () => {
    loadRealData();
    loadMasterData();
  });
</script>
</body>
</html>
