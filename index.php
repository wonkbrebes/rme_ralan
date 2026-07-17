<?php
// ============================================================
// 1. AMBIL PARAMETER HALAMAN & KONEKSI SUPABASE
// ============================================================
require_once __DIR__ . '/config.php';

try {
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    if ($page === 'emr') {
        $page = 'emr_dokter';
    }

// HANDLE FORM SUBMISSION (POST) - Shared handler
require_once __DIR__ . '/post_handler.php';

// Memuat semua data dan sinkronisasi real-time database terpusat
require_once __DIR__ . '/simrs_data.php';

// ============================================================
// 9. FUNGSI RENDER KONTEN
// ============================================================
function renderContent($page, $stats_dashboard, $antrian_terkini, $distribusi, $stats_antrian, $antrian, $sedang_dilayani, $status_poli, $info_hari_ini, $riwayat_pendaftaran, $emr_tabs, $stats_farmasi, $daftar_obat, $stok_menipis, $resep_terbaru, $detail_transaksi, $nominal_cepat, $nama_pasien, $jenis_pasien, $no_rm, $nik, $tgl_lahir, $alamat, $telepon, $penjamin, $dokter, $emr_pasien, $resep_pasien, $emr_history, $diagnosa_pasien, $order_lab, $order_radiologi, $order_results, $surat_rujukan, $poli) {
    global $pasien_selesai_emr;
    switch ($page) {
        case 'dashboard':
            // ---------- DASHBOARD ----------
            global $grafik_7_hari, $grafik_7_labels, $cnt_total;
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
                            <button class="btn-secondary" onclick="alert('Mengekspor laporan ke PDF...')">Export PDF</button>
                            <button class="btn-primary-sm" onclick="window.location.href='?page=antrian'">Details</button>
                        </div>
                    </div>
                    <div class="line-chart-placeholder">
                        <div class="chart-bg-lines">
                            <div></div><div></div><div></div><div></div><div></div>
                        </div>
                        <div class="svg-chart-container">
                            <?php
                            // Hitung koordinat SVG dari data $grafik_7_hari
                            $max_val = max(1, max($grafik_7_hari));
                            $chart_w = 700;
                            $chart_h = 200;
                            $padding_x = 20;
                            $padding_y = 15;
                            $usable_w = $chart_w - ($padding_x * 2);
                            $usable_h = $chart_h - ($padding_y * 2);
                            $step_x = $usable_w / 6; // 7 points = 6 gaps
                            
                            $points = [];
                            foreach ($grafik_7_hari as $i => $val) {
                                $x = $padding_x + ($i * $step_x);
                                $y = $padding_y + $usable_h - (($val / $max_val) * $usable_h);
                                $points[] = ['x' => round($x), 'y' => round($y)];
                            }
                            
                            // Build SVG path
                            $path_parts = [];
                            foreach ($points as $i => $pt) {
                                $path_parts[] = ($i === 0 ? 'M' : 'L') . " {$pt['x']} {$pt['y']}";
                            }
                            $path_d = implode(' ', $path_parts);
                            ?>
                            <svg width="100%" height="100%" viewBox="0 0 <?= $chart_w ?> <?= $chart_h ?>" preserveAspectRatio="none">
                                <path d="<?= $path_d ?>" 
                                      fill="none" stroke="#2e7d32" stroke-width="3" stroke-linecap="round"/>
                                <?php foreach ($points as $pt): ?>
                                <circle cx="<?= $pt['x'] ?>" cy="<?= $pt['y'] ?>" r="5" fill="#2e7d32"/>
                                <?php endforeach; ?>
                            </svg>
                        <div style="position: absolute; bottom: 15px; left: 24px; right: 24px; display: flex; justify-content: space-between; font-size: 11px; color: #9ca3af; font-weight: 600; pointer-events: none;">
                            <?php foreach ($grafik_7_labels as $lbl): ?>
                            <span><?= $lbl ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Distribusi Jenis Kunjungan</h2>
                    </div>
                    <div class="donut-wrapper">
                        <div class="donut-chart">
                            <div class="donut-center">
                                <strong><?= $cnt_total ?></strong>
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
                        <?php if (!empty($antrian_terkini)): ?>
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
                            <td><a href="?page=emr_dokter&no_antrian=<?= urlencode($row['no']) ?>&nama=<?= urlencode($row['nama']) ?>" class="btn-detail" style="text-decoration:none; display:inline-block;">Detail</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--gray-400); padding: 30px;">Belum ada antrian hari ini</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer">
                    <p>Menampilkan <?= count($antrian_terkini) ?> dari <?= $stats_antrian['total'] ?> antrian aktif</p>
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
                            <?php if (!empty($antrian)): ?>
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
                                <td>
                                    <?php if ($status_key === 'menunggu'): ?>
                                    <form method="POST" action="?page=antrian" style="display:inline; margin-right: 6px;">
                                        <input type="hidden" name="action" value="panggil_antrian">
                                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($row['no']) ?>">
                                        <button type="submit" class="btn-detail" style="text-decoration:none; display:inline-block;">Panggil</button>
                                    </form>
                                    <?php elseif ($status_key === 'dipanggil'): ?>
                                    <form method="POST" action="?page=antrian" style="display:inline; margin-right: 6px;">
                                        <input type="hidden" name="action" value="konfirmasi_masuk">
                                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($row['no']) ?>">
                                        <button type="submit" class="btn-detail" style="text-decoration:none; display:inline-block;">Masuk Ruangan</button>
                                    </form>
                                    <form method="POST" action="?page=antrian" style="display:inline; margin-right: 6px;">
                                        <input type="hidden" name="action" value="tunda_antrian">
                                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($row['no']) ?>">
                                        <button type="submit" class="btn-detail btn-danger" style="text-decoration:none; display:inline-block;">Kembali Menunggu</button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="?page=emr_dokter&no_antrian=<?= urlencode($row['no']) ?>&nama=<?= urlencode($row['nama']) ?>" class="btn-detail" style="text-decoration:none; display:inline-block;">Detail / Dilayani</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--gray-400); padding: 30px;">Belum ada antrian pelayanan hari ini</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <p>Menampilkan <?= count($antrian) ?> dari <?= $stats_antrian['total'] ?> antrian</p>
                        <div class="pagination">
                            <button class="page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                            <button class="page-btn active">1</button>
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
                                <span>Status</span>
                                <span style="font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($sedang_dilayani['status']) ?></span>
                            </div>
                            <div class="meta-row">
                                <span>Estimasi selesai</span>
                                <strong><?= htmlspecialchars($sedang_dilayani['estimasi']) ?></strong>
                            </div>
                        </div>
                        <div class="dilayani-actions" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:18px;">
                            <button type="button" class="btn-detail" onclick="announceQueueNumber()" style="background:#2563eb;">Speaker</button>
                            <?php if ($sedang_dilayani['status_key'] === 'dipanggil'): ?>
                            <form method="POST" action="?page=antrian" style="margin:0;">
                                <input type="hidden" name="action" value="konfirmasi_masuk">
                                <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($sedang_dilayani['no']) ?>">
                                <button type="submit" class="btn-detail" style="background:#16a34a;">Konfirmasi Masuk</button>
                            </form>
                            <form method="POST" action="?page=antrian" style="margin:0;">
                                <input type="hidden" name="action" value="tunda_antrian">
                                <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($sedang_dilayani['no']) ?>">
                                <button type="submit" class="btn-detail btn-danger" style="background:#dc2626;">Kembali Menunggu</button>
                            </form>
                            <?php endif; ?>
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

                window.announceQueueNumber = function() {
                    const noNode = document.querySelector('.antrian-box .no');
                    if (!noNode) {
                        alert('Nomor antrian belum tersedia.');
                        return;
                    }
                    const nomor = noNode.textContent.trim();
                    if (!('speechSynthesis' in window)) {
                        alert('Browser tidak mendukung speaker announce.');
                        return;
                    }
                    const utter = new SpeechSynthesisUtterance('Nomor antrian ' + nomor + ' dipersilakan menuju ruang pemeriksaan.');
                    utter.lang = 'id-ID';
                    window.speechSynthesis.speak(utter);
                };
            });
            </script>
            <?php
            break;

        case 'pendaftaran':
            // ---------- HALAMAN PENDAFTARAN ----------
            ?>
            <div class="pendaftaran-grid">
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
                            <!-- Dropdown Pilih Pasien Lama -->
                            <div class="form-section-title" style="background: #f0fdf4; color: #166534; padding: 12px 16px; border: 1px dashed #22c55e; border-radius: 8px; margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; width: 100%;">
                                    <div style="flex: 1; min-width: 220px;">
                                        <i class="fa-solid fa-user-check"></i> <b>Pilih dari Pasien Terdaftar (Pasien Lama)</b>
                                        <div style="font-size: 12px; font-weight: normal; color: #15803d; margin-top: 4px;">Pilih pasien yang sudah pernah terdaftar agar data otomatis terisi & dibuatkan antrian hari ini.</div>
                                    </div>
                                    <div style="flex: 1; min-width: 260px;">
                                        <select id="select-pasien-lama" class="form-select" style="border-color: #22c55e; font-weight: 600;" onchange="pilihPasienLama(this)">
                                            <option value="">-- Pilih Pasien Terdaftar --</option>
                                            <?php 
                                            global $daftar_pasien_master;
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
                            </div>

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

                            <!-- Pendaftaran Pelayanan -->
                            <div class="form-section-title">
                                <i class="fa-solid fa-hospital"></i> Pendaftaran Pelayanan
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Poliklinik Tujuan<span>*</span></label>
                                    <select name="polyclinic_id" class="form-select" required>
                                        <option value="" disabled selected>Pilih poliklinik tujuan</option>
                                        <?php
                                        if (!empty($daftar_polyclinics)) {
                                            foreach ($daftar_polyclinics as $poli_opt) {
                                                echo '<option value="' . htmlspecialchars($poli_opt['id']) . '">' . htmlspecialchars($poli_opt['nama_poli']) . '</option>';
                                            }
                                        } else {
                                            // Fallback statis jika query gagal
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
                                    <label>Jenis Pasien / Penjamin<span>*</span></label>
                                    <select name="jenis_pasien" class="form-select" required>
                                        <option value="" disabled selected>Pilih jenis pasien</option>
                                        <option value="Umum">Umum / Pribadi</option>
                                        <option value="BPJS">BPJS Kesehatan</option>
                                        <option value="Asuransi Lain">Asuransi Lain</option>
                                        <option value="Gratis">Gratis</option>
                                    </select>
                                </div>
                            </div>

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
                    function pilihPasienLama(selectObj) {
                        const opt = selectObj.options[selectObj.selectedIndex];
                        if (!opt || !opt.value) {
                            document.getElementById('existing_patient_id').value = '';
                            document.getElementById('form-pendaftaran').reset();
                            return;
                        }
                        const dataStr = opt.getAttribute('data-pasien');
                        if (dataStr) {
                            const p = JSON.parse(dataStr);
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
                        }
                    }
                    </script>
                </div>

                <div class="right-column">
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

                    <div class="card">
                        <div class="widget-title">
                            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pendaftaran Terakhir
                        </div>
                        <div class="history-list">
                            <?php foreach ($riwayat_pendaftaran as $riw): ?>
                            <div class="history-item">
                                <div class="history-badge"><?= $riw['no'] ?></div>
                                <div class="history-details">
                                    <div class="history-name"><?= htmlspecialchars($riw['nama']) ?></div>
                                    <div class="history-sub"><?= htmlspecialchars($riw['poli']) ?> &bull; <?= $riw['waktu'] ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <button class="btn-view-all">Lihat Semua</button>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'emr':
        case 'emr_dokter':
            // ---------- HALAMAN EMR DOKTER (100% DINAMIS SUPABASE) ----------
            ?>
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

            <?php if (!empty($_GET['no_antrian'])): ?>
            <div style="padding: 0 0 20px 0;">
                <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian']) ?>&nama=<?= urlencode($nama_pasien) ?>" style="display:flex; gap: 10px; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="simpan_emr">
                    <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian']) ?>">
                    <button type="submit" class="btn-submit" style="background:#2e7d32; color:#fff; border:none;">Mulai Pemeriksaan</button>
                    <a href="?page=kasir&no_antrian=<?= urlencode($_GET['no_antrian']) ?>&nama=<?= urlencode($nama_pasien) ?>" class="btn-submit" style="background:#2563eb; color:#fff; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Lanjut ke Kasir</a>
                </form>
            </div>
            <?php endif; ?>

            <?php if (!empty($_GET['no_antrian'])): ?>
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><h3>Form Catatan Pemeriksaan</h3></div>
                <form method="POST" action="?page=emr_dokter">
                    <input type="hidden" name="action" value="simpan_emr">
                    <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian']) ?>">
                    <div class="note-body" style="display:flex; flex-direction:column; gap:16px; padding:16px;">
                        <div class="note-row" style="flex-direction:column;">
                            <label style="font-weight:700; margin-bottom:6px;">Subjective / Keluhan Utama</label>
                            <textarea name="subjective" rows="3" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; resize:vertical;"></textarea>
                        </div>
                        <div class="note-row" style="flex-direction:column;">
                            <label style="font-weight:700; margin-bottom:6px;">Objective</label>
                            <textarea name="objective" rows="3" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; resize:vertical;"></textarea>
                        </div>
                        <div class="note-row" style="flex-direction:column;">
                            <label style="font-weight:700; margin-bottom:6px;">Assessment / Diagnosis</label>
                            <textarea name="assessment" rows="3" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; resize:vertical;"></textarea>
                        </div>
                        <div class="note-row" style="flex-direction:column;">
                            <label style="font-weight:700; margin-bottom:6px;">Plan / Tindak Lanjut</label>
                            <textarea name="plan" rows="3" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; resize:vertical;"></textarea>
                        </div>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <button type="submit" class="btn-submit" style="background:#2e7d32; color:#fff; border:none;">Simpan Catatan</button>
                            <a href="?page=kasir&no_antrian=<?= urlencode($_GET['no_antrian']) ?>&nama=<?= urlencode($nama_pasien) ?>" class="btn-submit" style="background:#2563eb; color:#fff; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Lanjut ke Kasir</a>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

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
                        <div class="card-header"><h3>Riwayat Kunjungan</h3></div>
                        <?php if (!empty($emr_history)): ?>
                        <div class="history-list" style="padding: 16px; display:grid; gap:12px;">
                            <?php foreach ($emr_history as $rh): ?>
                            <div class="history-item" style="border:1px solid #e5e7eb; border-radius:12px; padding:14px;">
                                <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:8px;">
                                    <strong><?= htmlspecialchars($rh['no_kunjungan']) ?></strong>
                                    <span style="font-size:12px; color:#6b7280;"><?= htmlspecialchars($rh['tanggal']) ?></span>
                                </div>
                                <div style="font-size:13px; color:#374151;"><strong>Keluhan:</strong> <?= htmlspecialchars($rh['subjective']) ?></div>
                                <div style="font-size:13px; color:#374151;"><strong>Diagnosis:</strong> <?= htmlspecialchars($rh['assessment']) ?></div>
                                <div style="font-size:13px; color:#374151;"><strong>Rencana:</strong> <?= htmlspecialchars($rh['plan']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-tab-view"><i class="fa-solid fa-clock-rotate-left"></i>Belum ada riwayat kunjungan pasien.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="pemeriksaan" class="tab-panel">
                    <div class="card">
                        <div class="card-header"><h3>Form Pemeriksaan Fisik</h3></div>
                        <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                            <input type="hidden" name="action" value="simpan_emr">
                            <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Keluhan Utama</label><textarea name="subjective" rows="3" placeholder="Keluhan pasien" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Tindakan / Pemeriksaan Objektif</label><textarea name="objective" rows="3" placeholder="Temuan objektif" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Assessment / Diagnosa Klinik</label><textarea name="assessment" rows="3" placeholder="Hasil penilaian klinis" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Rencana / Plan</label><textarea name="plan" rows="3" placeholder="Rencana penatalaksanaan" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Tekanan Darah</label><input name="pemeriksaan_tekanan_darah" type="text" placeholder="120/80 mmHg" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Berat Badan</label><input name="pemeriksaan_bb" type="text" placeholder="kg" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Tinggi Badan</label><input name="pemeriksaan_tb" type="text" placeholder="cm" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Pemeriksaan Fisik</label><textarea name="pemeriksaan_fisik" rows="4" placeholder="Temuan pemeriksaan fisik" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <button type="submit" class="btn-submit" style="background:#2e7d32; color:#fff; border:none;">Simpan Pemeriksaan</button>
                        </form>
                    </div>
                </div>
                <div id="diagnosa" class="tab-panel">
                    <div class="card">
                        <div class="card-header"><h3>Diagnosa ICD-10</h3></div>
                        <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                            <input type="hidden" name="action" value="simpan_emr">
                            <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Kode ICD-10</label><input name="kode_icd10" type="text" placeholder="Misal: A09" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Nama Diagnosa</label><input name="nama_diagnosa" type="text" placeholder="Misal: Gastroenteritis" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <button type="submit" class="btn-submit" style="background:#2563eb; color:#fff; border:none;">Simpan Diagnosa</button>
                        </form>
                        <?php if (!empty($diagnosa_pasien)): ?>
                        <div style="padding:16px; border-top:1px solid #e5e7eb;">
                            <?php foreach ($diagnosa_pasien as $diag): ?>
                            <div style="margin-bottom:10px; font-size:13px; color:#374151;">
                                <strong><?= htmlspecialchars($diag['kode_icd10']) ?></strong> - <?= htmlspecialchars($diag['nama_diagnosis']) ?> <span style="color:#6b7280;">(<?= htmlspecialchars($diag['jenis_diagnosis']) ?>)</span><br>
                                <span style="font-size:12px; color:#6b7280;"><?= htmlspecialchars($diag['waktu']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="terapi_obat" class="tab-panel">
                    <div class="card">
                        <div class="card-header"><h3>Resep & Terapi</h3></div>
                        <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                            <input type="hidden" name="action" value="simpan_emr">
                            <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Nama Obat / Terapi</label><input name="terapi_nama" type="text" placeholder="Nama obat atau terapi" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Dosis / Aturan Pakai</label><textarea name="terapi_aturan" rows="3" placeholder="Aturan pemakaian" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <button type="submit" class="btn-submit" style="background:#16a34a; color:#fff; border:none;">Simpan Resep</button>
                        </form>
                        <?php if (!empty($resep_pasien)): ?>
                        <div style="padding:16px; border-top:1px solid #e5e7eb;">
                            <?php foreach ($resep_pasien as $rsp): ?>
                            <div style="margin-bottom:10px; font-size:13px; color:#374151;">
                                <strong>Resep #<?= htmlspecialchars($rsp['resep_id']) ?></strong> (<?= htmlspecialchars($rsp['resep_status']) ?>)<br>
                                <span style="font-size:12px; color:#6b7280;"><?= htmlspecialchars($rsp['waktu']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="order" class="tab-panel">
                    <div class="card">
                        <div class="card-header"><h3>Order Lab / Radiologi</h3></div>
                        <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                            <input type="hidden" name="action" value="simpan_emr">
                            <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Pilih Tipe Order</label><select name="order_type" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"><option value="lab">Laboratorium</option><option value="radiologi">Radiologi</option></select></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Jenis Pemeriksaan</label><input name="order_lab_jenis" type="text" placeholder="Misal: Hematologi / Rontgen" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Catatan</label><textarea name="order_lab_catatan" rows="3" placeholder="Instruksi / catatan" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <button type="submit" class="btn-submit" style="background:#2563eb; color:#fff; border:none;">Simpan Order</button>
                        </form>
                        <?php if ((!empty($order_lab) && is_array($order_lab)) || (!empty($order_radiologi) && is_array($order_radiologi))): ?>
                        <div style="padding:16px; border-top:1px solid #e5e7eb; display:grid; gap:10px;">
                            <?php if (!empty($order_lab) && is_array($order_lab)): foreach ($order_lab as $ord): ?>
                            <div style="font-size:13px; color:#374151;"><strong>Lab:</strong> <?= htmlspecialchars($ord['jenis_pemeriksaan']) ?> <span style="color:#6b7280;">(<?= htmlspecialchars($ord['status']) ?>)</span><br><small><?= htmlspecialchars($ord['waktu']) ?></small></div>
                            <?php endforeach; endif; ?>
                            <?php if (!empty($order_radiologi) && is_array($order_radiologi)): foreach ($order_radiologi as $ord): ?>
                            <div style="font-size:13px; color:#374151;"><strong>Radiologi:</strong> <?= htmlspecialchars($ord['jenis_pemeriksaan']) ?> <span style="color:#6b7280;">(<?= htmlspecialchars($ord['status']) ?>)</span><br><small><?= htmlspecialchars($ord['waktu']) ?></small></div>
                            <?php endforeach; endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="hasil_pemeriksaan" class="tab-panel">
                    <div class="card">
                        <div class="card-header"><h3>Hasil Pemeriksaan</h3></div>
                        <?php if (!empty($order_results)): ?>
                        <div class="history-list" style="padding:16px; display:grid; gap:12px;">
                            <?php foreach ($order_results as $res): ?>
                            <div style="border:1px solid #e5e7eb; border-radius:12px; padding:14px;">
                                <div style="font-size:13px; color:#374151;"><strong><?= htmlspecialchars($res['parameter']) ?></strong>: <?= htmlspecialchars($res['nilai']) ?> <?= htmlspecialchars($res['satuan']) ?></div>
                                <div style="font-size:12px; color:#6b7280;">Referensi: <?= htmlspecialchars($res['nilai_rujukan']) ?></div>
                                <div style="font-size:12px; color:#6b7280;">Pemeriksaan: <?= htmlspecialchars($res['jenis_pemeriksaan']) ?> · <?= htmlspecialchars($res['tanggal']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-tab-view"><i class="fa-solid fa-square-poll-horizontal"></i>Belum ada hasil pemeriksaan yang tersedia.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="dokumen" class="tab-panel">
                    <div class="card">
                        <div class="card-header"><h3>Dokumen & Surat Rujukan</h3></div>
                        <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" style="padding:16px; display:grid; gap:14px;">
                            <input type="hidden" name="action" value="simpan_emr">
                            <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Fasilitas Tujuan</label><input name="rujukan_faskes" type="text" placeholder="Nama fasilitas tujuan" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Poli Tujuan</label><input name="rujukan_poli" type="text" placeholder="Poli tujuan" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Alasan Rujukan</label><textarea name="rujukan_alasan" rows="3" placeholder="Alasan rujukan" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></textarea></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">No. Rujukan BPJS (opsional)</label><input name="rujukan_bpjs" type="text" placeholder="No. rujukan BPJS" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <div class="note-row" style="flex-direction:column;"><label style="font-weight:700; margin-bottom:6px;">Tanggal Rujukan</label><input name="rujukan_tanggal" type="date" value="<?= date('Y-m-d') ?>" style="padding:10px; border:1px solid #d1d5db; border-radius:8px;"></div>
                            <button type="submit" class="btn-submit" style="background:#0f766e; color:#fff; border:none;">Simpan Rujukan</button>
                        </form>
                        <?php if (!empty($surat_rujukan)): ?>
                        <div class="history-list" style="padding:16px; display:grid; gap:12px;">
                            <?php foreach ($surat_rujukan as $sr): ?>
                            <div style="border:1px solid #e5e7eb; border-radius:12px; padding:14px;">
                                <div style="font-size:13px; color:#374151;"><strong><?= htmlspecialchars($sr['faskes_tujuan']) ?></strong> - <?= htmlspecialchars($sr['poli_tujuan']) ?></div>
                                <div style="font-size:12px; color:#6b7280;">Tanggal Rujukan: <?= htmlspecialchars($sr['tanggal']) ?></div>
                                <div style="font-size:12px; color:#374151;">Alasan: <?= htmlspecialchars($sr['alasan_rujukan']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-tab-view"><i class="fa-solid fa-folder-open"></i>Belum ada surat rujukan atau dokumen terlampir.</div>
                        <?php endif; ?>
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
                    <div class="widget-title"><span><i class="fa-solid fa-history"></i> Riwayat Kunjungan Terakhir</span> <a href="#" class="widget-link">Lihat Semua</a></div>
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
                    <div class="widget-title"><span><i class="fa-solid fa-star-of-life"></i> Diagnosa Aktif</span> <span style="color:var(--green-primary); cursor:pointer; font-size:11px;">+ Tambah</span></div>
                    <div style="padding:16px; text-align:center; font-size:12px; color:var(--gray-400);">
                        <?php if (!empty($emr_pasien) && !empty($emr_pasien[0]['assessment'])): ?>
                        <div style="text-align:left; font-weight:600; color:var(--gray-800);"><?= htmlspecialchars($emr_pasien[0]['assessment']) ?></div>
                        <?php else: ?>
                        Belum ada diagnosa aktif tercatat
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="widget-title"><span><i class="fa-solid fa-pills"></i> Terapi Aktif</span> <a href="#" class="widget-link">Lihat Semua</a></div>
                    <div class="order-list" style="padding:16px; text-align:center; color:var(--gray-400); font-size:12px;">
                        <?php if (!empty($resep_pasien)): ?>
                        <div style="text-align:left; font-weight:600; color:var(--gray-800);">Resep Aktif #<?= htmlspecialchars($resep_pasien[0]['resep_id']) ?> (<?= htmlspecialchars($resep_pasien[0]['resep_status']) ?>)</div>
                        <?php else: ?>
                        Belum ada terapi obat aktif tercatat
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
            <?php
            break;

        case 'farmasi':
            // ---------- HALAMAN FARMASI ----------
            ?>
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
                        <button class="btn-qa" onclick="alert('Membuka form Resep Baru...')"><i class="fa-solid fa-file-medical"></i> Resep Baru</button>
                        <button class="btn-qa" onclick="alert('Membuka antrian Penyerahan Obat...')"><i class="fa-solid fa-hand-holding-medical"></i> Penyerahan Obat</button>
                        <button class="btn-qa" onclick="alert('Membuka form Stok Masuk (Restock)...')"><i class="fa-solid fa-boxes-stacked"></i> Stok Masuk</button>
                        <button class="btn-qa" onclick="alert('Mengekspor Laporan Farmasi...')"><i class="fa-solid fa-file-lines"></i> Laporan Farmasi</button>
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
            </div>
            <?php
            break;

        case 'kasir':
            // ---------- HALAMAN KASIR ----------
            global $pasien_selesai_emr;
            $no_antrian_active = trim($_GET['no_antrian'] ?? '');
            ?>
            <?php if (!empty($pasien_selesai_emr) && count($pasien_selesai_emr) > 0): ?>
            <div class="emr-ready-banner" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #93c5fd; border-left: 5px solid #2563eb; padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(37,99,235,0.08);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0;">
                        <i class="fa-solid fa-bell fa-shake"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: #1e40af;">Perhatian: Ada <?= count($pasien_selesai_emr) ?> Pasien Selesai EMR Siap Pembayaran Kasir</h4>
                        <p style="margin: 2px 0 0; font-size: 12px; color: #3b82f6;">Silakan pilih dari daftar pasien di bawah untuk memproses rincian transaksi & tagihan.</p>
                    </div>
                </div>
                <span style="background: #2563eb; color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                    <i class="fa-solid fa-user-check"></i> <?= count($pasien_selesai_emr) ?> Selesai EMR
                </span>
            </div>
            <?php endif; ?>

            <section class="emr-completed-section" style="background: var(--white); border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06); border-top: 4px solid #2563eb;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-clipboard-check" style="color: #2563eb; font-size: 18px;"></i>
                        <h3 style="font-size: 15px; font-weight: 700; color: var(--gray-800); margin: 0;">Daftar Pasien Selesai EMR (Klik untuk Pilih)</h3>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <?php if (!empty($pasien_selesai_emr)): ?>
                            <span style="font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 12px;"><i class="fa-solid fa-users"></i> <?= count($pasien_selesai_emr) ?> Pasien Siap</span>
                        <?php endif; ?>
                        <a href="?page=kasir" class="btn-sim" style="background:#6b7280; padding: 6px 12px; font-size: 12px; border-radius: 6px; color: white; text-decoration: none; font-weight: 600;">Kosongkan Layar</a>
                    </div>
                </div>

                <?php if (!empty($pasien_selesai_emr)): ?>
                    <div class="emr-patient-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                        <?php foreach ($pasien_selesai_emr as $p_emr): 
                            $is_selected = ($no_antrian_active === $p_emr['no'] || $no_rm === $p_emr['no_rm'] || strcasecmp($nama_pasien, $p_emr['nama']) === 0);
                        ?>
                            <a href="?page=kasir&no_antrian=<?= urlencode($p_emr['no']) ?>&nama=<?= urlencode($p_emr['nama']) ?>" class="emr-patient-card-item" style="display: flex; flex-direction: column; justify-content: space-between; padding: 12px 14px; border: 1.5px solid <?= $is_selected ? '#2563eb' : '#e2e8f0' ?>; background: <?= $is_selected ? '#eff6ff' : '#ffffff' ?>; border-radius: 10px; text-decoration: none; transition: all 0.2s ease; position: relative;">
                                <?php if ($is_selected): ?>
                                    <div style="position: absolute; top: -8px; right: 10px; background: #2563eb; color: white; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Sedang Diproses</div>
                                <?php endif; ?>
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                    <div>
                                        <strong style="font-size: 13.5px; color: var(--gray-800); display: block; line-height: 1.3;"><?= htmlspecialchars($p_emr['nama']) ?></strong>
                                        <span style="font-size: 11.5px; color: var(--gray-400);"><?= htmlspecialchars($p_emr['no']) ?> • RM: <?= htmlspecialchars($p_emr['no_rm']) ?></span>
                                    </div>
                                    <span style="font-size: 10.5px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 6px; white-space: nowrap; border: 1px solid #7dd3fc;"><i class="fa-solid fa-check"></i> Selesai EMR</span>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; color: var(--gray-600); border-top: 1px dashed var(--gray-200); padding-top: 8px;">
                                    <span><i class="fa-solid fa-stethoscope" style="color: var(--green-primary);"></i> <?= htmlspecialchars($p_emr['poli']) ?></span>
                                    <span style="color: #2563eb; font-weight: 600;"><i class="fa-solid fa-arrow-right-long"></i> Pilih Kasir</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 24px; text-align: center; background: var(--gray-50); border: 1px dashed var(--gray-300); border-radius: 8px; color: var(--gray-600);">
                        <i class="fa-solid fa-clipboard-check" style="font-size: 28px; color: var(--gray-400); margin-bottom: 8px; display: block;"></i>
                        <p style="font-size: 13px; font-weight: 600; margin: 0;">Belum ada pasien yang selesai pemeriksaan EMR saat ini.</p>
                        <span style="font-size: 11.5px; color: var(--gray-400);">Pasien akan otomatis muncul sebagai daftar di sini begitu dokter menyimpan catatan EMR (SOAP).</span>
                    </div>
                <?php endif; ?>
            </section>

            <section class="search-patient-card">
                <h3>Cari Pasien (Manual):</h3>
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
                            <tr><td colspan="6" style="text-align: center; color: var(--gray-400); padding: 30px;">Belum ada rincian transaksi di database untuk pasien ini.</td></tr>
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
                        if (!empty($detail_transaksi)) {
                            foreach ($detail_transaksi as $row) {
                                $subtotal += intval(str_replace('.', '', $row['total'] ?? 0));
                            }
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
                    <form action="?page=kasir" method="POST">
                        <input type="hidden" name="action" value="bayar_kasir">
                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">
                        <div class="pay-form">
                            <div class="form-group">
                                <label>Metode Pembayaran</label>
                                <div class="select-container">
                                    <i class="fa-solid fa-money-bill-wave" id="pay-icon"></i>
                                    <select class="select-pay" id="payment-method" name="payment_method" onchange="updatePaymentIcon()">
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
                                    <input type="text" class="money-input" name="nominal" value="<?= $subtotal_fmt ?>">
                                </div>
                            </div>

                            <div class="nominal-grid">
                                <?php foreach ($nominal_cepat as $nom): ?>
                                    <button type="button" class="btn-nom" onclick="document.querySelector('.money-input').value='<?= $nom ?>';"><?= $nom ?></button>
                                <?php endforeach; ?>
                            </div>

                            <div class="change-box">
                                <span>Uang Kembalian:</span>
                                <strong>Rp 0</strong>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit-pay">
                            <i class="fa-solid fa-print"></i> Proses & Cetak Struk
                        </button>
                    </form>
                </div>
            </div>
            </div>
            <?php
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
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .stat-card .stat-left {
            display: flex;
            flex-direction: column;
            gap: 8px;
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
        .chart-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; }
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
        .badge-menunggu-kasir { background: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; font-weight: 600; }

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
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 20px;
            align-items: start;
        }
        .right-col, .right-column { display: flex; flex-direction: column; gap: 16px; width: 100%; min-width: 0; }
        .emr-grid-container { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; }
        .farmasi-grid-container { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; }
        .kasir-grid-container { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 20px; align-items: start; }
        .left-column, .left-column-panels { width: 100%; min-width: 0; }

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

        /* ===== EMR DOKTER ===== */
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

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .left-column-panels {
            width: 100%; min-width: 0;
        }

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

        .triple-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .med-list, .order-list { display: flex; flex-direction: column; gap: 10px; padding: 14px; }
        .med-item { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 10px; border-bottom: 1px dashed var(--gray-200); }
        .med-item:last-child { border-bottom: none; padding-bottom: 0; }
        .med-num { font-size: 11px; font-weight: 700; color: var(--white); background: var(--green-light); width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: 2px; }
        .med-details { flex: 1; margin-left: 10px; display: flex; flex-direction: column; gap: 2px; }
        .med-name { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .med-rule { font-size: 11px; color: var(--gray-400); }
        .med-qty { font-size: 11px; font-weight: 600; color: var(--gray-600); text-align: right; }

        .mini-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
        .badge-warning { background: #fef9c3; color: #a16207; }
        .badge-normal { background: #e0f2fe; color: #0369a1; }
        
        .btn-card-action {
            width: 100%; border: none; border-top: 1px solid var(--gray-200); background: var(--white);
            padding: 10px; text-align: center; color: var(--green-primary); font-size: 12px; font-weight: 600;
            cursor: pointer; transition: background .15s;
        }
        .btn-card-action:hover { background: var(--green-xpale); }

        .quick-action-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 14px 16px; }
        .btn-qa {
            display: flex; align-items: center; gap: 8px; padding: 10px;
            border: 1px solid var(--gray-200); border-radius: 8px; background: var(--white);
            font-size: 11px; font-weight: 600; color: var(--gray-600); cursor: pointer;
        }
        .btn-qa:hover { background: var(--green-xpale); border-color: var(--green-light); color: var(--green-primary); }
        .btn-qa i { color: var(--green-primary); font-size: 13px; }

        .history-box { padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; font-size: 12px; }
        .history-date-row { display: flex; justify-content: space-between; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
        .history-item-row { display: flex; margin-bottom: 4px; }
        .history-label { width: 80px; color: var(--gray-400); font-weight: 500; }
        .history-val { flex: 1; color: var(--gray-600); }

        .empty-tab-view { padding: 40px; text-align: center; color: var(--gray-400); font-size: 13px; }
        .empty-tab-view i { font-size: 32px; color: var(--gray-300); margin-bottom: 10px; display: block; }

        /* ===== FARMASI ===== */
        .stat-card.card-warning .stat-icon { background: #fef9c3; color: #a16207; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-value-group { display: flex; align-items: baseline; gap: 4px; margin-top: 2px; }
        .stat-value-group strong { font-size: 24px; font-weight: 700; color: var(--gray-800); }
        .stat-value-group span { font-size: 11px; color: var(--gray-400); }

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
        .btn-add-obat {
            padding: 8px 14px; border: none; border-radius: 8px; background: var(--green-primary);
            color: var(--white); font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;
        }
        .btn-add-obat:hover { background: var(--green-mid); }

        .obat-code { font-weight: 700; color: var(--green-primary); }
        .obat-name { font-weight: 500; color: var(--gray-800); }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-aman { background: #dcfce7; color: #15803d; }
        .badge-menipis { background: #ffedd5; color: #c2410c; }
        .badge-habis { background: #fee2e2; color: #b91c1c; }

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

        .resep-item { display: flex; align-items: center; gap: 12px; padding: 10px; border: 1px solid var(--gray-100); border-radius: 8px; background: var(--gray-50); }
        .resep-icon-box { width: 32px; height: 32px; background: var(--green-pale); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-size: 14px; }
        .resep-details { flex: 1; display: flex; flex-direction: column; gap: 1px; }
        .resep-no { font-size: 11px; font-weight: 600; color: var(--gray-400); }
        .resep-patient { font-size: 12px; font-weight: 700; color: var(--gray-800); }
        .resep-time { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
        .badge-done { background: #dcfce7; color: #15803d; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }

        /* ===== KASIR ===== */
        .simulasi-box { grid-column: 1 / -1; background: #fffbeb; border: 1px dashed #f59e0b; padding: 15px; border-radius: 10px; display: flex; gap: 15px; align-items: center; }
        .simulasi-box span { font-size: 12px; font-weight: bold; color: #b45309; }
        .btn-sim { padding: 6px 12px; background: #f59e0b; color: white; text-decoration: none; font-size: 12px; font-weight: 600; border-radius: 6px; }
        .btn-sim:hover { background: #d97706; }

        .search-patient-card { grid-column: 1 / -1; background: var(--white); border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06); display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--green-primary); }
        .search-form-flex { display: flex; gap: 10px; width: 450px; }
        .input-search-pasien { flex: 1; padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px; font-size: 13px; outline: none; }
        .btn-search-submit { padding: 8px 16px; background: var(--green-primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }

        .patient-profile-bar { display: flex; gap: 20px; padding: 20px; grid-column: 1 / -1; background: var(--white); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .patient-avatar-box { width: 56px; height: 56px; border-radius: 50%; background: var(--green-pale); display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-size: 24px; }
        .patient-details-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; flex: 1; }
        .p-group { display: flex; flex-direction: column; }
        .p-group label { font-size: 10px; color: var(--gray-400); text-transform: uppercase; font-weight: 600; }
        .p-group span, .p-group strong { font-size: 13px; color: var(--gray-800); }

        .table-action-bar { padding: 14px 20px; display: flex; justify-content: space-between; background: var(--white); border-bottom: 1px solid var(--gray-100); }
        .btn-add-item { padding: 6px 12px; background: var(--green-primary); color: var(--white); border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }

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

        .change-box { background: var(--green-xpale); padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .change-box strong { color: var(--green-primary); font-size: 14px; }

        .btn-submit-pay { width: calc(100% - 32px); margin: 0 16px 16px; padding: 12px; border: none; border-radius: 8px; background: var(--green-primary); color: var(--white); font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit-pay:hover { background: var(--green-mid); }

        /* ===== FOOTER ===== */
        footer { font-size: 11px; color: var(--gray-400); text-align: center; margin-top: 10px; padding-bottom: 10px; }

        /* ===== RESPONSIF TOTAL (DESKTOP, TABLET, MOBILE) ===== */
        @media (max-width: 1024px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .chart-grid, .lower-grid, .pendaftaran-grid, .triple-grid, .poli-grid, .emr-grid-container, .farmasi-grid-container, .kasir-grid-container { grid-template-columns: 1fr; gap: 16px; }
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
        <?php foreach ($nav_items as $item): 
            $is_active = ($page == $item['page']) ? 'class="active"' : '';
        ?>
        <li>
            <a href="?page=<?= $item['page'] ?>" <?= $is_active ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <a href="?page=pendaftaran" class="btn-quick">
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
            <?php 
            $notif_emr_cnt = !empty($pasien_selesai_emr) ? count($pasien_selesai_emr) : 0;
            ?>
            <button class="icon-btn" title="<?= $notif_emr_cnt > 0 ? $notif_emr_cnt . ' Pasien Selesai EMR Menunggu Kasir' : 'Tidak ada notifikasi baru' ?>" onclick="if(<?= $notif_emr_cnt ?> > 0) window.location.href='?page=kasir'">
                <i class="fa-solid fa-bell <?= $notif_emr_cnt > 0 ? 'fa-shake' : '' ?>" style="<?= $notif_emr_cnt > 0 ? 'color: #2563eb;' : '' ?>"></i>
                <?php if ($notif_emr_cnt > 0): ?>
                <span class="badge" style="background: #ef4444; color: white; font-weight: bold;"><?= $notif_emr_cnt ?></span>
                <?php else: ?>
                <span class="badge">0</span>
                <?php endif; ?>
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
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px; color: #d97706;"></i>
            <div>
                <strong>Peringatan Koneksi Cloud Supabase:</strong> <?= htmlspecialchars($db_conn_error) ?><br>
                <small style="font-weight: normal; color: #78350f;">Untuk mengaktifkan koneksi database PostgreSQL di terminal Anda saat ini, matikan server lokal (Ctrl+C) lalu jalankan dengan perintah: <b><code>php -d extension=pdo_pgsql -d extension=pgsql -S localhost:8000</code></b></small>
            </div>
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
                $riwayat_pendaftaran,
                $emr_tabs,
                $stats_farmasi,
                $daftar_obat,
                $stok_menipis,
                $resep_terbaru,
                $detail_transaksi,
                $nominal_cepat,
                $nama_pasien,
                $jenis_pasien,
                $no_rm,
                $nik,
                $tgl_lahir,
                $alamat,
                $telepon,
                $penjamin,
                $dokter,
                $emr_pasien,
                $resep_pasien,
                $emr_history,
                $diagnosa_pasien,
                $order_lab,
                $order_radiologi,
                $order_results,
                $surat_rujukan,
                $poli
            );
        } catch (Throwable $e) {
            error_log('Render exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
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

<script>
function switchTab(event, tabId) {
    const panels = document.querySelectorAll('.tab-panel');
    panels.forEach(panel => panel.classList.remove('active'));

    const tabs = document.querySelectorAll('.emr-tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}

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
    <?php
        $subtotal_js = 0;
        if (!empty($detail_transaksi)) {
            foreach ($detail_transaksi as $row) {
                $subtotal_js += intval(str_replace('.', '', $row['total'] ?? 0));
            }
        }
        $subtotal_js_fmt = number_format($subtotal_js, 0, ',', '.');
    ?>
    const totalTagihan = <?= $subtotal_js ?>;
    const totalFmt = '<?= $subtotal_js_fmt ?>';

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
                if (moneyInput) moneyInput.value = totalFmt;
                calcChange(String(totalTagihan));
            } else {
                if (moneyInput) moneyInput.value = txt;
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
<?php
} catch (Throwable $e) {
    error_log('Unhandled exception in index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (function_exists('renderFriendlyErrorPage')) {
        renderFriendlyErrorPage('Terjadi Kesalahan pada Halaman', $e->getMessage());
    } else {
        echo '<div style="padding:20px;color:red;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>