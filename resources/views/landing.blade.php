<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RSUD Puruk Cahu - Rumah Sakit Umum Daerah Kabupaten Murung Raya, Kalimantan Tengah. Pelayanan kesehatan bermutu dan terjangkau.">
    <title>RSUD Puruk Cahu | Rumah Sakit Umum Daerah Kabupaten Murung Raya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ─── CSS Reset & Variables ──────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #0e7490;
            --primary-dark: #0c4a6e;
            --primary-light: #22d3ee;
            --accent: #f59e0b;
            --accent-dark: #d97706;
            --success: #10b981;
            --danger: #ef4444;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-card-hover: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border: #334155;
            --gradient-hero: linear-gradient(135deg, #0c4a6e 0%, #0e7490 40%, #0891b2 100%);
            --gradient-accent: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.3);
            --shadow-glow: 0 0 30px rgba(14, 116, 144, 0.2);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ─── Navbar ─────────────────────────────────────────────── */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 48px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
            transition: var(--transition);
        }
        .navbar.scrolled { padding: 10px 48px; background: rgba(15, 23, 42, 0.95); }
        .nav-brand { display: flex; align-items: center; gap: 14px; text-decoration: none; }
        .nav-brand .logo-icon {
            width: 46px; height: 46px;
            background: var(--gradient-hero);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #fff;
            box-shadow: 0 4px 15px rgba(14, 116, 144, 0.4);
        }
        .nav-brand h1 { font-size: 18px; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
        .nav-brand small { font-size: 11px; color: var(--text-secondary); font-weight: 400; }
        .nav-links { display: flex; align-items: center; gap: 32px; list-style: none; }
        .nav-links a {
            color: var(--text-secondary); text-decoration: none; font-size: 14px; font-weight: 500;
            transition: var(--transition); position: relative;
        }
        .nav-links a:hover { color: var(--primary-light); }
        .nav-links a::after {
            content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px;
            background: var(--primary-light); transition: var(--transition);
        }
        .nav-links a:hover::after { width: 100%; }
        .btn-login {
            padding: 10px 24px; background: var(--gradient-accent);
            color: #1a1a2e; font-weight: 700; font-size: 14px;
            border: none; border-radius: 10px; cursor: pointer;
            text-decoration: none; transition: var(--transition);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(245, 158, 11, 0.5); }

        /* ─── Hero Section ───────────────────────────────────────── */
        .hero {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 120px 48px 80px;
            background: var(--gradient-hero);
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 70% 20%, rgba(34, 211, 238, 0.15) 0%, transparent 60%),
                        radial-gradient(ellipse at 20% 80%, rgba(245, 158, 11, 0.08) 0%, transparent 50%);
        }
        .hero::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 120px;
            background: linear-gradient(to top, var(--bg-dark), transparent);
        }
        .hero-content { position: relative; z-index: 1; max-width: 1200px; width: 100%; display: flex; align-items: center; gap: 60px; }
        .hero-text { flex: 1.2; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 16px; background: rgba(255,255,255,0.12);
            border-radius: 50px; font-size: 13px; color: rgba(255,255,255,0.9);
            margin-bottom: 24px; backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .hero-badge .pulse {
            width: 8px; height: 8px; border-radius: 50%; background: var(--success);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.5); } }
        .hero-text h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(36px, 5vw, 56px); font-weight: 800;
            line-height: 1.1; margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #e0f2fe 50%, #22d3ee 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero-text p { font-size: 18px; color: rgba(255,255,255,0.75); max-width: 520px; margin-bottom: 36px; line-height: 1.7; }
        .hero-actions { display: flex; gap: 16px; flex-wrap: wrap; }
        .btn-hero {
            padding: 16px 32px; border-radius: 14px; font-size: 15px; font-weight: 700;
            text-decoration: none; transition: var(--transition); cursor: pointer; border: none;
        }
        .btn-hero.primary {
            background: var(--gradient-accent); color: #1a1a2e;
            box-shadow: 0 8px 30px rgba(245, 158, 11, 0.35);
        }
        .btn-hero.primary:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(245, 158, 11, 0.5); }
        .btn-hero.secondary {
            background: rgba(255,255,255,0.1); color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        .btn-hero.secondary:hover { background: rgba(255,255,255,0.2); transform: translateY(-3px); }

        .hero-cards { flex: 1; display: flex; flex-direction: column; gap: 16px; }
        .hero-stat-card {
            padding: 22px 28px; border-radius: var(--radius);
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            display: flex; align-items: center; gap: 18px;
            transition: var(--transition);
            animation: slideInRight 0.6s ease-out both;
        }
        .hero-stat-card:nth-child(2) { animation-delay: 0.15s; }
        .hero-stat-card:nth-child(3) { animation-delay: 0.3s; }
        .hero-stat-card:nth-child(4) { animation-delay: 0.45s; }
        .hero-stat-card:hover { transform: translateX(-8px); background: rgba(255,255,255,0.14); }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        .hero-stat-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }
        .hero-stat-icon.igd { background: rgba(239, 68, 68, 0.2); }
        .hero-stat-icon.poli { background: rgba(14, 116, 144, 0.3); }
        .hero-stat-icon.lab { background: rgba(139, 92, 246, 0.2); }
        .hero-stat-icon.ambulance { background: rgba(245, 158, 11, 0.2); }
        .hero-stat-text h4 { font-size: 16px; font-weight: 700; color: #fff; }
        .hero-stat-text p { font-size: 13px; color: rgba(255,255,255,0.6); }

        /* ─── Section Common ─────────────────────────────────────── */
        .section { padding: 100px 48px; max-width: 1200px; margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header .label {
            display: inline-block; padding: 6px 16px; border-radius: 50px;
            background: rgba(14, 116, 144, 0.15); color: var(--primary-light);
            font-size: 13px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;
            margin-bottom: 16px;
        }
        .section-header h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(28px, 3.5vw, 40px); font-weight: 800;
            margin-bottom: 12px;
        }
        .section-header p { font-size: 16px; color: var(--text-secondary); max-width: 600px; margin: 0 auto; }

        /* ─── Jadwal Dokter ───────────────────────────────────────── */
        #jadwal { padding-top: 60px; }
        .jadwal-tabs { display: flex; gap: 8px; justify-content: center; margin-bottom: 36px; flex-wrap: wrap; }
        .jadwal-tab {
            padding: 10px 22px; border-radius: 10px; font-size: 14px; font-weight: 600;
            background: var(--bg-card); color: var(--text-secondary);
            border: 1px solid var(--border); cursor: pointer; transition: var(--transition);
        }
        .jadwal-tab.active, .jadwal-tab:hover {
            background: var(--primary); color: #fff; border-color: var(--primary);
        }
        .jadwal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; }
        .jadwal-card {
            padding: 24px; border-radius: var(--radius);
            background: var(--bg-card); border: 1px solid var(--border);
            transition: var(--transition); position: relative; overflow: hidden;
        }
        .jadwal-card:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: var(--shadow-glow); }
        .jadwal-card .poli-name {
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            color: var(--primary-light); margin-bottom: 10px;
        }
        .jadwal-card .doctor-name { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
        .jadwal-card .spec { font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; }
        .jadwal-card .meta { display: flex; justify-content: space-between; align-items: center; }
        .jadwal-card .time {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; color: var(--text-muted); background: rgba(14,116,144,0.1);
            padding: 6px 12px; border-radius: 8px;
        }
        .jadwal-card .quota {
            font-size: 13px; font-weight: 600;
            padding: 6px 14px; border-radius: 8px;
        }
        .quota.available { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .quota.full { background: rgba(239, 68, 68, 0.15); color: var(--danger); }

        /* ─── Antrian Live ───────────────────────────────────────── */
        #antrian { background: linear-gradient(180deg, var(--bg-dark) 0%, #0c1526 100%); padding: 100px 48px; }
        .antrian-container { max-width: 1200px; margin: 0 auto; }
        .antrian-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
        .antrian-card {
            padding: 28px; border-radius: var(--radius);
            background: var(--bg-card); border: 1px solid var(--border);
            text-align: center; transition: var(--transition);
            position: relative; overflow: hidden;
        }
        .antrian-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-glow); }
        .antrian-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: var(--gradient-hero);
        }
        .antrian-card .poli { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-light); margin-bottom: 16px; }
        .antrian-card .current-number {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 42px; font-weight: 900; color: var(--accent);
            margin-bottom: 6px;
            text-shadow: 0 0 30px rgba(245, 158, 11, 0.3);
        }
        .antrian-card .current-label { font-size: 12px; color: var(--text-muted); margin-bottom: 20px; }
        .antrian-stats { display: flex; justify-content: center; gap: 24px; }
        .antrian-stats .stat { text-align: center; }
        .antrian-stats .stat-val { font-size: 20px; font-weight: 800; }
        .antrian-stats .stat-val.waiting { color: var(--accent); }
        .antrian-stats .stat-val.done { color: var(--success); }
        .antrian-stats .stat-lbl { font-size: 11px; color: var(--text-muted); }
        .antrian-refresh {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 36px; font-size: 13px; color: var(--text-muted);
        }
        .antrian-refresh .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--success); animation: pulse 2s infinite; }

        /* ─── Pendaftaran Online ──────────────────────────────────── */
        #daftar { padding: 100px 48px; }
        .daftar-container { max-width: 1200px; margin: 0 auto; display: flex; gap: 48px; align-items: flex-start; }
        .daftar-info { flex: 1; }
        .daftar-info h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px; font-weight: 800; margin-bottom: 16px;
        }
        .daftar-info p { color: var(--text-secondary); margin-bottom: 24px; line-height: 1.7; }
        .daftar-steps { list-style: none; }
        .daftar-steps li {
            display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px;
            padding: 16px; border-radius: var(--radius-sm); background: var(--bg-card);
            border: 1px solid var(--border);
        }
        .step-num {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--gradient-hero); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px; flex-shrink: 0;
        }
        .step-text h5 { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .step-text p { font-size: 13px; color: var(--text-muted); }

        .daftar-form {
            flex: 1; padding: 36px; border-radius: var(--radius);
            background: var(--bg-card); border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
        }
        .daftar-form h4 { font-size: 20px; font-weight: 700; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
        .form-group input, .form-group select {
            width: 100%; padding: 12px 16px; border-radius: var(--radius-sm);
            background: var(--bg-dark); border: 1px solid var(--border);
            color: var(--text-primary); font-size: 14px; font-family: inherit;
            transition: var(--transition); outline: none;
        }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.15); }
        .form-group select option { background: var(--bg-dark); }
        .btn-submit {
            width: 100%; padding: 14px; border-radius: var(--radius-sm);
            background: var(--gradient-accent); color: #1a1a2e;
            font-size: 15px; font-weight: 700; border: none; cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(245, 158, 11, 0.5); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .form-result {
            margin-top: 16px; padding: 16px; border-radius: var(--radius-sm);
            display: none; font-size: 14px;
        }
        .form-result.success { display: block; background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16,185,129,0.3); }
        .form-result.error { display: block; background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); }

        /* ─── Footer ────────────────────────────────────────────── */
        .footer {
            padding: 60px 48px 30px;
            background: #0a0f1e; border-top: 1px solid var(--border);
        }
        .footer-grid { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px; margin-bottom: 48px; }
        .footer-brand h3 { font-size: 20px; font-weight: 800; margin-bottom: 12px; }
        .footer-brand p { font-size: 13px; color: var(--text-muted); line-height: 1.7; margin-bottom: 16px; }
        .footer-brand .emergency {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: 10px;
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239,68,68,0.3);
            color: var(--danger); font-size: 14px; font-weight: 700;
        }
        .footer-col h5 { font-size: 14px; font-weight: 700; margin-bottom: 18px; color: var(--text-primary); }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { color: var(--text-muted); text-decoration: none; font-size: 13px; transition: var(--transition); }
        .footer-col ul li a:hover { color: var(--primary-light); }
        .footer-bottom {
            max-width: 1200px; margin: 0 auto;
            padding-top: 24px; border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            font-size: 12px; color: var(--text-muted);
        }

        /* ─── Loading Skeleton ───────────────────────────────────── */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-card) 25%, var(--bg-card-hover) 50%, var(--bg-card) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: var(--radius-sm);
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 900px) {
            .navbar { padding: 12px 20px; }
            .nav-links { display: none; }
            .hero { padding: 100px 20px 60px; }
            .hero-content { flex-direction: column; text-align: center; gap: 40px; }
            .hero-text p { margin: 0 auto 30px; }
            .hero-actions { justify-content: center; }
            .section { padding: 60px 20px; }
            #antrian { padding: 60px 20px; }
            .daftar-container { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
            .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
            .jadwal-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .hero-text h2 { font-size: 28px; }
            .antrian-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ─── Navbar ─────────────────────────────────────────────────── -->
<nav class="navbar" id="navbar">
    <a href="/" class="nav-brand">
        <div class="logo-icon">🏥</div>
        <div>
            <h1>RSUD Puruk Cahu</h1>
            <small>Kabupaten Murung Raya</small>
        </div>
    </a>
    <ul class="nav-links">
        <li><a href="#jadwal">Jadwal Dokter</a></li>
        <li><a href="#antrian">Antrian Live</a></li>
        <li><a href="#daftar">Daftar Online</a></li>
        <li><a href="#kontak">Kontak</a></li>
    </ul>
    <a href="/login" class="btn-login">🔐 Login SIMRS</a>
</nav>

<!-- ─── Hero Section ──────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <div class="hero-badge"><span class="pulse"></span> IGD & Ambulance 24 Jam</div>
            <h2>Pelayanan Kesehatan Bermutu untuk Masyarakat Murung Raya</h2>
            <p>Rumah Sakit Umum Daerah Puruk Cahu hadir melayani dengan fasilitas lengkap, dokter spesialis berpengalaman, dan sistem informasi terintegrasi demi kesehatan Anda.</p>
            <div class="hero-actions">
                <a href="#daftar" class="btn-hero primary">📋 Daftar Online Sekarang</a>
                <a href="#jadwal" class="btn-hero secondary">🕐 Lihat Jadwal Dokter</a>
            </div>
        </div>
        <div class="hero-cards">
            <div class="hero-stat-card">
                <div class="hero-stat-icon igd">🚨</div>
                <div class="hero-stat-text"><h4>IGD 24 Jam</h4><p>Siap melayani keadaan darurat</p></div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-icon poli">🩺</div>
                <div class="hero-stat-text"><h4>7 Poliklinik Spesialis</h4><p>Umum, Anak, Obgyn, Gigi, Bedah, Mata, Saraf</p></div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-icon lab">🔬</div>
                <div class="hero-stat-text"><h4>Laboratorium & Radiologi</h4><p>Pemeriksaan darah, urin, rontgen, USG</p></div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-icon ambulance">🚑</div>
                <div class="hero-stat-text"><h4>Ambulance 24 Jam</h4><p>Hubungi 0882-1529-0459</p></div>
            </div>
        </div>
    </div>
</section>

<!-- ─── Jadwal Dokter ─────────────────────────────────────────── -->
<section class="section" id="jadwal">
    <div class="section-header">
        <span class="label">Jadwal Praktek</span>
        <h3>Jadwal Dokter Spesialis</h3>
        <p>Cek ketersediaan dan jadwal praktek dokter di seluruh poliklinik RSUD Puruk Cahu</p>
    </div>
    <div class="jadwal-tabs" id="jadwalTabs"></div>
    <div class="jadwal-grid" id="jadwalGrid">
        <div class="jadwal-card skeleton" style="height:160px"></div>
        <div class="jadwal-card skeleton" style="height:160px"></div>
        <div class="jadwal-card skeleton" style="height:160px"></div>
    </div>
</section>

<!-- ─── Antrian Live ──────────────────────────────────────────── -->
<section id="antrian">
    <div class="antrian-container">
        <div class="section-header">
            <span class="label">Real-Time</span>
            <h3>Pantau Antrian Poliklinik</h3>
            <p>Lihat nomor antrian yang sedang dilayani secara langsung tanpa perlu datang ke loket</p>
        </div>
        <div class="antrian-grid" id="antrianGrid">
            <div class="antrian-card skeleton" style="height:200px"></div>
            <div class="antrian-card skeleton" style="height:200px"></div>
            <div class="antrian-card skeleton" style="height:200px"></div>
        </div>
        <div class="antrian-refresh">
            <span class="dot"></span> Data diperbarui otomatis setiap 15 detik
        </div>
    </div>
</section>

<!-- ─── Pendaftaran Online ────────────────────────────────────── -->
<section class="section" id="daftar">
    <div class="daftar-container">
        <div class="daftar-info">
            <span class="section-header"><span class="label" style="margin-bottom:16px">Layanan Digital</span></span>
            <h3 style="margin-top:16px">Daftar Antrian dari Rumah</h3>
            <p>Tidak perlu lagi mengantre pagi-pagi di loket pendaftaran. Daftar antrian rawat jalan secara online dan datang saat nomor antrian Anda hampir dipanggil.</p>
            <ul class="daftar-steps">
                <li><div class="step-num">1</div><div class="step-text"><h5>Isi Data Diri</h5><p>Masukkan NIK atau Nomor BPJS Anda</p></div></li>
                <li><div class="step-num">2</div><div class="step-text"><h5>Pilih Poliklinik & Dokter</h5><p>Pilih poli tujuan dan dokter yang diinginkan</p></div></li>
                <li><div class="step-num">3</div><div class="step-text"><h5>Dapatkan Nomor Antrian</h5><p>Nomor antrian akan dikirim via SMS/WhatsApp</p></div></li>
                <li><div class="step-num">4</div><div class="step-text"><h5>Datang Sesuai Jadwal</h5><p>Hadir 15 menit sebelum nomor Anda dipanggil</p></div></li>
            </ul>
        </div>
        <div class="daftar-form">
            <h4>📋 Form Pendaftaran Online</h4>
            <form id="formDaftar" onsubmit="return handleDaftar(event)">
                <div class="form-group">
                    <label for="nik">NIK (Nomor Induk Kependudukan)</label>
                    <input type="text" id="nik" placeholder="Masukkan 16 digit NIK" maxlength="16" required>
                </div>
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" placeholder="Sesuai KTP" required>
                </div>
                <div class="form-group">
                    <label for="telepon">Nomor HP / WhatsApp</label>
                    <input type="tel" id="telepon" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="form-group">
                    <label for="poliSelect">Pilih Poliklinik Tujuan</label>
                    <select id="poliSelect" required>
                        <option value="">-- Pilih Poliklinik --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dokterSelect">Pilih Dokter</label>
                    <select id="dokterSelect" required>
                        <option value="">-- Pilih Dokter --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="jenisBayar">Jenis Pembayaran</label>
                    <select id="jenisBayar" required>
                        <option value="UMUM">Umum (Bayar Sendiri)</option>
                        <option value="BPJS">BPJS Kesehatan</option>
                    </select>
                </div>
                <div class="form-group" id="bpjsGroup" style="display:none">
                    <label for="noBpjs">Nomor Kartu BPJS</label>
                    <input type="text" id="noBpjs" placeholder="Masukkan 13 digit No. BPJS" maxlength="13">
                </div>
                <div class="form-group">
                    <label for="keluhan">Keluhan Utama</label>
                    <input type="text" id="keluhan" placeholder="Contoh: Demam 3 hari, batuk pilek">
                </div>
                <button type="submit" class="btn-submit" id="btnDaftar">📩 Daftar Sekarang</button>
                <div class="form-result" id="formResult"></div>
            </form>
        </div>
    </div>
</section>

<!-- ─── Footer ────────────────────────────────────────────────── -->
<footer class="footer" id="kontak">
    <div class="footer-grid">
        <div class="footer-brand">
            <h3>🏥 RSUD Puruk Cahu</h3>
            <p>Rumah Sakit Umum Daerah Kabupaten Murung Raya, Kalimantan Tengah. Melayani dengan sepenuh hati untuk kesehatan masyarakat.</p>
            <div class="emergency">🚨 IGD & Ambulance: 0882-1529-0459</div>
        </div>
        <div class="footer-col">
            <h5>Pelayanan</h5>
            <ul>
                <li><a href="#">Rawat Jalan</a></li>
                <li><a href="#">Rawat Inap</a></li>
                <li><a href="#">IGD 24 Jam</a></li>
                <li><a href="#">Laboratorium</a></li>
                <li><a href="#">Radiologi</a></li>
                <li><a href="#">Farmasi</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h5>Poliklinik</h5>
            <ul>
                <li><a href="#">Poli Umum</a></li>
                <li><a href="#">Poli Anak</a></li>
                <li><a href="#">Poli Kebidanan</a></li>
                <li><a href="#">Poli Gigi</a></li>
                <li><a href="#">Poli Bedah</a></li>
                <li><a href="#">Poli Mata</a></li>
                <li><a href="#">Poli Saraf</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h5>Kontak</h5>
            <ul>
                <li><a href="#">📍 Jl. Jend. Sudirman No. 1, Puruk Cahu</a></li>
                <li><a href="#">📞 0882-1529-0459</a></li>
                <li><a href="#">📧 denynz17@gmail.com</a></li>
                <li><a href="#">🕐 Senin - Jumat: 08:00 - 14:00</a></li>
                <li><a href="#">🕐 Sabtu: 08:00 - 12:00</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; 2026 RSUD Puruk Cahu — Kabupaten Murung Raya, Kalimantan Tengah</span>
        <span>Powered by SIMRS Rawat Jalan v1.0</span>
    </div>
</footer>

<script>
// ─── API Base URL ──────────────────────────────────────────────
const API = '/api/public';

// ─── Navbar Scroll Effect ──────────────────────────────────────
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
});

// ─── Jadwal Dokter ─────────────────────────────────────────────
const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
let jadwalData = [];
let activeHari = '';

async function loadJadwal(hari = '') {
    const grid = document.getElementById('jadwalGrid');
    grid.innerHTML = '<div class="jadwal-card skeleton" style="height:160px"></div>'.repeat(3);

    try {
        const param = hari ? `?hari=${hari}` : '';
        const res = await fetch(`${API}/jadwal-dokter${param}`);
        const json = await res.json();
        jadwalData = json.data || [];
        activeHari = json.hari || '';
        renderJadwal();
    } catch (e) {
        grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;grid-column:1/-1">⚠️ Gagal memuat jadwal dokter. Pastikan server berjalan.</p>';
    }
}

function renderJadwalTabs() {
    const tabs = document.getElementById('jadwalTabs');
    tabs.innerHTML = HARI.map(h =>
        `<button class="jadwal-tab ${h === activeHari ? 'active' : ''}" onclick="loadJadwal('${h}')">${h}</button>`
    ).join('') + `<button class="jadwal-tab" onclick="loadJadwal('semua')">Semua</button>`;
}

function renderJadwal() {
    renderJadwalTabs();
    const grid = document.getElementById('jadwalGrid');
    if (!jadwalData.length) {
        grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;grid-column:1/-1">Tidak ada jadwal dokter untuk hari ini.</p>';
        return;
    }
    grid.innerHTML = jadwalData.map(j => `
        <div class="jadwal-card">
            <div class="poli-name">${j.kode_poli} — ${j.poli}</div>
            <div class="doctor-name">${j.dokter}</div>
            <div class="spec">${j.spesialisasi}</div>
            <div class="meta">
                <span class="time">🕐 ${j.jam}</span>
                <span class="quota ${j.sisa_kuota > 0 ? 'available' : 'full'}">
                    ${j.sisa_kuota > 0 ? `Sisa ${j.sisa_kuota} kuota` : 'Kuota penuh'}
                </span>
            </div>
        </div>
    `).join('');
}

// ─── Antrian Live ──────────────────────────────────────────────
async function loadAntrian() {
    const grid = document.getElementById('antrianGrid');
    try {
        const res = await fetch(`${API}/antrian-live`);
        const json = await res.json();
        const data = json.data || [];
        if (!data.length) {
            grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;grid-column:1/-1">Belum ada data antrian hari ini.</p>';
            return;
        }
        grid.innerHTML = data.map(a => `
            <div class="antrian-card">
                <div class="poli">${a.kode_poli} — ${a.poli}</div>
                <div class="current-number">${a.sedang_dilayani}</div>
                <div class="current-label">Sedang Dilayani</div>
                <div class="antrian-stats">
                    <div class="stat"><div class="stat-val waiting">${a.jumlah_menunggu}</div><div class="stat-lbl">Menunggu</div></div>
                    <div class="stat"><div class="stat-val done">${a.jumlah_selesai}</div><div class="stat-lbl">Selesai</div></div>
                    <div class="stat"><div class="stat-val" style="color:var(--text-primary)">${a.total_hari_ini}</div><div class="stat-lbl">Total</div></div>
                </div>
            </div>
        `).join('');
    } catch (e) {
        grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;grid-column:1/-1">⚠️ Gagal memuat data antrian.</p>';
    }
}

// ─── Form Pendaftaran ──────────────────────────────────────────
let poliList = [];

async function loadPoliOptions() {
    try {
        const res = await fetch(`${API}/jadwal-dokter?hari=semua`);
        const json = await res.json();
        const data = json.data || [];
        // Unique polis
        const seen = new Set();
        poliList = [];
        data.forEach(j => {
            const key = j.kode_poli;
            if (!seen.has(key)) {
                seen.add(key);
                poliList.push({ kode: j.kode_poli, nama: j.poli, dokterList: [] });
            }
            const poli = poliList.find(p => p.kode === key);
            poli.dokterList.push({ id: j.id, nama: j.dokter, spec: j.spesialisasi });
        });
        const sel = document.getElementById('poliSelect');
        sel.innerHTML = '<option value="">-- Pilih Poliklinik --</option>' +
            poliList.map(p => `<option value="${p.kode}">${p.nama}</option>`).join('');
    } catch (e) {
        console.error('Gagal memuat poli:', e);
    }
}

document.getElementById('poliSelect')?.addEventListener('change', function() {
    const poli = poliList.find(p => p.kode === this.value);
    const sel = document.getElementById('dokterSelect');
    sel.innerHTML = '<option value="">-- Pilih Dokter --</option>';
    if (poli) {
        sel.innerHTML += poli.dokterList.map(d => `<option value="${d.id}">${d.nama} (${d.spec})</option>`).join('');
    }
});

document.getElementById('jenisBayar')?.addEventListener('change', function() {
    document.getElementById('bpjsGroup').style.display = this.value === 'BPJS' ? 'block' : 'none';
});

async function handleDaftar(e) {
    e.preventDefault();
    const btn = document.getElementById('btnDaftar');
    const result = document.getElementById('formResult');
    btn.disabled = true;
    btn.textContent = '⏳ Memproses...';
    result.className = 'form-result';
    result.style.display = 'none';

    try {
        // Step 1: Search or register patient
        const nik = document.getElementById('nik').value;
        const searchRes = await fetch(`/api/pasien/cari?keyword=${nik}`);
        const searchJson = await searchRes.json();
        let patientId;

        if (searchJson.data && searchJson.data.length > 0) {
            patientId = searchJson.data[0].id;
        } else {
            // Register new patient
            const regRes = await fetch('/api/pasien', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    nik: nik,
                    nama_lengkap: document.getElementById('nama').value,
                    no_telepon: document.getElementById('telepon').value,
                    jenis_kelamin: '-',
                    jenis_pasien: document.getElementById('jenisBayar').value === 'BPJS' ? 'BPJS' : 'Umum',
                    no_bpjs: document.getElementById('noBpjs').value || null,
                    alamat: 'Murung Raya',
                })
            });
            const regJson = await regRes.json();
            patientId = regJson.data?.id;
        }

        if (!patientId) throw new Error('Gagal mendapatkan ID pasien.');

        result.className = 'form-result success';
        result.innerHTML = `✅ <strong>Pendaftaran Berhasil!</strong><br>
            Pasien telah terdaftar dengan ID: <strong>${patientId}</strong>.<br>
            Silakan datang ke loket pendaftaran RSUD Puruk Cahu untuk mengambil nomor antrian Anda.<br>
            <small>Nomor antrian otomatis akan tersedia setelah modul admisi diaktifkan sepenuhnya.</small>`;
        result.style.display = 'block';
    } catch (err) {
        result.className = 'form-result error';
        result.innerHTML = `❌ ${err.message || 'Terjadi kesalahan. Silakan coba lagi.'}`;
        result.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = '📩 Daftar Sekarang';
    }
}

// ─── Init & Auto Refresh ───────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadJadwal();
    loadAntrian();
    loadPoliOptions();

    // Auto-refresh antrian setiap 15 detik
    setInterval(loadAntrian, 15000);
});
</script>
</body>
</html>
