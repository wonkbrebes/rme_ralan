<?php
// Memuat koneksi database & sumber data terpusat (sudah dinamis)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/simrs_data.php';

// Variabel pasien sudah di-load dari simrs_data.php (query DB)
// Variabel: $nama_pasien, $no_rm, $nik, $tgl_lahir, $alamat, $telepon, $penjamin, $dokter, $poli, $jenis_pasien

// Menyesuaikan active nav untuk Kasir
foreach ($nav_items as &$item) {
    if ($item['page'] === 'kasir') {
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
    <title>Kasir – SIMRS Clinical Precision</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-primary: #2e7d32; --green-mid: #388e3c; --green-light: #4caf50;
            --green-pale: #e8f5e9; --green-xpale: #f1f8f2; --sidebar-w: 230px;
            --white: #ffffff; --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-400: #9ca3af; --gray-600: #4b5563; --gray-800: #1f2937;
            --font: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        body { font-family: var(--font); background: var(--gray-100); color: var(--gray-800); display: flex; min-height: 100vh; }

        /* ── SIDEBAR ── */
        .sidebar { width: var(--sidebar-w); background: var(--white); display: flex; flex-direction: column; border-right: 1px solid var(--gray-200); position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; }
        .sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 20px 18px 18px; border-bottom: 1px solid var(--gray-200); }
        .brand-icon { width: 40px; height: 40px; background: var(--green-primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--white); }
        .brand-text h2 { font-size: 15px; font-weight: 700; color: var(--green-primary); }
        .brand-text p  { font-size: 11px; color: var(--gray-400); }
        .nav-list { list-style: none; padding: 10px 0; flex: 1; }
        .nav-list li a { display: flex; align-items: center; gap: 10px; padding: 10px 18px; font-size: 13px; color: var(--gray-600); text-decoration: none; border-left: 3px solid transparent; }
        .nav-list li a.active { background: var(--green-xpale); color: var(--green-primary); border-left: 3px solid var(--green-primary); font-weight: 600; }

        /* ── MAIN AREA ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }
        .topbar { background: var(--white); border-bottom: 1px solid var(--gray-200); padding: 0 28px; height: 64px; display: flex; align-items: center; justify-content: space-between; }
        .topbar-left h1 { font-size: 20px; font-weight: 700; color: var(--green-primary); }

        /* ── GRID CONTENT ── */
        .content { padding: 24px 28px; display: grid; grid-template-columns: 1fr 350px; gap: 20px; align-items: start; }

        /* BOX SIMULASI */
        .simulasi-box { grid-column: 1 / -1; background: #fffbeb; border: 1px dashed #f59e0b; padding: 15px; border-radius: 10px; display: flex; gap: 15px; align-items: center; }
        .simulasi-box span { font-size: 12px; font-weight: bold; color: #b45309; }
        .btn-sim { padding: 6px 12px; background: #f59e0b; color: white; text-decoration: none; font-size: 12px; font-weight: 600; border-radius: 6px; }
        .btn-sim:hover { background: #d97706; }

        /* SEARCH CARD */
        .search-patient-card { grid-column: 1 / -1; background: var(--white); border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06); display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--green-primary); }
        .search-form-flex { display: flex; gap: 10px; width: 450px; }
        .input-search-pasien { flex: 1; padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 13px; outline: none; }
        .btn-search-submit { padding: 8px 16px; background: var(--green-primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }

        /* PROFILE BAR */
        .patient-profile-bar { display: flex; gap: 20px; padding: 20px; grid-column: 1 / -1; background: var(--white); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .patient-avatar-box { width: 56px; height: 56px; border-radius: 50%; background: var(--green-pale); display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-size: 24px; }
        .patient-details-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; flex: 1; }
        .p-group { display: flex; flex-direction: column; }
        .p-group label { font-size: 10px; color: var(--gray-400); text-transform: uppercase; font-weight: 600; }
        .p-group span, .p-group strong { font-size: 13px; color: var(--gray-800); }

        /* CARD COMPONENT */
        .card { background: var(--white); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden; margin-bottom: 16px; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; }
        .card-header h2 { font-size: 14px; font-weight: 700; color: var(--gray-800); }

        /* TABLE RINCIAN TRANSAKSI */
        .table-action-bar { padding: 14px 20px; display: flex; justify-content: space-between; background: var(--white); border-bottom: 1px solid var(--gray-100); }
        .search-input { padding: 6px 12px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 12px; width: 220px; outline: none; }
        .btn-add-item { padding: 6px 12px; background: var(--green-primary); color: var(--white); border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--green-primary); background: var(--green-xpale); border-bottom: 1px solid var(--gray-200); text-align: left; }
        td { padding: 12px 20px; font-size: 12px; border-bottom: 1px solid var(--gray-100); color: var(--gray-800); }

        /* COLUMN KANAN - PEMBAYARAN */
        .kasir-grid-container { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 20px; align-items: start; }
        .left-column { width: 100%; min-width: 0; }
        .right-column { display: flex; flex-direction: column; gap: 16px; width: 100%; min-width: 0; }
        .billing-summary-list { padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 13px; color: var(--gray-600); }
        
        .total-pay-box { background: var(--green-xpale); padding: 14px 16px; border-top: 1px dashed var(--gray-200); border-bottom: 1px dashed var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .total-pay-box strong { font-size: 18px; color: var(--green-primary); font-weight: 700; }

        .pay-form { padding: 16px; display: flex; flex-direction: column; gap: 14px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 11px; font-weight: 600; color: var(--gray-400); text-transform: uppercase; }
        
        .select-container { position: relative; display: flex; align-items: center; }
        .select-container i { position: absolute; left: 12px; color: var(--gray-600); font-size: 14px; }
        .select-pay { width: 100%; padding: 10px 10px 10px 36px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 13px; outline: none; background: var(--white); font-weight: 600; color: var(--gray-800); -webkit-appearance: none; appearance: none; }
        .select-container::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 12px; color: var(--gray-400); pointer-events: none; }

        .input-money-container { position: relative; display: flex; align-items: center; }
        .currency-prefix { position: absolute; left: 12px; font-size: 13px; font-weight: 600; color: var(--gray-400); }
        .money-input { width: 100%; padding: 10px 12px 10px 38px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 14px; font-weight: 700; text-align: right; color: var(--gray-800); outline: none; }
        
        .nominal-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .btn-nom { padding: 10px; border: 1px solid var(--gray-200); border-radius: 6px; background: var(--white); font-size: 12px; font-weight: 600; color: var(--gray-600); cursor: pointer; text-align: center; }
        .btn-nom:hover { border-color: var(--green-light); color: var(--green-primary); }
        .btn-nom.active { background: var(--green-primary); color: var(--white); border-color: var(--green-primary); }

        .change-box { background: var(--green-xpale); padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .change-box strong { color: var(--green-primary); font-size: 14px; }

        .btn-submit-pay { width: calc(100% - 32px); margin: 0 16px 16px; padding: 12px; border: none; border-radius: 8px; background: var(--green-primary); color: var(--white); font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit-pay:hover { background: var(--green-mid); }

        /* ===== RESPONSIF TOTAL (DESKTOP, TABLET, MOBILE) ===== */
        @media (max-width: 1024px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .chart-grid, .lower-grid, .pendaftaran-grid, .triple-grid, .poli-grid, .kasir-grid-container { grid-template-columns: 1fr; gap: 16px; }
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
        <div class="brand-text"><h2>SIMRS</h2><p>Clinical Precision</p></div>
    </div>
    <ul class="nav-list">
        <?php foreach ($nav_items as $item): ?>
            <li><a href="?page=<?= isset($item['page']) ? $item['page'] : 'dashboard' ?>" class="<?= !empty($item['active']) ? 'active':'' ?>"><i class="fa-solid <?= $item['icon'] ?>"></i> <?= $item['label'] ?></a></li>
        <?php endforeach; ?>
    </ul>
</aside>

<div class="main">
    <header class="topbar">
        <div class="topbar-left"><h1>Modul Kasir</h1></div>
    </header>

    <main class="content">
        
        <section class="search-patient-card">
            <h3>Cari Pasien:</h3>
            <form action="" method="POST" class="search-form-flex">
                <input type="text" name="keyword" class="input-search-pasien" placeholder="Masukkan Nama Pasien / No. RM..." required autocomplete="off">
                <button type="submit" name="cari_pasien" class="btn-search-submit"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
            </form>
        </section>

        <section class="patient-profile-bar">
            <div class="patient-avatar-box"><i class="fa-solid fa-user"></i></div>
            <div class="patient-details-grid">
                <div class="p-group"><label>Nama Pasien</label><strong><?= $nama_pasien ?></strong></div>
                <div class="p-group"><label>No. RM</label><span><?= $no_rm ?></span></div>
                <div class="p-group"><label>NIK</label><span><?= $nik ?></span></div>
                <div class="p-group"><label>Tanggal Lahir</label><span><?= $tgl_lahir ?></span></div>
                <div class="p-group"><label>Alamat Pasien</label><span><?= $alamat ?></span></div>
                <div class="p-group"><label>No. Telepon</label><span><?= $telepon ?></span></div>
                <div class="p-group"><label>Dokter & Poli Penanggung</label><span><?= $dokter ?> / <?= $poli ?></span></div>
                <div class="p-group"><label>Jenis Penjamin</label><span style="font-weight:700; color:var(--green-primary);"><?= $penjamin ?></span></div>
            </div>
        </section>

        <div class="kasir-grid-container">
        <div class="left-column">
            <div class="card">
                <div class="card-header"><h2>Rincian Transaksi Tindakan & Obat</h2></div>
                <div class="table-action-bar">
                    <input type="text" class="search-input" placeholder="Cari tindakan tambahan...">
                    <button class="btn-add-item">+ Tambah Tindakan</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th>Deskripsi Tindakan / Layanan</th>
                            <th>Kategori</th>
                            <th style="text-align: center; width: 60px;">Qty</th>
                            <th style="text-align: right;">Harga</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($detail_transaksi)): ?>
                        <?php foreach ($detail_transaksi as $row): ?>
                        <tr>
                            <td style="text-align: center; color: var(--gray-400);"><?= $row['no'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['deskripsi']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td style="text-align: center; font-weight: 600;"><?= $row['qty'] ?></td>
                            <td style="text-align: right;">Rp <?= $row['harga'] ?></td>
                            <td style="text-align: right; font-weight: 600;">Rp <?= $row['total'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--gray-400); padding: 30px;">Belum ada transaksi. Pilih pasien untuk melihat rincian.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="right-column">
            <div class="card">
                <div class="card-header"><h2>Ringkasan Biaya</h2></div>
                <?php
                    $subtotal = 0;
                    foreach ($detail_transaksi as $row) {
                        $subtotal += intval(str_replace('.', '', $row['total']));
                    }
                    $subtotal_fmt = number_format($subtotal, 0, ',', '.');
                ?>
                <div class="billing-summary-list">
                    <div class="summary-row"><span>Subtotal Layanan</span><strong>Rp <?= $subtotal_fmt ?></strong></div>
                    <div class="summary-row"><span>Diskon Medis</span><strong>Rp 0</strong></div>
                    <div class="summary-row"><span>Pajak / Admin RS</span><strong>Rp 0</strong></div>
                </div>
                <div class="total-pay-box">
                    <span>Total Tagihan:</span>
                    <strong>Rp <?= $subtotal_fmt ?></strong>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2>Form Transaksi Pembayaran</h2></div>
                <div class="pay-form">
                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <div class="select-container">
                            <i class="fa-solid fa-money-bill-wave" id="pay-icon"></i>
                            <select class="select-pay" id="payment-method" onchange="updatePaymentIcon()">
                                <option value="Tunai">Tunai / Cash</option>
                                <option value="QRIS">QRIS / Digital Payment</option>
                                <option value="Debit">Debit Card / Transfer Bank</option>
                                <option value="BPJS">Jaminan BPJS Kesehatan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Uang Diterima</label>
                        <div class="input-money-container">
                            <span class="currency-prefix">Rp</span>
                            <input type="text" class="money-input" value="<?= $subtotal_fmt ?>">
                        </div>
                    </div>

                    <div class="nominal-grid">
                        <?php foreach ($nominal_cepat as $nom): ?>
                            <button class="btn-nom"><?= $nom ?></button>
                        <?php endforeach; ?>
                    </div>

                    <div class="change-box">
                        <span>Uang Kembalian:</span>
                        <strong>Rp 0</strong>
                    </div>
                </div>
                
                <button class="btn-submit-pay" onclick="alert('Transaksi Berhasil Disimpan & Struk Dicetak!')">
                    <i class="fa-solid fa-print"></i> Proses & Cetak Struk
                </button>
            </div>
        </div>
        </div>

    </main>
</div>

<script>
// Fungsi JavaScript untuk mengubah ikon hitam putih di samping pilihan metode pembayaran secara dinamis
function updatePaymentIcon() {
    const method = document.getElementById('payment-method').value;
    const icon = document.getElementById('pay-icon');
    
    if (method === 'Tunai') {
        icon.className = 'fa-solid fa-money-bill-wave';
    } else if (method === 'QRIS') {
        icon.className = 'fa-solid fa-qrcode';
    } else if (method === 'Debit') {
        icon.className = 'fa-solid fa-credit-card';
    } else if (method === 'BPJS') {
        icon.className = 'fa-solid fa-hospital';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const moneyInput = document.querySelector('.money-input');
    const changeVal = document.querySelector('.change-box strong');
    const nomBtns = document.querySelectorAll('.btn-nom');
    const totalTagihan = <?= $subtotal ?>;
    const totalFmt = '<?= $subtotal_fmt ?>';

    function calcChange(val) {
        let numericVal = parseInt(val.replace(/\D/g, '')) || 0;
        let kembalian = numericVal - totalTagihan;
        if (kembalian < 0) kembalian = 0;
        if (changeVal) changeVal.textContent = 'Rp ' + kembalian.toLocaleString('id-ID');
    }

    nomBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            let txt = this.textContent.trim();
            if (txt === 'Pas Tagihan') {
                moneyInput.value = totalFmt;
                calcChange(String(totalTagihan));
            } else {
                moneyInput.value = txt;
                calcChange(txt);
            }
        });
    });

    if (moneyInput) {
        moneyInput.addEventListener('input', function() {
            calcChange(this.value);
        });
    }
});
</script>

</body>
</html>