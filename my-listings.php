<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

// Delete listing (owner or admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action     = $_POST['action'] ?? '';
    $listing_id = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT);

    if ($action === 'delete' && $listing_id) {
        // Verify ownership
        $chk = $pdo->prepare("SELECT id, image FROM listings WHERE id = :id AND user_id = :uid LIMIT 1");
        $chk->execute([':id' => $listing_id, ':uid' => current_user_id()]);
        $row = $chk->fetch();

        if ($row) {
            // Delete image file
            if ($row['image'] && file_exists(UPLOAD_DIR . $row['image'])) {
                unlink(UPLOAD_DIR . $row['image']);
            }
            $pdo->prepare("DELETE FROM listings WHERE id = :id")->execute([':id' => $listing_id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Listing deleted.'];
        }
    }

    header('Location: /my-listings.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM listings
    WHERE user_id = :uid
    ORDER BY created_at DESC
");
$stmt->execute([':uid' => current_user_id()]);
$listings = $stmt->fetchAll();

$pageTitle = 'My Listings – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">My Listings</h1>
        <a href="/create-listing.php" class="btn btn-yellow">+ New listing</a>
    </div>

    <?php if (empty($listings)): ?>
        <div class="card p-12 text-center text-gray-400">
            <div class="text-4xl mb-3">📭</div>
            <p class="font-medium">You haven't posted any listings yet.</p>
            <a href="/create-listing.php" class="btn btn-primary mt-5">Post your first listing</a>
        </div>
    <?php else: ?>
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Listing</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Category</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Price</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $item): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <?php if ($item['image']): ?>
                                        <img src="/uploads/<?= htmlspecialchars($item['image']) ?>"
                                             alt="" class="w-12 h-12 rounded-lg object-cover shrink-0">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center
                                                    justify-content-center text-xl text-gray-400 shrink-0
                                                    flex items-center justify-center">📦</div>
                                    <?php endif; ?>
                                    <a href="/listing.php?id=<?= $item['id'] ?>"
                                       class="font-medium text-gray-900 hover:text-blue-600 line-clamp-1">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($item['category']) ?></td>
                            <td class="px-4 py-3 font-semibold text-blue-600">
                                <?php if ($item['price_type'] === 'wanted'): ?>
                                    <span class="price-wanted">Wanted</span>
                                <?php elseif ($item['price'] !== null): ?>
                                    R <?= number_format((float)$item['price'], 2) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-<?= $item['status'] ?>">
                                    <?= $item['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-400">
                                <?= date('d M Y', strtotime($item['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 justify-end">
                                    <a href="/listing.php?id=<?= $item['id'] ?>"
                                       class="btn btn-outline btn-sm">View</a>
                                    <?php if ($item['status'] !== 'completed'): ?>
                                        <form method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="listing_id" value="<?= $item['id'] ?>">
                                            <button name="action" value="delete"
                                                    class="btn btn-danger btn-sm"
                                                    data-confirm="Delete this listing? This cannot be undone.">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
