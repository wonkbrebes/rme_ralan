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
    global $pasien_selesai_emr, $pasien_menunggu_farmasi;
    switch ($page) {
        case 'dashboard':
            // ---------- DASHBOARD ----------
            global $grafik_7_hari, $grafik_7_labels, $cnt_total;
            ?>
            <div class="stat-grid">
                <?php foreach ($stats_dashboard as $st): ?>
                <div class="stat-card" <?= !empty($st['url']) ? 'onclick="window.location.href=\'' . $st['url'] . '\'" style="cursor: pointer; transition: all 0.2s;" title="Klik untuk membuka halaman ' . htmlspecialchars($st['label']) . '"' : '' ?>>
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

            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <div>
                        <h2><i class="fa-solid fa-user-doctor" style="color: #2e7d32; margin-right: 8px;"></i> Jadwal Praktik & Estimasi Biaya Dokter</h2>
                        <p style="font-size: 12px; color: var(--gray-400); font-weight: normal; margin-top: 2px;">Referensi lengkap jadwal dokter serta perbedaan tarif Dokter Spesialis dan Dokter Umum untuk pertimbangan pasien</p>
                    </div>
                    <div class="btn-group">
                        <span style="font-size: 12px; font-weight: 600; padding: 4px 10px; background: #e0e7ff; color: #3730a3; border-radius: 6px;">Spesialis: Rp 150.000 - Rp 200.000</span>
                        <span style="font-size: 12px; font-weight: 600; padding: 4px 10px; background: #dcfce7; color: #166534; border-radius: 6px;">Umum: Rp 50.000</span>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--gray-50); text-align: left; font-size: 12px; color: var(--gray-600);">
                                <th style="padding: 12px 16px;">Nama Dokter</th>
                                <th style="padding: 12px 16px;">Poliklinik / Spesialisasi</th>
                                <th style="padding: 12px 16px;">Kategori</th>
                                <th style="padding: 12px 16px;">Jadwal Praktik</th>
                                <th style="padding: 12px 16px;">Estimasi Biaya Jasa</th>
                                <th style="padding: 12px 16px;">Status / Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($jadwal_dokter_list)): ?>
                            <?php foreach ($jadwal_dokter_list as $dok): 
                                $is_spesialis = (stripos($dok['jenis_dokter'] ?? '', 'spesialis') !== false || stripos($dok['spesialisasi'] ?? '', 'spesialis') !== false || stripos($dok['nama_lengkap'] ?? '', 'Sp.') !== false);
                                $fee = !empty($dok['biaya_jasa']) ? floatval($dok['biaya_jasa']) : ($is_spesialis ? 150000 : 50000);
                                $badge_type = $is_spesialis ? 'background: #fef3c7; color: #92400e; border: 1px solid #fde68a;' : 'background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;';
                            ?>
                            <tr style="border-bottom: 1px solid var(--gray-100); font-size: 13px;">
                                <td style="padding: 12px 16px; font-weight: 600; color: var(--gray-800);">
                                    <i class="fa-solid <?= $is_spesialis ? 'fa-user-md' : 'fa-stethoscope' ?>" style="color: #2e7d32; margin-right: 6px;"></i>
                                    <?= htmlspecialchars($dok['nama_lengkap'] ?? 'Dokter') ?>
                                </td>
                                <td style="padding: 12px 16px; color: var(--gray-600);"><?= htmlspecialchars($dok['spesialisasi'] ?? 'Umum') ?></td>
                                <td style="padding: 12px 16px;">
                                    <span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; <?= $badge_type ?>">
                                        <?= htmlspecialchars($dok['jenis_dokter'] ?? ($is_spesialis ? 'Spesialis' : 'Umum')) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; color: var(--gray-600);">
                                    <i class="fa-regular fa-calendar-days" style="color: #64748b; margin-right: 4px;"></i> <?= htmlspecialchars($dok['hari_praktik'] ?? 'Senin - Jumat') ?>
                                    <span style="color: #94a3b8; font-size: 12px; margin-left: 4px;">(<?= htmlspecialchars(substr($dok['jam_mulai'] ?? '08:00', 0, 5) . ' - ' . substr($dok['jam_selesai'] ?? '14:00', 0, 5)) ?>)</span>
                                </td>
                                <td style="padding: 12px 16px; font-weight: 700; color: #16a34a;">
                                    Rp <?= number_format($fee, 0, ',', '.') ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <?php 
                                    $kuota = intval($dok['kuota'] ?? 20);
                                    $terisi = intval($dok['terisi'] ?? 0);
                                    $sisa = max(0, $kuota - $terisi);
                                    if ($sisa > 0): ?>
                                        <span style="color: #15803d; font-weight: 600; font-size: 12px;"><i class="fa-solid fa-check-circle"></i> Tersedia (Sisa <?= $sisa ?>)</span>
                                    <?php else: ?>
                                        <span style="color: #dc2626; font-weight: 600; font-size: 12px;"><i class="fa-solid fa-times-circle"></i> Penuh</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--gray-400); padding: 24px;">Data jadwal dokter belum tersedia</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
            require_role(['perawat']);
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
                                    <?php if (!empty($row['poli_kosong'])): ?>
                                    <span class="badge-status" style="background:#fef3c7; color:#b45309; margin-top:4px; display:inline-block; font-weight:700; border:1px solid #f59e0b;" title="Poli tujuan sedang kosong, pasien ini dapat didahulukan!">
                                        <i class="fa-solid fa-bolt"></i> Siap Masuk (Poli Kosong)
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn-detail" onclick="announceSpecificQueue('<?= htmlspecialchars(addslashes($row['no'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($row['poli'] ?? '')) ?>')" style="margin-right:4px; background:#e0f2fe; color:#0284c7; border-color:#38bdf8;" title="Panggil Suara Pasien Ini">
                                        <i class="fa-solid fa-volume-high"></i>
                                    </button>
                                    <?php if ($status_key === 'menunggu'): ?>
                                    <form method="POST" action="?page=antrian" style="display:inline; margin-right: 6px;">
                                        <input type="hidden" name="action" value="panggil_antrian">
                                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($row['no']) ?>">
                                        <button type="submit" class="btn-detail" style="text-decoration:none; display:inline-block; <?= !empty($row['poli_kosong']) ? 'background:#d97706; color:#fff; border-color:#b45309; font-weight:700;' : '' ?>">
                                            <?= !empty($row['poli_kosong']) ? '<i class="fa-solid fa-bolt"></i> Panggil Cepat' : 'Panggil' ?>
                                        </button>
                                    </form>
                                    <?php elseif ($status_key === 'dipanggil'): ?>
                                    <form method="POST" action="?page=antrian" style="display:inline; margin-right: 6px;">
                                        <input type="hidden" name="action" value="konfirmasi_masuk">
                                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($row['no']) ?>">
                                        <button type="submit" class="btn-detail" style="text-decoration:none; display:inline-block; background:#16a34a; color:#fff; border-color:#15803d; font-weight:600;">Masuk Ruangan</button>
                                    </form>
                                    <form method="POST" action="?page=antrian" style="display:inline; margin-right: 6px;">
                                        <input type="hidden" name="action" value="tunda_antrian">
                                        <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($row['no']) ?>">
                                        <button type="submit" class="btn-detail btn-danger" style="text-decoration:none; display:inline-block; background:#fee2e2; color:#dc2626; border-color:#f87171;">Kembali Menunggu</button>
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
                        <div class="dilayani-actions" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:16px;">
                            <button type="button" class="btn-detail" onclick="announceQueueNumber()" style="background:#2563eb; color:#fff; border:none; padding:8px 12px; border-radius:8px; font-weight:600; flex:1; justify-content:center; display:flex; align-items:center; gap:6px;">
                                <i class="fa-solid fa-volume-high"></i> Panggil Suara
                            </button>
                            <?php if ($sedang_dilayani['status_key'] === 'dipanggil'): ?>
                            <form method="POST" action="?page=antrian" style="margin:0; flex:1;">
                                <input type="hidden" name="action" value="konfirmasi_masuk">
                                <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($sedang_dilayani['no']) ?>">
                                <button type="submit" class="btn-detail" style="background:#16a34a; color:#fff; border:none; padding:8px 12px; border-radius:8px; font-weight:600; width:100%;">Masuk Ruangan</button>
                            </form>
                            <form method="POST" action="?page=antrian" style="margin:0; flex:1;">
                                <input type="hidden" name="action" value="tunda_antrian">
                                <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($sedang_dilayani['no']) ?>">
                                <button type="submit" class="btn-detail btn-danger" style="background:#dc2626; color:#fff; border:none; padding:8px 12px; border-radius:8px; font-weight:600; width:100%;">Kembali Menunggu</button>
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
                                    <div style="flex:1;">
                                        <div class="poli-name"><?= htmlspecialchars($poli['nama']) ?></div>
                                        <div class="poli-count" style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                                            <span><?= $poli['sekarang'] ?> / <?= $poli['total'] ?></span>
                                            <?= $poli['badge_html'] ?? '' ?>
                                        </div>
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
                    const poliNode = document.querySelector('.dilayani-header p');
                    if (!noNode) {
                        alert('Nomor antrian belum tersedia.');
                        return;
                    }
                    const nomor = noNode.textContent.trim();
                    const poli = poliNode ? poliNode.textContent.trim() : 'ruang pemeriksaan';
                    if (!('speechSynthesis' in window)) {
                        alert('Browser tidak mendukung speaker announce.');
                        return;
                    }
                    const utter = new SpeechSynthesisUtterance('Nomor antrian ' + nomor + ' dipersilakan menuju ' + poli + '.');
                    utter.lang = 'id-ID';
                    window.speechSynthesis.speak(utter);
                };

                window.announceSpecificQueue = function(nomor, poli) {
                    if (!nomor) return;
                    if (!('speechSynthesis' in window)) {
                        alert('Browser tidak mendukung speaker announce.');
                        return;
                    }
                    const targetPoli = poli ? poli : 'ruang pemeriksaan';
                    const utter = new SpeechSynthesisUtterance('Nomor antrian ' + nomor + ' dipersilakan bersiap menuju ' + targetPoli + '.');
                    utter.lang = 'id-ID';
                    window.speechSynthesis.speak(utter);
                };
            });
            </script>
            <?php
            break;

        case 'pendaftaran':
            require_role(['admisi', 'resepsionis']);
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
                    <script>
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
            </script>
            <?php
            break;

        case 'emr_dokter':
        case 'emr':
            require_role(['dokter']);
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
                        <div class="card-header" style="background:var(--green-pale);">
                            <h3 style="color:var(--green-primary);"><i class="fa-solid fa-prescription-bottle-medical"></i> Panel Resep Elektronik & Terapi Obat</h3>
                            <span class="header-meta">Sinkronisasi langsung ke Farmasi & Kasir</span>
                        </div>
                        <form method="POST" action="?page=emr_dokter&no_antrian=<?= urlencode($_GET['no_antrian'] ?? '') ?>&nama=<?= urlencode($nama_pasien) ?>" id="formResepIndex" style="padding:18px; display:grid; gap:16px;">
                            <input type="hidden" name="action" value="simpan_emr">
                            <input type="hidden" name="no_antrian" value="<?= htmlspecialchars($_GET['no_antrian'] ?? '') ?>">

                            <div style="background:var(--gray-50); border:1px solid var(--gray-200); border-radius:10px; padding:14px;">
                                <label style="font-weight:700; font-size:13px; color:var(--gray-800); display:block; margin-bottom:10px;"><i class="fa-solid fa-pills" style="color:var(--green-primary);"></i> Cari Obat dari Database Stok SIMRS</label>
                                <div style="display:grid; grid-template-columns: minmax(200px, 2fr) minmax(110px, 1fr) minmax(110px, 1fr) minmax(180px, 1.5fr) 80px auto; gap:10px; align-items:end;">
                                    <div>
                                        <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Nama Obat (Search / Auto-suggest)</label>
                                        <input type="text" id="inputObatSearchIdx" list="daftarObatListIdx" placeholder="Ketik nama / kode obat..." style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                        <datalist id="daftarObatListIdx">
                                            <?php if (!empty($daftar_obat)): foreach ($daftar_obat as $ob): ?>
                                            <option data-id="<?= htmlspecialchars($ob['obat_id'] ?? '') ?>" data-satuan="<?= htmlspecialchars($ob['satuan']) ?>" value="<?= htmlspecialchars($ob['nama']) ?>">Stok: <?= htmlspecialchars($ob['stok']) ?> (<?= htmlspecialchars($ob['satuan']) ?>)</option>
                                            <?php endforeach; endif; ?>
                                        </datalist>
                                    </div>
                                    <div>
                                        <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Takaran/Dosis</label>
                                        <input type="number" step="0.5" id="inputDosisIdx" value="1" placeholder="1" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                    </div>
                                    <div>
                                        <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Satuan</label>
                                        <input type="text" id="inputSatuanIdx" placeholder="Tablet/Botol" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                    </div>
                                    <div>
                                        <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Aturan Minum / Pakai</label>
                                        <input type="text" id="inputAturanIdx" placeholder="3x1 sesudah makan" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                    </div>
                                    <div>
                                        <label style="font-size:11px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:4px;">Jumlah</label>
                                        <input type="number" id="inputQtyIdx" value="10" min="1" style="width:100%; padding:9px 10px; border:1px solid var(--gray-300); border-radius:8px; font-size:13px;">
                                    </div>
                                    <div>
                                        <button type="button" onclick="tambahObatKeResepIdx()" style="background:var(--green-mid); color:#fff; border:none; padding:10px 14px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; height:37px;" title="Tambahkan ke Daftar Resep">+ Tambah</button>
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
                                        <tbody id="tabelItemResepBodyIdx">
                                            <tr id="emptyResepRowIdx">
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
                function tambahObatKeResepIdx() {
                    const inputSearch = document.getElementById('inputObatSearchIdx');
                    const inputDosis = document.getElementById('inputDosisIdx');
                    const inputSatuan = document.getElementById('inputSatuanIdx');
                    const inputAturan = document.getElementById('inputAturanIdx');
                    const inputQty = document.getElementById('inputQtyIdx');

                    const namaObat = inputSearch.value.trim();
                    if (!namaObat) {
                        alert('Silakan pilih atau ketik nama obat terlebih dahulu.');
                        inputSearch.focus();
                        return;
                    }

                    let obatId = '';
                    const datalist = document.getElementById('daftarObatListIdx');
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

                    const tbody = document.getElementById('tabelItemResepBodyIdx');
                    const emptyRow = document.getElementById('emptyResepRowIdx');
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
                        <td style="padding:10px 12px;"><button type="button" onclick="this.closest('tr').remove(); cekKosongResepIdx();" style="background:#fee2e2; color:#dc2626; border:none; padding:4px 8px; border-radius:6px; cursor:pointer;" title="Hapus"><i class="fa-solid fa-trash"></i></button></td>
                    `;
                    tbody.appendChild(tr);

                    inputSearch.value = '';
                    inputDosis.value = '1';
                    inputQty.value = '10';
                    inputSearch.focus();
                }

                function cekKosongResepIdx() {
                    const tbody = document.getElementById('tabelItemResepBodyIdx');
                    if (tbody && tbody.children.length === 0) {
                        tbody.innerHTML = `<tr id="emptyResepRowIdx"><td colspan="6" style="text-align:center; padding:20px; color:var(--gray-400);">Belum ada item obat ditambahkan. Gunakan form pencarian di atas atau ketik resep manual di bawah.</td></tr>`;
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const inputSearch = document.getElementById('inputObatSearchIdx');
                    const inputSatuan = document.getElementById('inputSatuanIdx');
                    if (inputSearch && inputSatuan) {
                        inputSearch.addEventListener('input', function() {
                            const val = this.value.trim();
                            const datalist = document.getElementById('daftarObatListIdx');
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
            </div>
            <?php
            break;

        case 'farmasi':
            require_role(['farmasi', 'apoteker']);
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

            <?php if (!empty($stok_menipis) && count($stok_menipis) > 0): ?>
            <div style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1px solid #fecdd3; border-left: 5px solid #e11d48; padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(225,29,72,0.08);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: #e11d48; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0;">
                        <i class="fa-solid fa-triangle-exclamation fa-beat"></i>
                    </div>
                    <div>
                        <h4 style="color: #be123c; margin: 0; font-size: 15px; font-weight: 700;">Peringatan Stok Menipis / Habis (<= 15 Unit)</h4>
                        <p style="color: #881337; margin: 2px 0 0 0; font-size: 13px;">Terdapat <strong><?= count($stok_menipis) ?> jenis obat</strong> yang membutuhkan perhatian atau stok opname / restock segera di gudang farmasi.</p>
                    </div>
                </div>
                <button onclick="switchFarmasiTab('stok_opname')" style="background: #be123c; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 12px; cursor: pointer; transition: background 0.2s;">
                    <i class="fa-solid fa-boxes-stacked" style="margin-right: 6px;"></i> Kelola Stok Opname
                </button>
            </div>
            <?php endif; ?>

            <div style="margin-bottom: 20px; display: flex; gap: 10px; border-bottom: 2px solid var(--gray-200); padding-bottom: 12px;">
                <button type="button" class="btn-secondary farmasi-tab-btn active" id="btnTabAntrianFarmasi" onclick="switchFarmasiTab('antrian_resep')" style="background: var(--green-primary); color: white; border-color: var(--green-primary); font-weight: 700; padding: 10px 18px;">
                    <i class="fa-solid fa-list-check"></i> Antrian Resep Menunggu Farmasi (<?= !empty($pasien_menunggu_farmasi) ? count($pasien_menunggu_farmasi) : 0 ?>)
                </button>
                <button type="button" class="btn-secondary farmasi-tab-btn" id="btnTabStokOpname" onclick="switchFarmasiTab('stok_opname')" style="background: var(--white); color: var(--gray-700); font-weight: 700; padding: 10px 18px;">
                    <i class="fa-solid fa-pills"></i> Manajemen Stok Opname & Daftar Obat
                </button>
                <button type="button" class="btn-secondary farmasi-tab-btn" id="btnTabRiwayatFarmasi" onclick="switchFarmasiTab('riwayat_farmasi')" style="background: var(--white); color: var(--gray-700); font-weight: 700; padding: 10px 18px;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Resep Terproses
                </button>
            </div>

            <div id="panel_antrian_resep" class="farmasi-panel" style="display:block;">
                <div class="card">
                    <div class="card-header" style="background: var(--green-pale); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 style="color: var(--green-primary);"><i class="fa-solid fa-prescription"></i> Daftar Pasien Menunggu Obat (Antrian Farmasi)</h2>
                            <p style="color: var(--gray-600); font-size: 13px;">Proses resep elektronik dari dokter, potong stok otomatis, dan teruskan ke Kasir</p>
                        </div>
                        <span class="badge" style="background: var(--green-primary); color: white; padding: 6px 12px; font-size: 13px; font-weight: bold;"><?= !empty($pasien_menunggu_farmasi) ? count($pasien_menunggu_farmasi) : 0 ?> Pasien</span>
                    </div>
                    <?php if (!empty($pasien_menunggu_farmasi)): ?>
                    <div style="padding: 16px; display: grid; gap: 16px;">
                        <?php foreach ($pasien_menunggu_farmasi as $pmf): ?>
                        <div style="border: 1px solid var(--gray-300); border-radius: 12px; padding: 16px; background: var(--white); box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px dashed var(--gray-200); padding-bottom: 12px; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <span style="font-size: 12px; background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 6px; font-weight: 700;">No. Antrian: <?= htmlspecialchars($pmf['no']) ?></span>
                                    <h3 style="font-size: 16px; color: var(--gray-800); margin: 6px 0 2px 0;"><?= htmlspecialchars($pmf['nama']) ?> <small style="color: var(--gray-500);">(RM: <?= htmlspecialchars($pmf['no_rm']) ?>)</small></h3>
                                    <span style="font-size: 13px; color: var(--gray-600);"><i class="fa-solid fa-stethoscope" style="color: var(--green-primary);"></i> Asal Poli: <strong><?= htmlspecialchars($pmf['poli']) ?></strong> &bull; Waktu EMR: <?= htmlspecialchars($pmf['waktu']) ?> WIB</span>
                                </div>
                                <div>
                                    <button type="button" onclick="bukaModalProsesFarmasi('<?= htmlspecialchars($pmf['queue_id'] ?? '') ?>', '<?= htmlspecialchars($pmf['no']) ?>', '<?= htmlspecialchars(addslashes($pmf['nama'])) ?>', '<?= htmlspecialchars(addslashes(json_encode($pmf['items'] ?? []))) ?>')" style="background: var(--green-primary); color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(46,125,50,0.2);">
                                        <i class="fa-solid fa-check-double"></i> Proses & Serahkan Obat
                                    </button>
                                </div>
                            </div>
                            <div style="background: var(--gray-50); border-radius: 8px; padding: 12px;">
                                <strong style="font-size: 12px; color: var(--gray-700); display: block; margin-bottom: 6px;"><i class="fa-solid fa-list"></i> Item Resep Dokter:</strong>
                                <?php if (!empty($pmf['items']) && is_array($pmf['items'])): ?>
                                <ul style="margin: 0 0 0 18px; font-size: 13px; color: var(--gray-800); display: grid; gap: 4px;">
                                    <?php foreach ($pmf['items'] as $ritem): ?>
                                    <li><strong><?= htmlspecialchars($ritem['nama_obat']) ?></strong> &mdash; <?= htmlspecialchars($ritem['dosis']) ?> <?= htmlspecialchars($ritem['satuan']) ?> (Aturan: <?= htmlspecialchars($ritem['aturan_pakai']) ?>, Qty: <strong><?= htmlspecialchars($ritem['qty']) ?> <?= htmlspecialchars($ritem['satuan']) ?></strong>)</li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                <p style="font-size: 12px; color: var(--gray-500); margin: 0; font-style: italic;">Tidak ada perincian item obat resep terpisah (Instruksi umum / racikan manual).</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--gray-400);">
                        <i class="fa-solid fa-clipboard-check" style="font-size: 42px; color: var(--green-light); margin-bottom: 12px;"></i>
                        <p style="font-weight: 600; font-size: 15px; color: var(--gray-700);">Semua Antrian Resep Farmasi Sudah Diproses!</p>
                        <p style="font-size: 13px;">Saat dokter menyimpan EMR dan meneruskan ke Farmasi, pasien akan muncul di sini secara otomatis.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="panel_stok_opname" class="farmasi-panel" style="display:none;">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h2>Manajemen Stok Opname & Daftar Obat</h2>
                            <p>Pantau ketersediaan, lakukan restock, dan perbarui harga obat</p>
                        </div>
                        <button onclick="bukaModalRestock('', '', 0, 0)" class="btn-add-obat" style="background: var(--green-primary); color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-plus"></i> Tambah / Opname Obat
                        </button>
                    </div>
                    <div class="table-filter-bar">
                        <div class="filter-left">
                            <div class="search-container">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" class="search-input" id="searchObatInput" onkeyup="filterTabelObat()" placeholder="Cari nama obat atau kode...">
                            </div>
                        </div>
                        <span style="font-size: 12px; color: var(--gray-500);"><i class="fa-solid fa-circle-info"></i> Obat dengan stok <= 15 diberi tanda penipisan otomatis</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table id="tabelDaftarObat">
                            <thead>
                                <tr>
                                    <th>Kode Obat</th>
                                    <th>Nama Obat</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th>Stok Gudang</th>
                                    <th>Harga / Satuan</th>
                                    <th>Status Stok</th>
                                    <th>Aksi Opname</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftar_obat as $row): 
                                    $stok_val = intval($row['stok'] ?? 0);
                                    $badge_class = 'badge-aman';
                                    $status_label = $row['status'] ?? 'Aman';
                                    if ($stok_val <= 0) {
                                        $badge_class = 'badge-habis';
                                        $status_label = 'Habis';
                                    } elseif ($stok_val <= 15) {
                                        $badge_class = 'badge-menipis';
                                        $status_label = 'Menipis';
                                    }
                                ?>
                                <tr class="obat-row">
                                    <td class="obat-code font-bold"><?= htmlspecialchars($row['kode']) ?></td>
                                    <td class="obat-name font-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td><?= htmlspecialchars($row['kategori'] ?? 'Umum') ?></td>
                                    <td><?= htmlspecialchars($row['satuan']) ?></td>
                                    <td style="font-weight: 700; font-size: 14px; color: <?= $stok_val <= 15 ? '#e11d48' : 'var(--gray-800)' ?>;">
                                        <?= $stok_val ?> <?= htmlspecialchars($row['satuan']) ?>
                                    </td>
                                    <td style="font-weight: 600;">Rp <?= number_format(floatval($row['harga'] ?? 10000), 0, ',', '.') ?></td>
                                    <td>
                                        <span class="status-badge <?= $badge_class ?>"><?= $status_label ?></span>
                                    </td>
                                    <td>
                                        <button type="button" onclick="bukaModalRestock('<?= htmlspecialchars($row['obat_id'] ?? $row['id'] ?? '') ?>', '<?= htmlspecialchars(addslashes($row['nama'])) ?>', <?= $stok_val ?>, <?= floatval($row['harga'] ?? 10000) ?>)" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-pen-to-square"></i> Stok Opname
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="panel_riwayat_farmasi" class="farmasi-panel" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Resep Terproses & Keluar</h2>
                        <p>Riwayat penyerahan obat kepada pasien hari ini</p>
                    </div>
                    <?php if (!empty($resep_terbaru)): ?>
                    <div class="list-widget" style="padding: 16px; display: grid; gap: 12px;">
                        <?php foreach ($resep_terbaru as $rs): ?>
                        <div class="resep-item" style="border: 1px solid var(--gray-200); border-radius: 10px; padding: 14px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div class="resep-icon-box" style="width: 44px; height: 44px; background: #dcfce7; color: #16a34a; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fa-solid fa-check"></i></div>
                                <div class="resep-details">
                                    <strong style="color: var(--gray-800); font-size: 15px;"><?= htmlspecialchars($rs['nama']) ?></strong>
                                    <div style="font-size: 12px; color: var(--gray-500); margin-top: 2px;">Nomor Resep: <strong><?= $rs['no'] ?></strong> &bull; <?= $rs['waktu'] ?></div>
                                </div>
                            </div>
                            <span class="badge-done" style="background: #dcfce7; color: #16a34a; font-weight: 700; padding: 6px 14px; border-radius: 20px; font-size: 12px;">Selesai / Obat Diserahkan</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--gray-400); padding: 40px; font-size: 13px;">Belum ada riwayat resep selesai hari ini.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MODAL PROSES & SERAHKAN OBAT -->
            <div id="modalProsesFarmasi" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.56); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
                <div style="background: white; border-radius: 16px; width: 100%; max-width: 680px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
                    <div style="background: var(--green-primary); color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 18px;"><i class="fa-solid fa-check-double"></i> Proses & Serahkan Obat Pasien</h3>
                        <button type="button" onclick="tutupModalProsesFarmasi()" style="background: none; border: none; color: white; font-size: 22px; cursor: pointer;">&times;</button>
                    </div>
                    <form method="POST" action="?page=farmasi" style="padding: 24px; overflow-y: auto; display: grid; gap: 16px;">
                        <input type="hidden" name="action" value="proses_resep_farmasi">
                        <input type="hidden" name="queue_id" id="modalQueueId">
                        <input type="hidden" name="no_antrian" id="modalNoAntrian">

                        <div style="background: var(--green-pale); border-radius: 10px; padding: 14px;">
                            <span style="font-size: 12px; font-weight: 700; color: var(--green-primary);">PASIEN TERPILIH</span>
                            <h4 id="modalNamaPasien" style="margin: 4px 0 0 0; font-size: 18px; color: var(--gray-800);">Nama Pasien</h4>
                        </div>

                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: var(--gray-800); margin-bottom: 8px;"><i class="fa-solid fa-pills"></i> Perincian Obat Resep (Akan Dipotong dari Stok & Ditagihkan)</h4>
                            <p style="font-size: 12px; color: var(--gray-600); margin-top: 0;">Silakan cocokkan dengan obat fisik dan tentukan jumlah yang diserahkan:</p>
                            <div style="border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                    <thead>
                                        <tr style="background: var(--gray-100); text-align: left; border-bottom: 1px solid var(--gray-200);">
                                            <th style="padding: 10px 12px;">Nama Obat / Dosis</th>
                                            <th style="padding: 10px 12px; width: 140px;">Pilih Obat Stok</th>
                                            <th style="padding: 10px 12px; width: 100px;">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalResepItemsTbody">
                                        <!-- Diisi via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div style="background: #fef9c3; border-left: 4px solid #eab308; padding: 12px; border-radius: 8px; font-size: 12px; color: #854d0e;">
                            <i class="fa-solid fa-circle-info"></i> Menekan tombol di bawah akan <strong>memotong stok obat secara otomatis</strong> di gudang, mencatat rincian tagihan obat di kasir, dan mengubah status pasien menjadi <strong>Menunggu Kasir</strong>.
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                            <button type="button" onclick="tutupModalProsesFarmasi()" style="background: var(--gray-200); color: var(--gray-700); border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Batal</button>
                            <button type="submit" style="background: var(--green-primary); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-check"></i> Konfirmasi & Serahkan Obat
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL RESTOCK / STOK OPNAME -->
            <div id="modalRestock" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.56); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
                <div style="background: white; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); overflow: hidden;">
                    <div style="background: var(--gray-800); color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 18px;"><i class="fa-solid fa-pen-to-square"></i> Stok Opname & Perbarui Obat</h3>
                        <button type="button" onclick="tutupModalRestock()" style="background: none; border: none; color: white; font-size: 22px; cursor: pointer;">&times;</button>
                    </div>
                    <form method="POST" action="?page=farmasi" style="padding: 24px; display: grid; gap: 16px;">
                        <input type="hidden" name="action" value="update_stok_obat">
                        <input type="hidden" name="obat_id" id="restockObatId">

                        <div>
                            <label style="font-weight: 700; font-size: 13px; color: var(--gray-700); display: block; margin-bottom: 6px;">Nama Obat</label>
                            <input type="text" id="restockNamaObat" readonly style="width: 100%; padding: 10px; border: 1px solid var(--gray-300); border-radius: 8px; background: var(--gray-100); font-weight: 700;">
                        </div>

                        <div>
                            <label style="font-weight: 700; font-size: 13px; color: var(--gray-700); display: block; margin-bottom: 6px;">Stok Fisik Saat Ini (Stok Opname / Restock)</label>
                            <input type="number" name="stok" id="restockStokVal" min="0" required style="width: 100%; padding: 10px; border: 1px solid var(--gray-300); border-radius: 8px; font-weight: 700; font-size: 15px;">
                            <small style="color: var(--gray-500); font-size: 11px;">* Jika <= 15, sistem akan memberi tanda merah (Menipis).</small>
                        </div>

                        <div>
                            <label style="font-weight: 700; font-size: 13px; color: var(--gray-700); display: block; margin-bottom: 6px;">Harga Jual / Satuan (Rp)</label>
                            <input type="number" name="harga" id="restockHargaVal" min="0" step="500" required style="width: 100%; padding: 10px; border: 1px solid var(--gray-300); border-radius: 8px; font-weight: 700; font-size: 15px;">
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                            <button type="button" onclick="tutupModalRestock()" style="background: var(--gray-200); color: var(--gray-700); border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Batal</button>
                            <button type="submit" style="background: var(--green-primary); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            let daftarObatData = <?= json_encode($daftar_obat ?? []) ?>;
            let searchTimeoutObat = null;

            function switchFarmasiTab(tabName) {
                document.querySelectorAll('.farmasi-panel').forEach(p => p.style.display = 'none');
                document.querySelectorAll('.farmasi-tab-btn').forEach(b => {
                    b.style.background = 'var(--white)';
                    b.style.color = 'var(--gray-700)';
                });

                if (tabName === 'antrian_resep') {
                    document.getElementById('panel_antrian_resep').style.display = 'block';
                    const btn = document.getElementById('btnTabAntrianFarmasi');
                    if (btn) { btn.style.background = 'var(--green-primary)'; btn.style.color = 'white'; }
                } else if (tabName === 'stok_opname') {
                    document.getElementById('panel_stok_opname').style.display = 'block';
                    const btn = document.getElementById('btnTabStokOpname');
                    if (btn) { btn.style.background = 'var(--green-primary)'; btn.style.color = 'white'; }
                } else if (tabName === 'riwayat_farmasi') {
                    document.getElementById('panel_riwayat_farmasi').style.display = 'block';
                    const btn = document.getElementById('btnTabRiwayatFarmasi');
                    if (btn) { btn.style.background = 'var(--green-primary)'; btn.style.color = 'white'; }
                }
            }

            function filterTabelObat() {
                const query = (document.getElementById('searchObatInput').value || '').trim();
                const tbody = document.querySelector('#tabelDaftarObat tbody');
                if (!tbody) return;

                if (searchTimeoutObat) clearTimeout(searchTimeoutObat);

                if (query === '') {
                    // Jika kosong, kembalikan tampilan dari daftarObatData awal atau ambil ulang 25 data
                }

                searchTimeoutObat = setTimeout(() => {
                    fetch(`api/search_obat.php?keyword=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.status === 'success' && Array.isArray(res.data)) {
                                daftarObatData = res.data;
                                if (res.data.length === 0) {
                                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--gray-400); padding: 30px;"><i class="fa-solid fa-box-open" style="font-size: 20px; display:block; margin-bottom: 6px;"></i> Obat tidak ditemukan dengan kata kunci tersebut.</td></tr>';
                                    return;
                                }
                                let html = '';
                                res.data.forEach(row => {
                                    const stokVal = parseInt(row.stok_num || row.stok || 0);
                                    let badgeClass = 'badge-aman';
                                    let statusLabel = row.status || 'Aman';
                                    if (stokVal <= 0) { badgeClass = 'badge-habis'; statusLabel = 'Habis'; }
                                    elseif (stokVal <= 15) { badgeClass = 'badge-menipis'; statusLabel = 'Menipis'; }
                                    
                                    html += `<tr class="obat-row">
                                        <td class="obat-code">${row.kode || '-'}</td>
                                        <td class="obat-name">${row.nama || '-'}</td>
                                        <td>${row.kategori || '-'}</td>
                                        <td>${row.satuan || '-'}</td>
                                        <td style="font-weight: 600; color: var(--gray-800);">${row.stok || '0'}</td>
                                        <td>Rp ${row.harga ? Number(row.harga).toLocaleString('id-ID') : '10.000'}</td>
                                        <td><span class="status-badge ${badgeClass}">${statusLabel}</span></td>
                                        <td>
                                            <button onclick="bukaModalRestock('${row.obat_id || row.id || ''}', '${(row.nama || '').replace(/'/g, "\\'")}', ${stokVal}, ${row.harga || 10000})" class="btn-restock">
                                                <i class="fa-solid fa-boxes-packing"></i> Opname / Edit
                                            </button>
                                        </td>
                                    </tr>`;
                                });
                                tbody.innerHTML = html;
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching obat:', err);
                        });
                }, 300);
            }

            function bukaModalProsesFarmasi(qid, noAntrian, namaPasien, itemsJson) {
                document.getElementById('modalQueueId').value = qid;
                document.getElementById('modalNoAntrian').value = noAntrian;
                document.getElementById('modalNamaPasien').textContent = namaPasien + ' (' + noAntrian + ')';

                const tbody = document.getElementById('modalResepItemsTbody');
                tbody.innerHTML = '';

                let items = [];
                try { items = JSON.parse(itemsJson); } catch(e) {}

                if (!items || items.length === 0) {
                    // Default 1 row jika tidak ada item resep rinci
                    tbody.innerHTML = `
                        <tr>
                            <td style="padding:10px 12px;"><strong>Paket Obat / Resep Umum</strong></td>
                            <td style="padding:10px 12px;">
                                <select name="obat_id[]" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:13px;">
                                    <option value="">-- Pilih Obat --</option>
                                    ${daftarObatData.map(o => `<option value="${o.obat_id || o.id}">${o.nama} (Stok: ${o.stok}) - Rp ${o.harga || 10000}</option>`).join('')}
                                </select>
                            </td>
                            <td style="padding:10px 12px;">
                                <input type="number" name="jumlah[]" value="1" min="1" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; text-align:center;">
                            </td>
                        </tr>
                    `;
                } else {
                    items.forEach((ritem, idx) => {
                        let matchedObatId = '';
                        daftarObatData.forEach(o => {
                            if (o.nama && ritem.nama_obat && o.nama.toLowerCase() === ritem.nama_obat.toLowerCase()) {
                                matchedObatId = o.obat_id || o.id || '';
                            }
                        });

                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--gray-200)';
                        tr.innerHTML = `
                            <td style="padding:10px 12px;">
                                <strong>${ritem.nama_obat}</strong><br>
                                <small style="color:var(--gray-500);">${ritem.dosis} ${ritem.satuan} &bull; ${ritem.aturan_pakai}</small>
                            </td>
                            <td style="padding:10px 12px;">
                                <select name="obat_id[]" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:13px;">
                                    <option value="">-- Pilih Obat Stok --</option>
                                    ${daftarObatData.map(o => {
                                        const oid = o.obat_id || o.id || '';
                                        const sel = (oid == matchedObatId) ? 'selected' : '';
                                        return `<option value="${oid}" ${sel}>${o.nama} (Stok: ${o.stok}) - Rp ${o.harga || 10000}</option>`;
                                    }).join('')}
                                </select>
                            </td>
                            <td style="padding:10px 12px;">
                                <input type="number" name="jumlah[]" value="${ritem.qty || 1}" min="1" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; text-align:center;">
                            </td>
                        </tr>
                        `;
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('modalProsesFarmasi').style.display = 'flex';
            }

            function tutupModalProsesFarmasi() {
                document.getElementById('modalProsesFarmasi').style.display = 'none';
            }

            function bukaModalRestock(oid, nama, stokVal, hargaVal) {
                document.getElementById('restockObatId').value = oid;
                document.getElementById('restockNamaObat').value = nama || 'Obat Baru / Pilih dari Stok';
                document.getElementById('restockStokVal').value = stokVal || 0;
                document.getElementById('restockHargaVal').value = hargaVal || 10000;
                document.getElementById('modalRestock').style.display = 'flex';
            }

            function tutupModalRestock() {
                document.getElementById('modalRestock').style.display = 'none';
            }
            </script>
            <?php
            break;

        case 'kasir':
            require_role(['kasir']);
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
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h2 style="margin: 0;">Rincian Transaksi Tindakan & Obat</h2>
                            <p style="margin: 2px 0 0 0; font-size: 13px; color: var(--gray-600);">Tagihan terpadu: Jasa Konsultasi Dokter, Resep Obat Farmasi, dan Tindakan</p>
                        </div>
                        <?php if (!empty($detail_transaksi)): ?>
                        <button type="button" onclick="bukaModalCetakStruk()" style="background: #2563eb; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(37,99,235,0.2);">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Cetak Nota / Struk Terpadu
                        </button>
                        <?php endif; ?>
                    </div>
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
                                <td>
                                    <?php
                                        $badge_bg = '#f1f5f9'; $badge_col = '#475569';
                                        if ($row['kategori'] === 'Obat & Farmasi') { $badge_bg = '#dcfce7'; $badge_col = '#16a34a'; }
                                        elseif ($row['kategori'] === 'Jasa Dokter & Pemeriksaan') { $badge_bg = '#e0f2fe'; $badge_col = '#0369a1'; }
                                    ?>
                                    <span style="background: <?= $badge_bg ?>; color: <?= $badge_col ?>; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700;">
                                        <?= htmlspecialchars($row['kategori']) ?>
                                    </span>
                                </td>
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
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px;">
                            <button type="button" onclick="bukaModalCetakStruk()" style="background: var(--white); border: 2px solid #2563eb; color: #2563eb; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fa-solid fa-file-invoice"></i> Pratinjau / Cetak Struk
                            </button>
                            <button type="submit" class="btn-submit-pay" style="width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fa-solid fa-check"></i> Bayar & Selesai
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            </div>

            <!-- MODAL CETAK STRUK TERPADU -->
            <div id="modalCetakStruk" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
                <div style="background: white; border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 24px 48px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
                    <div style="background: #1e293b; color: white; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                        <h3 style="margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-file-invoice-dollar" style="color: #60a5fa;"></i> Pratinjau Nota & Struk Tagihan Terpadu</h3>
                        <button type="button" onclick="tutupModalCetakStruk()" style="background: none; border: none; color: white; font-size: 22px; cursor: pointer;">&times;</button>
                    </div>
                    <div id="areaCetakStruk" style="padding: 24px; overflow-y: auto; background: white; font-family: 'Courier New', Courier, monospace; color: #0f172a;">
                        <div style="text-align: center; border-bottom: 2px dashed #94a3b8; padding-bottom: 16px; margin-bottom: 16px;">
                            <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #1e293b;">SIM RS RME RALAN</h2>
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #475569;">Jl. Kesehatan Medika No. 123 &bull; Telp: (021) 555-0199</p>
                            <span style="font-size: 11px; background: #e2e8f0; color: #334155; padding: 2px 8px; border-radius: 4px; font-weight: 700; display: inline-block; margin-top: 6px;">NOTA PEMBAYARAN RAWAT JALAN</span>
                        </div>
                        <div style="font-size: 12px; line-height: 1.6; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 14px; display: grid; grid-template-columns: auto 1fr; gap: 4px 12px;">
                            <strong>No. Antrian / Inv:</strong> <span><?= htmlspecialchars($_GET['no_antrian'] ?? $no_antrian_active ?: 'UMUM') ?></span>
                            <strong>Tanggal / Waktu:</strong> <span><?= date('d/m/Y H:i') ?> WIB</span>
                            <strong>No. RM / Pasien:</strong> <span><?= htmlspecialchars($no_rm) ?> - <?= htmlspecialchars($nama_pasien) ?></span>
                            <strong>Dokter / Poli:</strong> <span><?= htmlspecialchars($dokter) ?> (<?= htmlspecialchars($poli) ?>)</span>
                            <strong>Penjamin:</strong> <span><?= htmlspecialchars($penjamin) ?></span>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <strong style="font-size: 12px; display: block; border-bottom: 1px solid #0f172a; padding-bottom: 4px; margin-bottom: 8px;">PERINCIAN BIAYA & LAYANAN:</strong>
                            <?php if (!empty($detail_transaksi)): ?>
                            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                <?php foreach ($detail_transaksi as $dt): ?>
                                <tr>
                                    <td style="padding: 4px 0; vertical-align: top;">
                                        <strong><?= htmlspecialchars($dt['deskripsi']) ?></strong><br>
                                        <small style="color: #64748b; font-size: 10.5px;">[<?= htmlspecialchars($dt['kategori']) ?>]</small>
                                    </td>
                                    <td style="padding: 4px 0; text-align: right; vertical-align: top; white-space: nowrap;">
                                        <?= $dt['qty'] ?> x <?= $dt['harga'] ?><br>
                                        <strong>Rp <?= $dt['total'] ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php else: ?>
                            <p style="font-size: 12px; text-align: center; color: #64748b; margin: 12px 0;">Tidak ada item tagihan tersimpan.</p>
                            <?php endif; ?>
                        </div>
                        <div style="border-top: 2px dashed #94a3b8; padding-top: 12px; margin-top: 12px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 15px; margin-bottom: 6px;">
                                <span>TOTAL TAGIHAN:</span>
                                <span>Rp <?= $subtotal_fmt ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #475569;">
                                <span>Status & Metode:</span>
                                <strong>LUNAS / SIAP BAYAR (<span id="strukMetodeText">Tunai</span>)</strong>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 24px; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                            <p style="margin: 0; font-weight: 600;">Terima kasih atas kepercayaan Anda.</p>
                            <p style="margin: 2px 0 0 0;">Semoga lekas sembuh dan sehat selalu.</p>
                        </div>
                    </div>
                    <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 20px; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                        <button type="button" onclick="tutupModalCetakStruk()" style="background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Tutup</button>
                        <button type="button" onclick="cetakAreaStruk()" style="background: #2563eb; color: white; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(37,99,235,0.25);">
                            <i class="fa-solid fa-print"></i> Cetak Langsung (Print)
                        </button>
                    </div>
                </div>
            </div>

            <script>
            function bukaModalCetakStruk() {
                const sel = document.getElementById('payment-method');
                if (sel && document.getElementById('strukMetodeText')) {
                    document.getElementById('strukMetodeText').textContent = sel.value;
                }
                document.getElementById('modalCetakStruk').style.display = 'flex';
            }

            function tutupModalCetakStruk() {
                document.getElementById('modalCetakStruk').style.display = 'none';
            }

            function cetakAreaStruk() {
                const content = document.getElementById('areaCetakStruk').innerHTML;
                const win = window.open('', '_blank', 'width=420,height=600');
                win.document.write(`
                    <html>
                    <head>
                        <title>Cetak Nota Pembayaran SIM RS</title>
                        <style>
                            body { font-family: 'Courier New', Courier, monospace; padding: 16px; color: #000; margin: 0; }
                            @media print { body { padding: 0; } }
                        </style>
                    </head>
                    <body>
                        ${content}
                    </body>
                    </html>
                `);
                win.document.close();
                win.focus();
                setTimeout(() => {
                    win.print();
                    win.close();
                }, 300);
            }
            </script>
            <?php
            break;

        case 'igd':
            require_role(['dokter', 'perawat', 'admisi']);
            // ---------- HALAMAN IGD & TRIASE ----------
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
                        $triase_color = 'Kuning';
                        $keluhan = 'Observasi darurat';
                        $vitals = '-';
                        
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
            <style>
                .badge-triase { padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
                .badge-triase.Merah { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
                .badge-triase.Kuning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
                .badge-triase.Hijau { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
                .badge-triase.Hitam { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
                .triage-option { border: 2px solid var(--gray-200); border-radius: 10px; padding: 12px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 10px; }
                .triage-option input[type="radio"] { cursor: pointer; }
                .triage-option.merah:hover, .triage-option input:checked + .merah { border-color: #dc2626; background: #fef2f2; }
                .triage-option.kuning:hover, .triage-option input:checked + .kuning { border-color: #d97706; background: #fffbeb; }
                .triage-option.hijau:hover, .triage-option input:checked + .hijau { border-color: #16a34a; background: #f0fdf4; }
                .triage-option.hitam:hover, .triage-option input:checked + .hitam { border-color: #475569; background: #f8fafc; }
            </style>

            <!-- STATS TRIASE GRID -->
            <div class="stat-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div class="stat-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #eff6ff; color: #2563eb; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px;"><i class="fa-solid fa-truck-medical"></i></div>
                        <div class="stat-label" style="font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Total Pasien IGD</div>
                        <div class="stat-value" style="font-size: 24px; font-weight: 700; color: #1e293b; margin-top: 4px;"><?= $triase_stats['total'] ?> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;">Pasien</span></div>
                    </div>
                </div>
                <div class="stat-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #fee2e2; color: #dc2626; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px;"><i class="fa-solid fa-heart-pulse"></i></div>
                        <div class="stat-label" style="font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Triase Merah (Resusitasi)</div>
                        <div class="stat-value" style="font-size: 24px; font-weight: 700; color: #dc2626; margin-top: 4px;"><?= $triase_stats['merah'] ?> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;">Darurat</span></div>
                    </div>
                </div>
                <div class="stat-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #fef3c7; color: #d97706; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="stat-label" style="font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Triase Kuning (Urgent)</div>
                        <div class="stat-value" style="font-size: 24px; font-weight: 700; color: #d97706; margin-top: 4px;"><?= $triase_stats['kuning'] ?> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;">Pasien</span></div>
                    </div>
                </div>
                <div class="stat-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div class="stat-left">
                        <div class="stat-icon" style="background: #dcfce7; color: #16a34a; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px;"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-label" style="font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Triase Hijau (Non-Darurat)</div>
                        <div class="stat-value" style="font-size: 24px; font-weight: 700; color: #16a34a; margin-top: 4px;"><?= $triase_stats['hijau'] ?> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;">Pasien</span></div>
                    </div>
                </div>
            </div>

            <!-- FORM TRIASE CEPAT -->
            <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 24px; margin-bottom: 24px; border-top: 4px solid #dc2626;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
                    <h2 style="font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-notes-medical" style="color: #dc2626; font-size: 20px;"></i> Registrasi & Triase Cepat Pasien IGD</h2>
                    <span style="font-size: 12px; font-weight: 600; color: #dc2626; background: #fee2e2; padding: 4px 12px; border-radius: 12px;">Fast Admission</span>
                </div>

                <form action="" method="POST">
                    <input type="hidden" name="action" value="daftar_igd">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                        <div>
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">Nama Pasien / No. RM (Isi 'Mr. X' jika belum ada identitas):</label>
                                <input type="text" name="nama_lengkap" class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;" placeholder="Contoh: Budi Santoso / Mr. X (Korban KLL)" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">No. KTP / NIK / ID Darurat:</label>
                                    <input type="text" name="nik" class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;" placeholder="3329xxxxxxxx / Kosongkan">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">Jenis Kelamin:</label>
                                    <select name="jenis_kelamin" class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                                        <option value="L">Laki-laki (L)</option>
                                        <option value="P">Perempuan (P)</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">Cara Bayar / Jenis Pasien:</label>
                                    <select name="jenis_pasien" class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                                        <option value="BPJS">BPJS Kesehatan</option>
                                        <option value="Umum">Umum / Mandiri</option>
                                        <option value="Asuransi">Asuransi Swasta</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">No. Telepon / Pengantar:</label>
                                    <input type="text" name="no_telepon" class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;" placeholder="08xxxxxxxx">
                                </div>
                            </div>
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">Keluhan Utama & Mekanisme Datang:</label>
                                <input type="text" name="keluhan" class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;" placeholder="Contoh: Sesak napas berat, diantar Ambulans / KLL motor" required>
                            </div>
                        </div>

                        <div>
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">Kategori Triase (Prioritas Kegawatdaruratan):</label>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 6px;">
                                    <label class="triage-option merah">
                                        <input type="radio" name="triase" value="Merah" required>
                                        <div>
                                            <strong style="color: #dc2626; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Merah (Resusitasi)</strong>
                                            <span style="font-size: 11px; color: #475569;">Mengancam nyawa, butuh penanganan instan</span>
                                        </div>
                                    </label>
                                    <label class="triage-option kuning">
                                        <input type="radio" name="triase" value="Kuning" checked required>
                                        <div>
                                            <strong style="color: #d97706; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Kuning (Urgent)</strong>
                                            <span style="font-size: 11px; color: #475569;">Darurat, berpotensi mengancam jika ditunda</span>
                                        </div>
                                    </label>
                                    <label class="triage-option hijau">
                                        <input type="radio" name="triase" value="Hijau" required>
                                        <div>
                                            <strong style="color: #16a34a; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Hijau (Non-Darurat)</strong>
                                            <span style="font-size: 11px; color: #475569;">Luka ringan / kondisi stabil</span>
                                        </div>
                                    </label>
                                    <label class="triage-option hitam">
                                        <input type="radio" name="triase" value="Hitam" required>
                                        <div>
                                            <strong style="color: #334155; display: block; font-size: 13px;"><i class="fa-solid fa-circle"></i> Hitam (Ekspektasi)</strong>
                                            <span style="font-size: 11px; color: #475569;">Meninggal dunia (DOA) / tidak dapat diselamatkan</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div style="margin-top: 18px; margin-bottom: 16px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">Tanda Vital Cepat (Initial Vitals):</label>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                                    <div><input type="text" name="td" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" placeholder="TD (mmHg)" title="Tekanan Darah"></div>
                                    <div><input type="text" name="nadi" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" placeholder="Nadi (x/m)" title="Denyut Nadi"></div>
                                    <div><input type="text" name="suhu" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" placeholder="Suhu (°C)" title="Suhu Tubuh"></div>
                                    <div><input type="text" name="spo2" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" placeholder="SpO2 (%)" title="Saturasi Oksigen"></div>
                                </div>
                            </div>

                            <button type="submit" style="background: #dc2626; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; margin-top: 10px; transition: background 0.2s; box-shadow: 0 2px 4px rgba(220,38,38,0.2);">
                                <i class="fa-solid fa-file-circle-plus"></i> Daftarkan & Masukkan ke Ruang IGD
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- BOARD PASIEN IGD AKTIF -->
            <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;"><i class="fa-solid fa-clipboard-list" style="color: #2563eb;"></i> Daftar Pasien IGD Aktif & Status Triase</h3>
                        <p style="font-size: 12px; color: #475569; margin: 2px 0 0;">Daftar pasien gawat darurat yang sedang dalam pengawasan medis hari ini</p>
                    </div>
                    <span style="font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px;"><i class="fa-solid fa-users"></i> <?= count($igd_list) ?> Pasien IGD</span>
                </div>

                <div style="overflow-x: auto; margin-top: 14px;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">No. Antrian / Waktu</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Nama Pasien / Identitas</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Status Triase</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Keluhan Utama & Tanda Vital</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Status Pelayanan</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; text-align: right;">Aksi Medis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($igd_list)): ?>
                                <?php foreach ($igd_list as $row): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 14px 16px; vertical-align: middle;">
                                        <strong style="color: #2563eb; font-size: 14px;"><?= htmlspecialchars($row['no'] ?? '-') ?></strong><br>
                                        <span style="font-size: 11px; color: #94a3b8;"><?= htmlspecialchars($row['estimasi'] ?? '-') ?> WIB</span>
                                    </td>
                                    <td style="padding: 14px 16px; vertical-align: middle;">
                                        <strong style="font-size: 14px; color: #1e293b; display: block;"><?= htmlspecialchars($row['nama'] ?? '-') ?></strong>
                                        <span style="font-size: 12px; color: #475569;"><i class="fa-solid fa-hospital-user"></i> <?= htmlspecialchars($row['poli'] ?? 'Instalasi Gawat Darurat (IGD)') ?></span>
                                    </td>
                                    <td style="padding: 14px 16px; vertical-align: middle;">
                                        <span class="badge-triase <?= htmlspecialchars($row['triase_color'] ?? 'Kuning') ?>">
                                            <i class="fa-solid fa-circle"></i> <?= htmlspecialchars($row['triase_color'] ?? 'Kuning') ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; vertical-align: middle;">
                                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($row['keluhan'] ?? '-') ?></div>
                                        <div style="font-size: 11.5px; color: #475569; margin-top: 2px;"><i class="fa-solid fa-heart-pulse" style="color: #dc2626;"></i> Vitals: <?= htmlspecialchars($row['vitals'] ?? '-') ?></div>
                                    </td>
                                    <td style="padding: 14px 16px; vertical-align: middle;">
                                        <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid #bfdbfe;">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Dalam Pemeriksaan
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; vertical-align: middle; text-align: right;">
                                        <a href="?page=emr_dokter&search=<?= urlencode($row['no'] ?? '') ?>" style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; margin-right: 4px;" title="Buka EMR Dokter / SOAP Cepat">
                                            <i class="fa-solid fa-stethoscope"></i> SOAP EMR
                                        </a>
                                        <a href="?page=kasir&no_antrian=<?= urlencode($row['no'] ?? '') ?>&nama=<?= urlencode($row['nama'] ?? '') ?>" style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;" title="Proses Tagihan Kasir">
                                            <i class="fa-solid fa-credit-card"></i> Kasir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 36px; color: #94a3b8;">
                                        <i class="fa-solid fa-truck-medical" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                        <span style="font-weight: 600; font-size: 14px;">Belum Ada Pasien IGD Aktif Hari Ini</span><br>
                                        <span style="font-size: 12px;">Gunakan form di atas untuk mendaftarkan pasien gawat darurat baru ke IGD.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            break;

        case 'manajemen_user':
            require_role(['superuser', 'supervisor']);
            ?>
            <div class="content-header">
                <div>
                    <h1 class="page-title"><i class="fa-solid fa-users-gear" style="color: #6366f1;"></i> Manajemen User & Role (RBAC & PAM)</h1>
                    <p class="page-subtitle">Kelola akun pengguna dan hak akses role sistem SIM RS</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="bukaModalUser(0, '', '', '', 'admisi')" style="background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 4px 12px rgba(99,102,241,0.3); padding: 10px 20px; font-weight: 600;">
                        <i class="fa-solid fa-user-plus"></i> Tambah Akun / Role
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser'): ?>
            <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.1)); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #d97706; font-size: 24px;">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 4px 0; color: #92400e; font-size: 15px; font-weight: 700;">Keamanan RBAC & Privileged Access Management (PAM) Aktif</h4>
                    <p style="margin: 0; color: #b45309; font-size: 13px; line-height: 1.5;">Sebagai <strong>Super User</strong>, Anda dapat melihat seluruh akun, namun setiap aksi penambahan, perubahan, atau penghapusan Role wajib mendapatkan persetujuan / memasukkan <strong>PIN Supervisor (Contoh: 123456)</strong> untuk mencegah kerusakan sistem.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border-color); box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                <div class="card-header" style="background: rgba(99, 102, 241, 0.05); padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 16px; color: var(--text-color);"><i class="fa-solid fa-list-check"></i> Daftar Pengguna Aktif</h3>
                    <span style="background: #e0e7ff; color: #4f46e5; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Total: <?= count($daftar_users) ?> Akun</span>
                </div>
                <div class="table-responsive">
                    <table class="table" style="margin: 0; width: 100%;">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.02);">
                                <th style="padding: 14px 20px;">ID</th>
                                <th style="padding: 14px 20px;">Nama Lengkap</th>
                                <th style="padding: 14px 20px;">Email / Username</th>
                                <th style="padding: 14px 20px;">Role Akses (RBAC)</th>
                                <th style="padding: 14px 20px;">Tanggal Dibuat</th>
                                <th style="padding: 14px 20px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_users)): ?>
                                <?php foreach ($daftar_users as $usr): 
                                    $role_str = strtolower($usr['role'] ?? '');
                                    $badge_color = '#64748b'; $badge_bg = '#f1f5f9';
                                    if ($role_str === 'dokter') { $badge_color = '#0284c7'; $badge_bg = '#e0f2fe'; }
                                    elseif ($role_str === 'perawat') { $badge_color = '#0d9488'; $badge_bg = '#ccfbf1'; }
                                    elseif ($role_str === 'farmasi' || $role_str === 'apoteker') { $badge_color = '#9333ea'; $badge_bg = '#f3e8ff'; }
                                    elseif ($role_str === 'kasir') { $badge_color = '#d97706'; $badge_bg = '#fef3c7'; }
                                    elseif ($role_str === 'admisi' || $role_str === 'resepsionis') { $badge_color = '#0891b2'; $badge_bg = '#cffafe'; }
                                    elseif ($role_str === 'supervisor') { $badge_color = '#16a34a'; $badge_bg = '#dcfce7'; }
                                    elseif ($role_str === 'superuser') { $badge_color = '#dc2626'; $badge_bg = '#fee2e2'; }
                                ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 14px 20px; font-weight: 600;">#<?= $usr['id'] ?></td>
                                    <td style="padding: 14px 20px; font-weight: 600; color: var(--text-color);">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $badge_bg ?>; color: <?= $badge_color ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                                <?= strtoupper(substr($usr['name'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($usr['name']) ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 20px; color: #64748b;"><?= htmlspecialchars($usr['email']) ?></td>
                                    <td style="padding: 14px 20px;">
                                        <span style="background: <?= $badge_bg ?>; color: <?= $badge_color ?>; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-block; text-transform: uppercase;">
                                            <i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars(strtoupper($role_str)) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px; font-size: 13px; color: #64748b;"><?= $usr['tgl_dibuat'] ?? '-' ?></td>
                                    <td style="padding: 14px 20px; text-align: center;">
                                        <button class="btn btn-sm btn-outline-primary" onclick="bukaModalUser(<?= $usr['id'] ?>, '<?= addslashes(htmlspecialchars($usr['name'])) ?>', '<?= addslashes(htmlspecialchars($usr['email'])) ?>', '', '<?= addslashes(htmlspecialchars($role_str)) ?>')" style="padding: 6px 12px; border-radius: 8px;">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <?php if ($usr['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="bukaModalHapusUser(<?= $usr['id'] ?>, '<?= addslashes(htmlspecialchars($usr['name'])) ?>')" style="padding: 6px 12px; border-radius: 8px; margin-left: 6px;">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">Belum ada data user.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Form Simpan / Edit User -->
            <div id="modalFormUser" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
                <div class="modal-content" style="background: var(--card-bg, #fff); border-radius: 20px; max-width: 500px; width: 90%; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative;">
                    <button type="button" onclick="tutupModalUser()" style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;"><i class="fa-solid fa-xmark"></i></button>
                    <h3 id="modalUserTitle" style="margin: 0 0 6px 0; font-size: 20px; color: var(--text-color);"><i class="fa-solid fa-user-gear"></i> Tambah Akun & Role</h3>
                    <p style="margin: 0 0 20px 0; font-size: 13px; color: #64748b;">Tentukan hak akses pengguna sesuai peran operasional RS.</p>

                    <form action="post_handler.php" method="POST">
                        <input type="hidden" name="action" value="simpan_user">
                        <input type="hidden" name="user_id" id="form_user_id" value="0">

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama Lengkap <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="name" id="form_user_name" required class="form-control" placeholder="Contoh: dr. Budi Santoso, Sp.PD" style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Email / Username <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="email" id="form_user_email" required class="form-control" placeholder="Contoh: dokter@rs.com" style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Password <span style="font-size: 11px; color: #64748b; font-weight: normal;">(Kosongkan jika tidak ingin mengubah password saat edit)</span></label>
                            <input type="password" name="password" id="form_user_password" class="form-control" placeholder="Masukkan password akun..." style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Role Akses (RBAC) <span style="color: #ef4444;">*</span></label>
                            <select name="role" id="form_user_role" required class="form-control" style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                                <option value="admisi">Admisi / Pendaftaran</option>
                                <option value="resepsionis">Resepsionis</option>
                                <option value="dokter">Dokter (EMR)</option>
                                <option value="perawat">Perawat (Antrian & Triase)</option>
                                <option value="farmasi">Farmasi / Apoteker</option>
                                <option value="kasir">Kasir Pembayaran</option>
                                <option value="supervisor">Supervisor (Pengawas PAM)</option>
                                <option value="superuser">Super User</option>
                            </select>
                        </div>

                        <?php if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser'): ?>
                        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 14px; margin-bottom: 20px;">
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #b91c1c; margin-bottom: 6px;"><i class="fa-solid fa-lock"></i> PIN Supervisor (PAM Required) <span style="color: #ef4444;">*</span></label>
                            <input type="password" name="supervisor_pin" required class="form-control" placeholder="Masukkan PIN Supervisor (Contoh: 123456)" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(239,68,68,0.4); background: #fff;">
                            <span style="font-size: 11px; color: #991b1b; margin-top: 4px; display: block;">Wajib mendapat izin PIN supervisor untuk modifikasi akun/role oleh Super User.</span>
                        </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" onclick="tutupModalUser()" class="btn btn-secondary" style="padding: 10px 20px; border-radius: 10px;">Batal</button>
                            <button type="submit" class="btn btn-primary" style="background: #4f46e5; padding: 10px 24px; border-radius: 10px; font-weight: 600;"><i class="fa-solid fa-save"></i> Simpan Role / Akun</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Hapus User -->
            <div id="modalHapusUser" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
                <div class="modal-content" style="background: var(--card-bg, #fff); border-radius: 20px; max-width: 420px; width: 90%; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative; text-align: center;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px auto;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 style="margin: 0 0 8px 0; font-size: 18px; color: var(--text-color);">Konfirmasi Hapus Akun</h3>
                    <p style="margin: 0 0 20px 0; font-size: 13px; color: #64748b;">Apakah Anda yakin ingin menghapus akun <strong id="hapus_name_display"></strong>? Tindakan ini tidak dapat dibatalkan.</p>

                    <form action="post_handler.php" method="POST">
                        <input type="hidden" name="action" value="hapus_user">
                        <input type="hidden" name="user_id" id="hapus_user_id" value="0">

                        <?php if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser'): ?>
                        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 14px; margin-bottom: 20px; text-align: left;">
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #b91c1c; margin-bottom: 6px;"><i class="fa-solid fa-lock"></i> PIN Supervisor <span style="color: #ef4444;">*</span></label>
                            <input type="password" name="supervisor_pin" required class="form-control" placeholder="Masukkan PIN Supervisor (123456)" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(239,68,68,0.4); background: #fff;">
                        </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: center; gap: 12px;">
                            <button type="button" onclick="tutupModalHapusUser()" class="btn btn-secondary" style="padding: 10px 20px; border-radius: 10px;">Batal</button>
                            <button type="submit" class="btn btn-danger" style="background: #ef4444; color: #fff; padding: 10px 24px; border-radius: 10px; font-weight: 600; border: none;"><i class="fa-solid fa-trash"></i> Ya, Hapus Akun</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function bukaModalUser(id, name, email, password, role) {
                document.getElementById('form_user_id').value = id;
                document.getElementById('form_user_name').value = name;
                document.getElementById('form_user_email').value = email;
                document.getElementById('form_user_password').value = '';
                document.getElementById('form_user_role').value = role || 'admisi';
                document.getElementById('modalUserTitle').innerHTML = id > 0 ? '<i class="fa-solid fa-user-pen"></i> Edit Role & Akun' : '<i class="fa-solid fa-user-plus"></i> Tambah Akun & Role';
                document.getElementById('modalFormUser').style.display = 'flex';
            }
            function tutupModalUser() {
                document.getElementById('modalFormUser').style.display = 'none';
            }
            function bukaModalHapusUser(id, name) {
                document.getElementById('hapus_user_id').value = id;
                document.getElementById('hapus_name_display').textContent = name;
                document.getElementById('modalHapusUser').style.display = 'flex';
            }
            function tutupModalHapusUser() {
                document.getElementById('modalHapusUser').style.display = 'none';
            }
            </script>
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
            <a href="<?= $item['url'] ?? ('index.php?page=' . $item['page']) ?>" <?= $is_active ?>>
                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php
    $role_active = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : 'admisi';
    $nama_active = isset($_SESSION['nama']) ? $_SESSION['nama'] : (isset($_SESSION['email']) ? $_SESSION['email'] : 'Pengguna SIMRS');
    if ($role_active === 'dokter'): ?>
    <a href="index.php?page=emr_dokter" class="btn-quick">
        <i class="fa-solid fa-stethoscope"></i> <span>Pemeriksaan EMR</span>
    </a>
    <?php elseif ($role_active === 'farmasi'): ?>
    <a href="index.php?page=farmasi" class="btn-quick">
        <i class="fa-solid fa-pills"></i> <span>Resep & Obat</span>
    </a>
    <?php elseif ($role_active === 'kasir'): ?>
    <a href="index.php?page=kasir" class="btn-quick">
        <i class="fa-solid fa-cash-register"></i> <span>Kasir & Pembayaran</span>
    </a>
    <?php elseif ($role_active === 'perawat'): ?>
    <a href="index.php?page=antrian" class="btn-quick">
        <i class="fa-solid fa-user-clock"></i> <span>Kelola Antrian</span>
    </a>
    <?php else: ?>
    <a href="index.php?page=pendaftaran" class="btn-quick">
        <i class="fa-solid fa-plus"></i> <span>Quick Admission</span>
    </a>
    <?php endif; ?>

    <div class="sidebar-footer">
        <?php if ($role_active === 'superuser' || $role_active === 'supervisor'): ?>
        <a href="#" onclick="openPamModal()"><i class="fa-solid fa-shield-halved" style="color: #f59e0b;"></i> <span>PAM Security</span></a>
        <?php else: ?>
        <a href="#"><i class="fa-solid fa-gear"></i> <span>Settings</span></a>
        <?php endif; ?>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
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
            <p class="breadcrumb">SIMRS Role &rsaquo; <span style="color: var(--primary); font-weight: bold;"><?= htmlspecialchars(ucfirst($role_active)) ?></span> &rsaquo; <span><?= htmlspecialchars($page_title) ?></span></p>
        </div>
        <div class="topbar-right">
            <?php 
            $notif_emr_cnt = !empty($pasien_selesai_emr) ? count($pasien_selesai_emr) : 0;
            ?>
            <?php if ($role_active === 'superuser'): ?>
            <button class="btn-secondary" onclick="openPamModal()" style="font-size: 11px; padding: 6px 12px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                <i class="fa-solid fa-key" style="margin-right: 4px;"></i> PAM Approval PIN
            </button>
            <?php endif; ?>
            <button class="icon-btn" title="<?= $notif_emr_cnt > 0 ? $notif_emr_cnt . ' Pasien Selesai EMR Menunggu Kasir' : 'Tidak ada notifikasi baru' ?>" onclick="if(<?= $notif_emr_cnt ?> > 0) window.location.href='?page=kasir'">
                <i class="fa-solid fa-bell <?= $notif_emr_cnt > 0 ? 'fa-shake' : '' ?>" style="<?= $notif_emr_cnt > 0 ? 'color: #2563eb;' : '' ?>"></i>
                <?php if ($notif_emr_cnt > 0): ?>
                <span class="badge" style="background: #ef4444; color: white; font-weight: bold;"><?= $notif_emr_cnt ?></span>
                <?php else: ?>
                <span class="badge">0</span>
                <?php endif; ?>
            </button>
            <button class="icon-btn"><i class="fa-solid fa-user-shield" title="RBAC & PAM Active"></i></button>
            <div class="user-info">
                <div class="user-avatar"><i class="fa-solid fa-user-tie"></i></div>
                <div class="user-text">
                    <strong><?= htmlspecialchars($nama_active) ?></strong>
                    <small style="text-transform: capitalize; font-weight: 600; color: #2563eb;"><?= htmlspecialchars($role_active) ?></small>
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

<!-- MODAL PAM / SUPERVISOR APPROVAL -->
<div id="pamModal" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 10000;">
    <div class="modal-content" style="max-width: 440px; border-radius: 14px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 18px 22px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-shield-halved" style="font-size: 20px; color: #fde047;"></i>
                <h3 style="margin: 0; font-size: 16px;">Otorisasi PAM Supervisor</h3>
            </div>
            <button onclick="closePamModal()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 22px;">
            <p style="font-size: 13px; color: #4b5563; margin-bottom: 16px; line-height: 1.5;">
                Sebagai <strong>Super User</strong>, tindakan perubahan krusial atau administratif memerlukan verifikasi PIN dari <strong>Supervisor (Irwan Pengawas)</strong> sesuai standar keamanan PAM (Privileged Access Management).
            </p>
            <form method="POST" action="">
                <input type="hidden" name="action" id="pamActionType" value="pam_approve">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">PIN Supervisor (Default: 123456)</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-key" style="position: absolute; left: 12px; top: 12px; color: #9ca3af;"></i>
                        <input type="password" name="supervisor_pin" placeholder="Masukkan PIN Supervisor 6 angka..." required style="width: 100%; padding: 10px 10px 10px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; letter-spacing: 2px;">
                    </div>
                </div>
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Atau Ajukan Request PAM Tanpa PIN</label>
                    <input type="text" name="request_reason" placeholder="Alasan perubahan / permintaan otorisasi..." style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closePamModal()" style="padding: 9px 16px; border-radius: 8px;">Batal</button>
                    <button type="button" onclick="submitPamRequest(this.form)" class="btn-secondary" style="padding: 9px 16px; border-radius: 8px; background: #f3f4f6; color: #374151;">Ajukan Request</button>
                    <button type="submit" class="btn-primary" style="padding: 9px 18px; border-radius: 8px; background: #2563eb; color: white; font-weight: 600;">Verifikasi PIN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openPamModal() {
    const m = document.getElementById('pamModal');
    if (m) m.style.display = 'flex';
}
function closePamModal() {
    const m = document.getElementById('pamModal');
    if (m) m.style.display = 'none';
}
function submitPamRequest(form) {
    document.getElementById('pamActionType').value = 'pam_request';
    form.submit();
}
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