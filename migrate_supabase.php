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

// Membersihkan tabel yang strukturnya lama/konflik agar bisa dibentuk ulang dengan sempurna
// CATATAN: Tabel lama 'poliklinik' dan 'antrian' diganti → 'polyclinics' dan 'queues'
$pdo->exec("DROP TABLE IF EXISTS visits, antrian, queues, bpjs_sep, rekam_medis, rm_diagnoses, resep, resep_items, order_lab, order_lab_hasil, order_radiologi, surat_rujukan, tagihan CASCADE;");
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
            no_antrian VARCHAR(10) NOT NULL,
            tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
            status VARCHAR(30) NOT NULL DEFAULT 'Menunggu',
            jenis_daftar VARCHAR(20) DEFAULT 'Offline',
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
            riwayat_penyakit TEXT NULL,
            riwayat_alergi TEXT NULL,
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
];

foreach ($seed_polyclinics as $poli) {
    try {
        $stmt = $pdo->prepare("INSERT INTO polyclinics (kode_poli, nama_poli) VALUES (:kode, :nama) ON CONFLICT (kode_poli) DO NOTHING");
        $stmt->execute(['kode' => $poli[0], 'nama' => $poli[1]]);
        echo " - {$poli[1]} ({$poli[0]})... OK ✓\n";
    } catch (PDOException $e) {
        echo " - {$poli[1]}: SKIP (" . $e->getMessage() . ")\n";
    }
}

echo "\n=========================================================\n";
echo "  MIGRASI + SEED DATA POLYCLINICS SELESAI!               \n";
echo "=========================================================\n\n";

// ===== SEED DATA: Obat (Farmasi) =====
echo "[SEED] Mengisi data obat awal...\n";
$seed_obat = [
    ['OBT-001', 'Paracetamol 500 mg',  'tablet', 1250],
    ['OBT-002', 'Amoxicillin 500 mg',  'kapsul', 320],
    ['OBT-003', 'Ranitidine 150 mg',   'tablet', 45],
    ['OBT-004', 'CTM 4 mg',            'tablet', 30],
    ['OBT-005', 'Omeprazole 20 mg',    'kapsul', 540],
    ['OBT-006', 'Simvastatin 10 mg',   'tablet', 180],
    ['OBT-007', 'Ambroxol 30 mg',      'sirup',  12],
    ['OBT-008', 'Metformin 500 mg',    'tablet', 890],
    ['OBT-009', 'Amlodipine 5 mg',     'tablet', 650],
    ['OBT-010', 'Cetirizine 10 mg',    'tablet', 420],
    ['OBT-011', 'Dexamethasone 0.5 mg','tablet', 15],
    ['OBT-012', 'Ibuprofen 400 mg',    'tablet', 780],
    ['OBT-013', 'Captopril 25 mg',     'tablet', 340],
    ['OBT-014', 'Salbutamol 2 mg',     'tablet', 8],
    ['OBT-015', 'Vitamin B Complex',   'tablet', 1500],
];

foreach ($seed_obat as $obat) {
    try {
        $stmt = $pdo->prepare("INSERT INTO obat (kode_obat, nama_obat, satuan, stok) VALUES (:kode, :nama, :satuan, :stok) ON CONFLICT (kode_obat) DO UPDATE SET stok = EXCLUDED.stok");
        $stmt->execute(['kode' => $obat[0], 'nama' => $obat[1], 'satuan' => $obat[2], 'stok' => $obat[3]]);
        echo " - {$obat[1]} ({$obat[0]}, stok: {$obat[3]})... OK ✓\n";
    } catch (PDOException $e) {
        echo " - {$obat[1]}: SKIP (" . $e->getMessage() . ")\n";
    }
}

echo "\n=========================================================\n";
echo "  SEMUA MIGRASI + SEED DATA SELESAI!                     \n";
echo "  Silakan cek Dashboard Supabase Anda.                   \n";
echo "=========================================================\n";

