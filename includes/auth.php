<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true when behind HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- CSRF helpers ---

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('Invalid request.');
    }
}

// --- Domain check ---

function is_allowed_email(string $email): bool {
    return str_ends_with(strtolower(trim($email)), strtolower(ALLOWED_DOMAIN));
}

// --- Session helpers ---

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function is_admin(): bool {
    return !empty($_SESSION['user']['is_admin']);
}

// --- Guards ---

function require_login(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        $back = urlencode($_SERVER['REQUEST_URI']);
        header("Location: {$redirect}?back={$back}");
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied.');
    }
}

// --- Login / Logout ---

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = $user;
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}
