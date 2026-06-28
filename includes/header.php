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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { blue: '#2563eb', yellow: '#facc15', dark: '#1e3a8a' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col font-sans">

<!-- Top Nav -->
<header class="bg-brand-dark shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between gap-6">

        <!-- Logo -->
        <a href="/index.php" class="text-white font-bold text-xl tracking-tight shrink-0 flex items-center gap-1">
            <span class="text-brand-yellow">Vossie</span><span class="font-light opacity-90">Trading Hall</span>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden md:flex items-center gap-1 ml-auto">
            <a href="/index.php"
               class="nav-link <?= (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/index.php' || parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/') ? 'nav-link-active' : '' ?>">
               Browse
            </a>
            <a href="/books.php"
               class="nav-link <?= parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/books.php' ? 'nav-link-active' : '' ?>">
               Books
            </a>
            <?php if ($_currentUser): ?>
                <a href="/create-listing.php"
                   class="ml-1 bg-brand-yellow text-gray-900 font-semibold px-4 py-1.5 rounded-lg
                          text-sm hover:bg-yellow-300 transition-colors">
                    + Sell
                </a>
                <a href="/scan-transaction.php" class="nav-link">Scan QR</a>
                <a href="/my-listings.php" class="nav-link">My Listings</a>
                <?php if (!empty($_currentUser['is_admin'])): ?>
                    <a href="/admin.php" class="nav-link text-red-300 hover:text-red-100">Admin</a>
                <?php endif; ?>

                <!-- Avatar dropdown -->
                <div class="relative ml-1" id="avatar-menu">
                    <button onclick="toggleAvatarMenu()"
                            class="w-8 h-8 rounded-full bg-brand-yellow text-gray-900 font-bold text-sm
                                   flex items-center justify-center ring-2 ring-transparent
                                   hover:ring-yellow-300 transition-all select-none cursor-pointer">
                        <?= strtoupper(substr($_currentUser['name'], 0, 1)) ?>
                    </button>
                    <div id="avatar-dropdown"
                         class="hidden absolute right-0 top-11 bg-white rounded-xl shadow-lg
                                border border-gray-100 py-1 w-48 z-50">
                        <div class="px-4 py-2.5 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($_currentUser['name']) ?></p>
                            <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($_currentUser['email']) ?></p>
                        </div>
                        <a href="/my-listings.php" class="dropdown-item">My Listings</a>
                        <a href="/logout.php" class="dropdown-item text-red-600 hover:bg-red-50">Sign out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login.php" class="nav-link">Login</a>
                <a href="/register.php"
                   class="ml-1 bg-brand-yellow text-gray-900 font-semibold px-4 py-1.5 rounded-lg
                          text-sm hover:bg-yellow-300 transition-colors">
                    Register
                </a>
            <?php endif; ?>
        </nav>

        <!-- Mobile: right side -->
        <div class="md:hidden flex items-center gap-2">
            <?php if ($_currentUser): ?>
                <a href="/create-listing.php"
                   class="bg-brand-yellow text-gray-900 font-semibold px-3 py-1.5 rounded-lg text-sm">
                    + Sell
                </a>
            <?php endif; ?>
            <button id="mobile-menu-btn" onclick="toggleMobileMenu()"
                    class="text-white p-1.5 rounded-lg hover:bg-blue-800 transition-colors">
                <svg id="menu-icon-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="menu-icon-close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-blue-900 border-t border-blue-800 px-4 py-3 space-y-1">
        <a href="/index.php" class="mobile-nav-link">Browse Listings</a>
        <a href="/books.php" class="mobile-nav-link">Books</a>
        <?php if ($_currentUser): ?>
            <a href="/scan-transaction.php" class="mobile-nav-link">Scan QR</a>
            <a href="/my-listings.php" class="mobile-nav-link">My Listings</a>
            <?php if (!empty($_currentUser['is_admin'])): ?>
                <a href="/admin.php" class="mobile-nav-link text-red-300">Admin Panel</a>
            <?php endif; ?>
            <div class="border-t border-blue-800 pt-2 mt-2">
                <p class="text-xs text-blue-300 mb-1 px-3">Signed in as <?= htmlspecialchars($_currentUser['name']) ?></p>
                <a href="/logout.php" class="mobile-nav-link text-red-300">Sign out</a>
            </div>
        <?php else: ?>
            <a href="/login.php" class="mobile-nav-link">Login</a>
            <a href="/register.php" class="mobile-nav-link">Register</a>
        <?php endif; ?>
    </div>
</header>

<!-- Flash message -->
<?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div data-flash class="max-w-7xl mx-auto w-full px-4 pt-3">
        <div class="rounded-lg px-4 py-3 text-sm font-medium flex items-center gap-2
            <?= $flash['type'] === 'error'
                ? 'bg-red-50 text-red-700 border border-red-200'
                : 'bg-green-50 text-green-700 border border-green-200' ?>">
            <span><?= $flash['type'] === 'error' ? '⚠️' : '✓' ?></span>
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    </div>
<?php endif; ?>

<main class="flex-1">
