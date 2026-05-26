<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Must be logged in; preserve the return URL
require_login('/login.php');

$token   = trim($_GET['token'] ?? '');
$error   = '';
$listing = null;

if ($token === '') {
    $error = 'Invalid or missing transaction token.';
} else {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name AS seller_name
        FROM listings l
        JOIN users u ON u.id = l.user_id
        WHERE l.qr_token = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $listing = $stmt->fetch();

    if (!$listing) {
        $error = 'Transaction token not found.';
    } elseif ($listing['status'] === 'completed') {
        $error = 'This transaction has already been completed.';
    } elseif ((int)$listing['user_id'] === current_user_id()) {
        $error = 'You cannot confirm receipt of your own listing.';
    }
}

// Handle confirmation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $listing) {
    csrf_verify();

    if (($_POST['action'] ?? '') === 'confirm') {
        // Double-check listing is still not completed (race condition guard)
        $check = $pdo->prepare("SELECT status FROM listings WHERE id = :id LIMIT 1");
        $check->execute([':id' => $listing['id']]);
        $fresh = $check->fetch();

        if ($fresh && $fresh['status'] !== 'completed') {
            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare("
                    INSERT INTO transactions (listing_id, seller_id, buyer_id, qr_token)
                    VALUES (:listing_id, :seller_id, :buyer_id, :qr_token)
                ");
                $ins->execute([
                    ':listing_id' => $listing['id'],
                    ':seller_id'  => $listing['user_id'],
                    ':buyer_id'   => current_user_id(),
                    ':qr_token'   => $listing['qr_token'],
                ]);

                $pdo->prepare("UPDATE listings SET status = 'completed' WHERE id = :id")
                    ->execute([':id' => $listing['id']]);

                $pdo->commit();

                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Transaction confirmed! Listing marked as completed.'];
                header('Location: /listing.php?id=' . $listing['id']);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Something went wrong. Please try again.';
            }
        } else {
            $error = 'This transaction was already completed.';
        }
    }
}

$pageTitle = 'Confirm Transaction – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-lg mx-auto px-4 py-16">

    <?php if ($error): ?>
        <div class="card p-8 text-center">
            <div class="text-4xl mb-4">❌</div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Cannot confirm transaction</h1>
            <p class="text-sm text-gray-500 mb-6"><?= htmlspecialchars($error) ?></p>
            <a href="/index.php" class="btn btn-primary">Back to home</a>
        </div>

    <?php elseif ($listing): ?>
        <div class="card p-8">
            <div class="text-center mb-6">
                <div class="text-4xl mb-2">📦</div>
                <h1 class="text-xl font-bold text-gray-900">Confirm you received this item</h1>
                <p class="text-sm text-gray-500 mt-1">This action cannot be undone.</p>
            </div>

            <!-- Listing summary -->
            <div class="bg-gray-50 rounded-xl p-4 flex gap-4 items-center mb-6">
                <?php if ($listing['image']): ?>
                    <img src="/uploads/<?= htmlspecialchars($listing['image']) ?>"
                         alt="" class="w-16 h-16 rounded-lg object-cover shrink-0">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center text-2xl shrink-0">📦</div>
                <?php endif; ?>
                <div>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($listing['title']) ?></p>
                    <p class="text-sm text-gray-500">Seller: <?= htmlspecialchars($listing['seller_name']) ?></p>
                    <?php if ($listing['price_type'] !== 'wanted' && $listing['price'] !== null): ?>
                        <p class="text-sm font-bold text-blue-600">R <?= number_format((float)$listing['price'], 2) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                By clicking <strong>Confirm</strong>, you confirm that you physically received
                this item from the seller and the exchange is complete.
                The listing will be marked as <strong>Completed</strong>.
            </p>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="confirm">
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary flex-1 py-3">
                        ✅ I received this item
                    </button>
                    <a href="/index.php" class="btn btn-outline py-3">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
