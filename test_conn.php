<?php
$configs = [
    ['aws-0-ap-southeast-1.pooler.supabase.com', '6543', 'postgres.hzewijlfggyghkaqfqrc', 'rme_ramlan.123'],
    ['aws-0-ap-southeast-1.pooler.supabase.com', '5432', 'postgres.hzewijlfggyghkaqfqrc', 'rme_ramlan.123'],
    ['db.hzewijlfggyghkaqfqrc.supabase.co', '5432', 'postgres', 'rme_ramlan.123'],
];

foreach ($configs as $idx => $c) {
    echo "Testing #$idx: Host={$c[0]}, Port={$c[1]}, User={$c[2]} ... ";
    try {
        $pdo = new PDO("pgsql:host={$c[0]};port={$c[1]};dbname=postgres;sslmode=require", $c[2], $c[3], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "SUCCESS!\n";
        break;
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}
