<?php
require_once __DIR__ . '/config.php';

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
    http_response_code(500);
    die('Database connection failed.');
}
