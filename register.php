<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error  = '';
$fields = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fields['name']     = trim($_POST['name']     ?? '');
    $fields['email']    = strtolower(trim($_POST['email']    ?? ''));
    $fields['phone']    = trim($_POST['phone']    ?? '');
    $password           = $_POST['password']  ?? '';
    $password2          = $_POST['password2'] ?? '';

    // Validate
    if ($fields['name'] === '') {
        $error = 'Full name is required.';
    } elseif (!is_allowed_email($fields['email'])) {
        $error = 'Only @vossie.net email addresses are allowed.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute([':email' => $fields['email']]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash    = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $isAdmin = ($fields['email'] === strtolower(ADMIN_EMAIL)) ? 1 : 0;

            $ins = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, phone, is_admin)
                VALUES (:name, :email, :hash, :phone, :is_admin)
            ");
            $ins->execute([
                ':name'     => $fields['name'],
                ':email'    => $fields['email'],
                ':hash'     => $hash,
                ':phone'    => $fields['phone'],
                ':is_admin' => $isAdmin,
            ]);

            $user = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $user->execute([':id' => $pdo->lastInsertId()]);
            login_user($user->fetch());

            header('Location: /index.php');
            exit;
        }
    }
}

$pageTitle = 'Register – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-16">
    <div class="card p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
            <p class="text-sm text-gray-500 mt-1">Eduvos students only — <strong>@vossie.net</strong> required</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-5">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="name">Full name</label>
                <input type="text" id="name" name="name" required autofocus
                       placeholder="Your name"
                       class="form-input"
                       value="<?= htmlspecialchars($fields['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Eduvos email</label>
                <input type="email" id="email" name="email" required
                       placeholder="you@vossie.net"
                       class="form-input"
                       value="<?= htmlspecialchars($fields['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone / WhatsApp <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="tel" id="phone" name="phone"
                       placeholder="+27 81 234 5678"
                       class="form-input"
                       value="<?= htmlspecialchars($fields['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Min. 8 characters"
                       class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label" for="password2">Confirm password</label>
                <input type="password" id="password2" name="password2" required
                       placeholder="Repeat password"
                       class="form-input">
            </div>

            <button type="submit" class="btn btn-primary w-full py-3 mt-1">Create account</button>
        </form>

        <hr class="divider">
        <p class="text-sm text-center text-gray-500">
            Already have an account?
            <a href="/login.php" class="text-blue-600 font-medium hover:underline">Sign in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
