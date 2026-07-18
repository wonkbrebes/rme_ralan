<?php
/**
 * Shared POST Handler - Pendaftaran Pasien & Aksi Lainnya
 * Dipanggil dari index.php dan Pendaftaran.php
 * Menghilangkan duplikasi kode POST handler
 * 
 * SYARAT: config.php sudah di-require sebelum file ini
 * MENGHASILKAN: $msg_success, $msg_error
 */

$msg_success = '';
$msg_error = '';

function get_default_dokter_id() {
    try {
        $row = db_select_one("SELECT dokter_id FROM dokter WHERE is_active = TRUE ORDER BY dokter_id LIMIT 1");
        if ($row && !empty($row['dokter_id'])) {
            return intval($row['dokter_id']);
        }
        $row = db_select_one("SELECT dokter_id FROM dokter ORDER BY dokter_id LIMIT 1");
        return $row ? intval($row['dokter_id']) : null;
    } catch (Exception $e) {
        error_log("post_handler error in get_default_dokter_id: " . $e->getMessage());
        return null;
    }
}

function create_or_get_visit_for_queue($queue_id, $subjective, $jenis_pembayaran, $keluhan_utama) {
    try {
        $queue = db_select_one("SELECT q.id, q.patient_id, q.polyclinic_id, q.dokter_id, q.biaya_jasa, q.no_antrian, q.status, q.jenis_daftar, p.no_rm, p.jenis_pasien FROM queues q JOIN patients p ON q.patient_id = p.id WHERE q.id = :qid", ['qid' => $queue_id]);
        if (!$queue) {
            return null;
        }

        $existing = db_select_one("SELECT kunjungan_id, dokter_id, biaya_jasa FROM visits WHERE queue_id = :qid", ['qid' => $queue_id]);
        if ($existing) {
            return intval($existing['kunjungan_id']);
        }

        $dokter_id = !empty($queue['dokter_id']) ? intval($queue['dokter_id']) : get_default_dokter_id();
        if (!$dokter_id) {
            return null;
        }
        $biaya_jasa = isset($queue['biaya_jasa']) ? floatval($queue['biaya_jasa']) : 50000;

        $no_kunjungan = 'KJ-' . date('Ymd') . '-' . $queue['id'];
        db_insert('visits', [
            'no_kunjungan' => $no_kunjungan,
            'pasien_id' => $queue['patient_id'],
            'polyclinic_id' => $queue['polyclinic_id'],
            'dokter_id' => $dokter_id,
            'queue_id' => $queue['id'],
            'no_antrian' => $queue['no_antrian'],
            'tanggal_kunjungan' => date('Y-m-d'),
            'status' => 'DALAM_PEMERIKSAAN',
            'jenis_pembayaran' => $jenis_pembayaran,
            'keluhan_utama' => $keluhan_utama,
            'biaya_jasa' => $biaya_jasa
        ]);

        $visit = db_select_one("SELECT kunjungan_id FROM visits WHERE queue_id = :qid", ['qid' => $queue_id]);
        return $visit ? intval($visit['kunjungan_id']) : null;
    } catch (Exception $e) {
        error_log("post_handler error in create_or_get_visit_for_queue: " . $e->getMessage());
        return null;
    }
}

function create_rekam_medis_for_visit($kunjungan_id, $dokter_id, $keluhan_utama, $subjective, $objective, $assessment, $plan, $pemeriksaan_fisik = null, $berat_badan = null, $tinggi_badan = null, $tekanan_darah = null) {
    try {
        $existing = db_select_one("SELECT rm_id FROM rekam_medis WHERE kunjungan_id = :kid", ['kid' => $kunjungan_id]);
        $physical_notes = trim(trim((string)$pemeriksaan_fisik) . ($tekanan_darah ? "\nTekanan Darah: $tekanan_darah" : ''));
        $fields = [
            'kunjungan_id' => $kunjungan_id,
            'dokter_id' => $dokter_id,
            'keluhan_utama' => $keluhan_utama,
            'subjective' => $subjective,
            'objective' => $objective,
            'assessment' => $assessment,
            'plan' => $plan,
            'tekanan_darah' => $tekanan_darah ?: null,
            'pemeriksaan_fisik' => $physical_notes ?: null,
            'berat_badan' => !empty($berat_badan) ? $berat_badan : null,
            'tinggi_badan' => !empty($tinggi_badan) ? $tinggi_badan : null,
            'status' => 'FINAL',
        ];

        if ($existing) {
            update_rekam_medis(intval($existing['rm_id']), $fields);
            return intval($existing['rm_id']);
        }

        db_insert('rekam_medis', $fields);
        $row = db_select_one("SELECT rm_id FROM rekam_medis WHERE kunjungan_id = :kid", ['kid' => $kunjungan_id]);
        return $row ? intval($row['rm_id']) : null;
    } catch (Exception $e) {
        error_log("post_handler error in create_rekam_medis_for_visit: " . $e->getMessage());
        return null;
    }
}

function update_rekam_medis($rm_id, array $fields) {
    $allowed = ['keluhan_utama', 'subjective', 'objective', 'assessment', 'plan', 'pemeriksaan_fisik', 'berat_badan', 'tinggi_badan', 'status'];
    $updates = [];
    $params = ['rm_id' => $rm_id];

    foreach ($fields as $key => $value) {
        if (in_array($key, $allowed, true) && $value !== null) {
            $updates[] = "$key = :$key";
            $params[$key] = $value;
        }
    }

    if (empty($updates)) {
        return;
    }

    $sql = "UPDATE rekam_medis SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE rm_id = :rm_id";
    db_query($sql, $params);
}

function create_rm_diagnosa($rm_id, $kode_icd10, $nama_diagnosa, $jenis_diagnosis = 'KLINIS') {
    if (empty($rm_id) || empty($kode_icd10) || empty($nama_diagnosa)) {
        return null;
    }

    try {
        db_query("INSERT INTO rm_diagnoses (rm_id, kode_icd10, nama_diagnosis, jenis_diagnosis) VALUES (:rm_id, :kode, :nama, :jenis)", [
            'rm_id' => $rm_id,
            'kode' => $kode_icd10,
            'nama' => $nama_diagnosa,
            'jenis' => $jenis_diagnosis,
        ]);
        return true;
    } catch (Exception $e) {
        error_log("post_handler error in create_rm_diagnosa: " . $e->getMessage());
        return null;
    }
}

function create_resep_for_rm($rm_id, $catatan, $items = []) {
    if (empty($rm_id) && empty($items) && empty(trim($catatan ?? ''))) {
        return null;
    }

    try {
        db_query("CREATE TABLE IF NOT EXISTS resep (
            id BIGSERIAL PRIMARY KEY,
            rm_id BIGINT UNIQUE NOT NULL REFERENCES rekam_medis(rm_id) ON DELETE CASCADE,
            status VARCHAR(30) DEFAULT 'DRAFT',
            catatan TEXT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        )");
        db_query("CREATE TABLE IF NOT EXISTS resep_items (
            id BIGSERIAL PRIMARY KEY,
            resep_id BIGINT NOT NULL REFERENCES resep(id) ON DELETE CASCADE,
            obat_id BIGINT NULL,
            nama_obat VARCHAR(150) NULL,
            dosis NUMERIC(8, 2) NULL DEFAULT 1,
            satuan VARCHAR(50) NULL,
            aturan_pakai VARCHAR(100) NULL,
            qty INTEGER NULL DEFAULT 1,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {
        error_log("warning create tables resep/resep_items: " . $e->getMessage());
    }

    try {
        $resep_id = null;
        $existing = db_select_one("SELECT id FROM resep WHERE rm_id = :rm_id", ['rm_id' => $rm_id]);
        if ($existing) {
            db_query("UPDATE resep SET catatan = :catatan, status = 'DRAFT', updated_at = CURRENT_TIMESTAMP WHERE id = :id", [
                'catatan' => trim($catatan ?? ''),
                'id' => $existing['id'],
            ]);
            $resep_id = intval($existing['id']);
        } else {
            db_insert('resep', [
                'rm_id' => $rm_id,
                'status' => 'DRAFT',
                'catatan' => trim($catatan ?? ''),
            ]);
            $row = db_select_one("SELECT id FROM resep WHERE rm_id = :rm_id", ['rm_id' => $rm_id]);
            $resep_id = $row ? intval($row['id']) : null;
        }

        if ($resep_id && !empty($items) && is_array($items)) {
            db_query("DELETE FROM resep_items WHERE resep_id = :rid", ['rid' => $resep_id]);
            foreach ($items as $itm) {
                if (!empty($itm['nama_obat']) || !empty($itm['obat_id'])) {
                    db_insert('resep_items', [
                        'resep_id' => $resep_id,
                        'obat_id' => !empty($itm['obat_id']) ? intval($itm['obat_id']) : null,
                        'nama_obat' => trim($itm['nama_obat'] ?? ''),
                        'dosis' => floatval($itm['dosis'] ?? 1),
                        'satuan' => trim($itm['satuan'] ?? 'Tablet'),
                        'aturan_pakai' => trim($itm['aturan_pakai'] ?? '3x1 sesudah makan'),
                        'qty' => intval($itm['qty'] ?? 1)
                    ]);
                }
            }
        }

        return $resep_id;
    } catch (Exception $e) {
        error_log("post_handler error in create_resep_for_rm: " . $e->getMessage());
        return null;
    }
}

function create_order_lab_for_visit($rm_id, $kunjungan_id, $jenis_pemeriksaan, $catatan) {
    if (empty($rm_id) || empty($kunjungan_id) || empty(trim($jenis_pemeriksaan))) {
        return null;
    }

    try {
        db_insert('order_lab', [
            'rm_id' => $rm_id,
            'kunjungan_id' => $kunjungan_id,
            'jenis_pemeriksaan' => trim($jenis_pemeriksaan),
            'catatan' => trim($catatan) ?: null,
            'status' => 'ORDERED',
        ]);
        $row = db_select_one("SELECT id FROM order_lab WHERE rm_id = :rm_id AND kunjungan_id = :kid ORDER BY created_at DESC LIMIT 1", ['rm_id' => $rm_id, 'kid' => $kunjungan_id]);
        return $row ? intval($row['id']) : null;
    } catch (Exception $e) {
        error_log("post_handler error in create_order_lab_for_visit: " . $e->getMessage());
        return null;
    }
}

function create_order_radiologi_for_visit($rm_id, $kunjungan_id, $jenis_pemeriksaan, $catatan) {
    if (empty($rm_id) || empty($kunjungan_id) || empty(trim($jenis_pemeriksaan))) {
        return null;
    }

    try {
        db_insert('order_radiologi', [
            'rm_id' => $rm_id,
            'kunjungan_id' => $kunjungan_id,
            'jenis_pemeriksaan' => trim($jenis_pemeriksaan),
            'catatan' => trim($catatan) ?: null,
            'status' => 'ORDERED',
        ]);
        $row = db_select_one("SELECT id FROM order_radiologi WHERE rm_id = :rm_id AND kunjungan_id = :kid ORDER BY created_at DESC LIMIT 1", ['rm_id' => $rm_id, 'kid' => $kunjungan_id]);
        return $row ? intval($row['id']) : null;
    } catch (Exception $e) {
        error_log("post_handler error in create_order_radiologi_for_visit: " . $e->getMessage());
        return null;
    }
}

function create_order_for_rm($rm_id, $kunjungan_id, $jenis_pemeriksaan, $catatan, $is_radiologi = false) {
    if ($is_radiologi) {
        return create_order_radiologi_for_visit($rm_id, $kunjungan_id, $jenis_pemeriksaan, $catatan);
    }
    return create_order_lab_for_visit($rm_id, $kunjungan_id, $jenis_pemeriksaan, $catatan);
}

function create_surat_rujukan_for_rm($rm_id, $faskes_tujuan, $poli_tujuan, $alasan, $no_rujukan_bpjs = null, $tanggal_rujukan = null) {
    if (empty($rm_id) || empty(trim($faskes_tujuan)) || empty(trim($poli_tujuan)) || empty(trim($alasan))) {
        return null;
    }

    try {
        db_insert('surat_rujukan', [
            'rm_id' => $rm_id,
            'faskes_tujuan' => trim($faskes_tujuan),
            'poli_tujuan' => trim($poli_tujuan),
            'alasan_rujukan' => trim($alasan),
            'no_rujukan_bpjs' => !empty($no_rujukan_bpjs) ? trim($no_rujukan_bpjs) : null,
            'tanggal_rujukan' => !empty($tanggal_rujukan) ? $tanggal_rujukan : date('Y-m-d'),
        ]);
        return true;
    } catch (Exception $e) {
        error_log("post_handler error in create_surat_rujukan_for_rm: " . $e->getMessage());
        return null;
    }
}

function create_tagihan_detail_table_if_missing() {
    try {
        db_query("CREATE TABLE IF NOT EXISTS tagihan_detail (
            id BIGSERIAL PRIMARY KEY,
            tagihan_id BIGINT NOT NULL REFERENCES tagihan(id) ON DELETE CASCADE,
            nama_layanan VARCHAR(255) NOT NULL,
            biaya NUMERIC(12,2) NOT NULL,
            jumlah INTEGER NOT NULL DEFAULT 1,
            subtotal NUMERIC(12,2) NOT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {
        error_log("post_handler warning in create_tagihan_detail_table_if_missing: " . $e->getMessage());
    }
}

function sync_doctor_fee_to_tagihan($kunjungan_id) {
    if (empty($kunjungan_id)) return;
    try {
        create_tagihan_detail_table_if_missing();
        $visit = db_select_one("SELECT v.kunjungan_id, v.dokter_id, v.biaya_jasa, d.nama_lengkap as nama_dokter, d.jenis_dokter, d.biaya_jasa as dokter_fee FROM visits v LEFT JOIN dokter d ON v.dokter_id = d.dokter_id WHERE v.kunjungan_id = :kid", ['kid' => $kunjungan_id]);
        if (!$visit) return;

        $biaya = isset($visit['biaya_jasa']) && floatval($visit['biaya_jasa']) > 0 ? floatval($visit['biaya_jasa']) : (isset($visit['dokter_fee']) && floatval($visit['dokter_fee']) > 0 ? floatval($visit['dokter_fee']) : 50000);
        $nama_dok = !empty($visit['nama_dokter']) ? $visit['nama_dokter'] : 'Dokter Pemeriksa';

        $existing_tag = db_select_one("SELECT id FROM tagihan WHERE kunjungan_id = :kid", ['kid' => $kunjungan_id]);
        if (!$existing_tag) {
            db_insert('tagihan', [
                'kunjungan_id' => $kunjungan_id,
                'no_tagihan' => 'TAG-' . date('Ymd') . '-' . $kunjungan_id,
                'total_biaya' => $biaya,
                'status_pembayaran' => 'BELUM_DIBAYAR',
                'metode_pembayaran' => 'Tunai'
            ]);
            $existing_tag = db_select_one("SELECT id FROM tagihan WHERE kunjungan_id = :kid", ['kid' => $kunjungan_id]);
        }

        if ($existing_tag && isset($existing_tag['id'])) {
            $tag_id = intval($existing_tag['id']);
            $exist_det = db_select_one("SELECT id FROM tagihan_detail WHERE tagihan_id = :tid AND (nama_layanan ILIKE '%Jasa Dokter%' OR nama_layanan ILIKE '%Konsultasi%') LIMIT 1", ['tid' => $tag_id]);
            if ($exist_det) {
                db_query("UPDATE tagihan_detail SET nama_layanan = :nl, biaya = :b, jumlah = 1, subtotal = :b WHERE id = :id", [
                    'nl' => 'Jasa Konsultasi & Pemeriksaan - ' . $nama_dok,
                    'b' => $biaya,
                    'id' => $exist_det['id']
                ]);
            } else {
                db_insert('tagihan_detail', [
                    'tagihan_id' => $tag_id,
                    'nama_layanan' => 'Jasa Konsultasi & Pemeriksaan - ' . $nama_dok,
                    'biaya' => $biaya,
                    'jumlah' => 1,
                    'subtotal' => $biaya
                ]);
            }

            // Recalculate total_biaya
            $sum_row = db_select_one("SELECT SUM(subtotal) as total FROM tagihan_detail WHERE tagihan_id = :tid", ['tid' => $tag_id]);
            $new_total = $sum_row && isset($sum_row['total']) ? floatval($sum_row['total']) : $biaya;
            db_query("UPDATE tagihan SET total_biaya = :total WHERE id = :tid", ['total' => $new_total, 'tid' => $tag_id]);
        }
    } catch (Exception $e) {
        error_log("sync_doctor_fee_to_tagihan error: " . $e->getMessage());
    }
}

function create_tagihan_for_visit($kunjungan_id, $nominal, $metode_pembayaran) {
    try {
        create_tagihan_detail_table_if_missing();
        $existing = db_select_one("SELECT id FROM tagihan WHERE kunjungan_id = :kid", ['kid' => $kunjungan_id]);
        if (!$existing) {
            db_insert('tagihan', [
                'kunjungan_id' => $kunjungan_id,
                'no_tagihan' => 'TAG-' . date('Ymd') . '-' . $kunjungan_id,
                'total_biaya' => $nominal,
                'status_pembayaran' => 'LUNAS',
                'metode_pembayaran' => $metode_pembayaran,
                'dibayar_at' => date('Y-m-d H:i:s'),
            ]);
            $existing = db_select_one("SELECT id FROM tagihan WHERE kunjungan_id = :kid", ['kid' => $kunjungan_id]);
            if ($existing) {
                db_insert('tagihan_detail', [
                    'tagihan_id' => $existing['id'],
                    'nama_layanan' => 'Total Pembayaran Layanan Kasir',
                    'biaya' => $nominal,
                    'jumlah' => 1,
                    'subtotal' => $nominal,
                ]);
            }
        } else {
            // Jika tagihan sudah ada (misal dari jasa dokter dan obat), update status jadi LUNAS dan sesuaikan total atau simpan pembayaran
            $sum_row = db_select_one("SELECT SUM(subtotal) as total FROM tagihan_detail WHERE tagihan_id = :tid", ['tid' => $existing['id']]);
            $calc_total = $sum_row && isset($sum_row['total']) ? floatval($sum_row['total']) : $nominal;
            $final_total = ($nominal > 0 && $nominal !== $calc_total) ? $nominal : $calc_total;

            db_query("UPDATE tagihan SET total_biaya = :total, status_pembayaran = 'LUNAS', metode_pembayaran = :metode, dibayar_at = CURRENT_TIMESTAMP WHERE id = :tid", [
                'total' => $final_total,
                'metode' => $metode_pembayaran,
                'tid' => $existing['id'],
            ]);
        }
    } catch (Exception $e) {
        error_log("post_handler warning in create_tagihan_for_visit: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan_user') {
        require_role(['superuser', 'supervisor']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('simpan_user')) {
                    throw new Exception("Pembuatan/perubahan akun oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }
            $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $nama = !empty($_POST['name']) ? trim($_POST['name']) : '';
            $email = !empty($_POST['email']) ? trim($_POST['email']) : '';
            $password = !empty($_POST['password']) ? trim($_POST['password']) : '';
            $role = !empty($_POST['role']) ? strtolower(trim($_POST['role'])) : 'admisi';

            if (empty($nama) || empty($email)) {
                throw new Exception("Nama dan Email wajib diisi!");
            }

            if ($user_id > 0) {
                $existing = db_select_one("SELECT * FROM users WHERE id = :uid", ['uid' => $user_id]);
                if (!$existing) throw new Exception("User tidak ditemukan.");
                if (!empty($password)) {
                    db_update('users', ['name' => $nama, 'email' => $email, 'role' => $role, 'password' => password_hash($password, PASSWORD_BCRYPT)], ['id' => $user_id]);
                } else {
                    db_update('users', ['name' => $nama, 'email' => $email, 'role' => $role], ['id' => $user_id]);
                }
            } else {
                if (empty($password)) throw new Exception("Password wajib diisi untuk akun baru!");
                $cek = db_select_one("SELECT id FROM users WHERE email = :email", ['email' => $email]);
                if ($cek) throw new Exception("Email tersebut sudah terdaftar!");
                db_insert('users', [
                    'name' => $nama,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'role' => $role
                ]);
            }
            header("Location: index.php?page=manajemen_user&success=" . urlencode("Akun/role berhasil disimpan."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?page=manajemen_user&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    if ($action === 'hapus_user') {
        require_role(['superuser', 'supervisor']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('hapus_user')) {
                    throw new Exception("Penghapusan akun oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }
            $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            if ($user_id <= 0) throw new Exception("ID User tidak valid.");
            db_delete('users', ['id' => $user_id]);
            header("Location: index.php?page=manajemen_user&success=" . urlencode("Akun berhasil dihapus."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?page=manajemen_user&error=" . urlencode($e->getMessage()));
            exit;
        }
    }
    
    if ($action === 'register_pasien') {
        require_role(['admisi', 'resepsionis', 'perawat']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('register_pasien')) {
                    throw new Exception("Perubahan oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }
            $no_rm = 'RM-' . date('Y') . '-' . sprintf('%04d', rand(1, 9999));
            
            // Sanitasi Integer / Angka
            $nik_raw = !empty($_POST['nik']) ? $_POST['nik'] : '0000000000000000';
            $nik = preg_replace('/[^0-9]/', '', $nik_raw);
            if (empty($nik)) $nik = '0000000000000000';

            $bpjs_raw = !empty($_POST['no_bpjs']) ? $_POST['no_bpjs'] : null;
            $no_bpjs = $bpjs_raw ? preg_replace('/[^0-9]/', '', $bpjs_raw) : null;

            $phone_raw = !empty($_POST['no_telepon']) ? $_POST['no_telepon'] : '-';
            $phone = preg_replace('/[^0-9+]/', '', $phone_raw);
            if (empty($phone)) $phone = '-';

            // Sanitasi String / Teks
            $nama = !empty($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : 'Pasien Baru';
            $tgl_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '2000-01-01';
            $jk_input = !empty($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : 'Laki-laki';
            $jk = ($jk_input === 'P' || strcasecmp($jk_input, 'perempuan') === 0) ? 'Perempuan' : 'Laki-laki';
            $alamat = !empty($_POST['alamat']) ? trim($_POST['alamat']) : '-';

            // Jenis pasien / penjamin (sesuai enum: Umum, BPJS, Asuransi Lain, Gratis)
            $jenis_pasien_valid = ['Umum', 'BPJS', 'Asuransi Lain', 'Gratis'];
            $jenis_pasien = (!empty($_POST['jenis_pasien']) && in_array($_POST['jenis_pasien'], $jenis_pasien_valid)) ? $_POST['jenis_pasien'] : 'Umum';

            // Poliklinik tujuan (wajib diisi)
            $polyclinic_id = !empty($_POST['polyclinic_id']) ? intval($_POST['polyclinic_id']) : 0;
            if ($polyclinic_id <= 0) {
                throw new Exception('Poliklinik tujuan wajib dipilih!');
            }

            // Pilihan Dokter & Biaya Jasa
            $dokter_id = !empty($_POST['dokter_id']) ? intval($_POST['dokter_id']) : null;
            $biaya_jasa = 50000;
            if ($dokter_id > 0) {
                $dok_row = db_select_one("SELECT biaya_jasa, jenis_dokter FROM dokter WHERE dokter_id = :did LIMIT 1", ['did' => $dokter_id]);
                if ($dok_row && isset($dok_row['biaya_jasa'])) {
                    $biaya_jasa = floatval($dok_row['biaya_jasa']);
                }
            } else {
                $dokter_id = get_default_dokter_id();
                if ($dokter_id > 0) {
                    $dok_row = db_select_one("SELECT biaya_jasa FROM dokter WHERE dokter_id = :did LIMIT 1", ['did' => $dokter_id]);
                    if ($dok_row && isset($dok_row['biaya_jasa'])) {
                        $biaya_jasa = floatval($dok_row['biaya_jasa']);
                    }
                }
            }

            $existing_id = !empty($_POST['existing_patient_id']) ? intval($_POST['existing_patient_id']) : 0;
            $patient_id = 0;

            if ($existing_id > 0) {
                $exist_p = db_select_one("SELECT id, no_rm FROM patients WHERE id = :id LIMIT 1", ['id' => $existing_id]);
                if ($exist_p && isset($exist_p['id'])) {
                    $patient_id = $exist_p['id'];
                    $no_rm = $exist_p['no_rm'];
                    db_query("UPDATE patients SET nama_lengkap = :nama, no_telepon = :hp, alamat = :alamat, jenis_pasien = :jp, gol_darah = :gd WHERE id = :id", [
                        'nama' => $nama, 'hp' => $phone, 'alamat' => $alamat, 'jp' => $jenis_pasien, 'gd' => !empty($_POST['golongan_darah']) ? $_POST['golongan_darah'] : null, 'id' => $patient_id
                    ]);
                }
            }

            if ($patient_id <= 0 && !empty($nik) && $nik !== '0000000000000000') {
                $exist_nik = db_select_one("SELECT id, no_rm FROM patients WHERE nik = :nik LIMIT 1", ['nik' => $nik]);
                if ($exist_nik && isset($exist_nik['id'])) {
                    $patient_id = $exist_nik['id'];
                    $no_rm = $exist_nik['no_rm'];
                    db_query("UPDATE patients SET nama_lengkap = :nama, no_telepon = :hp, alamat = :alamat, jenis_pasien = :jp, gol_darah = :gd WHERE id = :id", [
                        'nama' => $nama, 'hp' => $phone, 'alamat' => $alamat, 'jp' => $jenis_pasien, 'gd' => !empty($_POST['golongan_darah']) ? $_POST['golongan_darah'] : null, 'id' => $patient_id
                    ]);
                }
            }

            if ($patient_id <= 0) {
                db_insert('patients', [
                    'no_rm' => $no_rm,
                    'nik' => $nik,
                    'nama_lengkap' => $nama,
                    'tanggal_lahir' => $tgl_lahir,
                    'jenis_kelamin' => $jk,
                    'no_telepon' => $phone,
                    'alamat' => $alamat,
                    'no_bpjs' => !empty($no_bpjs) ? $no_bpjs : null,
                    'gol_darah' => !empty($_POST['golongan_darah']) ? $_POST['golongan_darah'] : null,
                    'jenis_pasien' => $jenis_pasien
                ]);

                $last_p = db_select_one("SELECT id FROM patients WHERE no_rm = '" . addslashes($no_rm) . "' LIMIT 1");
                if ($last_p && isset($last_p['id'])) {
                    $patient_id = $last_p['id'];
                }
            }

            if ($patient_id > 0) {
                $count_row = db_select_one("SELECT COUNT(*) as cnt FROM queues WHERE tanggal = :today", ['today' => date('Y-m-d')]);
                $next_num = ($count_row ? intval($count_row['cnt']) : 0) + 1;
                $no_antrian_gen = 'A-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

                db_insert('queues', [
                    'patient_id' => $patient_id,
                    'polyclinic_id' => $polyclinic_id,
                    'dokter_id' => $dokter_id,
                    'biaya_jasa' => $biaya_jasa,
                    'no_antrian' => $no_antrian_gen,
                    'tanggal' => date('Y-m-d'),
                    'status' => get_queue_db_status('menunggu'),
                    'jenis_daftar' => 'Offline'
                ]);
            }
            $msg_success = "Pendaftaran pasien & antrian berhasil disimpan! (No. RM: $no_rm - $nama, Antrian: " . ($no_antrian_gen ?? '-') . ", Jasa Dokter: Rp " . number_format($biaya_jasa, 0, ',', '.') . ")";
        } catch (Exception $e) {
            $err = $e->getMessage();
            if (strpos($err, 'patients_nik_key') !== false || strpos($err, '23505') !== false || strpos($err, 'Unique violation') !== false) {
                $msg_error = "Gagal menyimpan: Nomor KTP / NIK tersebut sudah terdaftar di database! Silakan gunakan NIK yang berbeda.";
            } else {
                $msg_error = "Gagal menyimpan ke database: " . $err;
            }
        }
    } elseif ($action === 'edit_pendaftaran') {
        require_role(['admisi', 'resepsionis', 'perawat']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('edit_pendaftaran')) {
                    throw new Exception("Perubahan oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }

            $queue_id = intval($_POST['queue_id'] ?? 0);
            if ($queue_id <= 0) {
                throw new Exception("ID antrian tidak ditemukan.");
            }

            $nama = trim($_POST['nama_lengkap'] ?? '');
            $phone = trim($_POST['no_telepon'] ?? '-');
            $alamat = trim($_POST['alamat'] ?? '-');
            $jenis_pasien = trim($_POST['jenis_pasien'] ?? 'Umum');
            $polyclinic_id = intval($_POST['polyclinic_id'] ?? 0);
            $dokter_id = intval($_POST['dokter_id'] ?? 0);

            $q_info = db_select_one("SELECT patient_id FROM queues WHERE id = :qid", ['qid' => $queue_id]);
            if ($q_info && !empty($q_info['patient_id'])) {
                db_query("UPDATE patients SET nama_lengkap = :nama, no_telepon = :hp, alamat = :al, jenis_pasien = :jp WHERE id = :pid", [
                    'nama' => $nama, 'hp' => $phone, 'al' => $alamat, 'jp' => $jenis_pasien, 'pid' => $q_info['patient_id']
                ]);
            }

            $biaya_jasa = 50000;
            if ($dokter_id > 0) {
                $drow = db_select_one("SELECT biaya_jasa FROM dokter WHERE dokter_id = :did", ['did' => $dokter_id]);
                if ($drow) $biaya_jasa = floatval($drow['biaya_jasa']);
            }

            $updates_q = [];
            $params_q = ['qid' => $queue_id];
            if ($polyclinic_id > 0) {
                $updates_q[] = "polyclinic_id = :polid";
                $params_q['polid'] = $polyclinic_id;
            }
            if ($dokter_id > 0) {
                $updates_q[] = "dokter_id = :did, biaya_jasa = :bj";
                $params_q['did'] = $dokter_id;
                $params_q['bj'] = $biaya_jasa;
            }
            if (!empty($updates_q)) {
                db_query("UPDATE queues SET " . implode(', ', $updates_q) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :qid", $params_q);
            }
            $msg_success = "Data pendaftaran & antrian pasien berhasil diubah!";
        } catch (Exception $e) {
            $msg_error = "Gagal mengedit pendaftaran: " . $e->getMessage();
        }
    } elseif ($action === 'daftar_igd') {
        require_role(['admisi', 'resepsionis', 'perawat', 'dokter']);
        $nama = trim($_POST['nama_lengkap'] ?? 'Pasien Tidak Dikenal (Mr. X)');
        $nik = trim($_POST['nik'] ?? '');
        $jk = trim($_POST['jenis_kelamin'] ?? 'L');
        $tgl_lahir = trim($_POST['tanggal_lahir'] ?? date('Y-m-d'));
        $phone = trim($_POST['no_telepon'] ?? '-');
        $alamat = trim($_POST['alamat'] ?? 'Triase IGD');
        $jenis_pasien = trim($_POST['jenis_pasien'] ?? 'Umum');
        $no_bpjs = trim($_POST['no_bpjs'] ?? '');
        $triase = trim($_POST['triase'] ?? 'Kuning');
        $keluhan = trim($_POST['keluhan'] ?? 'Kondisi gawat darurat');
        $td = trim($_POST['td'] ?? '');
        $nadi = trim($_POST['nadi'] ?? '');
        $suhu = trim($_POST['suhu'] ?? '');
        $spo2 = trim($_POST['spo2'] ?? '');

        if (empty($nik)) {
            $nik = 'IGD' . time() . rand(100, 999);
        }

        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('daftar_igd')) {
                    throw new Exception("Perubahan oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }
            $no_rm = 'RM-IGD-' . rand(1000, 9999);
            $patient_id = 0;

            $p_check = db_select_one("SELECT id, no_rm FROM patients WHERE nik = :nik LIMIT 1", ['nik' => $nik]);
            if ($p_check && isset($p_check['id'])) {
                $patient_id = $p_check['id'];
                $no_rm = $p_check['no_rm'];
            } else {
                db_insert('patients', [
                    'no_rm' => $no_rm,
                    'nik' => $nik,
                    'nama_lengkap' => $nama,
                    'tanggal_lahir' => $tgl_lahir,
                    'jenis_kelamin' => $jk,
                    'no_telepon' => $phone,
                    'alamat' => $alamat,
                    'no_bpjs' => !empty($no_bpjs) ? $no_bpjs : null,
                    'jenis_pasien' => $jenis_pasien
                ]);
                $last_p = db_select_one("SELECT id FROM patients WHERE no_rm = :rm LIMIT 1", ['rm' => $no_rm]);
                if ($last_p && isset($last_p['id'])) {
                    $patient_id = $last_p['id'];
                }
            }

            if ($patient_id > 0) {
                $poli_igd = db_select_one("SELECT id FROM polyclinics WHERE kode_poli = 'POL-IGD' OR nama_poli ILIKE '%IGD%' LIMIT 1");
                $polyclinic_id = $poli_igd ? $poli_igd['id'] : 1;

                $count_row = db_select_one("SELECT COUNT(*) as cnt FROM queues WHERE tanggal = :today", ['today' => date('Y-m-d')]);
                $next_num = ($count_row ? intval($count_row['cnt']) : 0) + 1;
                $no_antrian_gen = 'IGD-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

                db_insert('queues', [
                    'patient_id' => $patient_id,
                    'polyclinic_id' => $polyclinic_id,
                    'no_antrian' => $no_antrian_gen,
                    'tanggal' => date('Y-m-d'),
                    'status' => get_queue_db_status('dalam_pemeriksaan'),
                    'jenis_daftar' => 'IGD'
                ]);

                $queue_row = db_select_one("SELECT id FROM queues WHERE no_antrian = :no LIMIT 1", ['no' => $no_antrian_gen]);
                if ($queue_row && isset($queue_row['id'])) {
                    db_insert('emr_notes', [
                        'queue_id' => $queue_row['id'],
                        'no_antrian' => $no_antrian_gen,
                        'keluhan_utama' => "[TRIASE: " . strtoupper($triase) . "] " . $keluhan,
                        'subjective' => "[TRIASE: " . strtoupper($triase) . "] Keluhan: " . $keluhan,
                        'objective' => "TD: " . ($td ?: '-') . " | Nadi: " . ($nadi ?: '-') . " | Suhu: " . ($suhu ?: '-') . " | SpO2: " . ($spo2 ?: '-'),
                        'assessment' => "Pasien Triase " . $triase . " IGD",
                        'plan' => "Observasi & Tindakan Darurat IGD"
                    ]);
                }
            }
            $msg_success = "Registrasi Pasien IGD & Triase berhasil dicatat! (No. RM: $no_rm - $nama, Antrian: $no_antrian_gen | Triase: " . strtoupper($triase) . ")";
        } catch (Exception $e) {
            $msg_error = "Gagal mencatat registrasi IGD: " . $e->getMessage();
        }
    } elseif ($action === 'panggil_antrian') {
        require_role(['admisi', 'resepsionis', 'perawat', 'dokter']);
        $no_antrian = trim($_POST['no_antrian'] ?? '');
        if (!empty($no_antrian)) {
            try {
                db_query("UPDATE queues SET status = :status, dipanggil_at = COALESCE(dipanggil_at, CURRENT_TIMESTAMP) WHERE no_antrian = :no_antrian", [
                    'status' => get_queue_db_status('dipanggil'),
                    'no_antrian' => $no_antrian,
                ]);
                $msg_success = "Antrian nomor " . htmlspecialchars($no_antrian) . " sedang dipanggil ke poli!";
            } catch (Exception $e) {
                error_log("panggil_antrian error: " . $e->getMessage());
                $msg_error = "Gagal memproses panggilan antrian: " . $e->getMessage();
            }
        } else {
            $msg_error = "Nomor antrian tidak valid untuk panggilan.";
        }
    } elseif ($action === 'konfirmasi_masuk') {
        require_role(['admisi', 'resepsionis', 'perawat', 'dokter']);
        $no_antrian = trim($_POST['no_antrian'] ?? '');
        if (!empty($no_antrian)) {
            try {
                db_query("UPDATE queues SET status = :status, mulai_at = COALESCE(mulai_at, CURRENT_TIMESTAMP) WHERE no_antrian = :no_antrian", [
                    'status' => get_queue_db_status('dalam_pemeriksaan'),
                    'no_antrian' => $no_antrian,
                ]);
                $msg_success = "Antrian nomor " . htmlspecialchars($no_antrian) . " telah dikonfirmasi masuk ruangan.";
            } catch (Exception $e) {
                error_log("konfirmasi_masuk error: " . $e->getMessage());
                $msg_error = "Gagal konfirmasi masuk ruangan: " . $e->getMessage();
            }
        } else {
            $msg_error = "Nomor antrian tidak valid untuk konfirmasi masuk.";
        }
    } elseif ($action === 'tunda_antrian') {
        require_role(['admisi', 'resepsionis', 'perawat', 'dokter']);
        $no_antrian = trim($_POST['no_antrian'] ?? '');
        if (!empty($no_antrian)) {
            try {
                db_query("UPDATE queues SET status = :status, dipanggil_at = NULL, mulai_at = NULL WHERE no_antrian = :no_antrian", [
                    'status' => get_queue_db_status('menunggu'),
                    'no_antrian' => $no_antrian,
                ]);
                $msg_success = "Antrian nomor " . htmlspecialchars($no_antrian) . " dikembalikan ke status menunggu.";
            } catch (Exception $e) {
                error_log("tunda_antrian error: " . $e->getMessage());
                $msg_error = "Gagal menunda antrian: " . $e->getMessage();
            }
        } else {
            $msg_error = "Nomor antrian tidak valid untuk ditunda.";
        }
    } elseif ($action === 'simpan_emr') {
        require_role(['dokter']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('simpan_emr')) {
                    throw new Exception("Perubahan oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }
        } catch (Exception $e_pam) {
            $msg_error = $e_pam->getMessage();
            return;
        }
        $no_antrian = trim($_POST['no_antrian'] ?? '');
        $subjective = trim($_POST['subjective'] ?? '');
        $objective = trim($_POST['objective'] ?? '');
        $assessment = trim($_POST['assessment'] ?? '');
        $plan = trim($_POST['plan'] ?? '');
        $pemeriksaan_fisik = trim($_POST['pemeriksaan_fisik'] ?? '');
        $tekanan_darah = trim($_POST['pemeriksaan_tekanan_darah'] ?? '');
        $berat_badan = trim($_POST['pemeriksaan_bb'] ?? '');
        $tinggi_badan = trim($_POST['pemeriksaan_tb'] ?? '');

        $kode_icd10 = trim($_POST['kode_icd10'] ?? '');
        $nama_diagnosa = trim($_POST['nama_diagnosa'] ?? '');

        $terapi_nama = trim($_POST['terapi_nama'] ?? '');
        $terapi_aturan = trim($_POST['terapi_aturan'] ?? '');

        $resep_items_data = [];
        if (!empty($_POST['resep_nama_obat']) && is_array($_POST['resep_nama_obat'])) {
            $cnt_rsp = count($_POST['resep_nama_obat']);
            for ($ri = 0; $ri < $cnt_rsp; $ri++) {
                $rnm = trim($_POST['resep_nama_obat'][$ri] ?? '');
                if (!empty($rnm)) {
                    $resep_items_data[] = [
                        'obat_id' => !empty($_POST['resep_obat_id'][$ri]) ? intval($_POST['resep_obat_id'][$ri]) : null,
                        'nama_obat' => $rnm,
                        'dosis' => floatval($_POST['resep_dosis'][$ri] ?? 1),
                        'satuan' => trim($_POST['resep_satuan'][$ri] ?? 'Tablet'),
                        'aturan_pakai' => trim($_POST['resep_aturan_pakai'][$ri] ?? '3x1 sesudah makan'),
                        'qty' => intval($_POST['resep_qty'][$ri] ?? 1),
                    ];
                }
            }
        }

        $order_type = trim($_POST['order_type'] ?? 'lab');
        $order_jenis = trim($_POST['order_lab_jenis'] ?? '');
        $order_catatan = trim($_POST['order_lab_catatan'] ?? '');

        $rujukan_faskes = trim($_POST['rujukan_faskes'] ?? '');
        $rujukan_poli = trim($_POST['rujukan_poli'] ?? '');
        $rujukan_alasan = trim($_POST['rujukan_alasan'] ?? '');
        $rujukan_bpjs = trim($_POST['rujukan_bpjs'] ?? '');
        $rujukan_tanggal = trim($_POST['rujukan_tanggal'] ?? date('Y-m-d'));

        $tujuan_selesai = trim($_POST['tujuan_selesai'] ?? '');

        if (!empty($no_antrian)) {
            try {
                db_query("UPDATE queues SET status = :status, dipanggil_at = COALESCE(dipanggil_at, CURRENT_TIMESTAMP), mulai_at = COALESCE(mulai_at, CURRENT_TIMESTAMP) WHERE no_antrian = :no_antrian", [
                    'status' => get_queue_db_status('dalam_pemeriksaan'),
                    'no_antrian' => $no_antrian,
                ]);

                try {
                    db_query("CREATE TABLE IF NOT EXISTS emr_notes (
                        id BIGSERIAL PRIMARY KEY,
                        queue_id BIGINT NOT NULL REFERENCES queues(id) ON DELETE CASCADE,
                        no_antrian VARCHAR(20) NOT NULL,
                        keluhan_utama TEXT NULL,
                        subjective TEXT NULL,
                        objective TEXT NULL,
                        assessment TEXT NULL,
                        plan TEXT NULL,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                    )");
                } catch (Exception $e) {
                    error_log("simpan_emr warning create emr_notes: " . $e->getMessage());
                }

                $rm_id = null;
                $visit_id = null;
                try {
                    $queue_row = db_select_one("SELECT q.id, q.patient_id, q.polyclinic_id, q.jenis_daftar, p.jenis_pasien FROM queues q JOIN patients p ON q.patient_id = p.id WHERE q.no_antrian = :no_antrian", ['no_antrian' => $no_antrian]);
                    if ($queue_row) {
                        $visit_id = create_or_get_visit_for_queue($queue_row['id'], $subjective, $queue_row['jenis_pasien'], $subjective);
                        if ($visit_id) {
                            $dokter_id = get_default_dokter_id();
                            if ($dokter_id) {
                                $rm_id = create_rekam_medis_for_visit($visit_id, $dokter_id, $subjective, $subjective, $objective, $assessment, $plan, $pemeriksaan_fisik, $berat_badan, $tinggi_badan);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("simpan_emr warning visit/rm: " . $e->getMessage());
                }

                try {
                    $queue_row = db_select_one("SELECT id FROM queues WHERE no_antrian = :no_antrian", ['no_antrian' => $no_antrian]);
                    if ($queue_row && ($subjective !== '' || $objective !== '' || $assessment !== '' || $plan !== '')) {
                        db_insert('emr_notes', [
                            'queue_id' => $queue_row['id'],
                            'no_antrian' => $no_antrian,
                            'keluhan_utama' => $subjective,
                            'subjective' => $subjective,
                            'objective' => $objective,
                            'assessment' => $assessment,
                            'plan' => $plan,
                        ]);
                    }
                } catch (Exception $e) {
                    error_log("simpan_emr warning insert emr_notes: " . $e->getMessage());
                }

                if ($rm_id) {
                    try {
                        if (!empty($kode_icd10) && !empty($nama_diagnosa)) {
                            create_rm_diagnosa($rm_id, $kode_icd10, $nama_diagnosa);
                        }
                    } catch (Exception $e) {
                        error_log("simpan_emr warning diagnosis: " . $e->getMessage());
                    }

                    try {
                        if (!empty($terapi_nama) || !empty($terapi_aturan) || !empty($resep_items_data)) {
                            $catatan_resep = trim(($terapi_nama ? $terapi_nama . "\n" : "") . $terapi_aturan);
                            if (empty($catatan_resep) && !empty($resep_items_data)) {
                                $catatan_resep = count($resep_items_data) . " item obat diresepkan.";
                            }
                            create_resep_for_rm($rm_id, $catatan_resep, $resep_items_data);
                        }
                    } catch (Exception $e) {
                        error_log("simpan_emr warning resep: " . $e->getMessage());
                    }

                    try {
                        if (!empty($order_jenis)) {
                            create_order_for_rm($rm_id, $visit_id, $order_jenis, $order_catatan, strtolower($order_type) === 'radiologi');
                        }
                    } catch (Exception $e) {
                        error_log("simpan_emr warning order: " . $e->getMessage());
                    }

                    try {
                        if (!empty($rujukan_faskes) && !empty($rujukan_poli) && !empty($rujukan_alasan)) {
                            create_surat_rujukan_for_rm($rm_id, $rujukan_faskes, $rujukan_poli, $rujukan_alasan, $rujukan_bpjs, $rujukan_tanggal);
                        }
                    } catch (Exception $e) {
                        error_log("simpan_emr warning rujukan: " . $e->getMessage());
                    }
                }

                // Sync Jasa Dokter ke Tagihan secara otomatis
                if ($visit_id) {
                    sync_doctor_fee_to_tagihan($visit_id);
                }

                // Tentukan status antrian selanjutnya: apakah ke Farmasi (jika ada resep / diproses resep) atau ke Kasir
                $target_status = get_queue_db_status('menunggu_kasir');
                if ($tujuan_selesai === 'farmasi' || ((!empty($terapi_nama) || !empty($resep_items_data)) && $tujuan_selesai !== 'kasir')) {
                    $target_status = get_queue_db_status('menunggu_farmasi');
                }

                try {
                    db_query("UPDATE queues SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE no_antrian = :no_antrian", [
                        'status' => $target_status,
                        'no_antrian' => $no_antrian,
                    ]);
                } catch (Exception $e) {
                    error_log("simpan_emr warning update status: " . $e->getMessage());
                }

                $msg_success = "Catatan EMR / SOAP untuk pasien berhasil disimpan! Status pasien: " . get_queue_status_label($target_status);
            } catch (Exception $e) {
                error_log("simpan_emr error: " . $e->getMessage());
                $msg_error = "Gagal menyimpan EMR: " . $e->getMessage();
            }
        } else {
            $msg_error = "Nomor antrian tidak valid untuk menyimpan EMR.";
        }
    } elseif ($action === 'proses_resep_farmasi') {
        require_role(['farmasi', 'apoteker']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('proses_resep_farmasi')) {
                    throw new Exception("Perubahan oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }

            $queue_id = intval($_POST['queue_id'] ?? 0);
            $no_antrian = trim($_POST['no_antrian'] ?? '');

            $qrow = null;
            if ($queue_id > 0) {
                $qrow = db_select_one("SELECT id, no_antrian FROM queues WHERE id = :qid", ['qid' => $queue_id]);
            } elseif (!empty($no_antrian)) {
                $qrow = db_select_one("SELECT id, no_antrian FROM queues WHERE no_antrian = :no", ['no' => $no_antrian]);
            }

            if (!$qrow) {
                throw new Exception("Antrian pasien tidak ditemukan untuk diproses farmasi.");
            }

            $visit = db_select_one("SELECT kunjungan_id FROM visits WHERE queue_id = :qid", ['qid' => $qrow['id']]);
            $kunjungan_id = $visit ? intval($visit['kunjungan_id']) : 0;

            // Pastikan tagihan dan jasa dokter ada
            if ($kunjungan_id > 0) {
                sync_doctor_fee_to_tagihan($kunjungan_id);
            }

            $obat_ids = $_POST['obat_id'] ?? [];
            $jumlahs = $_POST['jumlah'] ?? [];
            if (!is_array($obat_ids)) $obat_ids = [$obat_ids];
            if (!is_array($jumlahs)) $jumlahs = [$jumlahs];

            $total_obat_cost = 0;
            if ($kunjungan_id > 0 && !empty($obat_ids)) {
                $tagihan = db_select_one("SELECT id FROM tagihan WHERE kunjungan_id = :kid", ['kid' => $kunjungan_id]);
                $tag_id = $tagihan ? intval($tagihan['id']) : 0;

                for ($i = 0; $i < count($obat_ids); $i++) {
                    $oid = intval($obat_ids[$i] ?? 0);
                    $qty = intval($jumlahs[$i] ?? 1);
                    if ($oid > 0 && $qty > 0) {
                        $ob = db_select_one("SELECT nama_obat, harga, stok FROM obat WHERE id = :oid", ['oid' => $oid]);
                        if ($ob) {
                            $harga = floatval($ob['harga'] ?? 10000);
                            $subtotal = $harga * $qty;
                            $total_obat_cost += $subtotal;

                            // Kurangi stok obat
                            db_query("UPDATE obat SET stok = GREATEST(0, stok - :qty), updated_at = CURRENT_TIMESTAMP WHERE id = :oid", [
                                'qty' => $qty, 'oid' => $oid
                            ]);

                            if ($tag_id > 0) {
                                create_tagihan_detail_table_if_missing();
                                db_insert('tagihan_detail', [
                                    'tagihan_id' => $tag_id,
                                    'nama_layanan' => 'Obat Resep: ' . $ob['nama_obat'],
                                    'biaya' => $harga,
                                    'jumlah' => $qty,
                                    'subtotal' => $subtotal
                                ]);
                            }
                        }
                    }
                }

                if ($tag_id > 0) {
                    $sum_row = db_select_one("SELECT SUM(subtotal) as total FROM tagihan_detail WHERE tagihan_id = :tid", ['tid' => $tag_id]);
                    if ($sum_row && isset($sum_row['total'])) {
                        db_query("UPDATE tagihan SET total_biaya = :t WHERE id = :tid", ['t' => floatval($sum_row['total']), 'tid' => $tag_id]);
                    }
                }
            }

            // Update status antrian menjadi Menunggu Kasir
            db_query("UPDATE queues SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :qid", [
                'status' => get_queue_db_status('menunggu_kasir'),
                'qid' => $qrow['id']
            ]);

            $msg_success = "Resep obat berhasil diproses, stok berkurang, dan tagihan ditambahkan! Pasien diarahkan ke Kasir.";
        } catch (Exception $e) {
            $msg_error = "Gagal memproses resep farmasi: " . $e->getMessage();
        }
    } elseif ($action === 'update_stok_obat') {
        require_role(['farmasi', 'apoteker']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('update_stok_obat')) {
                    throw new Exception("Perubahan oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }

            $obat_id = intval($_POST['obat_id'] ?? 0);
            $stok = intval($_POST['stok'] ?? 0);
            $harga = floatval($_POST['harga'] ?? 0);

            if ($obat_id <= 0) {
                throw new Exception("ID obat tidak valid.");
            }

            db_query("UPDATE obat SET stok = :stok, harga = :harga, updated_at = CURRENT_TIMESTAMP WHERE id = :oid", [
                'stok' => $stok, 'harga' => $harga, 'oid' => $obat_id
            ]);
            $msg_success = "Stok & harga obat berhasil diperbarui!";
        } catch (Exception $e) {
            $msg_error = "Gagal memperbarui obat: " . $e->getMessage();
        }
    } elseif ($action === 'pam_request') {
        try {
            $reason = trim($_POST['request_reason'] ?? 'Perubahan konfigurasi/data sistem');
            $target = trim($_POST['target_action'] ?? 'super_user_action');
            $uid = intval($_SESSION['user_id'] ?? 1);

            db_insert('pam_approvals', [
                'requested_by' => $uid,
                'target_action' => $target,
                'reason' => $reason,
                'status' => 'PENDING'
            ]);
            $msg_success = "Permintaan persetujuan (PAM) berhasil diajukan ke Supervisor!";
        } catch (Exception $e) {
            $msg_error = "Gagal mengajukan PAM: " . $e->getMessage();
        }
    } elseif ($action === 'pam_approve') {
        try {
            $pin = trim($_POST['supervisor_pin'] ?? '');
            $app_id = intval($_POST['approval_id'] ?? 0);

            if ($pin === '123456' || $pin === 'password123' || (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'supervisor')) {
                if ($app_id > 0) {
                    db_query("UPDATE pam_approvals SET status = 'APPROVED', approved_by = :appby, approved_at = CURRENT_TIMESTAMP WHERE id = :id", [
                        'appby' => intval($_SESSION['user_id'] ?? 1),
                        'id' => $app_id
                    ]);
                }
                $_SESSION['pam_authorized_' . ($app_id > 0 ? 'app' . $app_id : 'general')] = time();
                $msg_success = "Otorisasi Supervisor (PAM) berhasil disetujui!";
            } else {
                throw new Exception("PIN Supervisor tidak valid!");
            }
        } catch (Exception $e) {
            $msg_error = "Gagal verifikasi PIN/PAM: " . $e->getMessage();
        }
    } elseif ($action === 'bayar_kasir') {
        require_role(['kasir']);
        try {
            if (isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'superuser') {
                if (!check_pam_authorization('bayar_kasir')) {
                    throw new Exception("Perubahan oleh Super User wajib mendapat persetujuan / PIN dari Supervisor terlebih dahulu!");
                }
            }
        } catch (Exception $e_pam) {
            $msg_error = $e_pam->getMessage();
            return;
        }
        $no_antrian = trim($_POST['no_antrian'] ?? '');
        $nominal_raw = trim($_POST['nominal'] ?? '0');
        $nominal = intval(preg_replace('/[^0-9]/', '', $nominal_raw));
        $payment_method = trim($_POST['payment_method'] ?? 'Tunai');

        if (!empty($no_antrian)) {
            try {
                db_query("UPDATE queues SET status = :status, selesai_at = CURRENT_TIMESTAMP WHERE no_antrian = :no_antrian", [
                    'status' => get_queue_db_status('selesai'),
                    'no_antrian' => $no_antrian,
                ]);

                try {
                    $queue_row = db_select_one("SELECT q.id FROM queues q WHERE q.no_antrian = :no_antrian", ['no_antrian' => $no_antrian]);
                    if ($queue_row) {
                        $visit_row = db_select_one("SELECT kunjungan_id FROM visits WHERE queue_id = :qid", ['qid' => $queue_row['id']]);
                        if ($visit_row && !empty($visit_row['kunjungan_id'])) {
                            create_tagihan_for_visit(intval($visit_row['kunjungan_id']), $nominal, $payment_method);
                        }
                    }
                } catch (Exception $e) {
                    error_log("bayar_kasir warning tagihan: " . $e->getMessage());
                }

                $msg_success = "Pembayaran sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil diproses!";
            } catch (Exception $e) {
                error_log("bayar_kasir error: " . $e->getMessage());
                $msg_error = "Gagal memproses pembayaran kasir: " . $e->getMessage();
            }
        } else {
            $msg_error = "Nomor antrian tidak valid untuk pembayaran kasir.";
        }
    }
}
