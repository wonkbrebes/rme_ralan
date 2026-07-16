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
                    'status' => 'menunggu',
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
            db_query("UPDATE queues SET status = :status WHERE no_antrian = :no_antrian", [
                'status' => 'dipanggil',
                'no_antrian' => $no_antrian,
            ]);
        }
        $msg_success = "Antrian nomor " . htmlspecialchars($no_antrian) . " sedang dipanggil ke poli!";
    } elseif ($action === 'simpan_emr') {
        $no_antrian = trim($_POST['no_antrian'] ?? '');
        if (!empty($no_antrian)) {
            db_query("UPDATE queues SET status = :status WHERE no_antrian = :no_antrian", [
                'status' => 'dalam_pemeriksaan',
                'no_antrian' => $no_antrian,
            ]);
        }
        $msg_success = "Catatan EMR / SOAP untuk pasien berhasil disimpan!";
    } elseif ($action === 'bayar_kasir') {
        $no_antrian = trim($_POST['no_antrian'] ?? '');
        if (!empty($no_antrian)) {
            db_query("UPDATE queues SET status = :status WHERE no_antrian = :no_antrian", [
                'status' => 'selesai',
                'no_antrian' => $no_antrian,
            ]);
        }
        $msg_success = "Pembayaran sebesar Rp " . htmlspecialchars($_POST['nominal'] ?? '0') . " berhasil diproses!";
    }
}
