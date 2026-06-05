<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_login('/login.php');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /my-listings.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$listing = $stmt->fetch();

if (!$listing) {
    http_response_code(404);
    $pageTitle = 'Listing Not Found - ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="max-w-xl mx-auto py-20 text-center text-gray-400">'
       . '<p class="text-4xl mb-4">😕</p>'
       . '<p class="font-medium">Listing not found.</p>'
       . '<a href="/my-listings.php" class="btn btn-primary mt-6">Back to My Listings</a>'
       . '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$isOwner = (int)$listing['user_id'] === current_user_id();
if (!$isOwner) {
    http_response_code(403);
    $pageTitle = 'Access Denied - ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="max-w-xl mx-auto py-20 text-center text-gray-400">'
       . '<p class="text-4xl mb-4">⛔</p>'
       . '<p class="font-medium">You can only view QR codes for your own listings.</p>'
       . '<a href="/my-listings.php" class="btn btn-primary mt-6">Back to My Listings</a>'
       . '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

if (empty($listing['qr_token'])) {
    $newToken = generate_qr_token();
    $pdo->prepare("UPDATE listings SET qr_token = :token WHERE id = :id")
        ->execute([':token' => $newToken, ':id' => $listing['id']]);
    $listing['qr_token'] = $newToken;
}

$pageTitle = 'Listing QR - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-xl mx-auto px-4 py-8 sm:py-10">
    <div class="card p-5 sm:p-8 text-center">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">Transaction QR</h1>
        <p class="text-sm text-gray-500 mb-5"><?= htmlspecialchars($listing['title']) ?></p>

        <div class="inline-block p-3 bg-white border border-gray-200 rounded-2xl shadow-sm">
            <img src="<?= htmlspecialchars(qr_image_url($listing['qr_token'])) ?>"
                 alt="Transaction QR code"
                 class="w-64 h-64 sm:w-72 sm:h-72 object-contain">
        </div>

        <p class="text-sm text-gray-600 mt-5">
            Ask the buyer to scan this code and confirm receipt.
        </p>

        <div class="mt-6 flex justify-center gap-3">
            <a href="/my-listings.php" class="btn btn-outline btn-sm">Back</a>
            <a href="/listing.php?id=<?= $listing['id'] ?>" class="btn btn-primary btn-sm">Open listing</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
