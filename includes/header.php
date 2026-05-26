<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// --- Global auth wall ---
// Only login.php and register.php are accessible without a session.
$_publicPaths = ['/login.php', '/register.php'];
$_currentURI  = $_SERVER['REQUEST_URI'] ?? '/';
$_currentPath = parse_url($_currentURI, PHP_URL_PATH);
if (!is_logged_in() && !in_array($_currentPath, $_publicPaths, true)) {
    header('Location: /login.php?back=' . urlencode($_currentURI));
    exit;
}

$_currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            blue:   '#2563eb',
                            yellow: '#facc15',
                            dark:   '#1e3a8a',
                        }
                    },
                    borderRadius: {
                        card: '0.75rem',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

<!-- Top Nav -->
<header class="bg-brand-dark shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">

        <!-- Logo -->
        <a href="/index.php" class="text-white font-bold text-xl tracking-tight shrink-0">
            <span class="text-brand-yellow">Eduvos</span>Market
        </a>

        <!-- Search bar -->
        <form method="GET" action="/index.php" class="flex-1 flex gap-2 max-w-2xl">
            <input
                type="text"
                name="q"
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                placeholder="Search listings…"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm
                       shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-yellow"
            >
            <button type="submit"
                class="bg-brand-yellow text-gray-900 font-semibold px-5 py-2 rounded-lg
                       text-sm hover:bg-yellow-300 transition-colors shrink-0">
                Search
            </button>
        </form>

        <!-- Nav links -->
        <nav class="flex items-center gap-3 shrink-0">
            <?php if ($_currentUser): ?>
                <a href="/create-listing.php"
                   class="bg-brand-yellow text-gray-900 font-semibold px-4 py-2 rounded-lg
                          text-sm hover:bg-yellow-300 transition-colors">
                    + Sell
                </a>
                <a href="/my-listings.php"
                   class="text-gray-200 hover:text-white text-sm transition-colors">
                    My Listings
                </a>
                <?php if (!empty($_currentUser['is_admin'])): ?>
                    <a href="/admin.php"
                       class="text-red-300 hover:text-red-100 text-sm transition-colors">
                        Admin
                    </a>
                <?php endif; ?>
                <a href="/logout.php"
                   class="text-gray-400 hover:text-gray-200 text-sm transition-colors">
                    Logout
                </a>
            <?php else: ?>
                <a href="/login.php"
                   class="text-gray-200 hover:text-white text-sm transition-colors">
                    Login
                </a>
                <a href="/register.php"
                   class="bg-brand-yellow text-gray-900 font-semibold px-4 py-2 rounded-lg
                          text-sm hover:bg-yellow-300 transition-colors">
                    Register
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- Flash message -->
<?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="max-w-7xl mx-auto w-full px-4 pt-4">
        <div class="rounded-lg px-4 py-3 text-sm font-medium
            <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200'
                                           : 'bg-green-50 text-green-700 border border-green-200' ?>">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    </div>
<?php endif; ?>

<main class="flex-1">
