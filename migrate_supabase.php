<?php
/**
 * Standalone Supabase PostgreSQL Migration Script
 * Menjalankan migrasi 15 tabel RME (Rekam Medis Elektronik) langsung ke Supabase
 */

require_once __DIR__ . '/db_helper.php';

echo "=========================================================\n";
echo "    MIGRASI DATABASE RME KE SUPABASE (POSTGRESQL)       \n";
echo "=========================================================\n\n";

try {
    echo "[1/3] Menghubungkan ke server Supabase via db_helper... ";
    $pdo = get_db_connection();
    echo "BERHASIL! ✓\n\n";
} catch (Exception $e) {
    echo "GAGAL! ✗\n\n";
    echo "Pesan Error: " . $e->getMessage() . "\n";
    echo "---------------------------------------------------------\n";
    echo "TIPS PERBAIKAN:\n";
    echo "1. Pastikan file .env lokal Anda sudah terisi dengan benar.\n";
    echo "2. Jika di server cloud/Vercel, pastikan Environment Variables sudah diatur.\n";
    echo "---------------------------------------------------------\n";
    exit(1);
}

echo "[2/3] Mempersiapkan skema tabel (15 Tabel RME)...\n";

// Membersihkan tabel dan view lama yang tidak digunakan (Sisa migrasi/legacy) agar bersih 100% (Tester H.1)
$pdo->exec("DROP VIEW IF EXISTS v_daily_revenue, v_medicine_stock_status, v_top_diagnoses CASCADE;");
$pdo->exec("DROP TABLE IF EXISTS audit_logs, billing, billing_items, bpjs_claims, diagnoses, doctor_schedules, doctors, icd10_codes, igd_visits, inpatient_admissions, lab_requests, lab_results, medicine_stocks, medicines, migrations, notifications, payments, physiotherapy_sessions, prescription_items, prescriptions, radiology_requests, radiology_results, referrals, roles, rooms, service_rates, stock_transactions, vital_signs, ward_notes CASCADE;");
$pdo->exec("DROP TABLE IF EXISTS visits, antrian, queues, bpjs_sep, rekam_medis, rm_diagnoses, resep, resep_items, order_lab, order_lab_hasil, order_radiologi, surat_rujukan, tagihan, tagihan_detail, emr_notes, rbac_roles, pam_supervisor_requests CASCADE;");
$pdo->exec("DROP TABLE IF EXISTS jadwal_dokter CASCADE;");
$pdo->exec("DROP TABLE IF EXISTS poliklinik CASCADE;");

$queries = [
    "Tabel users" => "
        CREATE TABLE IF NOT EXISTS users (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            email_verified_at TIMESTAMP WITH TIME ZONE NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(30) DEFAULT 'admisi',
            remember_token VARCHAR(100) NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel pam_approvals" => "
        CREATE TABLE IF NOT EXISTS pam_approvals (
            id BIGSERIAL PRIMARY KEY,
            requested_by BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
            action_type VARCHAR(100) NOT NULL,
            target_table VARCHAR(100) NULL,
            target_id VARCHAR(100) NULL,
            payload JSONB NULL,
            status VARCHAR(30) DEFAULT 'PENDING',
            approved_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
            reason TEXT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel polyclinics" => "
        CREATE TABLE IF NOT EXISTS polyclinics (
            id BIGSERIAL PRIMARY KEY,
            kode_poli VARCHAR(10) UNIQUE NOT NULL,
            nama_poli VARCHAR(100) NOT NULL,
            kode_bpjs_poli VARCHAR(20) NULL,
            satusehat_location_id VARCHAR(100) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel dokter" => "
        CREATE TABLE IF NOT EXISTS dokter (
            dokter_id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
            sip VARCHAR(50) UNIQUE NOT NULL,
            nama_lengkap VARCHAR(150) NOT NULL,
            spesialisasi VARCHAR(100) NULL,
            jenis_dokter VARCHAR(30) DEFAULT 'Umum',
            biaya_jasa NUMERIC(12, 2) DEFAULT 50000,
            satusehat_practitioner_id VARCHAR(100) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel jadwal_dokter" => "
        CREATE TABLE IF NOT EXISTS jadwal_dokter (
            jadwal_id BIGSERIAL PRIMARY KEY,
            dokter_id BIGINT NOT NULL REFERENCES dokter(dokter_id) ON DELETE CASCADE,
            polyclinic_id BIGINT NOT NULL REFERENCES polyclinics(id) ON DELETE CASCADE,
            hari VARCHAR(20) NOT NULL,
            jam_mulai TIME NOT NULL,
            jam_selesai TIME NOT NULL,
            kuota INTEGER DEFAULT 30,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel icd_diagnosis" => "
        CREATE TABLE IF NOT EXISTS icd_diagnosis (
            kode VARCHAR(10) PRIMARY KEY,
            nama_icd VARCHAR(500) NOT NULL,
            name_en VARCHAR(500) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel obat" => "
        CREATE TABLE IF NOT EXISTS obat (
            obat_id BIGSERIAL PRIMARY KEY,
            kode_obat VARCHAR(50) UNIQUE NOT NULL,
            nama_obat VARCHAR(150) NOT NULL,
            satuan VARCHAR(50) DEFAULT 'tablet',
            stok INTEGER DEFAULT 0,
            harga NUMERIC(12, 2) DEFAULT 10000,
            kfa_code VARCHAR(50) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel patients" => "
        CREATE TABLE IF NOT EXISTS patients (
            id BIGSERIAL PRIMARY KEY,
            no_rm VARCHAR(20) UNIQUE NOT NULL,
            nik VARCHAR(16) UNIQUE NOT NULL,
            no_bpjs VARCHAR(20) NULL,
            nama_lengkap VARCHAR(200) NOT NULL,
            tanggal_lahir DATE NOT NULL,
            jenis_kelamin VARCHAR(20) NOT NULL,
            gol_darah VARCHAR(5) NULL,
            alamat TEXT NOT NULL,
            kota VARCHAR(100) NULL,
            kecamatan VARCHAR(100) NULL,
            no_telepon VARCHAR(20) NULL,
            jenis_pasien VARCHAR(30) DEFAULT 'Umum',
            alergi TEXT NULL,
            satusehat_patient_id VARCHAR(100) NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_patients_no_bpjs ON patients(no_bpjs);
    ",
    "Tabel queues (antrian)" => "
        CREATE TABLE IF NOT EXISTS queues (
            id BIGSERIAL PRIMARY KEY,
            patient_id BIGINT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
            polyclinic_id BIGINT NOT NULL REFERENCES polyclinics(id) ON DELETE CASCADE,
            dokter_id BIGINT NULL REFERENCES dokter(dokter_id) ON DELETE SET NULL,
            no_antrian VARCHAR(10) NOT NULL,
            tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
            status VARCHAR(30) NOT NULL DEFAULT 'Menunggu',
            jenis_daftar VARCHAR(20) DEFAULT 'Offline',
            biaya_jasa NUMERIC(12, 2) DEFAULT 50000,
            dipanggil_at TIMESTAMP WITH TIME ZONE NULL,
            mulai_at TIMESTAMP WITH TIME ZONE NULL,
            selesai_at TIMESTAMP WITH TIME ZONE NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_queues_tanggal ON queues(tanggal);
        CREATE INDEX IF NOT EXISTS idx_queues_status ON queues(status);
    ",
    "Tabel visits (kunjungan)" => "
        CREATE TABLE IF NOT EXISTS visits (
            kunjungan_id BIGSERIAL PRIMARY KEY,
            no_kunjungan VARCHAR(30) UNIQUE NOT NULL,
            pasien_id BIGINT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
            polyclinic_id BIGINT NOT NULL REFERENCES polyclinics(id) ON DELETE CASCADE,
            dokter_id BIGINT NOT NULL REFERENCES dokter(dokter_id) ON DELETE CASCADE,
            jadwal_id BIGINT NULL REFERENCES jadwal_dokter(jadwal_id) ON DELETE SET NULL,
            queue_id BIGINT NULL REFERENCES queues(id) ON DELETE SET NULL,
            no_antrian VARCHAR(10) NOT NULL,
            tanggal_kunjungan DATE NOT NULL,
            status VARCHAR(30) DEFAULT 'MENUNGGU',
            jenis_pembayaran VARCHAR(30) NULL,
            biaya_jasa NUMERIC(12, 2) DEFAULT 50000,
            no_sep VARCHAR(50) NULL,
            keluhan_utama TEXT NULL,
            registered_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
            satusehat_encounter_id VARCHAR(100) NULL,
            alasan_batal TEXT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_visits_no_sep ON visits(no_sep);
    ",
    "Tabel bpjs_sep" => "
        CREATE TABLE IF NOT EXISTS bpjs_sep (
            id BIGSERIAL PRIMARY KEY,
            kunjungan_id BIGINT NULL REFERENCES visits(kunjungan_id) ON DELETE CASCADE,
            pasien_id BIGINT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
            no_sep VARCHAR(50) NULL,
            tanggal_sep DATE NOT NULL,
            data_resp JSONB NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_bpjs_sep_no_sep ON bpjs_sep(no_sep);
    ",
    "Tabel rekam_medis" => "
        CREATE TABLE IF NOT EXISTS rekam_medis (
            rm_id BIGSERIAL PRIMARY KEY,
            kunjungan_id BIGINT UNIQUE NOT NULL REFERENCES visits(kunjungan_id) ON DELETE CASCADE,
            dokter_id BIGINT NOT NULL REFERENCES dokter(dokter_id) ON DELETE CASCADE,
            keluhan_utama TEXT NULL,
            subjective TEXT NULL,
            objective TEXT NULL,
            assessment TEXT NULL,
            plan TEXT NULL,
            riwayat_penyakit TEXT NULL,
            riwayat_alergi TEXT NULL,
            tekanan_darah VARCHAR(30) NULL,
            tekanan_darah_sistol INTEGER NULL,
            tekanan_darah_diastol INTEGER NULL,
            suhu NUMERIC(4, 1) NULL,
            nadi INTEGER NULL,
            respirasi INTEGER NULL,
            berat_badan NUMERIC(5, 1) NULL,
            tinggi_badan NUMERIC(5, 1) NULL,
            spo2 INTEGER NULL,
            pemeriksaan_fisik TEXT NULL,
            pemeriksaan_penunjang_catatan TEXT NULL,
            catatan_dokter TEXT NULL,
            tindak_lanjut TEXT NULL,
            status VARCHAR(20) DEFAULT 'DRAFT',
            addendum TEXT NULL,
            satusehat_pushed_at TIMESTAMP WITH TIME ZONE NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel rm_diagnoses" => "
        CREATE TABLE IF NOT EXISTS rm_diagnoses (
            id BIGSERIAL PRIMARY KEY,
            rm_id BIGINT NOT NULL REFERENCES rekam_medis(rm_id) ON DELETE CASCADE,
            kode_icd10 VARCHAR(10) NOT NULL REFERENCES icd_diagnosis(kode) ON DELETE CASCADE,
            nama_diagnosis VARCHAR(500) NOT NULL,
            jenis_diagnosis VARCHAR(20) NOT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel resep" => "
        CREATE TABLE IF NOT EXISTS resep (
            id BIGSERIAL PRIMARY KEY,
            rm_id BIGINT UNIQUE NOT NULL REFERENCES rekam_medis(rm_id) ON DELETE CASCADE,
            status VARCHAR(30) DEFAULT 'DRAFT',
            catatan TEXT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel resep_items" => "
        CREATE TABLE IF NOT EXISTS resep_items (
            id BIGSERIAL PRIMARY KEY,
            resep_id BIGINT NOT NULL REFERENCES resep(id) ON DELETE CASCADE,
            obat_id BIGINT NOT NULL REFERENCES obat(obat_id) ON DELETE CASCADE,
            nama_obat VARCHAR(150) NULL,
            dosis NUMERIC(8, 2) NOT NULL,
            satuan VARCHAR(50) NOT NULL,
            aturan_pakai VARCHAR(100) NOT NULL,
            qty INTEGER NOT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel order_lab" => "
        CREATE TABLE IF NOT EXISTS order_lab (
            id BIGSERIAL PRIMARY KEY,
            rm_id BIGINT NOT NULL REFERENCES rekam_medis(rm_id) ON DELETE CASCADE,
            kunjungan_id BIGINT NOT NULL REFERENCES visits(kunjungan_id) ON DELETE CASCADE,
            jenis_pemeriksaan VARCHAR(100) NOT NULL,
            catatan TEXT NULL,
            status VARCHAR(30) DEFAULT 'ORDERED',
            hasil_text TEXT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel order_lab_hasil" => "
        CREATE TABLE IF NOT EXISTS order_lab_hasil (
            id BIGSERIAL PRIMARY KEY,
            order_lab_id BIGINT NOT NULL REFERENCES order_lab(id) ON DELETE CASCADE,
            parameter VARCHAR(100) NOT NULL,
            nilai VARCHAR(100) NOT NULL,
            satuan VARCHAR(50) NOT NULL,
            nilai_rujukan VARCHAR(100) NOT NULL,
            keterangan VARCHAR(100) NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel order_radiologi" => "
        CREATE TABLE IF NOT EXISTS order_radiologi (
            id BIGSERIAL PRIMARY KEY,
            rm_id BIGINT NOT NULL REFERENCES rekam_medis(rm_id) ON DELETE CASCADE,
            kunjungan_id BIGINT NOT NULL REFERENCES visits(kunjungan_id) ON DELETE CASCADE,
            jenis_pemeriksaan VARCHAR(100) NOT NULL,
            catatan TEXT NULL,
            status VARCHAR(30) DEFAULT 'ORDERED',
            hasil_ekspertise TEXT NULL,
            file_url VARCHAR(255) NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel surat_rujukan" => "
        CREATE TABLE IF NOT EXISTS surat_rujukan (
            id BIGSERIAL PRIMARY KEY,
            rm_id BIGINT NOT NULL REFERENCES rekam_medis(rm_id) ON DELETE CASCADE,
            faskes_tujuan VARCHAR(150) NOT NULL,
            poli_tujuan VARCHAR(100) NOT NULL,
            alasan_rujukan TEXT NOT NULL,
            no_rujukan_bpjs VARCHAR(50) NULL,
            tanggal_rujukan DATE NOT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel tagihan" => "
        CREATE TABLE IF NOT EXISTS tagihan (
            id BIGSERIAL PRIMARY KEY,
            kunjungan_id BIGINT UNIQUE NOT NULL REFERENCES visits(kunjungan_id) ON DELETE CASCADE,
            no_tagihan VARCHAR(30) UNIQUE NOT NULL,
            total_biaya NUMERIC(12, 2) DEFAULT 0,
            status_pembayaran VARCHAR(30) DEFAULT 'BELUM_DIBAYAR',
            metode_pembayaran VARCHAR(50) NULL,
            dibayar_at TIMESTAMP WITH TIME ZONE NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel tagihan_detail" => "
        CREATE TABLE IF NOT EXISTS tagihan_detail (
            id BIGSERIAL PRIMARY KEY,
            tagihan_id BIGINT NOT NULL REFERENCES tagihan(id) ON DELETE CASCADE,
            nama_layanan VARCHAR(255) NOT NULL,
            biaya NUMERIC(12, 2) DEFAULT 0,
            jumlah INTEGER DEFAULT 1,
            subtotal NUMERIC(12, 2) DEFAULT 0,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel emr_notes" => "
        CREATE TABLE IF NOT EXISTS emr_notes (
            id BIGSERIAL PRIMARY KEY,
            queue_id BIGINT NULL,
            no_antrian VARCHAR(20) NULL,
            subjective TEXT NULL,
            objective TEXT NULL,
            assessment TEXT NULL,
            plan TEXT NULL,
            icd10_code VARCHAR(30) NULL,
            icd10_name VARCHAR(255) NULL,
            prescription TEXT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel rbac_roles" => "
        CREATE TABLE IF NOT EXISTS rbac_roles (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NULL,
            role_name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Tabel pam_supervisor_requests" => "
        CREATE TABLE IF NOT EXISTS pam_supervisor_requests (
            id BIGSERIAL PRIMARY KEY,
            action_code VARCHAR(100) NOT NULL,
            supervisor_pin VARCHAR(50) NOT NULL,
            status VARCHAR(30) DEFAULT 'APPROVED',
            requested_by BIGINT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ",
    "Optimalisasi Index Foreign Key (Tester H.2 & H.3)" => "
        CREATE INDEX IF NOT EXISTS idx_pam_app_req ON pam_approvals(requested_by);
        CREATE INDEX IF NOT EXISTS idx_pam_app_app ON pam_approvals(approved_by);
        CREATE INDEX IF NOT EXISTS idx_dokter_user ON dokter(user_id);
        CREATE INDEX IF NOT EXISTS idx_jadwal_dokter_id ON jadwal_dokter(dokter_id);
        CREATE INDEX IF NOT EXISTS idx_jadwal_poli_id ON jadwal_dokter(polyclinic_id);
        CREATE INDEX IF NOT EXISTS idx_queues_patient ON queues(patient_id);
        CREATE INDEX IF NOT EXISTS idx_queues_poli ON queues(polyclinic_id);
        CREATE INDEX IF NOT EXISTS idx_queues_dokter ON queues(dokter_id);
        CREATE INDEX IF NOT EXISTS idx_visits_pasien ON visits(pasien_id);
        CREATE INDEX IF NOT EXISTS idx_visits_poli ON visits(polyclinic_id);
        CREATE INDEX IF NOT EXISTS idx_visits_dokter ON visits(dokter_id);
        CREATE INDEX IF NOT EXISTS idx_visits_jadwal ON visits(jadwal_id);
        CREATE INDEX IF NOT EXISTS idx_visits_queue ON visits(queue_id);
        CREATE INDEX IF NOT EXISTS idx_visits_regby ON visits(registered_by);
        CREATE INDEX IF NOT EXISTS idx_bpjs_kunjungan ON bpjs_sep(kunjungan_id);
        CREATE INDEX IF NOT EXISTS idx_bpjs_pasien ON bpjs_sep(pasien_id);
        CREATE INDEX IF NOT EXISTS idx_rm_kunjungan ON rekam_medis(kunjungan_id);
        CREATE INDEX IF NOT EXISTS idx_rm_dokter ON rekam_medis(dokter_id);
        CREATE INDEX IF NOT EXISTS idx_rm_diag_rm ON rm_diagnoses(rm_id);
        CREATE INDEX IF NOT EXISTS idx_rm_diag_icd ON rm_diagnoses(kode_icd10);
        CREATE INDEX IF NOT EXISTS idx_resep_rm ON resep(rm_id);
        CREATE INDEX IF NOT EXISTS idx_resep_items_resep ON resep_items(resep_id);
        CREATE INDEX IF NOT EXISTS idx_resep_items_obat ON resep_items(obat_id);
        CREATE INDEX IF NOT EXISTS idx_ord_lab_rm ON order_lab(rm_id);
        CREATE INDEX IF NOT EXISTS idx_ord_lab_kunjungan ON order_lab(kunjungan_id);
        CREATE INDEX IF NOT EXISTS idx_ord_lab_hasil_ord ON order_lab_hasil(order_lab_id);
        CREATE INDEX IF NOT EXISTS idx_ord_rad_rm ON order_radiologi(rm_id);
        CREATE INDEX IF NOT EXISTS idx_ord_rad_kunjungan ON order_radiologi(kunjungan_id);
        CREATE INDEX IF NOT EXISTS idx_surat_rujuk_rm ON surat_rujukan(rm_id);
        CREATE INDEX IF NOT EXISTS idx_tagihan_kunjungan ON tagihan(kunjungan_id);
        CREATE INDEX IF NOT EXISTS idx_tagihan_detail_tag ON tagihan_detail(tagihan_id);
        CREATE INDEX IF NOT EXISTS idx_emr_notes_queue ON emr_notes(queue_id);
        CREATE INDEX IF NOT EXISTS idx_rbac_user ON rbac_roles(user_id);
        CREATE INDEX IF NOT EXISTS idx_pam_sup_reqby ON pam_supervisor_requests(requested_by);
    "
];

echo "[3/3] Mengeksekusi pembuatan tabel di Supabase...\n\n";

foreach ($queries as $tableName => $sql) {
    try {
        echo " - Membentuk $tableName... ";
        $pdo->exec($sql);
        echo "OK! ✓\n";
    } catch (PDOException $e) {
        echo "GAGAL! ✗\n";
        echo "   -> Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=========================================================\n";
echo "  MIGRASI TABEL SELESAI!                                 \n";
echo "=========================================================\n\n";

echo "[ALTER] Memeriksa & menambahkan kolom baru pada tabel eksisting...\n";
$alter_queries = [
    "queues (jenis_daftar)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS jenis_daftar VARCHAR(20) DEFAULT 'Offline'",
    "queues (dipanggil_at)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS dipanggil_at TIMESTAMP WITH TIME ZONE NULL",
    "queues (mulai_at)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS mulai_at TIMESTAMP WITH TIME ZONE NULL",
    "queues (selesai_at)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS selesai_at TIMESTAMP WITH TIME ZONE NULL",
    "queues (created_at)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP",
    "queues (updated_at)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP",
    "queues (dokter_id)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS dokter_id BIGINT NULL REFERENCES dokter(dokter_id) ON DELETE SET NULL",
    "queues (biaya_jasa)" => "ALTER TABLE queues ADD COLUMN IF NOT EXISTS biaya_jasa NUMERIC(12, 2) DEFAULT 50000",
    "patients (satusehat_patient_id)" => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS satusehat_patient_id VARCHAR(100) NULL",
    "patients (gol_darah)" => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS gol_darah VARCHAR(5) NULL",
    "patients (jenis_pasien)" => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS jenis_pasien VARCHAR(30) DEFAULT 'Umum'",
    "patients (alergi)" => "ALTER TABLE patients ADD COLUMN IF NOT EXISTS alergi TEXT NULL",
    "visits (registered_by)" => "ALTER TABLE visits ADD COLUMN IF NOT EXISTS registered_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL",
    "visits (satusehat_encounter_id)" => "ALTER TABLE visits ADD COLUMN IF NOT EXISTS satusehat_encounter_id VARCHAR(100) NULL",
    "visits (alasan_batal)" => "ALTER TABLE visits ADD COLUMN IF NOT EXISTS alasan_batal TEXT NULL",
    "visits (jenis_pembayaran)" => "ALTER TABLE visits ADD COLUMN IF NOT EXISTS jenis_pembayaran VARCHAR(30) NULL",
    "visits (no_sep)" => "ALTER TABLE visits ADD COLUMN IF NOT EXISTS no_sep VARCHAR(50) NULL",
    "visits (keluhan_utama)" => "ALTER TABLE visits ADD COLUMN IF NOT EXISTS keluhan_utama TEXT NULL",
    "visits (biaya_jasa)" => "ALTER TABLE visits ADD COLUMN IF NOT EXISTS biaya_jasa NUMERIC(12, 2) DEFAULT 50000",
    "users (name)" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(255) NULL",
    "users (nama)" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS nama VARCHAR(255) NULL",
    "users (nama nullable)" => "ALTER TABLE users ALTER COLUMN nama DROP NOT NULL",
    "users (role)" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(30) DEFAULT 'admisi'",
    "users (role_id nullable)" => "ALTER TABLE users ALTER COLUMN role_id DROP NOT NULL",
    "dokter (jenis_dokter)" => "ALTER TABLE dokter ADD COLUMN IF NOT EXISTS jenis_dokter VARCHAR(30) DEFAULT 'Umum'",
    "dokter (biaya_jasa)" => "ALTER TABLE dokter ADD COLUMN IF NOT EXISTS biaya_jasa NUMERIC(12, 2) DEFAULT 50000",
    "obat (harga)" => "ALTER TABLE obat ADD COLUMN IF NOT EXISTS harga NUMERIC(12, 2) DEFAULT 10000",
    "rekam_medis (addendum)" => "ALTER TABLE rekam_medis ADD COLUMN IF NOT EXISTS addendum TEXT NULL",
    "rekam_medis (satusehat_pushed_at)" => "ALTER TABLE rekam_medis ADD COLUMN IF NOT EXISTS satusehat_pushed_at TIMESTAMP WITH TIME ZONE NULL",
    "rekam_medis (subjective)" => "ALTER TABLE rekam_medis ADD COLUMN IF NOT EXISTS subjective TEXT NULL",
    "rekam_medis (objective)" => "ALTER TABLE rekam_medis ADD COLUMN IF NOT EXISTS objective TEXT NULL",
    "rekam_medis (assessment)" => "ALTER TABLE rekam_medis ADD COLUMN IF NOT EXISTS assessment TEXT NULL",
    "rekam_medis (plan)" => "ALTER TABLE rekam_medis ADD COLUMN IF NOT EXISTS plan TEXT NULL",
    "rekam_medis (tekanan_darah)" => "ALTER TABLE rekam_medis ADD COLUMN IF NOT EXISTS tekanan_darah VARCHAR(30) NULL",
    "tagihan (metode_pembayaran)" => "ALTER TABLE tagihan ADD COLUMN IF NOT EXISTS metode_pembayaran VARCHAR(50) NULL",
    "tagihan (dibayar_at)" => "ALTER TABLE tagihan ADD COLUMN IF NOT EXISTS dibayar_at TIMESTAMP WITH TIME ZONE NULL",
    "tagihan (status_pembayaran)" => "ALTER TABLE tagihan ADD COLUMN IF NOT EXISTS status_pembayaran VARCHAR(30) DEFAULT 'BELUM_DIBAYAR'",
    "tagihan (total_biaya)" => "ALTER TABLE tagihan ADD COLUMN IF NOT EXISTS total_biaya NUMERIC(12, 2) DEFAULT 0",
];

foreach ($alter_queries as $desc => $alter_sql) {
    try {
        echo " - Memeriksa kolom $desc... ";
        $pdo->exec($alter_sql);
        echo "OK! ✓\n";
    } catch (PDOException $e) {
        echo "SKIP (" . $e->getMessage() . ")\n";
    }
}
echo "\n";

// ===== SEED DATA: Polyclinics (Poliklinik) =====
echo "[SEED] Mengisi data poliklinik awal...\n";
$seed_polyclinics = [
    ['POL-001', 'Poli Umum'],
    ['POL-002', 'Poli Gigi'],
    ['POL-003', 'Poli Anak'],
    ['POL-004', 'Poli Jantung'],
    ['POL-005', 'Poli Kulit'],
    ['POL-006', 'Poli Mata'],
    ['POL-007', 'Poli THT'],
    ['POL-008', 'Poli Kebidanan'],
    ['POL-009', 'Poli Bedah'],
    ['POL-010', 'Poli Paru'],
    ['POL-011', 'Poli Saraf'],
    ['POL-IGD', 'Instalasi Gawat Darurat (IGD)'],
];

foreach ($seed_polyclinics as $poli) {
    try {
        $chk = db_select("SELECT id FROM polyclinics WHERE nama_poli = :nama LIMIT 1", ['nama' => $poli[1]]);
        if (empty($chk)) {
            $stmt = $pdo->prepare("INSERT INTO polyclinics (kode_poli, nama_poli) VALUES (:kode, :nama) ON CONFLICT (kode_poli) DO NOTHING");
            $stmt->execute(['kode' => $poli[0], 'nama' => $poli[1]]);
        }
        echo " - {$poli[1]} ({$poli[0]})... OK ✓\n";
    } catch (PDOException $e) {
        echo " - {$poli[1]}: SKIP (" . $e->getMessage() . ")\n";
    }
}

echo "\n=========================================================\n";
echo "  MIGRASI + SEED DATA POLYCLINICS SELESAI!               \n";
echo "=========================================================\n\n";

// ===== SEED DATA: ICD-10 Diagnoses =====
echo "[SEED] Mengisi data diagnosa ICD-10 awal...\n";
$seed_icd = [
    ['J00', 'Acute nasopharyngitis [common cold]', 'Acute nasopharyngitis [common cold]'],
    ['J02.9', 'Acute pharyngitis, unspecified', 'Acute pharyngitis, unspecified'],
    ['J06.9', 'Acute upper respiratory infection, unspecified', 'Acute upper respiratory infection, unspecified'],
    ['A09', 'Infectious gastroenteritis and colitis, unspecified', 'Infectious gastroenteritis and colitis, unspecified'],
    ['I10', 'Essential (primary) hypertension', 'Essential (primary) hypertension'],
    ['E11.9', 'Type 2 diabetes mellitus without complications', 'Type 2 diabetes mellitus without complications'],
    ['K30', 'Functional dyspepsia', 'Functional dyspepsia'],
    ['R50.9', 'Fever, unspecified', 'Fever, unspecified'],
    ['J45.9', 'Asthma, unspecified', 'Asthma, unspecified'],
    ['M54.5', 'Low back pain', 'Low back pain'],
];

foreach ($seed_icd as $icd) {
    try {
        $stmt = $pdo->prepare("INSERT INTO icd_diagnosis (kode, nama_icd, name_en) VALUES (:k, :n, :e) ON CONFLICT (kode) DO UPDATE SET nama_icd = EXCLUDED.nama_icd, name_en = EXCLUDED.name_en");
        $stmt->execute([':k' => $icd[0], ':n' => $icd[1], ':e' => $icd[2]]);
        echo " - ICD-10: {$icd[0]} ({$icd[1]})... OK ✓\n";
    } catch (PDOException $e) {
        echo " - ICD-10 {$icd[0]}: SKIP (" . $e->getMessage() . ")\n";
    }
}
echo "\n";

// ===== SEED DATA: Obat (Farmasi) =====
echo "[SEED] Mengisi data obat awal...\n";
$seed_obat = [
    ['OBT-001', 'Paracetamol 500 mg',  'tablet', 1250, 15000],
    ['OBT-002', 'Amoxicillin 500 mg',  'kapsul', 320, 25000],
    ['OBT-003', 'Ranitidine 150 mg',   'tablet', 45, 18000],
    ['OBT-004', 'CTM 4 mg',            'tablet', 30, 8000],
    ['OBT-005', 'Omeprazole 20 mg',    'kapsul', 540, 35000],
    ['OBT-006', 'Simvastatin 10 mg',   'tablet', 180, 28000],
    ['OBT-007', 'Ambroxol 30 mg',      'sirup',  12, 22000],
    ['OBT-008', 'Metformin 500 mg',    'tablet', 890, 20000],
    ['OBT-009', 'Amlodipine 5 mg',     'tablet', 650, 24000],
    ['OBT-010', 'Cetirizine 10 mg',    'tablet', 420, 15000],
    ['OBT-011', 'Dexamethasone 0.5 mg','tablet', 15, 12000],
    ['OBT-012', 'Ibuprofen 400 mg',    'tablet', 780, 16000],
    ['OBT-013', 'Captopril 25 mg',     'tablet', 340, 14000],
    ['OBT-014', 'Salbutamol 2 mg',     'tablet', 8, 19000],
    ['OBT-015', 'Vitamin B Complex',   'tablet', 1500, 30000],
];

foreach ($seed_obat as $obat) {
    try {
        $stmt = $pdo->prepare("INSERT INTO obat (kode_obat, nama_obat, satuan, stok, harga) VALUES (:kode, :nama, :satuan, :stok, :harga) ON CONFLICT (kode_obat) DO UPDATE SET stok = EXCLUDED.stok, harga = EXCLUDED.harga");
        $stmt->execute(['kode' => $obat[0], 'nama' => $obat[1], 'satuan' => $obat[2], 'stok' => $obat[3], 'harga' => $obat[4]]);
        echo " - {$obat[1]} ({$obat[0]}, stok: {$obat[3]}, harga: Rp " . number_format($obat[4], 0, ',', '.') . ")... OK ✓\n";
    } catch (PDOException $e) {
        echo " - {$obat[1]}: SKIP (" . $e->getMessage() . ")\n";
    }
}

// ===== SEED DATA: Users (Semua Role Tester A-G) =====
echo "\n[SEED] Mengisi akun pengguna dan hak akses role...\n";
$seed_users = [
    ['dr. Budi Santoso, Sp.PD', 'dokter@rs.com', 'password123', 'dokter'],
    ['dr. Andi Pratama', 'dokter.umum@rs.com', 'password123', 'dokter'],
    ['Suster Siti Nurbaya, S.Kep', 'perawat@rs.com', 'password123', 'perawat'],
    ['Apt. Hendra Kurniawan, S.Farm', 'farmasi@rs.com', 'password123', 'farmasi'],
    ['Dewi Rahmawati (Kasir)', 'kasir@rs.com', 'password123', 'kasir'],
    ['Rina Admisi (Resepsionis)', 'resepsionis@rs.com', 'password123', 'resepsionis'],
    ['Irwan Pengawas (Supervisor)', 'supervisor@rs.com', 'password123', 'supervisor'],
    ['Administrator Superuser', 'superuser@rs.com', 'password123', 'superuser']
];

foreach ($seed_users as $usr) {
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, nama, email, password, role) VALUES (:name, :name, :email, :password, :role) ON CONFLICT (email) DO UPDATE SET name = EXCLUDED.name, nama = EXCLUDED.name, role = EXCLUDED.role");
        $stmt->execute([
            'name' => $usr[0],
            'email' => $usr[1],
            'password' => password_hash($usr[2], PASSWORD_DEFAULT),
            'role' => $usr[3]
        ]);
        echo " - User: {$usr[0]} ({$usr[1]} | Role: {$usr[3]})... OK ✓\n";
    } catch (PDOException $e) {
        echo " - User: {$usr[0]}: SKIP (" . $e->getMessage() . ")\n";
    }
}

// ===== SEED DATA: Dokter & Jadwal Dokter =====
echo "\n[SEED] Mengisi data dokter dan jadwal praktik...\n";
$seed_dokter = [
    ['SIP-001', 'dr. Budi Santoso, Sp.PD', 'Penyakit Dalam', 'Spesialis', 150000, 'POL-001', 'dokter@rs.com'],
    ['SIP-002', 'dr. Andi Pratama', 'Umum', 'Umum', 50000, 'POL-001', 'dokter.umum@rs.com'],
    ['SIP-003', 'drg. Melati Sukma, Sp.KG', 'Gigi & Mulut', 'Spesialis', 150000, 'POL-002', null],
    ['SIP-004', 'dr. Sarah Wijaya, Sp.A', 'Anak', 'Spesialis', 175000, 'POL-003', null],
    ['SIP-005', 'dr. Anton Subekti, Sp.JP', 'Jantung', 'Spesialis', 200000, 'POL-004', null],
    ['SIP-006', 'dr. Rina Handayani, Sp.KK', 'Kulit & Kelamin', 'Spesialis', 160000, 'POL-005', null],
    ['SIP-007', 'dr. Yeni Amalia, Sp.M', 'Mata', 'Spesialis', 150000, 'POL-006', null],
    ['SIP-008', 'dr. Fajar Nugroho, Sp.THT', 'THT', 'Spesialis', 150000, 'POL-007', null],
    ['SIP-009', 'dr. Ratna, Sp.OG', 'Kebidanan', 'Spesialis', 180000, 'POL-008', null],
    ['SIP-010', 'dr. Agus, Sp.B', 'Bedah', 'Spesialis', 200000, 'POL-009', null]
];

foreach ($seed_dokter as $dok) {
    try {
        $user_id = null;
        if (!empty($dok[6])) {
            $u_row = db_select_one("SELECT id FROM users WHERE email = :email", ['email' => $dok[6]]);
            if ($u_row) $user_id = $u_row['id'];
        }
        $stmt = $pdo->prepare("INSERT INTO dokter (sip, nama_lengkap, spesialisasi, jenis_dokter, biaya_jasa, user_id) VALUES (:sip, :nama, :spes, :jenis, :biaya, :uid) ON CONFLICT (sip) DO UPDATE SET nama_lengkap = EXCLUDED.nama_lengkap, jenis_dokter = EXCLUDED.jenis_dokter, biaya_jasa = EXCLUDED.biaya_jasa, user_id = COALESCE(EXCLUDED.user_id, dokter.user_id)");
        $stmt->execute([
            'sip' => $dok[0],
            'nama' => $dok[1],
            'spes' => $dok[2],
            'jenis' => $dok[3],
            'biaya' => $dok[4],
            'uid' => $user_id
        ]);
        echo " - Dokter: {$dok[1]} ({$dok[3]} - Rp " . number_format($dok[4], 0, ',', '.') . ")... OK ✓\n";

        // Tambahkan jadwal dokter
        $dok_row = db_select_one("SELECT dokter_id FROM dokter WHERE sip = :sip", ['sip' => $dok[0]]);
        $poli_row = db_select_one("SELECT id FROM polyclinics WHERE kode_poli = :kode", ['kode' => $dok[5]]);
        if ($dok_row && $poli_row) {
            $hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            foreach ($hari_list as $hari) {
                $pdo->prepare("INSERT INTO jadwal_dokter (dokter_id, polyclinic_id, hari, jam_mulai, jam_selesai, kuota) VALUES (:did, :pid, :hari, '08:00', '14:00', 30) ON CONFLICT DO NOTHING")->execute([
                    'did' => $dok_row['dokter_id'],
                    'pid' => $poli_row['id'],
                    'hari' => $hari
                ]);
            }
        }
    } catch (PDOException $e) {
        echo " - Dokter {$dok[1]}: SKIP (" . $e->getMessage() . ")\n";
    }
}

echo "\n=========================================================\n";
echo "  SEMUA MIGRASI + SEED DATA SELESAI!                     \n";
echo "  Silakan cek Dashboard Supabase Anda.                   \n";
echo "=========================================================\n";

