<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';

    if (!is_allowed_email($email)) {
        $error = 'Only @vossie.net email addresses are allowed.';
    } elseif ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            login_user($user);
            $back = $_GET['back'] ?? '/index.php';
            // Prevent open redirect
            $back = (str_starts_with($back, '/') && !str_starts_with($back, '//')) ? $back : '/index.php';
            header("Location: $back");
            exit;
        }
    }
}

$pageTitle = 'Login – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-16">
    <div class="card p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
            <p class="text-sm text-gray-500 mt-1">Sign in with your <strong>@vossie.net</strong> account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-5">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login.php<?= isset($_GET['back']) ? '?back=' . urlencode($_GET['back']) : '' ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="you@vossie.net"
                       class="form-input"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••"
                       class="form-input">
            </div>

            <button type="submit" class="btn btn-primary w-full py-3 mt-1">Sign in</button>
        </form>

        <hr class="divider">
        <p class="text-sm text-center text-gray-500">
            Don't have an account?
            <a href="/register.php" class="text-blue-600 font-medium hover:underline">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
