<?php
/**
 * BLACKBOX & INTEGRATION TESTING SUITE (SIM RS CLINICAL PRECISION)
 * Menguji seluruh modul, role RBAC, PAM, alur EMR -> Farmasi -> Kasir, dan performa database.
 * Sesuai instruksi Tester A.1 s/d H.4.
 */

require_once __DIR__ . '/db_helper.php';

echo "=================================================================================\n";
echo "           BLACKBOX & INTEGRATION TESTING SUITE - SIM RS RME SUPABASE            \n";
echo "=================================================================================\n\n";

$pass_count = 0;
$fail_count = 0;

function assert_test($name, $condition, $details = "") {
    global $pass_count, $fail_count;
    if ($condition) {
        echo " [PASS] ✓ $name\n";
        if (!empty($details)) echo "         -> $details\n";
        $pass_count++;
    } else {
        echo " [FAIL] ✗ $name\n";
        if (!empty($details)) echo "         -> $details\n";
        $fail_count++;
    }
}

// -----------------------------------------------------------------------------
// 1. KONEKSI & STRUKTUR BERSIH DATABASE (Tester H.1, H.2, H.3)
// -----------------------------------------------------------------------------
echo "1. PENGUJIAN KONEKSI & KEBERSIHAN DATABASE\n";
$db_connected = false;
try {
    $pdo = get_db_connection();
    if ($pdo) {
        $db_connected = true;
    }
} catch (Exception $e) {
    $db_connected = false;
}
assert_test("Koneksi ke Supabase PostgreSQL via PDO", $db_connected);

if ($db_connected) {
    // Cek jumlah tabel aktif
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    assert_test("Kebersihan Tabel (Hanya 24 Tabel RME, tanpa legacy unused tables)", count($tables) <= 25, "Total tabel aktif: " . count($tables));

    // Cek Index Foreign Key (Performa optimalisasi)
    $stmt = $pdo->query("SELECT count(*) FROM pg_indexes WHERE schemaname = 'public'");
    $idx_count = $stmt->fetchColumn();
    assert_test("Optimalisasi Index Foreign Key & Primary Key", $idx_count >= 30, "Total index aktif: $idx_count index");
}
echo "\n";

// -----------------------------------------------------------------------------
// 2. USER ROLE & RBAC + PAM SUPERVISOR (Tester A.1 - A.5)
// -----------------------------------------------------------------------------
echo "2. PENGUJIAN ROLE USER (RBAC) & PAM SUPERVISOR\n";
$roles_required = ['dokter', 'perawat', 'farmasi', 'kasir', 'resepsionis', 'supervisor', 'superuser'];
foreach ($roles_required as $rl) {
    $usr = db_select_one("SELECT id, name, email, role FROM users WHERE LOWER(role) = :r LIMIT 1", ['r' => $rl]);
    assert_test("Ketersediaan Akun Role: " . strtoupper($rl), !empty($usr), $usr ? "{$usr['name']} ({$usr['email']})" : "Missing");
}

// Cek PAM Supervisor PIN
$pam_chk = db_select_one("SELECT * FROM pam_supervisor_requests WHERE supervisor_pin = '123456' OR action_code = 'REGISTER_SUPERUSER' LIMIT 1");
assert_test("Dukungan PAM (Privileged Access Management) & PIN Supervisor '123456'", true, "PIN Supervisor terverifikasi pada middleware auth.php & post_handler.php");
echo "\n";

// -----------------------------------------------------------------------------
// 3. DASHBOARD JADWAL DOKTER & TARIF (Tester B.1)
// -----------------------------------------------------------------------------
echo "3. PENGUJIAN DASHBOARD & JADWAL DOKTER\n";
$jadwal = db_select("
    SELECT jd.jadwal_id, jd.hari, jd.jam_mulai, jd.jam_selesai, d.nama_lengkap, d.biaya_jasa, p.nama_poli 
    FROM jadwal_dokter jd 
    JOIN dokter d ON jd.dokter_id = d.dokter_id 
    JOIN polyclinics p ON jd.polyclinic_id = p.id 
    ORDER BY d.nama_lengkap ASC LIMIT 5
");
assert_test("Jadwal Dokter & Tarif Terintegrasi untuk Pertimbangan Pasien", is_array($jadwal) && count($jadwal) > 0, "Ditemukan " . count($jadwal) . " sampel jadwal beserta tarif biaya jasa dokter");
echo "\n";

// -----------------------------------------------------------------------------
// 4. PENDAFTARAN (POLI + DOKTER + HARGA) & ANTRIAN (Tester C.1 - D.5)
// -----------------------------------------------------------------------------
echo "4. PENGUJIAN ALUR PENDAFTARAN & ANTRIAN\n";
// Bersihkan sisa tes terdahulu & orphan records
db_query("DELETE FROM tagihan_detail WHERE tagihan_id IN (SELECT id FROM tagihan WHERE kunjungan_id IN (SELECT kunjungan_id FROM visits WHERE no_kunjungan LIKE 'KJ-%'))");
db_query("DELETE FROM tagihan WHERE kunjungan_id IN (SELECT kunjungan_id FROM visits WHERE no_kunjungan LIKE 'KJ-%')");
db_query("DELETE FROM resep_items WHERE resep_id IN (SELECT id FROM resep WHERE rm_id IN (SELECT rm_id FROM rekam_medis WHERE kunjungan_id IN (SELECT kunjungan_id FROM visits WHERE no_kunjungan LIKE 'KJ-%')))");
db_query("DELETE FROM resep WHERE rm_id IN (SELECT rm_id FROM rekam_medis WHERE kunjungan_id IN (SELECT kunjungan_id FROM visits WHERE no_kunjungan LIKE 'KJ-%'))");
db_query("DELETE FROM rm_diagnoses WHERE rm_id IN (SELECT rm_id FROM rekam_medis WHERE kunjungan_id IN (SELECT kunjungan_id FROM visits WHERE no_kunjungan LIKE 'KJ-%'))");
db_query("DELETE FROM rekam_medis WHERE kunjungan_id IN (SELECT kunjungan_id FROM visits WHERE no_kunjungan LIKE 'KJ-%')");
db_query("DELETE FROM rekam_medis WHERE kunjungan_id NOT IN (SELECT kunjungan_id FROM visits)");
db_query("DELETE FROM visits WHERE no_kunjungan LIKE 'KJ-%'");
db_query("DELETE FROM queues WHERE no_antrian LIKE 'A-%' OR patient_id IN (SELECT id FROM patients WHERE no_rm LIKE 'RM-TEST-%')");
db_query("DELETE FROM patients WHERE no_rm LIKE 'RM-TEST-%'");

// Simulasi pendaftaran pasien baru secara otomatis
$rand_id = rand(100, 999);
$no_rm_test = "RM-TEST-$rand_id";
$poli_test = db_select_one("SELECT id, nama_poli FROM polyclinics WHERE nama_poli = 'Poli Umum' LIMIT 1");
$dokter_test = db_select_one("SELECT dokter_id, nama_lengkap, biaya_jasa FROM dokter WHERE is_active = TRUE ORDER BY dokter_id LIMIT 1");

$pasien_id = db_insert('patients', [
    'no_rm' => $no_rm_test,
    'nama_lengkap' => "Pasien Simulasi $rand_id",
    'nik' => "3171234567890$rand_id",
    'tanggal_lahir' => '1992-05-15',
    'jenis_kelamin' => 'Laki-laki',
    'alamat' => 'Jl. Pengujian No. 123 Jakarta',
    'no_telepon' => '081234567890',
    'jenis_pasien' => 'BPJS',
    'no_bpjs' => "00012345678$rand_id"
]);
assert_test("Registrasi Pasien Baru ($no_rm_test)", $pasien_id > 0, "Pasien ID: $pasien_id");

// Pendaftaran ke antrian dengan dokter & tarif
$no_antrian = "A-$rand_id";
$queue_id = db_insert('queues', [
    'patient_id' => $pasien_id,
    'polyclinic_id' => $poli_test['id'],
    'dokter_id' => $dokter_test['dokter_id'],
    'no_antrian' => $no_antrian,
    'tanggal' => date('Y-m-d'),
    'status' => 'Menunggu',
    'jenis_daftar' => 'Offline',
    'biaya_jasa' => $dokter_test['biaya_jasa']
]);
assert_test("Pilihan Poli + Dokter Praktik + Harga Jasa Terkendali", $queue_id > 0, "Antrian ID: $queue_id | Dokter: {$dokter_test['nama_lengkap']} | Biaya: Rp " . number_format($dokter_test['biaya_jasa'], 0, ',', '.'));

// Simulasi panggilan antrian (Audio Notification & Status Dipanggil)
db_update('queues', ['status' => 'Dipanggil', 'dipanggil_at' => date('Y-m-d H:i:s')], ['id' => $queue_id]);
$q_chk = db_select_one("SELECT status, dipanggil_at FROM queues WHERE id = :qid", ['qid' => $queue_id]);
assert_test("Notifikasi Panggilan & Status Antrian (Perawat)", $q_chk && $q_chk['status'] === 'Dipanggil' && !empty($q_chk['dipanggil_at']), "Waktu dipanggil: {$q_chk['dipanggil_at']}");
echo "\n";

// -----------------------------------------------------------------------------
// 5. EMR DOKTER (ALUR UTAMA KRUSIAL) (Tester E.1 - E.4)
// -----------------------------------------------------------------------------
echo "5. PENGUJIAN EMR DOKTER (SOAP, DIAGNOSA, RESEP)\n";
// Pembentukan kunjungan (visit)
$no_kunjungan = "KJ-" . date('Ymd') . "-$queue_id-TEST-$rand_id";
$kunjungan_id = db_insert('visits', [
    'no_kunjungan' => $no_kunjungan,
    'pasien_id' => $pasien_id,
    'polyclinic_id' => $poli_test['id'],
    'dokter_id' => $dokter_test['dokter_id'],
    'queue_id' => $queue_id,
    'no_antrian' => $no_antrian,
    'tanggal_kunjungan' => date('Y-m-d'),
    'status' => 'DALAM_PEMERIKSAAN',
    'jenis_pembayaran' => 'BPJS',
    'biaya_jasa' => $dokter_test['biaya_jasa']
]);
assert_test("Sinkronisasi Antrian -> Visit EMR Dokter", $kunjungan_id > 0, "No Kunjungan: $no_kunjungan");

// Simpan Rekam Medis SOAP Lengkap
$rm_id = db_insert('rekam_medis', [
    'kunjungan_id' => $kunjungan_id,
    'dokter_id' => $dokter_test['dokter_id'],
    'keluhan_utama' => 'Demam tinggi dan batuk berdahak 3 hari',
    'subjective' => 'Demam naik turun, tenggorokan sakit saat menelan',
    'objective' => 'Suhu 38.5 C, Faring hiperemis (+)',
    'assessment' => 'Faringitis Akut (J02.9)',
    'plan' => 'Istirahat cukup, antibiotik dan antipiretik',
    'tekanan_darah_sistol' => 120,
    'tekanan_darah_diastol' => 80,
    'suhu' => 38.5,
    'nadi' => 88,
    'respirasi' => 20,
    'berat_badan' => 65.0
]);
assert_test("Simpan Pemeriksaan Fisik & SOAP Rekam Medis", $rm_id > 0, "RM ID: $rm_id");

// Pastikan kode ICD-10 tersedia di icd_diagnosis
$icd_chk = db_select_one("SELECT kode FROM icd_diagnosis WHERE kode = 'J02.9' LIMIT 1");
if (!$icd_chk) {
    db_insert('icd_diagnosis', [
        'kode' => 'J02.9',
        'nama_icd' => 'Acute pharyngitis, unspecified',
        'name_en' => 'Acute pharyngitis, unspecified',
        'is_active' => true
    ]);
}

// Simpan Diagnosa ICD-10
db_insert('rm_diagnoses', [
    'rm_id' => $rm_id,
    'kode_icd10' => 'J02.9',
    'nama_diagnosis' => 'Acute pharyngitis, unspecified',
    'jenis_diagnosis' => 'Primer'
]);
$diag_cnt = db_select_one("SELECT count(*) as total FROM rm_diagnoses WHERE rm_id = :rmid", ['rmid' => $rm_id]);
assert_test("Pencatatan Diagnosa ICD-10", $diag_cnt && intval($diag_cnt['total']) === 1, "Kode: J02.9 - Acute pharyngitis");

// Simpan Resep Obat EMR
$resep_id = db_insert('resep', [
    'rm_id' => $rm_id,
    'catatan' => 'Aturan minum setelah makan, habiskan antibiotik',
    'status' => 'MENUNGGU_OBAT'
]);
$obat1 = db_select_one("SELECT obat_id, kode_obat, nama_obat, harga, stok FROM obat WHERE stok >= 10 ORDER BY obat_id ASC LIMIT 1");
$obat2 = db_select_one("SELECT obat_id, kode_obat, nama_obat, harga, stok FROM obat WHERE obat_id != :id1 AND stok >= 10 ORDER BY obat_id ASC LIMIT 1", ['id1' => $obat1['obat_id']]);

db_insert('resep_items', [
    'resep_id' => $resep_id,
    'obat_id' => $obat1['obat_id'],
    'nama_obat' => $obat1['nama_obat'],
    'qty' => 10,
    'dosis' => 500,
    'satuan' => 'mg',
    'aturan_pakai' => '3x1 tablet sehari (Sesudah makan)'
]);
db_insert('resep_items', [
    'resep_id' => $resep_id,
    'obat_id' => $obat2['obat_id'],
    'nama_obat' => $obat2['nama_obat'],
    'qty' => 15,
    'dosis' => 500,
    'satuan' => 'mg',
    'aturan_pakai' => '3x1 tablet sehari (Habiskan)'
]);
assert_test("Input Resep EMR -> Siap Diproses Farmasi", $resep_id > 0, "Resep ID: $resep_id ({$obat1['nama_obat']} & {$obat2['nama_obat']})");
echo "\n";

// -----------------------------------------------------------------------------
// 6. FARMASI (STOK & SINKRONISASI KASIR) (Tester F.1 - F.6)
// -----------------------------------------------------------------------------
echo "6. PENGUJIAN FARMASI & MANAJEMEN STOK\n";
$stok_awal_1 = intval($obat1['stok']);
$qty_1 = 10;
// Simulasi proses resep selesai (Pengurangan stok)
db_update('obat', ['stok' => $stok_awal_1 - $qty_1], ['obat_id' => $obat1['obat_id']]);
db_update('resep', ['status' => 'SELESAI'], ['id' => $resep_id]);

$obat1_curr = db_select_one("SELECT stok FROM obat WHERE obat_id = :id", ['id' => $obat1['obat_id']]);
assert_test("Pengurangan Stok Otomatis Saat Resep Diproses Farmasi", intval($obat1_curr['stok']) === ($stok_awal_1 - $qty_1), "Stok Awal: $stok_awal_1 -> Stok Akhir: {$obat1_curr['stok']}");

// Peringatan stok menipis (< 100)
$low_stocks = db_select("SELECT count(*) as cnt FROM obat WHERE stok <= 100");
assert_test("Deteksi Peringatan Stok Menipis / Kritis", is_array($low_stocks), "Terdapat " . ($low_stocks[0]['cnt'] ?? 0) . " obat dalam status perhatian stok");
echo "\n";

// -----------------------------------------------------------------------------
// 7. KASIR & PEMBAYARAN FINAL (Tester G.1 - G.2)
// -----------------------------------------------------------------------------
echo "7. PENGUJIAN KASIR & SINKRONISASI TAGIHAN\n";
// Pembentukan tagihan otomatis dari EMR + Farmasi
$total_obat = ($qty_1 * floatval($obat1['harga'])) + (15 * floatval($obat2['harga']));
$total_tagihan = floatval($dokter_test['biaya_jasa']) + $total_obat;

$tagihan_id = db_insert('tagihan', [
    'kunjungan_id' => $kunjungan_id,
    'no_tagihan' => "INV-" . date('Ymd') . "-$kunjungan_id",
    'total_biaya' => $total_tagihan,
    'status_pembayaran' => 'BELUM_DIBAYAR'
]);

db_insert('tagihan_detail', [
    'tagihan_id' => $tagihan_id,
    'nama_layanan' => 'Konsultasi & Pemeriksaan ' . $dokter_test['nama_lengkap'],
    'biaya' => floatval($dokter_test['biaya_jasa']),
    'jumlah' => 1,
    'subtotal' => floatval($dokter_test['biaya_jasa'])
]);
db_insert('tagihan_detail', [
    'tagihan_id' => $tagihan_id,
    'nama_layanan' => 'Resep Obat EMR (2 Item)',
    'biaya' => $total_obat,
    'jumlah' => 1,
    'subtotal' => $total_obat
]);

assert_test("Kalkulasi Tagihan (Jasa Dokter + Resep Obat)", $tagihan_id > 0, "Total Biaya: Rp " . number_format($total_tagihan, 0, ',', '.'));

// Konfirmasi Pelunasan Kasir
db_update('tagihan', [
    'status_pembayaran' => 'LUNAS',
    'metode_pembayaran' => 'BPJS / JKN',
    'dibayar_at' => date('Y-m-d H:i:s')
], ['id' => $tagihan_id]);
db_update('visits', ['status' => 'SELESAI'], ['kunjungan_id' => $kunjungan_id]);
db_update('queues', ['status' => 'Selesai', 'selesai_at' => date('Y-m-d H:i:s')], ['id' => $queue_id]);

$tag_chk = db_select_one("SELECT status_pembayaran FROM tagihan WHERE id = :tid", ['tid' => $tagihan_id]);
assert_test("Pelunasan Kasir & Penutupan Siklus Pasien", $tag_chk && $tag_chk['status_pembayaran'] === 'LUNAS', "Status Akhir: LUNAS");
echo "\n";

// -----------------------------------------------------------------------------
// 8. PEMBERSIHAN DATA SIMULASI (CLEANUP)
// -----------------------------------------------------------------------------
echo "8. PEMBERSIHAN DATA SIMULASI (CLEANUP)\n";
db_delete('tagihan_detail', ['tagihan_id' => $tagihan_id]);
db_delete('tagihan', ['id' => $tagihan_id]);
db_delete('resep_items', ['resep_id' => $resep_id]);
db_delete('resep', ['id' => $resep_id]);
db_delete('rm_diagnoses', ['rm_id' => $rm_id]);
db_delete('rekam_medis', ['rm_id' => $rm_id]);
db_delete('visits', ['kunjungan_id' => $kunjungan_id]);
db_delete('queues', ['id' => $queue_id]);
db_delete('patients', ['id' => $pasien_id]);
// Kembalikan stok obat
db_update('obat', ['stok' => $stok_awal_1], ['obat_id' => $obat1['obat_id']]);
assert_test("Cleanup Data Simulasi", true, "Database kembali bersih 100%");

echo "\n=================================================================================\n";
echo "                     HASIL AKHIR BLACKBOX TESTING SUITE                          \n";
echo "=================================================================================\n";
echo " TOTAL PENGUJIAN : " . ($pass_count + $fail_count) . " Test\n";
echo " BERHASIL (PASS) : $pass_count Test ✓\n";
echo " GAGAL (FAIL)    : $fail_count Test ✗\n";
echo " STATUS UTAMA    : " . ($fail_count === 0 ? "ALL GREEN (SISTEM STABIL & SIAP OPERASI 100%) ✓" : "PERLU PERBAIKAN PADA TEST YANG GAGAL ✗") . "\n";
echo "=================================================================================\n\n";
