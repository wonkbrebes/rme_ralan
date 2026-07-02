<?php
/**
 * DB Helper - Koneksi Real-time ke Supabase PostgreSQL
 * DIGUNAKAN UNTUK SIMRS RAWAT JALAN & RME (wonkbrebes.web.id)
 */

function get_db_connection() {
    static $pdo = null;
    static $conn_attempted = false;
    static $last_error = null;

    if ($pdo === null && !$conn_attempted) {
        $conn_attempted = true;
        if (!extension_loaded('pdo_pgsql')) {
            $last_error = "Ekstensi PDO PostgreSQL (pdo_pgsql) belum aktif di PHP Anda. Gunakan perintah terminal: php -d extension=pdo_pgsql -d extension=pgsql -S localhost:8000";
            throw new Exception($last_error);
        }
        // Coba load file .env jika ada (untuk fleksibilitas lokal & Vercel)
        if (file_exists(__DIR__ . '/.env')) {
            $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($key, $val) = explode('=', $line, 2) + [null, null];
                if ($key && $val !== null) {
                    putenv(trim($key) . "=" . trim($val));
                    $_ENV[trim($key)] = trim($val);
                }
            }
        }
        $host = getenv('DB_HOST') ?: (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '');
        $port = getenv('DB_PORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '6543');
        $db   = getenv('DB_DATABASE') ?: (isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'postgres');
        $user = getenv('DB_USERNAME') ?: (isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : '');
        $pass = getenv('DB_PASSWORD') ?: (isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '');
        
        if (empty($host) || empty($user) || empty($pass)) {
            $last_error = "Konfigurasi database belum diatur. Pastikan file .env sudah ada (untuk lokal) atau Environment Variables sudah diset di server deployment.";
            throw new Exception($last_error);
        }
        
        $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $last_error = "Koneksi Supabase Gagal: " . $e->getMessage();
            throw new Exception($last_error);
        }
    }
    if ($pdo === null && $last_error !== null) {
        throw new Exception($last_error);
    }
    return $pdo;
}

/**
 * Eksekusi query dengan parameter (Prepared Statement)
 */
function db_query($sql, $params = []) {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception("Koneksi database tidak tersedia.");
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Ambil banyak baris data (Select All)
 */
function db_select($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Ambil 1 baris data (Select One)
 */
function db_select_one($sql, $params = []) {
    $stmt = db_query($sql, $params);
    $row = $stmt->fetch();
    return $row ? $row : null;
}

/**
 * Insert data ke dalam tabel (Associative Array)
 */
function db_insert($table, $data) {
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    db_query($sql, $data);
    return true;
}

/**
 * Update data pada tabel
 */
function db_update($table, $data, $whereClause, $whereParams = []) {
    $setParts = [];
    foreach (array_keys($data) as $col) {
        $setParts[] = "$col = :val_$col";
    }
    $setString = implode(', ', $setParts);
    
    $params = [];
    foreach ($data as $col => $val) {
        $params["val_$col"] = $val;
    }
    foreach ($whereParams as $k => $v) {
        $params[$k] = $v;
    }
    
    $sql = "UPDATE $table SET $setString WHERE $whereClause";
    db_query($sql, $params);
    return true;
}

/**
 * Delete data dari tabel
 */
function db_delete($table, $whereClause, $whereParams = []) {
    $sql = "DELETE FROM $table WHERE $whereClause";
    db_query($sql, $whereParams);
    return true;
}
