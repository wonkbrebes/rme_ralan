<?php

/**
 * ============================================================
 * Seed Master Data — SIMRS RSUD Puruk Cahu
 * ============================================================
 * Menjalankan:
 *   C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe seed_master_data.php
 *
 * Script ini menyemai data master ke database PostgreSQL (Supabase):
 *   - Roles
 *   - Users (Dokter)
 *   - Polyclinics
 *   - Doctors
 *   - Doctor Schedules (Senin–Jumat × 7 dokter)
 *   - ICD-10 Codes (20 diagnosa umum)
 *   - Medicines (15 obat umum)
 *   - Medicine Stocks
 *   - Service Rates (10 tarif)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Helper: echo with timestamp
function info(string $msg): void
{
    echo '[' . date('H:i:s') . "] {$msg}\n";
}

try {
    DB::connection()->getPdo();
    info('✅ Koneksi database berhasil.');
} catch (\Exception $e) {
    info('❌ Gagal koneksi: ' . $e->getMessage());
    exit(1);
}

// ══════════════════════════════════════════════════════════════
// 1. ROLES
// ══════════════════════════════════════════════════════════════
info('Seeding roles...');

$roles = [
    ['id' => 1, 'nama_role' => 'Admin',       'permissions' => '[]'],
    ['id' => 2, 'nama_role' => 'Dokter',      'permissions' => '[]'],
    ['id' => 3, 'nama_role' => 'Perawat',     'permissions' => '[]'],
    ['id' => 4, 'nama_role' => 'Apoteker',    'permissions' => '[]'],
    ['id' => 5, 'nama_role' => 'Kasir',       'permissions' => '[]'],
    ['id' => 6, 'nama_role' => 'Pendaftaran', 'permissions' => '[]'],
];

foreach ($roles as $role) {
    $exists = DB::table('roles')->where('id', $role['id'])->exists();
    if (! $exists) {
        DB::table('roles')->insert(array_merge($role, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        info("  → Role '{$role['nama_role']}' created.");
    } else {
        info("  → Role '{$role['nama_role']}' already exists, skipped.");
    }
}

// ══════════════════════════════════════════════════════════════
// 2. POLYCLINICS
// ══════════════════════════════════════════════════════════════
info('Seeding polyclinics...');

$polyclinics = [
    ['kode_poli' => 'UMU', 'nama_poli' => 'Poli Umum'],
    ['kode_poli' => 'ANA', 'nama_poli' => 'Poli Anak'],
    ['kode_poli' => 'KBD', 'nama_poli' => 'Poli Kebidanan & Kandungan'],
    ['kode_poli' => 'GIG', 'nama_poli' => 'Poli Gigi & Mulut'],
    ['kode_poli' => 'BDH', 'nama_poli' => 'Poli Bedah'],
    ['kode_poli' => 'MAT', 'nama_poli' => 'Poli Mata'],
    ['kode_poli' => 'SAR', 'nama_poli' => 'Poli Saraf'],
];

$polyclinicIds = [];
foreach ($polyclinics as $poli) {
    $existing = DB::table('polyclinics')->where('kode_poli', $poli['kode_poli'])->first();
    if (! $existing) {
        $id = DB::table('polyclinics')->insertGetId(array_merge($poli, [
            'deskripsi'  => null,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $polyclinicIds[] = $id;
        info("  → Polyclinic '{$poli['nama_poli']}' created (ID: {$id}).");
    } else {
        $polyclinicIds[] = $existing->id;
        info("  → Polyclinic '{$poli['nama_poli']}' already exists (ID: {$existing->id}), skipped.");
    }
}

// ══════════════════════════════════════════════════════════════
// 3. USERS (Dokter) + DOCTORS
// ══════════════════════════════════════════════════════════════
info('Seeding users & doctors...');

$dokterData = [
    ['nama' => 'dr. Ahmad Fauzi',         'email' => 'dokter1@rsud-purukcahu.id', 'no_str' => '31XX01', 'spesialisasi' => 'Dokter Umum',     'gelar' => 'dr.'],
    ['nama' => 'dr. Siti Rahayu Sp.A',    'email' => 'dokter2@rsud-purukcahu.id', 'no_str' => '31XX02', 'spesialisasi' => 'Sp.A (Anak)',     'gelar' => 'dr.'],
    ['nama' => 'dr. Maya Kusuma Sp.OG',   'email' => 'dokter3@rsud-purukcahu.id', 'no_str' => '31XX03', 'spesialisasi' => 'Sp.OG (Obgyn)',   'gelar' => 'dr.'],
    ['nama' => 'drg. Budi Hartono',       'email' => 'dokter4@rsud-purukcahu.id', 'no_str' => '31XX04', 'spesialisasi' => 'drg. (Gigi)',     'gelar' => 'drg.'],
    ['nama' => 'dr. Rizki Pratama Sp.B',  'email' => 'dokter5@rsud-purukcahu.id', 'no_str' => '31XX05', 'spesialisasi' => 'Sp.B (Bedah)',    'gelar' => 'dr.'],
    ['nama' => 'dr. Lina Dewi Sp.M',     'email' => 'dokter6@rsud-purukcahu.id', 'no_str' => '31XX06', 'spesialisasi' => 'Sp.M (Mata)',     'gelar' => 'dr.'],
    ['nama' => 'dr. Hendra Wijaya Sp.S',  'email' => 'dokter7@rsud-purukcahu.id', 'no_str' => '31XX07', 'spesialisasi' => 'Sp.S (Saraf)',    'gelar' => 'dr.'],
];

$hashedPassword = Hash::make('password');
$doctorIds = [];

foreach ($dokterData as $i => $d) {
    // Check if user already exists
    $existingUser = DB::table('users')->where('email', $d['email'])->first();

    if ($existingUser) {
        $userId = $existingUser->id;
        info("  → User '{$d['nama']}' already exists (ID: {$userId}), skipped.");
    } else {
        $userId = DB::table('users')->insertGetId([
            'nama'       => $d['nama'],
            'email'      => $d['email'],
            'password'   => $hashedPassword,
            'role_id'    => 2,
            'unit_kerja' => 'Poliklinik',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        info("  → User '{$d['nama']}' created (ID: {$userId}).");
    }

    // Check if doctor already exists
    $existingDoctor = DB::table('doctors')->where('no_str', $d['no_str'])->first();

    if ($existingDoctor) {
        $doctorIds[] = $existingDoctor->id;
        info("  → Doctor '{$d['no_str']}' already exists (ID: {$existingDoctor->id}), skipped.");
    } else {
        $doctorId = DB::table('doctors')->insertGetId([
            'user_id'       => $userId,
            'polyclinic_id' => $polyclinicIds[$i],
            'no_str'        => $d['no_str'],
            'spesialisasi'  => $d['spesialisasi'],
            'gelar'         => $d['gelar'],
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $doctorIds[] = $doctorId;
        info("  → Doctor '{$d['nama']}' created (ID: {$doctorId}).");
    }
}

// ══════════════════════════════════════════════════════════════
// 4. DOCTOR SCHEDULES (Senin–Jumat × 7 dokter = 35 records)
// ══════════════════════════════════════════════════════════════
info('Seeding doctor schedules...');

$hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$scheduleCount = 0;

foreach ($doctorIds as $idx => $doctorId) {
    foreach ($hariKerja as $hari) {
        $exists = DB::table('doctor_schedules')
            ->where('doctor_id', $doctorId)
            ->where('hari', $hari)
            ->exists();

        if (! $exists) {
            DB::table('doctor_schedules')->insert([
                'doctor_id'     => $doctorId,
                'polyclinic_id' => $polyclinicIds[$idx],
                'hari'          => $hari,
                'jam_mulai'     => '08:00',
                'jam_selesai'   => '14:00',
                'kuota_harian'  => 30,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $scheduleCount++;
        }
    }
}
info("  → {$scheduleCount} doctor schedules created.");

// ══════════════════════════════════════════════════════════════
// 5. ICD-10 CODES (20 diagnosa umum)
// ══════════════════════════════════════════════════════════════
info('Seeding ICD-10 codes...');

$icd10 = [
    ['kode' => 'A09',   'nama_penyakit' => 'Diare dan gastroenteritis',                  'kategori' => 'Infeksi'],
    ['kode' => 'J00',   'nama_penyakit' => 'Nasofaringitis akut (common cold)',           'kategori' => 'Respirasi'],
    ['kode' => 'J06.9', 'nama_penyakit' => 'ISPA (Infeksi Saluran Pernapasan Atas)',      'kategori' => 'Respirasi'],
    ['kode' => 'K30',   'nama_penyakit' => 'Dispepsia (maag)',                            'kategori' => 'Gastrointestinal'],
    ['kode' => 'M79.3', 'nama_penyakit' => 'Panniculitis',                                'kategori' => 'Muskuloskeletal'],
    ['kode' => 'R50.9', 'nama_penyakit' => 'Demam tidak spesifik',                        'kategori' => 'Gejala Umum'],
    ['kode' => 'I10',   'nama_penyakit' => 'Hipertensi esensial (primer)',                 'kategori' => 'Kardiovaskular'],
    ['kode' => 'E11',   'nama_penyakit' => 'Diabetes Melitus Tipe 2',                      'kategori' => 'Endokrin'],
    ['kode' => 'J18.9', 'nama_penyakit' => 'Pneumonia',                                    'kategori' => 'Respirasi'],
    ['kode' => 'K29.7', 'nama_penyakit' => 'Gastritis',                                    'kategori' => 'Gastrointestinal'],
    ['kode' => 'N39.0', 'nama_penyakit' => 'Infeksi Saluran Kemih (ISK)',                  'kategori' => 'Urologi'],
    ['kode' => 'L30.9', 'nama_penyakit' => 'Dermatitis',                                   'kategori' => 'Dermatologi'],
    ['kode' => 'M54.5', 'nama_penyakit' => 'Low Back Pain (Nyeri Punggung Bawah)',         'kategori' => 'Muskuloskeletal'],
    ['kode' => 'B82.9', 'nama_penyakit' => 'Helminthiasis (Cacingan)',                     'kategori' => 'Infeksi'],
    ['kode' => 'H10.9', 'nama_penyakit' => 'Konjungtivitis',                               'kategori' => 'Mata'],
    ['kode' => 'K04.7', 'nama_penyakit' => 'Abses Periapikal (Gigi)',                      'kategori' => 'Gigi & Mulut'],
    ['kode' => 'G43.9', 'nama_penyakit' => 'Migrain',                                      'kategori' => 'Neurologi'],
    ['kode' => 'J45.9', 'nama_penyakit' => 'Asma',                                         'kategori' => 'Respirasi'],
    ['kode' => 'E78.5', 'nama_penyakit' => 'Hiperlipidemia',                               'kategori' => 'Endokrin'],
    ['kode' => 'Z00.0', 'nama_penyakit' => 'Pemeriksaan Umum (General Check-up)',          'kategori' => 'Pemeriksaan'],
];

$icd10Count = 0;
foreach ($icd10 as $code) {
    $exists = DB::table('icd10_codes')->where('kode', $code['kode'])->exists();
    if (! $exists) {
        DB::table('icd10_codes')->insert($code);
        $icd10Count++;
    }
}
info("  → {$icd10Count} ICD-10 codes created.");

// ══════════════════════════════════════════════════════════════
// 6. MEDICINES (15 obat umum)
// ══════════════════════════════════════════════════════════════
info('Seeding medicines...');

$medicines = [
    ['kode_obat' => 'OBT001', 'nama_obat' => 'Paracetamol 500mg',            'nama_generik' => 'Paracetamol',         'satuan' => 'Tablet',  'kategori' => 'Analgesik',          'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Bebas',          'harga_jual' => 500],
    ['kode_obat' => 'OBT002', 'nama_obat' => 'Amoxicillin 500mg',            'nama_generik' => 'Amoxicillin',         'satuan' => 'Kapsul',  'kategori' => 'Antibiotik',         'bentuk_sediaan' => 'Kapsul',       'golongan_obat' => 'Keras',          'harga_jual' => 1500],
    ['kode_obat' => 'OBT003', 'nama_obat' => 'Omeprazole 20mg',              'nama_generik' => 'Omeprazole',          'satuan' => 'Kapsul',  'kategori' => 'Antasida',           'bentuk_sediaan' => 'Kapsul',       'golongan_obat' => 'Keras',          'harga_jual' => 2000],
    ['kode_obat' => 'OBT004', 'nama_obat' => 'Antasida DOEN',                'nama_generik' => 'Al-Mg Hidroksida',    'satuan' => 'Tablet',  'kategori' => 'Antasida',           'bentuk_sediaan' => 'Tablet Kunyah','golongan_obat' => 'Bebas',          'harga_jual' => 300],
    ['kode_obat' => 'OBT005', 'nama_obat' => 'Metformin 500mg',              'nama_generik' => 'Metformin',           'satuan' => 'Tablet',  'kategori' => 'Antidiabetes',       'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Keras',          'harga_jual' => 800],
    ['kode_obat' => 'OBT006', 'nama_obat' => 'Amlodipine 5mg',               'nama_generik' => 'Amlodipine',          'satuan' => 'Tablet',  'kategori' => 'Antihipertensi',     'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Keras',          'harga_jual' => 1200],
    ['kode_obat' => 'OBT007', 'nama_obat' => 'Cetirizine 10mg',              'nama_generik' => 'Cetirizine',          'satuan' => 'Tablet',  'kategori' => 'Antihistamin',       'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Bebas Terbatas', 'harga_jual' => 700],
    ['kode_obat' => 'OBT008', 'nama_obat' => 'Ibuprofen 400mg',              'nama_generik' => 'Ibuprofen',           'satuan' => 'Tablet',  'kategori' => 'NSAID',              'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Bebas Terbatas', 'harga_jual' => 600],
    ['kode_obat' => 'OBT009', 'nama_obat' => 'Salbutamol 2mg',               'nama_generik' => 'Salbutamol',          'satuan' => 'Tablet',  'kategori' => 'Bronkodilator',      'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Keras',          'harga_jual' => 400],
    ['kode_obat' => 'OBT010', 'nama_obat' => 'Dexamethasone 0.5mg',          'nama_generik' => 'Dexamethasone',       'satuan' => 'Tablet',  'kategori' => 'Kortikosteroid',     'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Keras',          'harga_jual' => 500],
    ['kode_obat' => 'OBT011', 'nama_obat' => 'Oralit',                       'nama_generik' => 'Garam Rehidrasi Oral','satuan' => 'Sachet',  'kategori' => 'Rehidrasi',          'bentuk_sediaan' => 'Serbuk',       'golongan_obat' => 'Bebas',          'harga_jual' => 1000],
    ['kode_obat' => 'OBT012', 'nama_obat' => 'Domperidone 10mg',             'nama_generik' => 'Domperidone',         'satuan' => 'Tablet',  'kategori' => 'Antiemetik',         'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Keras',          'harga_jual' => 900],
    ['kode_obat' => 'OBT013', 'nama_obat' => 'Ciprofloxacin 500mg',          'nama_generik' => 'Ciprofloxacin',       'satuan' => 'Tablet',  'kategori' => 'Antibiotik',         'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Keras',          'harga_jual' => 2500],
    ['kode_obat' => 'OBT014', 'nama_obat' => 'Chloramphenicol Tetes Mata',   'nama_generik' => 'Chloramphenicol',     'satuan' => 'Botol',   'kategori' => 'Antibiotik Topikal', 'bentuk_sediaan' => 'Tetes Mata',   'golongan_obat' => 'Keras',          'harga_jual' => 8000],
    ['kode_obat' => 'OBT015', 'nama_obat' => 'Mebendazole 500mg',            'nama_generik' => 'Mebendazole',         'satuan' => 'Tablet',  'kategori' => 'Antihelmintik',      'bentuk_sediaan' => 'Tablet',       'golongan_obat' => 'Bebas Terbatas', 'harga_jual' => 3000],
];

$medicineIds = [];
foreach ($medicines as $med) {
    $existing = DB::table('medicines')->where('kode_obat', $med['kode_obat'])->first();
    if (! $existing) {
        $id = DB::table('medicines')->insertGetId(array_merge($med, [
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $medicineIds[$med['kode_obat']] = $id;
        info("  → Medicine '{$med['nama_obat']}' created (ID: {$id}).");
    } else {
        $medicineIds[$med['kode_obat']] = $existing->id;
        info("  → Medicine '{$med['nama_obat']}' already exists (ID: {$existing->id}), skipped.");
    }
}

// ══════════════════════════════════════════════════════════════
// 7. MEDICINE STOCKS
// ══════════════════════════════════════════════════════════════
info('Seeding medicine stocks...');

$stockCount = 0;
foreach ($medicineIds as $kode => $medId) {
    $exists = DB::table('medicine_stocks')->where('medicine_id', $medId)->exists();
    if (! $exists) {
        DB::table('medicine_stocks')->insert([
            'medicine_id'   => $medId,
            'stok_tersedia' => 100,
            'stok_minimum'  => 20,
            'no_batch'      => 'BATCH-2026-001',
            'updated_at'    => now(),
        ]);
        $stockCount++;
    }
}
info("  → {$stockCount} medicine stocks created.");

// ══════════════════════════════════════════════════════════════
// 8. SERVICE RATES (10 tarif layanan)
// ══════════════════════════════════════════════════════════════
info('Seeding service rates...');

$serviceRates = [
    ['kode_tarif' => 'KON001', 'nama_layanan' => 'Konsultasi Dokter Umum',       'kategori' => 'Konsultasi',    'harga_umum' => 50000,  'harga_bpjs' => 25000],
    ['kode_tarif' => 'KON002', 'nama_layanan' => 'Konsultasi Dokter Spesialis',  'kategori' => 'Konsultasi',    'harga_umum' => 100000, 'harga_bpjs' => 50000],
    ['kode_tarif' => 'ADM001', 'nama_layanan' => 'Administrasi Rawat Jalan',     'kategori' => 'Administrasi',  'harga_umum' => 15000,  'harga_bpjs' => 10000],
    ['kode_tarif' => 'LAB001', 'nama_layanan' => 'Pemeriksaan Darah Lengkap',    'kategori' => 'Laboratorium',  'harga_umum' => 75000,  'harga_bpjs' => 50000],
    ['kode_tarif' => 'LAB002', 'nama_layanan' => 'Pemeriksaan Gula Darah',       'kategori' => 'Laboratorium',  'harga_umum' => 35000,  'harga_bpjs' => 25000],
    ['kode_tarif' => 'LAB003', 'nama_layanan' => 'Pemeriksaan Urin Lengkap',     'kategori' => 'Laboratorium',  'harga_umum' => 50000,  'harga_bpjs' => 35000],
    ['kode_tarif' => 'RAD001', 'nama_layanan' => 'Rontgen Thorax',               'kategori' => 'Radiologi',     'harga_umum' => 150000, 'harga_bpjs' => 100000],
    ['kode_tarif' => 'RAD002', 'nama_layanan' => 'USG Abdomen',                  'kategori' => 'Radiologi',     'harga_umum' => 250000, 'harga_bpjs' => 175000],
    ['kode_tarif' => 'TIN001', 'nama_layanan' => 'Tindakan Jahit Luka',          'kategori' => 'Tindakan',      'harga_umum' => 100000, 'harga_bpjs' => 75000],
    ['kode_tarif' => 'TIN002', 'nama_layanan' => 'Perawatan Luka',               'kategori' => 'Tindakan',      'harga_umum' => 50000,  'harga_bpjs' => 35000],
];

$rateCount = 0;
foreach ($serviceRates as $rate) {
    $exists = DB::table('service_rates')->where('kode_tarif', $rate['kode_tarif'])->exists();
    if (! $exists) {
        DB::table('service_rates')->insert(array_merge($rate, [
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $rateCount++;
    }
}
info("  → {$rateCount} service rates created.");

// ══════════════════════════════════════════════════════════════
info('');
info('🎉 Seeding selesai! Semua data master telah dimasukkan.');
