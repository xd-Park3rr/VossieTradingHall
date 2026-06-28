<?php
require_once __DIR__ . '/config.php';

$driver = strtolower(trim((string)(getenv('DB_DRIVER') ?: 'mysql')));

function db_fail(PDOException $e): void {
    http_response_code(500);
    if ((string)(getenv('DB_DEBUG_DETAILS') ?: '0') === '1') {
        die('Database connection failed: ' . $e->getMessage());
    }
    die('Database connection failed.');
}

/**
 * Check whether a column exists on a table (driver-aware).
 * $table is only ever called with hard-coded names, never user input.
 */
function db_column_exists(PDO $pdo, string $driver, string $table, string $column): bool {
    if ($driver === 'sqlite') {
        foreach ($pdo->query('PRAGMA table_info(' . $table . ')') as $row) {
            if (strcasecmp((string)$row['name'], $column) === 0) {
                return true;
            }
        }
        return false;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Idempotently ensure newer tables/columns exist on already-created databases.
 * Schema files only run on a fresh DB, so this keeps existing installs in sync.
 * Safe to run on every request; only issues DDL when something is missing.
 */
function run_migrations(PDO $pdo, string $driver): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS promotions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                image TEXT,
                link_url TEXT,
                active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    } else {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS promotions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                description TEXT,
                image VARCHAR(255),
                link_url VARCHAR(255),
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    $columns = [
        'isbn'        => $driver === 'sqlite' ? 'TEXT' : 'VARCHAR(20)',
        'author'      => $driver === 'sqlite' ? 'TEXT' : 'VARCHAR(150)',
        'edition'     => $driver === 'sqlite' ? 'TEXT' : 'VARCHAR(60)',
        'is_business' => $driver === 'sqlite' ? 'INTEGER NOT NULL DEFAULT 0' : 'TINYINT(1) NOT NULL DEFAULT 0',
    ];
    foreach ($columns as $name => $type) {
        if (!db_column_exists($pdo, $driver, 'listings', $name)) {
            $pdo->exec('ALTER TABLE listings ADD COLUMN ' . $name . ' ' . $type);
        }
    }
}

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
        run_migrations($pdo, 'sqlite');
        return;
    } catch (PDOException $e) {
        db_fail($e);
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
    run_migrations($pdo, 'mysql');
} catch (PDOException $e) {
    $allowSqliteFallback = (string)(getenv('DB_FALLBACK_SQLITE') ?: '0') === '1';
    if ($allowSqliteFallback) {
        try {
            $pdo = connect_sqlite();
            run_migrations($pdo, 'sqlite');
        } catch (PDOException $inner) {
            db_fail($inner);
        }
    } else {
        db_fail($e);
    }
}
