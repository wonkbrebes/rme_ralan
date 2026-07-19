<?php
try {
    require_once __DIR__ . '/config.php';
    if (isset($_GET['page']) && $_GET['page'] !== 'pendaftaran') {
        header('Location: index.php?page=' . urlencode($_GET['page']));
        exit;
    }

    // HANDLE FORM SUBMISSION (POST) - Shared handler
    require_once __DIR__ . '/post_handler.php';

    // ============================================================
    // 1. AMBIL PARAMETER HALAMAN
    // ============================================================
    $page = isset($_GET['page']) ? $_GET['page'] : 'pendaftaran';
    if ($page === 'emr') {
        $page = 'emr_dokter';
    }

    // Memuat semua data dan sinkronisasi real-time database terpusat
    require_once __DIR__ . '/simrs_data.php';

// ============================================================
// 6. FUNGSI RENDER KONTEN
// ============================================================
function renderContent($page, $stats_dashboard, $antrian_terkini, $distribusi, $stats_antrian, $antrian, $sedang_dilayani, $status_poli, $info_hari_ini, $riwayat_pendaftaran) {
    switch ($page) {
        case 'dashboard':
            // ---------- DASHBOARD ----------
            ?>
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
                                $status_key = normalize_queue_status($row['status'] ?? '');
                                $status_label = get_queue_status_label($status_key);
                                $status_class = get_queue_badge_class($status_key);
                                ?>
                                <span class="badge-status <?= $status_class ?>"><?= htmlspecialchars($status_label) ?></span>
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
            <?php
            break;

        case 'antrian':
            // ---------- HALAMAN ANTRIAN ----------
            ?>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <div class="stat-label">Total Antrian Hari Ini</div>
                        <div class="stat-value"><?= $stats_antrian['total'] ?></div>
                        <div class="stat-unit">Pasien</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div>
                        <div class="stat-label">Sedang Dilayani</div>
                        <div class="stat-value"><?= $stats_antrian['dilayani'] ?></div>
                        <div class="stat-unit">Pasien</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="stat-label">Rata-rata Waktu Tunggu</div>
                        <div class="stat-value"><?= $stats_antrian['rata_tunggu'] ?></div>
                        <div class="stat-unit">Menit</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="stat-label">Selesai Hari Ini</div>
                        <div class="stat-value"><?= $stats_antrian['selesai'] ?></div>
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
                        <button class="btn-refresh">
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
                                    <?php
                                    $status_key = normalize_queue_status($row['status'] ?? '');
                                    $status_label = get_queue_status_label($status_key);
                                    $status_class = get_queue_badge_class($status_key);
                                    ?>
                                    <span class="badge-status <?= $status_class ?>"><?= htmlspecialchars($status_label) ?></span>
                                </td>
                                <td><button class="btn-detail">Detail</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <p>Menampilkan 1 - <?= count($antrian) ?> dari <?= $stats_antrian['total'] ?> antrian</p>
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
                                    <div class="progress-fill" style="width:<?= round($poli['sekarang']/max(1, $poli['total'])*100) ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'pendaftaran':
            // ---------- HALAMAN PENDAFTARAN ----------
            ?>
            <div class="pendaftaran-grid">
                <!-- Kolom Kiri: Form -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>Formulir Pendaftaran Pasien</h2>
                            <p>Lengkapi data pasien dengan benar</p>
                        </div>
                        <button type="button" class="btn-reset" onclick="this.closest('.card').querySelector('form').reset()">
                            <i class="fa-solid fa-rotate-right"></i> Reset Form
                        </button>
                    </div>
                    <form action="?page=pendaftaran" method="POST" id="form-pendaftaran">
                        <input type="hidden" name="action" value="register_pasien">
                        <input type="hidden" name="existing_patient_id" id="existing_patient_id" value="">
                        <div class="form-body">
                            <!-- Dropdown & Live Search Pilih Pasien Lama -->
                            <div class="form-section-title" style="background: #f0fdf4; color: #166534; padding: 14px 18px; border: 1.5px dashed #22c55e; border-radius: 10px; margin-bottom: 22px; position: relative;">
                                <div style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                        <div>
                                            <i class="fa-solid fa-user-check" style="font-size: 16px; margin-right: 6px;"></i> <b style="font-size: 15px;">Pilih dari Pasien Terdaftar (Pasien Lama - Live Search)</b>
                                            <div style="font-size: 12px; font-weight: normal; color: #15803d; margin-top: 3px;">Ketik No. RM, NIK, atau Nama Pasien untuk pencarian cepat & otomatis mengisi formulir.</div>
                                        </div>
                                        <button type="button" id="btn-reset-pasien-lama" onclick="resetPasienLama()" style="display: none; background: #fee2e2; color: #dc2626; border: 1px solid #f87171; border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s;"><i class="fa-solid fa-rotate-left"></i> Reset / Pasien Baru</button>
                                    </div>
                                    <div style="position: relative; width: 100%;">
                                        <div id="search-box-pasien-lama" style="display: flex; align-items: center; background: #ffffff; border: 2px solid #22c55e; border-radius: 8px; padding: 8px 14px; box-shadow: 0 2px 8px rgba(34, 197, 94, 0.12); transition: all 0.2s;">
                                            <i class="fa-solid fa-magnifying-glass" style="color: #16a34a; font-size: 16px; margin-right: 12px;"></i>
                                            <input type="text" id="input-live-pasien-lama" class="form-input" placeholder="🔍 Ketik No. RM (mis. RM-2026), NIK (16 digit), atau Nama Pasien..." style="border: none; outline: none; box-shadow: none; padding: 4px 0; font-size: 14px; font-weight: 600; width: 100%; background: transparent; color: #1e293b;" autocomplete="off" oninput="handleLiveSearchPasien(this.value)" onclick="handleLiveSearchPasien(this.value)">
                                            <span id="badge-terpilih-pasien" style="display: none; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid #86efac; white-space: nowrap;"><i class="fa-solid fa-check-circle"></i> Terpilih</span>
                                        </div>
                                        <!-- Hasil Pencarian -->
                                        <div id="dropdown-pasien-results" style="display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #ffffff; border: 1.5px solid #22c55e; border-radius: 8px; box-shadow: 0 12px 30px rgba(0,0,0,0.18); max-height: 280px; overflow-y: auto; z-index: 99999;">
                                        </div>
                                    </div>
                                    <select id="select-pasien-lama" class="form-select" style="display: none;" onchange="pilihPasienLama(this)">
                                        <option value="">-- Pilih Pasien Terdaftar --</option>
                                        <?php 
                                        global $daftar_polyclinics, $daftar_pasien_master, $daftar_dokter_aktif, $jadwal_dokter_list, $daftar_antrian_all;
                                        if (!empty($daftar_pasien_master)) {
                                            foreach ($daftar_pasien_master as $pm) {
                                                $data_json = htmlspecialchars(json_encode($pm), ENT_QUOTES, 'UTF-8');
                                                echo '<option value="' . $pm['id'] . '" data-pasien="' . $data_json . '">' . htmlspecialchars($pm['no_rm'] . ' - ' . $pm['nama_lengkap'] . ' (NIK: ' . ($pm['nik'] ?? '-') . ')') . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Data Pasien -->
                            <div class="form-section-title">
                                <i class="fa-solid fa-user"></i> Data Pasien
                            </div>
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>No. Rekam Medis</label>
                                    <input type="text" class="form-input form-input-locked" value="RM-2026-07-<?= rand(1000,9999) ?>" readonly>
                                    <i class="fa-solid fa-lock lock-icon"></i>
                                </div>
                                <div class="form-group">
                                    <label>Nama Lengkap<span>*</span></label>
                                    <input type="text" name="nama_lengkap" class="form-input" placeholder="Masukkan nama lengkap (Hanya huruf)" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,']/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>Jenis Kelamin<span>*</span></label>
                                    <select name="jenis_kelamin" class="form-select" required>
                                        <option value="" disabled selected>Pilih jenis kelamin</option>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>Tempat Lahir<span>*</span></label>
                                    <input type="text" name="tempat_lahir" class="form-input" placeholder="Masukkan tempat lahir (Hanya huruf)" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,'-]/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Lahir<span>*</span></label>
                                    <input type="date" name="tanggal_lahir" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label>Status Perkawinan</label>
                                    <select name="status_perkawinan" class="form-select">
                                        <option value="" disabled selected>Pilih status</option>
                                        <option value="Belum Kawin">Belum Kawin</option>
                                        <option value="Kawin">Kawin</option>
                                        <option value="Cerai">Cerai</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>No. KTP / NIK<span>*</span> (16 Angka)</label>
                                    <input type="text" name="nik" class="form-input" placeholder="16 digit angka NIK" maxlength="16" pattern="[0-9]{16}" title="NIK wajib 16 digit angka" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>No. BPJS (Jika ada - Angka)</label>
                                    <input type="text" name="no_bpjs" class="form-input" placeholder="13 digit angka BPJS" maxlength="13" pattern="[0-9]*" title="No BPJS hanya boleh angka" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                <div class="form-group">
                                    <label>Golongan Darah</label>
                                    <select name="golongan_darah" class="form-select">
                                        <option value="" disabled selected>Pilih golongan darah</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="AB">AB</option>
                                        <option value="O">O</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Pendaftaran Pelayanan & Dokter Praktek -->
                            <div class="form-section-title">
                                <i class="fa-solid fa-hospital"></i> Pendaftaran Pelayanan & Dokter Praktek
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Poliklinik Tujuan<span>*</span></label>
                                    <select name="polyclinic_id" id="polyclinic_id_select" class="form-select" onchange="filterDokterByPoli(this.value)" required>
                                        <option value="" disabled selected>Pilih poliklinik tujuan</option>
                                        <?php
                                        if (!empty($daftar_polyclinics)) {
                                            foreach ($daftar_polyclinics as $poli_opt) {
                                                echo '<option value="' . htmlspecialchars($poli_opt['id']) . '">' . htmlspecialchars($poli_opt['nama_poli']) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="1">Poli Umum</option>';
                                            echo '<option value="2">Poli Gigi</option>';
                                            echo '<option value="3">Poli Anak</option>';
                                            echo '<option value="6">Poli Mata</option>';
                                            echo '<option value="7">Poli THT</option>';
                                            echo '<option value="8">Poli Kulit</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Dokter Praktek Hari Ini (Spesialis / Umum)<span>*</span></label>
                                    <select name="dokter_id" id="dokter_id_select" class="form-select" onchange="updateEstimasiBiaya(this.options[this.selectedIndex])" required>
                                        <option value="">-- Pilih Dokter Praktek (Tarif otomatis ke Kasir) --</option>
                                        <?php
                                        if (!empty($daftar_dokter_aktif)) {
                                            foreach ($daftar_dokter_aktif as $dok_opt) {
                                                $is_sp = (stripos($dok_opt['jenis_dokter'] ?? '', 'spesialis') !== false || stripos($dok_opt['spesialisasi'] ?? '', 'spesialis') !== false || stripos($dok_opt['nama_lengkap'] ?? '', 'Sp.') !== false);
                                                $kat = $dok_opt['jenis_dokter'] ?? ($is_sp ? 'Spesialis' : 'Umum');
                                                $fee_dok = !empty($dok_opt['biaya_jasa']) ? floatval($dok_opt['biaya_jasa']) : ($is_sp ? 150000 : 50000);
                                                $label_dok = htmlspecialchars($dok_opt['nama_lengkap']) . " [Kategori: $kat - Tarif: Rp " . number_format($fee_dok, 0, ',', '.') . "]";
                                                echo '<option value="' . htmlspecialchars($dok_opt['dokter_id']) . '" data-poli="' . htmlspecialchars($dok_opt['polyclinic_id'] ?? '') . '">' . $label_dok . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Jenis Pasien / Penjamin<span>*</span></label>
                                    <select name="jenis_pasien" class="form-select" required>
                                        <option value="" disabled selected>Pilih jenis pasien</option>
                                        <option value="Umum">Umum / Pribadi</option>
                                        <option value="BPJS">BPJS Kesehatan</option>
                                        <option value="Asuransi Lain">Asuransi Lain</option>
                                        <option value="Gratis">Gratis</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Estimasi Biaya Jasa Dokter</label>
                                    <input type="text" id="estimasi_biaya_show" class="form-input form-input-locked" value="Rp 0 (Pilih Dokter)" readonly style="background: #f0fdf4; font-weight: 700; color: #16a34a; border-color: #86efac;">
                                </div>
                            </div>

                            <!-- Kontak & Alamat -->
                            <div class="form-section-title">
                                <i class="fa-solid fa-location-dot"></i> Kontak & Alamat
                            </div>

                            <div class="form-group">
                                <label>Alamat Lengkap<span>*</span></label>
                                <textarea name="alamat" class="form-textarea" placeholder="Masukkan alamat lengkap pasien" required></textarea>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>No. Telepon<span>*</span> (Angka / HP)</label>
                                    <input type="tel" name="no_telepon" class="form-input" placeholder="08xxxxxxxxxx" maxlength="15" pattern="[\+0-9]*" title="No Telepon hanya boleh angka" oninput="this.value = this.value.replace(/[^0-9+]/g, '')" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-input" placeholder="Masukkan email (opsional)">
                                </div>
                            </div>

                            <!-- Penanggung Jawab -->
                            <div class="form-section-title">
                                <i class="fa-solid fa-users-viewfinder"></i> Penanggung Jawab / Kontak Darurat
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>Nama Lengkap (Hanya huruf)</label>
                                    <input type="text" name="pj_nama" class="form-input" placeholder="Nama penanggung jawab" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,']/g, '')">
                                </div>
                                <div class="form-group">
                                    <label>Hubungan</label>
                                    <select name="pj_hubungan" class="form-select">
                                        <option value="" disabled selected>Pilih hubungan</option>
                                        <option value="Orang Tua">Orang Tua</option>
                                        <option value="Suami/Istri">Suami/Istri</option>
                                        <option value="Anak">Anak</option>
                                        <option value="Saudara">Saudara Kandung</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>No. Telepon (Angka / HP)</label>
                                    <input type="tel" name="pj_telepon" class="form-input" placeholder="08xxxxxxxxxx" maxlength="15" pattern="[\+0-9]*" oninput="this.value = this.value.replace(/[^0-9+]/g, '')">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="window.location.href='?page=dashboard'">Batal</button>
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Pendaftaran
                            </button>
                        </div>
                    </form>
                    <script>
                    let searchTimeoutPasien = null;

                    function handleLiveSearchPasien(keyword) {
                        const resultsDiv = document.getElementById('dropdown-pasien-results');
                        if (!resultsDiv) return;

                        if (searchTimeoutPasien) clearTimeout(searchTimeoutPasien);

                        const kw = (keyword || '').trim();

                        resultsDiv.innerHTML = '<div style="padding: 12px; text-align: center; color: #64748b; font-size: 13px;"><i class="fa-solid fa-spinner fa-spin"></i> Mencari data pasien...</div>';
                        resultsDiv.style.display = 'block';

                        searchTimeoutPasien = setTimeout(() => {
                            fetch(`api/search_pasien.php?keyword=${encodeURIComponent(kw)}`)
                                .then(response => {
                                    if (!response.ok) throw new Error('Network response was not ok');
                                    return response.json();
                                })
                                .then(res => {
                                    if (res.status === 'success') {
                                        renderPasienResults(res.data);
                                    } else {
                                        renderPasienResults([]);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error fetching pasien:', error);
                                    resultsDiv.innerHTML = '<div style="padding: 12px; text-align: center; color: #ef4444; font-size: 13px;"><i class="fa-solid fa-triangle-exclamation"></i> Gagal mengambil data dari server.</div>';
                                });
                        }, 300);
                    }

                    function renderPasienResults(list) {
                        const resultsDiv = document.getElementById('dropdown-pasien-results');
                        if (!resultsDiv) return;
                        if (!list || list.length === 0) {
                            resultsDiv.innerHTML = '<div style="padding: 16px; text-align: center; color: #64748b; font-size: 13px;"><i class="fa-solid fa-user-xmark" style="font-size: 20px; display: block; margin-bottom: 6px; color: #94a3b8;"></i> Pasien tidak ditemukan dengan kata kunci tersebut.</div>';
                            resultsDiv.style.display = 'block';
                            return;
                        }
                        let html = '<div style="padding: 6px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Daftar Pasien Cocok (' + list.length + ' hasil)</div>';
                        list.forEach((p) => {
                            const pJsonStr = encodeURIComponent(JSON.stringify(p));
                            html += `<div onclick="selectLivePasien('${pJsonStr}')" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#ffffff'">
                                <div>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 14px;">${p.nama_lengkap || '-'} <span style="font-size: 12px; color: #16a34a; background: #dcfce7; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">${p.no_rm || '-'}</span></div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;"><i class="fa-regular fa-id-card"></i> NIK: ${p.nik || '-'} &nbsp;|&nbsp; <i class="fa-solid fa-phone"></i> Telp: ${p.no_telepon || '-'} &nbsp;|&nbsp; <i class="fa-solid fa-location-dot"></i> ${p.alamat || '-'}</div>
                                </div>
                                <div style="color: #22c55e; font-size: 14px;"><i class="fa-solid fa-circle-check"></i> Pilih</div>
                            </div>`;
                        });
                        resultsDiv.innerHTML = html;
                        resultsDiv.style.display = 'block';
                    }

                    function selectLivePasien(pJsonEncoded) {
                        try {
                            const p = JSON.parse(decodeURIComponent(pJsonEncoded));
                            document.getElementById('existing_patient_id').value = p.id || '';
                            if (document.querySelector('#form-pendaftaran input[name="nama_lengkap"]')) document.querySelector('#form-pendaftaran input[name="nama_lengkap"]').value = p.nama_lengkap || '';
                            if (document.querySelector('#form-pendaftaran input[name="nik"]')) document.querySelector('#form-pendaftaran input[name="nik"]').value = p.nik || '';
                            if (document.querySelector('#form-pendaftaran input[name="tanggal_lahir"]')) document.querySelector('#form-pendaftaran input[name="tanggal_lahir"]').value = p.tanggal_lahir || '';
                            if (document.querySelector('#form-pendaftaran input[name="tempat_lahir"]')) document.querySelector('#form-pendaftaran input[name="tempat_lahir"]').value = p.tempat_lahir || '';
                            if (document.querySelector('#form-pendaftaran select[name="jenis_kelamin"]')) document.querySelector('#form-pendaftaran select[name="jenis_kelamin"]').value = p.jenis_kelamin || 'Laki-laki';
                            if (document.querySelector('#form-pendaftaran input[name="no_telepon"]')) document.querySelector('#form-pendaftaran input[name="no_telepon"]').value = p.no_telepon || '';
                            if (document.querySelector('#form-pendaftaran input[name="no_bpjs"]')) document.querySelector('#form-pendaftaran input[name="no_bpjs"]').value = p.no_bpjs || '';
                            if (document.querySelector('#form-pendaftaran select[name="golongan_darah"]')) document.querySelector('#form-pendaftaran select[name="golongan_darah"]').value = p.gol_darah || '';
                            if (document.querySelector('#form-pendaftaran select[name="jenis_pasien"]')) document.querySelector('#form-pendaftaran select[name="jenis_pasien"]').value = p.jenis_pasien || 'Umum';
                            if (document.querySelector('#form-pendaftaran textarea[name="alamat"]') || document.querySelector('#form-pendaftaran input[name="alamat"]')) {
                                const el = document.querySelector('#form-pendaftaran textarea[name="alamat"]') || document.querySelector('#form-pendaftaran input[name="alamat"]');
                                el.value = p.alamat || '';
                            }
                            
                            const sel = document.getElementById('select-pasien-lama');
                            if (sel) sel.value = p.id || '';

                            const inputLive = document.getElementById('input-live-pasien-lama');
                            if (inputLive) inputLive.value = `[${p.no_rm}] ${p.nama_lengkap} (NIK: ${p.nik || '-'})`;
                            const badge = document.getElementById('badge-terpilih-pasien');
                            if (badge) badge.style.display = 'inline-block';
                            const btnReset = document.getElementById('btn-reset-pasien-lama');
                            if (btnReset) btnReset.style.display = 'inline-block';
                            const resultsDiv = document.getElementById('dropdown-pasien-results');
                            if (resultsDiv) resultsDiv.style.display = 'none';

                            if (document.querySelector('#form-pendaftaran input[name="nama_lengkap"]')) document.querySelector('#form-pendaftaran input[name="nama_lengkap"]').style.background = '#f8fafc';
                        } catch (e) {
                            console.error("Error selecting live pasien:", e);
                        }
                    }

                    function resetPasienLama() {
                        document.getElementById('existing_patient_id').value = '';
                        document.getElementById('form-pendaftaran').reset();
                        const sel = document.getElementById('select-pasien-lama');
                        if (sel) sel.value = '';
                        const inputLive = document.getElementById('input-live-pasien-lama');
                        if (inputLive) inputLive.value = '';
                        const badge = document.getElementById('badge-terpilih-pasien');
                        if (badge) badge.style.display = 'none';
                        const btnReset = document.getElementById('btn-reset-pasien-lama');
                        if (btnReset) btnReset.style.display = 'none';
                        if (document.querySelector('#form-pendaftaran input[name="nama_lengkap"]')) document.querySelector('#form-pendaftaran input[name="nama_lengkap"]').style.background = '#ffffff';
                    }

                    function pilihPasienLama(selectObj) {
                        const opt = selectObj.options[selectObj.selectedIndex];
                        if (!opt || !opt.value) {
                            resetPasienLama();
                            return;
                        }
                        const dataStr = opt.getAttribute('data-pasien');
                        if (dataStr) {
                            selectLivePasien(encodeURIComponent(dataStr));
                        }
                    }

                    document.addEventListener('click', function(e) {
                        const box = document.getElementById('search-box-pasien-lama');
                        const resultsDiv = document.getElementById('dropdown-pasien-results');
                        if (box && resultsDiv && !box.contains(e.target) && !resultsDiv.contains(e.target)) {
                            resultsDiv.style.display = 'none';
                        }
                    });
                    </script>
                </div>

                <!-- Kolom Kanan: Widget -->
                <div class="right-column">
                    <!-- Informasi Hari Ini -->
                    <div class="card">
                        <div class="widget-title">
                            <i class="fa-solid fa-calendar-days"></i> Informasi Hari Ini
                        </div>
                        <div class="info-header">
                            <span class="info-date"><?php
                                $hari_map = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                                $bulan_map = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                                echo $hari_map[date('l')] . ', ' . date('d') . ' ' . $bulan_map[(int)date('m')] . ' ' . date('Y');
                            ?></span>
                            <span class="info-time"><?= date('H:i') ?> WIB</span>
                        </div>
                        <div class="info-list">
                            <?php foreach ($info_hari_ini as $inf): ?>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fa-solid fa-circle-chevron-right" style="font-size: 10px; color: var(--green-light);"></i>
                                    <?= $inf['label'] ?>
                                </div>
                                <strong><?= $inf['value'] ?></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Petunjuk -->
                    <div class="guide-box">
                        <div class="widget-title" style="border-bottom: 1px solid #bbf7d0; color: #14532d;">
                            <i class="fa-solid fa-book-open" style="color: #16a34a;"></i> Petunjuk
                        </div>
                        <div class="guide-body">
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Pastikan semua data terisi dengan benar</span>
                            </div>
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Field bertanda <span style="color:#b91c1c;">*</span> wajib diisi</span>
                            </div>
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Periksa kembali data sebelum disimpan</span>
                            </div>
                            <div class="guide-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Gunakan NIK yang valid (16 digit)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Pendaftaran Terakhir -->
                    <div class="card">
                        <div class="widget-title">
                            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pendaftaran Terakhir
                        </div>
                        <div class="history-list">
                            <?php if (!empty($riwayat_pendaftaran) && count($riwayat_pendaftaran) > 0): ?>
                                <?php foreach ($riwayat_pendaftaran as $riw): ?>
                                <div class="history-item">
                                    <div class="history-badge"><?= htmlspecialchars($riw['no'] ?? '-') ?></div>
                                    <div class="history-details">
                                        <div class="history-name"><?= htmlspecialchars($riw['nama'] ?? '-') ?></div>
                                        <div class="history-sub"><?= htmlspecialchars($riw['poli'] ?? '-') ?> &bull; <?= htmlspecialchars($riw['waktu'] ?? '-') ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 24px 12px; color: #94a3b8; font-size: 13px;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 26px; margin-bottom: 8px; display: block; color: #cbd5e1;"></i>
                                    Belum ada riwayat pendaftaran baru hari ini
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-view-all" onclick="window.location.href='?page=antrian'">Lihat Semua Antrian</button>
                    </div>
                </div>
            </div>

            <!-- Bagian Pencarian Riwayat & Edit Data Pendaftaran Pasien -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2><i class="fa-solid fa-list-check" style="color: #2e7d32; margin-right: 8px;"></i> Riwayat & Edit Data Pendaftaran Pasien</h2>
                        <p style="font-size: 12px; color: var(--gray-400); font-weight: normal; margin-top: 2px;">Perawat, Resepsionis, atau Admisi dapat mencari dan memperbarui data pendaftaran / antrian pasien</p>
                    </div>
                    <div class="search-wrap" style="min-width: 280px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #9ca3af; font-size: 13px;"></i>
                        <input type="text" id="searchRiwayatInput" placeholder="Cari Nama Pasien / No RM / Antrian..." style="width: 100%; padding: 8px 12px 8px 34px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px;" oninput="filterRiwayatTable(this.value)">
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;" id="tableRiwayatDaftar">
                        <thead>
                            <tr style="background: var(--gray-50); font-size: 12px; color: var(--gray-600); border-bottom: 1px solid var(--gray-200);">
                                <th style="padding: 12px 16px;">No. Antrian</th>
                                <th style="padding: 12px 16px;">No. RM</th>
                                <th style="padding: 12px 16px;">Nama Pasien</th>
                                <th style="padding: 12px 16px;">Poliklinik</th>
                                <th style="padding: 12px 16px;">Dokter Praktek</th>
                                <th style="padding: 12px 16px;">Penjamin / Jenis</th>
                                <th style="padding: 12px 16px;">Status</th>
                                <th style="padding: 12px 16px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $list_edit = !empty($daftar_antrian_all) ? $daftar_antrian_all : (!empty($antrian_terkini) ? $antrian_terkini : (!empty($antrian) ? $antrian : []));
                            if (!empty($list_edit)):
                                foreach ($list_edit as $qrow):
                                    $q_id = $qrow['queue_id'] ?? $qrow['id'] ?? 0;
                            ?>
                            <tr style="border-bottom: 1px solid var(--gray-100); font-size: 13px;" class="row-riwayat">
                                <td style="padding: 12px 16px; font-weight: 700; color: #2e7d32;"><?= htmlspecialchars($qrow['no'] ?? $qrow['no_antrian'] ?? '-') ?></td>
                                <td style="padding: 12px 16px; color: var(--gray-600);"><?= htmlspecialchars($qrow['no_rm'] ?? '-') ?></td>
                                <td style="padding: 12px 16px; font-weight: 600; color: var(--gray-800);" class="cell-nama"><?= htmlspecialchars($qrow['nama'] ?? $qrow['nama_lengkap'] ?? '-') ?></td>
                                <td style="padding: 12px 16px; color: var(--gray-600);" class="cell-poli"><?= htmlspecialchars($qrow['poli'] ?? $qrow['nama_poli'] ?? '-') ?></td>
                                <td style="padding: 12px 16px; color: var(--gray-600);"><?= htmlspecialchars($qrow['dokter'] ?? $qrow['nama_dokter'] ?? '-') ?></td>
                                <td style="padding: 12px 16px;"><span style="padding: 2px 8px; background: #f1f5f9; border-radius: 4px; font-size: 11px; font-weight: 600; color: #475569;"><?= htmlspecialchars($qrow['jenis_pasien'] ?? 'Umum') ?></span></td>
                                <td style="padding: 12px 16px;">
                                    <?php 
                                    $st_class = 'badge-menunggu';
                                    $st_val = strtolower($qrow['status'] ?? $qrow['q_status'] ?? '');
                                    if ($st_val === 'dipanggil' || $st_val === 'dilayani') $st_class = 'badge-dipanggil';
                                    if ($st_val === 'selesai') $st_class = 'badge-selesai';
                                    ?>
                                    <span class="badge-status <?= $st_class ?>"><?= htmlspecialchars($qrow['status_label'] ?? $qrow['status'] ?? '-') ?></span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; cursor: pointer;" onclick='bukaModalEdit(<?= htmlspecialchars(json_encode($qrow), ENT_QUOTES, 'UTF-8') ?>)'>
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach;
                            else: ?>
                            <tr><td colspan="8" style="text-align: center; color: var(--gray-400); padding: 24px;">Belum ada data pendaftaran untuk dieksplorasi</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Edit Pendaftaran -->
            <div id="modalEditPendaftaran" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 10000; position: fixed; inset: 0; background: rgba(0,0,0,0.5);">
                <div class="modal-content" style="max-width: 580px; width: 90%; border-radius: 14px; overflow: hidden; background: white; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 18px 22px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-pen-to-square" style="font-size: 20px; color: #fde047;"></i>
                            <h3 style="margin: 0; font-size: 16px;">Edit Data Pendaftaran & Antrian</h3>
                        </div>
                        <button type="button" onclick="tutupModalEdit()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
                    </div>
                    <form method="POST" action="?page=pendaftaran" style="padding: 22px;" onsubmit="return handleEditPendaftaranSubmit(event, this)">
                        <input type="hidden" name="action" value="edit_pendaftaran">
                        <input type="hidden" name="queue_id" id="edit_queue_id" value="">
                        <input type="hidden" name="pam_supervisor_pin" id="edit_pam_supervisor_pin" value="">
                        <input type="hidden" name="pam_supervisor_name" id="edit_pam_supervisor_name" value="">
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Nama Lengkap Pasien</label>
                            <input type="text" name="nama_lengkap" id="edit_nama" class="form-input" required style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                        </div>
                        <div style="display: flex; gap: 14px; margin-bottom: 14px;">
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">No. Telepon</label>
                                <input type="text" name="no_telepon" id="edit_telepon" class="form-input" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Jenis Pasien / Penjamin</label>
                                <select name="jenis_pasien" id="edit_jenis_pasien" class="form-select" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                                    <option value="Umum">Umum / Pribadi</option>
                                    <option value="BPJS">BPJS Kesehatan</option>
                                    <option value="Asuransi Lain">Asuransi Lain</option>
                                    <option value="Gratis">Gratis</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Alamat</label>
                            <input type="text" name="alamat" id="edit_alamat" class="form-input" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                        </div>
                        <div style="display: flex; gap: 14px; margin-bottom: 18px;">
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Poliklinik Tujuan</label>
                                <select name="polyclinic_id" id="edit_polyclinic_id" class="form-select" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                                    <?php
                                    if (!empty($daftar_polyclinics)) {
                                        foreach ($daftar_polyclinics as $po) {
                                            echo '<option value="' . htmlspecialchars($po['id']) . '">' . htmlspecialchars($po['nama_poli']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Dokter Praktek</label>
                                <select name="dokter_id" id="edit_dokter_id" class="form-select" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                                    <?php
                                    if (!empty($daftar_dokter_aktif)) {
                                        foreach ($daftar_dokter_aktif as $do) {
                                            echo '<option value="' . htmlspecialchars($do['dokter_id']) . '">' . htmlspecialchars($do['nama_lengkap']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" class="btn-secondary" onclick="tutupModalEdit()" style="padding: 9px 16px; border-radius: 8px;">Batal</button>
                            <button type="submit" class="btn-primary" style="padding: 9px 18px; border-radius: 8px; background: #2563eb; color: white; font-weight: 600;">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
            function filterRiwayatTable(kw) {
                const rows = document.querySelectorAll('#tableRiwayatDaftar tbody .row-riwayat');
                const lk = kw.toLowerCase().trim();
                rows.forEach(r => {
                    const text = r.textContent.toLowerCase();
                    r.style.display = text.includes(lk) ? '' : 'none';
                });
            }
            function bukaModalEdit(data) {
                const m = document.getElementById('modalEditPendaftaran');
                if (!m) return;
                document.getElementById('edit_queue_id').value = data.queue_id || data.id || '';
                document.getElementById('edit_nama').value = data.nama || data.nama_lengkap || '';
                document.getElementById('edit_telepon').value = data.no_telepon || data.telepon || '';
                document.getElementById('edit_alamat').value = data.alamat || '';
                if (data.jenis_pasien) document.getElementById('edit_jenis_pasien').value = data.jenis_pasien;
                if (data.polyclinic_id || data.poli_id) document.getElementById('edit_polyclinic_id').value = data.polyclinic_id || data.poli_id;
                if (data.dokter_id) document.getElementById('edit_dokter_id').value = data.dokter_id;
                m.style.display = 'flex';
            }
            function tutupModalEdit() {
                const m = document.getElementById('modalEditPendaftaran');
                if (m) m.style.display = 'none';
            }
            function handleEditPendaftaranSubmit(e, formObj) {
                <?php if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser'): ?>
                e.preventDefault();
                openPAMModal('Edit Data Pendaftaran Pasien', 'edit_pendaftaran', () => {
                    formObj.submit();
                }, formObj);
                return false;
                <?php else: ?>
                return true;
                <?php endif; ?>
            }
            function filterDokterByPoli(poliId) {
                const dokSelect = document.getElementById('dokter_id_select');
                if (!dokSelect) return;
                const opts = dokSelect.querySelectorAll('option');
                let firstMatched = false;
                opts.forEach(opt => {
                    if (!opt.value) return;
                    const dPoli = opt.getAttribute('data-poli');
                    if (!poliId || dPoli === String(poliId) || !dPoli) {
                        opt.style.display = '';
                        if (!firstMatched && dPoli === String(poliId)) {
                            dokSelect.value = opt.value;
                            firstMatched = true;
                            updateEstimasiBiaya(opt);
                        }
                    } else {
                        opt.style.display = 'none';
                    }
                });
                if (!firstMatched) {
                    dokSelect.value = '';
                    updateEstimasiBiaya(null);
                }
            }
            function updateEstimasiBiaya(opt) {
                const show = document.getElementById('estimasi_biaya_show');
                if (!show) return;
                if (!opt || !opt.value) {
                    show.value = 'Rp 0 (Pilih Dokter)';
                    return;
                }
                const txt = opt.text;
                const match = txt.match(/Tarif: Rp ([0-9\.]+)/);
                if (match && match[1]) {
                    show.value = 'Rp ' + match[1];
                } else {
                    show.value = 'Tertera di Kasir';
                }
            }
            </script>
            <?php
            break;

        // ---------- HALAMAN LAIN (placeholder) ----------
        case 'emr':
        case 'emr_dokter':
            echo '<h2 style="margin: 20px 0;">Halaman EMR Dokter</h2>';
            echo '<p>Rekam medis elektronik.</p>';
            break;
        case 'farmasi':
            echo '<h2 style="margin: 20px 0;">Halaman Farmasi</h2>';
            echo '<p>Manajemen obat dan resep.</p>';
            break;
        case 'kasir':
            echo '<h2 style="margin: 20px 0;">Halaman Kasir</h2>';
            echo '<p>Transaksi pembayaran.</p>';
            break;
        default:
            echo '<h2>Halaman tidak ditemukan</h2>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMRS Clinical Precision</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ===== RESET & VARIABEL ===== */
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

        /* ===== SIDEBAR ===== */
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

        /* ===== MAIN ===== */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ===== TOPBAR ===== */
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
        .user-avatar i { font-size: 18px; color: var(--green-primary); }
        .user-text small { display: block; font-size: 10px; color: var(--gray-400); }
        .user-text strong { font-size: 13px; }

        /* ===== CONTENT ===== */
        .content { padding: 24px 28px; display: flex; flex-direction: column; gap: 22px; }

        /* ===== STAT CARDS ===== */
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
        .stat-card .stat-left {
            display: flex; flex-direction: column; gap: 8px;
        }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            background: var(--green-pale);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-card .stat-icon i { font-size: 20px; color: var(--green-primary); }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-400); margin-bottom: 4px; }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--gray-800); line-height: 1; }
        .stat-unit  { font-size: 12px; color: var(--gray-400); margin-top: 3px; }

        .trend-badge {
            font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 6px;
            display: flex; align-items: center; gap: 4px;
            margin-left: auto;
        }
        .trend-up { background: #dcfce7; color: #15803d; }
        .trend-down { background: #fee2e2; color: #b91c1c; }
        .trend-neutral { background: var(--gray-100); color: var(--gray-600); }

        /* ===== CHART (Dashboard) ===== */
        .chart-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; }
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

        .btn-group { display: flex; gap: 6px; }
        .btn-secondary {
            padding: 6px 12px; border: 1px solid var(--gray-200); border-radius: 6px;
            background: var(--white); font-size: 12px; font-weight: 600; color: var(--gray-600); cursor: pointer;
        }
        .btn-primary-sm {
            padding: 6px 12px; border: none; border-radius: 6px;
            background: var(--green-primary); font-size: 12px; font-weight: 600; color: var(--white); cursor: pointer;
        }

        .line-chart-placeholder {
            padding: 24px; position: relative; height: 260px;
            display: flex; align-items: flex-end; justify-content: space-between;
        }
        .chart-bg-lines {
            position: absolute; left: 24px; right: 24px; top: 24px; bottom: 50px;
            display: flex; flex-direction: column; justify-content: space-between;
            pointer-events: none;
        }
        .chart-bg-lines div { border-bottom: 1px dashed var(--gray-200); width: 100%; height: 0; }
        .svg-chart-container {
            position: absolute; left: 24px; right: 24px; top: 24px; bottom: 50px;
        }

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
        .donut-center strong { font-size: 24px; color: var(--gray-800); font-weight: 700; }
        .donut-center span { font-size: 10px; color: var(--gray-400); font-weight: 600; letter-spacing: .5px; }
        .donut-legend { width: 100%; display: flex; flex-direction: column; gap: 8px; font-size: 12px; }
        .legend-item { display: flex; justify-content: space-between; align-items: center; }
        .legend-label { display: flex; align-items: center; gap: 8px; color: var(--gray-600); }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }

        /* ===== TABLE ===== */
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
        .badge-dipanggil { background: #dcfce7; color: #15803d; }
        .badge-dilayani  { background: #dcfce7; color: #15803d; }
        .badge-menunggu  { background: #fef9c3; color: #a16207; }
        .badge-selesai   { background: #e0e7ff; color: #3730a3; }

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
        .pagination-nav { display: flex; gap: 6px; }
        .nav-btn {
            width: 28px; height: 28px; border: 1px solid var(--gray-200); background: var(--white);
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            color: var(--gray-600); font-size: 11px; cursor: pointer;
        }
        .nav-btn:hover { background: var(--gray-50); }

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

        /* ===== FILTER (untuk antrian) ===== */
        .table-filters {
            padding: 14px 20px;
            display: flex; gap: 10px; align-items: center;
            border-bottom: 1px solid var(--gray-200);
            flex-wrap: wrap;
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
        .search-wrap { position: relative; flex: 1; min-width: 150px; }
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

        /* ===== KOMPONEN ANTRIAN ===== */
        .lower-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }
        .right-col { display: flex; flex-direction: column; gap: 16px; }

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

        /* ===== UTILITY DASHBOARD ===== */
        .search-container { position: relative; width: 240px; }
        .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 13px; }
        .search-input-dash {
            width: 100%; padding: 7px 12px 7px 34px; border: 1px solid var(--gray-200); border-radius: 7px;
            font-size: 13px; outline: none; background: var(--white);
        }
        .search-input-dash:focus { border-color: var(--green-light); }

        /* ===== PENDAFTARAN ===== */
        .pendaftaran-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            align-items: start;
        }

        .form-body { padding: 24px 20px; display: flex; flex-direction: column; gap: 24px; }
        .form-section-title {
            display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700;
            color: var(--gray-800); border-bottom: 1px dashed var(--gray-200); padding-bottom: 10px;
        }
        .form-section-title i { color: var(--green-primary); font-size: 15px; }
        
        .form-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .form-row-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        
        .form-group { display: flex; flex-direction: column; gap: 6px; position: relative; }
        .form-group label { font-size: 12px; font-weight: 600; color: var(--gray-800); }
        .form-group label span { color: #b91c1c; margin-left: 2px; }

        .form-input, .form-select, .form-textarea {
            width: 100%; padding: 10px 12px; border: 1px solid var(--gray-200); border-radius: 8px;
            font-size: 13px; font-family: var(--font); color: var(--gray-800); outline: none; background: var(--white);
            transition: border-color .15s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--green-light); }
        .form-input::placeholder, .form-textarea::placeholder { color: var(--gray-400); }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
        }
        
        .form-input-locked { background: var(--gray-50); color: var(--gray-600); padding-right: 36px; font-weight: 500; }
        .lock-icon { position: absolute; right: 12px; bottom: 12px; color: var(--gray-400); font-size: 13px; }
        .form-textarea { resize: vertical; min-height: 70px; }

        .form-actions {
            padding: 16px 20px; background: var(--gray-50); border-top: 1px solid var(--gray-200);
            display: flex; justify-content: flex-end; gap: 10px;
        }
        .btn-cancel {
            padding: 10px 24px; border: 1px solid var(--gray-200); border-radius: 8px;
            background: var(--white); color: var(--gray-600); font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn-cancel:hover { background: var(--gray-100); }
        .btn-submit {
            padding: 10px 24px; border: none; border-radius: 8px;
            background: var(--green-primary); color: var(--white); font-size: 13px; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
        }
        .btn-submit:hover { background: var(--green-mid); }
        .btn-reset {
            padding: 7px 14px; border: 1px solid var(--gray-200); border-radius: 7px;
            background: var(--white); color: var(--gray-600); font-size: 12px; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background .15s;
        }
        .btn-reset:hover { background: var(--gray-50); }

        .widget-title {
            display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700;
            color: var(--gray-800); padding: 14px 16px; border-bottom: 1px solid var(--gray-200);
        }
        .widget-title i { color: var(--green-primary); font-size: 14px; }

        .info-header { padding: 12px 16px; background: var(--gray-50); display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
        .info-date { font-weight: 600; color: var(--gray-600); }
        .info-time { background: var(--green-pale); color: var(--green-primary); font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
        .info-list { padding: 8px 16px 14px; display: flex; flex-direction: column; gap: 12px; }
        .info-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
        .info-label { color: var(--gray-400); display: flex; align-items: center; gap: 8px; }
        .info-row strong { font-size: 14px; color: var(--gray-800); }

        .guide-box { background: #f0fdf4; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden; }
        .guide-body { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 10px; }
        .guide-item { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #166534; line-height: 1.4; }
        .guide-item i { color: var(--green-primary); margin-top: 2px; font-size: 11px; }

        .history-list { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
        .history-item {
            display: flex; align-items: center; gap: 12px; padding: 10px;
            border: 1px solid var(--gray-100); border-radius: 8px; background: var(--gray-50);
        }
        .history-badge {
            background: var(--green-pale); color: var(--green-primary); font-weight: 700;
            font-size: 12px; padding: 6px 8px; border-radius: 6px; min-width: 50px; text-align: center;
        }
        .history-details { display: flex; flex-direction: column; gap: 1px; flex: 1; }
        .history-name { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .history-sub { font-size: 11px; color: var(--gray-400); }
        
        .btn-view-all {
            width: calc(100% - 32px); margin: 4px 16px 16px; padding: 8px;
            border: 1px solid var(--green-primary); border-radius: 8px; background: var(--white);
            color: var(--green-primary); font-size: 12px; font-weight: 600; cursor: pointer; text-align: center;
            transition: background .15s;
        }
        .btn-view-all:hover { background: var(--green-xpale); }

        /* ===== FOOTER ===== */
        footer { font-size: 11px; color: var(--gray-400); text-align: center; margin-top: 10px; padding-bottom: 10px; }

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
        <?php foreach ($nav_items as $item): 
            $is_active = ($page == $item['page']) ? 'class="active"' : '';
        ?>
        <li>
            <a href="<?= $item['url'] ?? ('index.php?page=' . $item['page']) ?>" <?= $is_active ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <a href="index.php?page=pendaftaran" class="btn-quick">
        <i class="fa-solid fa-plus"></i> <span>Quick Admission</span>
    </a>

    <div class="sidebar-footer">
        <a href="#"><i class="fa-solid fa-gear"></i> <span>Settings</span></a>
        <a href="#"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<div class="main">

    <header class="topbar">
        <div class="topbar-left">
            <?php
            $page_title = ucfirst($page);
            if ($page === 'emr_dokter' || $page === 'emr') {
                $page_title = 'EMR Dokter';
            }
            ?>
            <h1><?= htmlspecialchars($page_title) ?></h1>
            <p class="breadcrumb">Front Office &rsaquo; <span><?= htmlspecialchars($page_title) ?></span></p>
        </div>
        <div class="topbar-right">
            <button class="icon-btn">
                <i class="fa-solid fa-bell"></i>
                <span class="badge">3</span>
            </button>
            <button class="icon-btn"><i class="fa-solid fa-gear"></i></button>
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
        <?php if (!empty($db_conn_error)): ?>
        <div style="background: #fef3c7; color: #92400e; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f59e0b; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
            <span><strong>Mode Offline / Fallback:</strong> <?= htmlspecialchars($db_conn_error) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($msg_success)): ?>
        <div style="background: #dcfce7; color: #15803d; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #16a34a; font-weight: 600; display: flex; align-items: center; gap: 10px;">
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
        <?php
        try {
            renderContent(
                $page,
                $stats_dashboard,
                $antrian_terkini,
                $distribusi,
                $stats_antrian,
                $antrian,
                $sedang_dilayani,
                $status_poli,
                $info_hari_ini,
                $riwayat_pendaftaran
            );
        } catch (Throwable $e) {
            error_log('Render exception in Pendaftaran.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo '<div style="background: #fee2e2; color: #b91c1c; padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #dc2626;">';
            echo '<strong>Maaf, halaman ini gagal ditampilkan.</strong><br>Ada masalah saat merender konten. Silakan muat ulang atau kembali ke menu utama.';
            echo '</div>';
        }
        ?>
        <footer>
            &copy; 2026 SIMRS. All rights reserved.
        </footer>
    </main>
</div>

</body>
</html>
<?php
} catch (Throwable $e) {
    error_log('Unhandled exception in Pendaftaran.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (function_exists('renderFriendlyErrorPage')) {
        renderFriendlyErrorPage('Terjadi Kesalahan pada Halaman', $e->getMessage());
    } else {
        echo '<div style="padding:20px;color:red;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>