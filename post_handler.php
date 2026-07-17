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
        $queue = db_select_one("SELECT q.id, q.patient_id, q.polyclinic_id, q.no_antrian, q.status, q.jenis_daftar, p.no_rm, p.jenis_pasien FROM queues q JOIN patients p ON q.patient_id = p.id WHERE q.id = :qid", ['qid' => $queue_id]);
        if (!$queue) {
            return null;
        }

        $existing = db_select_one("SELECT kunjungan_id FROM visits WHERE queue_id = :qid", ['qid' => $queue_id]);
        if ($existing) {
            return intval($existing['kunjungan_id']);
        }

        $dokter_id = get_default_dokter_id();
        if (!$dokter_id) {
            return null;
        }

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

function create_resep_for_rm($rm_id, $catatan) {
    if (empty($rm_id) || empty(trim($catatan))) {
        return null;
    }

    try {
        $existing = db_select_one("SELECT id FROM resep WHERE rm_id = :rm_id", ['rm_id' => $rm_id]);
        if ($existing) {
            db_query("UPDATE resep SET catatan = :catatan, updated_at = CURRENT_TIMESTAMP WHERE id = :id", [
                'catatan' => $catatan,
                'id' => $existing['id'],
            ]);
            return intval($existing['id']);
        }

        db_insert('resep', [
            'rm_id' => $rm_id,
            'status' => 'DRAFT',
            'catatan' => trim($catatan),
        ]);
        $row = db_select_one("SELECT id FROM resep WHERE rm_id = :rm_id", ['rm_id' => $rm_id]);
        return $row ? intval($row['id']) : null;
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

function create_tagihan_for_visit($kunjungan_id, $nominal, $metode_pembayaran) {
    try {
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
        } else {
            db_query("UPDATE tagihan SET total_biaya = :total, status_pembayaran = 'LUNAS', metode_pembayaran = :metode, dibayar_at = CURRENT_TIMESTAMP WHERE id = :tid", [
                'total' => $nominal,
                'metode' => $metode_pembayaran,
                'tid' => $existing['id'],
            ]);
        }

        if ($existing) {
            create_tagihan_detail_table_if_missing();
            db_insert('tagihan_detail', [
                'tagihan_id' => $existing['id'],
                'nama_layanan' => 'Pembayaran Kasir',
                'biaya' => $nominal,
                'jumlah' => 1,
                'subtotal' => $nominal,
            ]);
        }
    } catch (Exception $e) {
        error_log("post_handler warning in create_tagihan_for_visit: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register_pasien') {
        try {
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
                    'no_antrian' => $no_antrian_gen,
                    'tanggal' => date('Y-m-d'),
                    'status' => get_queue_db_status('menunggu'),
                    'jenis_daftar' => 'Offline'
                ]);
            }
            $msg_success = "Pendaftaran pasien & antrian berhasil disimpan! (No. RM: $no_rm - $nama, Antrian: " . ($no_antrian_gen ?? '-') . ")";
        } catch (Exception $e) {
            $err = $e->getMessage();
            if (strpos($err, 'patients_nik_key') !== false || strpos($err, '23505') !== false || strpos($err, 'Unique violation') !== false) {
                $msg_error = "Gagal menyimpan: Nomor KTP / NIK tersebut sudah terdaftar di database! Silakan gunakan NIK yang berbeda.";
            } else {
                $msg_error = "Gagal menyimpan ke database: " . $err;
            }
        }
    } elseif ($action === 'panggil_antrian') {
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

        $order_type = trim($_POST['order_type'] ?? 'lab');
        $order_jenis = trim($_POST['order_lab_jenis'] ?? '');
        $order_catatan = trim($_POST['order_lab_catatan'] ?? '');

        $rujukan_faskes = trim($_POST['rujukan_faskes'] ?? '');
        $rujukan_poli = trim($_POST['rujukan_poli'] ?? '');
        $rujukan_alasan = trim($_POST['rujukan_alasan'] ?? '');
        $rujukan_bpjs = trim($_POST['rujukan_bpjs'] ?? '');
        $rujukan_tanggal = trim($_POST['rujukan_tanggal'] ?? date('Y-m-d'));

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
                        if (!empty($terapi_nama) && !empty($terapi_aturan)) {
                            create_resep_for_rm($rm_id, $terapi_nama . '\n' . $terapi_aturan);
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

                $msg_success = "Catatan EMR / SOAP untuk pasien berhasil disimpan!";
            } catch (Exception $e) {
                error_log("simpan_emr error: " . $e->getMessage());
                $msg_error = "Gagal menyimpan EMR: " . $e->getMessage();
            }
        } else {
            $msg_error = "Nomor antrian tidak valid untuk menyimpan EMR.";
        }
    } elseif ($action === 'bayar_kasir') {
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
