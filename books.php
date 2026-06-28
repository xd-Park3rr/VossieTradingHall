<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Books Marketplace – ' . SITE_NAME;

// --- Search params (title / author / ISBN) ---
$q    = trim($_GET['q'] ?? '');
$sort = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc']) ? $_GET['sort'] : 'newest';

$params = [':category' => BOOKS_CATEGORY];
$where  = ["l.status = 'available'", "l.category = :category"];

if ($q !== '') {
    $where[]       = "(l.title LIKE :q OR l.author LIKE :qa OR l.isbn LIKE :qi)";
    $params[':q']  = "%$q%";
    $params[':qa'] = "%$q%";
    $params[':qi'] = "%$q%";
}

$orderBy = match($sort) {
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    default      => 'l.created_at DESC',
};

$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT l.*, u.name AS seller_name
    FROM listings l
    JOIN users u ON u.id = l.user_id
    WHERE $whereSQL
    ORDER BY $orderBy
    LIMIT 60
");
$stmt->execute($params);
$books = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero-search">
    <div class="max-w-2xl mx-auto text-center">
        <h1 class="text-white text-3xl font-bold mb-1 tracking-tight">📚 Books Marketplace</h1>
        <p class="text-blue-200 text-sm mb-5">Search second-hand textbooks by ISBN, title or author</p>
        <form method="GET" action="/books.php" class="flex gap-2">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($q) ?>"
                   placeholder="ISBN, book title or author…"
                   class="flex-1 rounded-xl border-0 px-5 py-3 text-sm shadow-md
                          focus:outline-none focus:ring-2 focus:ring-brand-yellow">
            <button type="submit" class="btn btn-yellow px-6 py-3 rounded-xl font-semibold">
                Search
            </button>
        </form>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Results header -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <p class="text-sm <?= $q ? 'text-gray-500' : 'font-semibold text-gray-700' ?>">
            <span class="font-semibold text-gray-800"><?= count($books) ?></span>
            book<?= count($books) !== 1 ? 's' : '' ?>
            <?= $q ? ' for "<span class="font-medium text-blue-600">'.htmlspecialchars($q).'</span>"' : ' available' ?>
        </p>

        <form method="GET" action="/books.php">
            <?php if ($q): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
            <select name="sort" onchange="this.form.submit()" class="form-input text-sm py-1.5 w-auto">
                <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
                <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price ↑</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
            </select>
        </form>
    </div>

    <?php if (empty($books)): ?>
        <div class="card empty-state">
            <div class="icon">📚</div>
            <p><?= $q ? "No books found for \"".htmlspecialchars($q)."\"." : "No textbooks listed yet." ?></p>
            <a href="/create-listing.php" class="btn btn-primary mt-4">List a textbook</a>
            <?php if ($q): ?>
                <a href="/books.php" class="btn btn-outline mt-2">Clear search</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($books as $item):
                $imgSrc = listing_image_src($item['image']);
            ?>
                <a href="/listing.php?id=<?= $item['id'] ?>" class="listing-card card card-hover">
                    <div class="relative">
                        <?php if ($imgSrc): ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>"
                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                 class="listing-card-img"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="listing-card-no-img">📚</div>
                        <?php endif; ?>
                    </div>

                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-gray-900 leading-snug mb-1
                                   overflow-hidden" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                            <?= htmlspecialchars($item['title']) ?>
                        </h3>

                        <?php if (!empty($item['author'])): ?>
                            <p class="text-xs text-gray-500 mb-0.5">by <?= htmlspecialchars($item['author']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['edition'])): ?>
                            <p class="text-xs text-gray-400 mb-0.5"><?= htmlspecialchars($item['edition']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['isbn'])): ?>
                            <p class="text-xs text-gray-400 mb-1.5">ISBN: <?= htmlspecialchars($item['isbn']) ?></p>
                        <?php endif; ?>

                        <div class="flex items-center justify-between mt-1">
                            <span class="text-base font-bold <?= $item['price_type'] === 'wanted' ? 'text-pink-600' : 'text-blue-600' ?>">
                                <?php if ($item['price_type'] === 'wanted'): ?>
                                    Wanted
                                <?php elseif ($item['price'] !== null): ?>
                                    R <?= number_format((float)$item['price'], 2) ?>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm font-normal">Price TBD</span>
                                <?php endif; ?>
                            </span>
                            <span class="text-xs text-gray-400 truncate max-w-[8rem]">
                                <?= htmlspecialchars($item['seller_name']) ?>
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
