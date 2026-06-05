<?php
require_once __DIR__ . '/config.php';

$driver = strtolower(trim((string)(getenv('DB_DRIVER') ?: 'mysql')));

/**
 * Build a PDO connection to SQLite and bootstrap schema if needed.
 */
function connect_sqlite(): PDO {
    $sqlitePath = (string)(getenv('SQLITE_PATH') ?: (__DIR__ . '/../storage/app.sqlite'));
    $sqliteDir = dirname($sqlitePath);

    if (!is_dir($sqliteDir)) {
        mkdir($sqliteDir, 0755, true);
    }

    $pdo = new PDO(
        'sqlite:' . $sqlitePath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    $pdo->exec('PRAGMA foreign_keys = ON');

    $schemaFile = __DIR__ . '/../sql/schema.sqlite.sql';
    if (is_file($schemaFile)) {
        $schemaSql = file_get_contents($schemaFile);
        if ($schemaSql !== false && trim($schemaSql) !== '') {
            $pdo->exec($schemaSql);
        }
    }

    return $pdo;
}

if ($driver === 'sqlite') {
    try {
        $pdo = connect_sqlite();
        return;
    } catch (PDOException $e) {
        http_response_code(500);
        die('Database connection failed.');
    }
}

$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'eduvos_market';
$user = getenv('DB_USER') ?: 'market_user';
$pass = getenv('DB_PASS') ?: 'market_pass';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$name;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    $allowSqliteFallback = (string)(getenv('DB_FALLBACK_SQLITE') ?: '0') === '1';
    if ($allowSqliteFallback) {
        try {
            $pdo = connect_sqlite();
        } catch (PDOException $inner) {
            http_response_code(500);
            die('Database connection failed.');
        }
    } else {
        http_response_code(500);
        die('Database connection failed.');
    }
}
