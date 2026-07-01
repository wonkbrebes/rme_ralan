<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function testRoute($path) {
    global $app;
    $req = \Illuminate\Http\Request::create($path, 'GET');
    $res = $app->handle($req);
    echo "=== GET {$path} (Status: " . $res->getStatusCode() . ") ===\n";
    echo substr($res->getContent(), 0, 300) . "\n\n";
}

testRoute('/api/ping');
testRoute('/api/public/profil-rs');
testRoute('/api/public/statistik');
testRoute('/api/public/jadwal-dokter?hari=semua');
testRoute('/api/public/antrian-live');
testRoute('/api/notifikasi/unread-count');
testRoute('/api/settings');
testRoute('/');
testRoute('/login');
testRoute('/app');

testRoute('/api/notifikasi/unread-count');
testRoute('/api/notifikasi');

