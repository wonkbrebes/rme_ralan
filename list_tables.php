<?php
require_once __DIR__ . '/db_helper.php';
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name");
    echo "=== ACTIVE TABLES IN DATABASE ===\n";
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $r['table_name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
