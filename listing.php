<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT l.*, u.name AS seller_name, u.email AS seller_email
    FROM listings l
    JOIN users u ON u.id = l.user_id
    WHERE l.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$listing = $stmt->fetch();

if (!$listing) {
    http_response_code(404);
    $pageTitle = 'Not Found – ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="max-w-xl mx-auto py-20 text-center text-gray-400">
        <p class="text-4xl mb-4">😕</p>
        <p class="font-medium">Listing not found.</p>
        <a href="/index.php" class="btn btn-primary mt-6">Back to home</a>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Handle status change by seller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    csrf_verify();

    $action = $_POST['action'] ?? '';

    // Only the seller can update status (except confirm-transaction handles completed)
    if ((int)$listing['user_id'] === current_user_id()) {
        if ($action === 'set_pending' && $listing['status'] === 'available') {
            $pdo->prepare("UPDATE listings SET status = 'pending' WHERE id = :id")
                ->execute([':id' => $id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Listing marked as Pending.'];
        } elseif ($action === 'set_available' && $listing['status'] === 'pending') {
            $pdo->prepare("UPDATE listings SET status = 'available' WHERE id = :id")
                ->execute([':id' => $id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Listing is Available again.'];
        }
        header("Location: /listing.php?id=$id");
        exit;
    }
}

$isOwner = is_logged_in() && (int)$listing['user_id'] === current_user_id();

$statusClasses = [
    'available' => 'badge-available',
    'pending'   => 'badge-pending',
    'completed' => 'badge-completed',
];

$pageTitle = htmlspecialchars($listing['title']) . ' – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-10">

    <!-- Breadcrumb -->
    <nav class="text-sm text-gray-500 mb-6">
        <a href="/index.php" class="hover:text-blue-600">Home</a>
        <span class="mx-2">›</span>
        <a href="/index.php?category=<?= urlencode($listing['category']) ?>"
           class="hover:text-blue-600"><?= htmlspecialchars($listing['category']) ?></a>
        <span class="mx-2">›</span>
        <span class="text-gray-800"><?= htmlspecialchars($listing['title']) ?></span>
    </nav>

    <div class="flex flex-col md:flex-row gap-8">

        <!-- Image -->
        <div class="md:w-1/2">
            <?php if ($listing['image']): ?>
                <img src="/uploads/<?= htmlspecialchars($listing['image']) ?>"
                     alt="<?= htmlspecialchars($listing['title']) ?>"
                     class="w-full rounded-card object-cover max-h-[420px]">
            <?php else: ?>
                <div class="w-full rounded-card bg-gray-100 flex items-center justify-center"
                     style="min-height:280px; font-size:5rem;">📦</div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="flex-1 flex flex-col gap-4">

            <div>
                <span class="badge <?= $statusClasses[$listing['status']] ?? '' ?> mb-2">
                    <?= htmlspecialchars($listing['status']) ?>
                </span>
                <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($listing['title']) ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($listing['category']) ?></p>
            </div>

            <!-- Price -->
            <div>
                <?php if ($listing['price_type'] === 'wanted'): ?>
                    <span class="price-wanted text-base">Wanted</span>
                    <p class="text-sm text-gray-400 mt-1">Buyer is looking for this item.</p>
                <?php elseif ($listing['price'] !== null): ?>
                    <p class="text-3xl font-bold text-blue-600">
                        R <?= number_format((float)$listing['price'], 2) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($listing['description']): ?>
                <div>
                    <h2 class="font-semibold text-sm text-gray-700 mb-1">Description</h2>
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">
                        <?= htmlspecialchars($listing['description']) ?>
                    </p>
                </div>
            <?php endif; ?>

            <hr class="divider">

            <!-- Seller info -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center
                            text-blue-700 font-bold text-sm shrink-0">
                    <?= strtoupper(substr($listing['seller_name'], 0, 1)) ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($listing['seller_name']) ?></p>
                    <p class="text-xs text-gray-400">Listed <?= date('d M Y', strtotime($listing['created_at'])) ?></p>
                </div>
            </div>

            <!-- Contact -->
            <?php if ($listing['phone'] && is_logged_in()): ?>
                <div class="flex gap-3">
                    <?php
                    $rawPhone = preg_replace('/\D/', '', $listing['phone']);
                    $waNumber = ltrim($rawPhone, '0');
                    if (strlen($waNumber) <= 10) $waNumber = '27' . $waNumber;
                    ?>
                    <a href="tel:<?= htmlspecialchars($listing['phone']) ?>"
                       class="btn btn-outline btn-sm">
                        📞 Call
                    </a>
                    <a href="https://wa.me/<?= htmlspecialchars($waNumber) ?>"
                       target="_blank" rel="noopener"
                       class="btn btn-primary btn-sm">
                        💬 WhatsApp
                    </a>
                </div>
            <?php elseif (!is_logged_in()): ?>
                <a href="/login.php?back=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   class="btn btn-primary btn-sm w-fit">
                    Login to contact seller
                </a>
            <?php endif; ?>

            <!-- Seller controls: pending toggle -->
            <?php if ($isOwner && $listing['status'] !== 'completed'): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-2">
                    <p class="text-sm font-semibold text-blue-800 mb-2">Your listing</p>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <?php if ($listing['status'] === 'available'): ?>
                            <button name="action" value="set_pending"
                                    class="btn btn-outline btn-sm">
                                Mark as Pending
                            </button>
                        <?php elseif ($listing['status'] === 'pending'): ?>
                            <button name="action" value="set_available"
                                    class="btn btn-outline btn-sm">
                                Mark as Available again
                            </button>
                        <?php endif; ?>
                        <a href="/my-listings.php" class="btn btn-outline btn-sm ml-2">Manage listings</a>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- QR Code (seller only, non-completed) -->
    <?php if ($isOwner && $listing['status'] !== 'completed'): ?>
        <div class="mt-10 card p-6">
            <h2 class="font-bold text-gray-900 text-lg mb-1">Transaction QR Code</h2>
            <p class="text-sm text-gray-500 mb-4">
                Show this QR to the buyer when you meet in person. They scan it to confirm they received the item.
            </p>
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="qr-box">
                    <img src="<?= htmlspecialchars(qr_image_url($listing['qr_token'])) ?>"
                         alt="QR Code"
                         width="200" height="200">
                </div>
                <div class="text-sm text-gray-600 space-y-1">
                    <p>1. Meet the buyer in person.</p>
                    <p>2. Show them this QR code.</p>
                    <p>3. They scan and confirm receipt.</p>
                    <p>4. Listing will be marked <strong>Completed</strong>.</p>
                    <p class="text-xs text-gray-400 pt-2">Token: <code><?= htmlspecialchars($listing['qr_token']) ?></code></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Transaction confirmed banner -->
    <?php if ($listing['status'] === 'completed'): ?>
        <?php
        $txStmt = $pdo->prepare("
            SELECT t.*, u.name AS buyer_name
            FROM transactions t
            JOIN users u ON u.id = t.buyer_id
            WHERE t.listing_id = :lid
            ORDER BY t.confirmed_at DESC LIMIT 1
        ");
        $txStmt->execute([':lid' => $listing['id']]);
        $tx = $txStmt->fetch();
        ?>
        <div class="mt-8 bg-indigo-50 border border-indigo-200 rounded-xl p-5 flex gap-4 items-center">
            <span class="text-3xl">✅</span>
            <div>
                <p class="font-semibold text-indigo-900">Transaction completed</p>
                <?php if ($tx): ?>
                    <p class="text-sm text-indigo-700">
                        Received by <strong><?= htmlspecialchars($tx['buyer_name']) ?></strong>
                        on <?= date('d M Y \a\t H:i', strtotime($tx['confirmed_at'])) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
