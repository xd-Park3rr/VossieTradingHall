<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action     = $_POST['action']     ?? '';
    $listing_id = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT);

    if ($action === 'delete_listing' && $listing_id) {
        // Fetch image before deleting
        $row = $pdo->prepare("SELECT image FROM listings WHERE id = :id");
        $row->execute([':id' => $listing_id]);
        $img = $row->fetchColumn();

        if ($img && file_exists(UPLOAD_DIR . $img)) {
            unlink(UPLOAD_DIR . $img);
        }

        $pdo->prepare("DELETE FROM listings WHERE id = :id")->execute([':id' => $listing_id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Listing #$listing_id deleted."];
    }

    header('Location: /admin.php');
    exit;
}

// Fetch all listings (newest first) with seller info
$listings = $pdo->query("
    SELECT l.*, u.name AS seller_name, u.email AS seller_email
    FROM listings l
    JOIN users u ON u.id = l.user_id
    ORDER BY l.created_at DESC
")->fetchAll();

// Stats
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalListings = (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$totalTx       = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$activeListings= (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'available'")->fetchColumn();

$pageTitle = 'Admin – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Admin Panel</h1>

    <!-- Stats row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php foreach ([
            ['Users',            $totalUsers,     'text-blue-600'],
            ['Total Listings',   $totalListings,  'text-gray-700'],
            ['Active Listings',  $activeListings, 'text-green-600'],
            ['Transactions',     $totalTx,        'text-indigo-600'],
        ] as [$label, $val, $cls]): ?>
            <div class="card p-5 text-center">
                <p class="text-3xl font-bold <?= $cls ?>"><?= $val ?></p>
                <p class="text-sm text-gray-500 mt-1"><?= $label ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Listings table -->
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-semibold text-gray-700">All Listings</h2>
            <span class="text-sm text-gray-400"><?= count($listings) ?> total</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">ID</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Title</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Seller</th>
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
                        <td class="px-4 py-3 text-gray-400">#<?= $item['id'] ?></td>
                        <td class="px-4 py-3">
                            <a href="/listing.php?id=<?= $item['id'] ?>"
                               class="font-medium text-gray-900 hover:text-blue-600">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <div><?= htmlspecialchars($item['seller_name']) ?></div>
                            <div class="text-xs text-gray-400"><?= htmlspecialchars($item['seller_email']) ?></div>
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
                            <span class="badge badge-<?= $item['status'] ?>"><?= $item['status'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            <?= date('d M Y', strtotime($item['created_at'])) ?>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="listing_id" value="<?= $item['id'] ?>">
                                <button name="action" value="delete_listing"
                                        class="btn btn-danger btn-sm"
                                        data-confirm="Delete listing #<?= $item['id'] ?>? This cannot be undone.">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($listings)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">No listings yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
