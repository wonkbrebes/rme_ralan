<?php
require 'db_helper.php';
get_db_connection();
echo "JADWAL DOKTER DENGAN POLI:\n";
$jads = db_select('SELECT jd.jadwal_id, jd.dokter_id, d.nama_lengkap, jd.polyclinic_id, p.nama_poli, p.kode_poli, jd.hari, jd.jam_mulai, jd.jam_selesai FROM jadwal_dokter jd JOIN dokter d ON jd.dokter_id = d.dokter_id JOIN polyclinics p ON jd.polyclinic_id = p.id ORDER BY jd.jadwal_id');
foreach ($jads as $j) {
    echo "Jadwal ID: {$j['jadwal_id']} - Dok ID: {$j['dokter_id']} ({$j['nama_lengkap']}) - Poli ID: {$j['polyclinic_id']} ({$j['nama_poli']} - {$j['kode_poli']}) - Hari: {$j['hari']}\n";
}
