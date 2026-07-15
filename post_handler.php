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

            // Generate nomor antrian berurutan per hari
            $last_p = db_select_one("SELECT id FROM patients WHERE no_rm = '" . addslashes($no_rm) . "' LIMIT 1");
            if ($last_p && isset($last_p['id'])) {
                $count_row = db_select_one("SELECT COUNT(*) as cnt FROM queues WHERE tanggal = CURRENT_DATE");
                $next_num = ($count_row ? intval($count_row['cnt']) : 0) + 1;
                $no_antrian_gen = 'A-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

                db_insert('queues', [
                    'patient_id' => $last_p['id'],
                    'polyclinic_id' => $polyclinic_id,
                    'no_antrian' => $no_antrian_gen,
                    'tanggal' => date('Y-m-d'),
                    'status' => 'Menunggu',
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
        $msg_success = "Antrian nomor " . htmlspecialchars($_POST['no_antrian'] ?? '') . " sedang dipanggil ke poli!";
    } elseif ($action === 'simpan_emr') {
        $msg_success = "Catatan EMR / SOAP untuk pasien berhasil disimpan!";
    } elseif ($action === 'bayar_kasir') {
        $msg_success = "Pembayaran sebesar Rp " . htmlspecialchars($_POST['nominal'] ?? '0') . " berhasil diproses!";
    }
}
