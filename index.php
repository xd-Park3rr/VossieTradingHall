<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Eduvos Marketplace – Buy &amp; Sell';

// --- Search / filter params ---
$q         = trim($_GET['q']        ?? '');
$category  = trim($_GET['category'] ?? '');
$min_price = is_numeric($_GET['min_price'] ?? '') ? (float)$_GET['min_price'] : null;
$max_price = is_numeric($_GET['max_price'] ?? '') ? (float)$_GET['max_price'] : null;
$sort      = $_GET['sort'] ?? 'newest';

// Build query
$params = [];
$where  = ["l.status = 'available'"];

if ($q !== '') {
    $where[]    = "(l.title LIKE :q OR l.description LIKE :q2)";
    $params[':q']  = "%$q%";
    $params[':q2'] = "%$q%";
}

if ($category !== '') {
    $where[]              = "l.category = :category";
    $params[':category']  = $category;
}

if ($min_price !== null) {
    $where[]               = "l.price >= :min_price";
    $params[':min_price']  = $min_price;
}

if ($max_price !== null) {
    $where[]               = "l.price <= :max_price";
    $params[':max_price']  = $max_price;
}

$orderBy = match($sort) {
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    default      => 'l.created_at DESC',
};

$whereSQL = implode(' AND ', $where);
$sql = "SELECT l.*, u.name AS seller_name
        FROM listings l
        JOIN users u ON u.id = l.user_id
        WHERE $whereSQL
        ORDER BY $orderBy
        LIMIT 60";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero search -->
<section class="hero-search">
    <h1 class="text-white text-3xl font-bold mb-1">Eduvos Marketplace</h1>
    <p class="text-blue-200 text-sm mb-5">Buy &amp; sell with fellow Eduvos students</p>

    <form method="GET" action="/index.php" class="max-w-2xl mx-auto flex gap-2">
        <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="What are you looking for?"
            class="flex-1 rounded-lg border-0 px-4 py-3 text-sm shadow-sm
                   focus:outline-none focus:ring-2 focus:ring-yellow-300"
        >
        <button type="submit" class="btn btn-yellow px-6 py-3 text-base">Search</button>
    </form>
</section>

<div class="max-w-7xl mx-auto px-4 py-8 flex gap-6">

    <!-- Sidebar filters -->
    <aside class="w-56 shrink-0 hidden md:block">
        <form method="GET" action="/index.php" id="filter-form">
            <?php if ($q): ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
            <?php endif; ?>

            <div class="card p-4 space-y-5">
                <h2 class="font-semibold text-sm text-gray-700 uppercase tracking-wide">Filters</h2>

                <!-- Category -->
                <div>
                    <label class="form-label">Category</label>
                    <select name="category" onchange="document.getElementById('filter-form').submit()"
                            class="form-input text-sm">
                        <option value="">All Categories</option>
                        <?php foreach (CATEGORIES as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"
                                <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price range -->
                <div>
                    <label class="form-label">Price Range (R)</label>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" placeholder="Min"
                               value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>"
                               class="form-input text-sm w-full" min="0">
                        <input type="number" name="max_price" placeholder="Max"
                               value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>"
                               class="form-input text-sm w-full" min="0">
                    </div>
                </div>

                <!-- Sort -->
                <div>
                    <label class="form-label">Sort By</label>
                    <select name="sort" onchange="document.getElementById('filter-form').submit()"
                            class="form-input text-sm">
                        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
                        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low–High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High–Low</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-full">Apply</button>

                <?php if ($q || $category || $min_price || $max_price): ?>
                    <a href="/index.php" class="btn btn-outline btn-sm w-full text-center">Clear filters</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Category shortcuts -->
        <div class="card p-4 mt-4 space-y-1">
            <h2 class="font-semibold text-sm text-gray-700 uppercase tracking-wide mb-2">Categories</h2>
            <?php foreach (CATEGORIES as $cat): ?>
                <a href="/index.php?category=<?= urlencode($cat) ?>"
                   class="block text-sm px-2 py-1 rounded-md transition-colors
                          <?= $category === $cat
                              ? 'bg-blue-50 text-blue-700 font-medium'
                              : 'text-gray-600 hover:bg-gray-100' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Listings grid -->
    <div class="flex-1">
        <?php if ($q || $category): ?>
            <p class="text-sm text-gray-500 mb-4">
                <?= count($listings) ?> result<?= count($listings) !== 1 ? 's' : '' ?>
                <?= $q ? ' for "<strong>' . htmlspecialchars($q) . '</strong>"' : '' ?>
                <?= $category ? ' in <strong>' . htmlspecialchars($category) . '</strong>' : '' ?>
            </p>
        <?php endif; ?>

        <?php if (empty($listings)): ?>
            <div class="card p-12 text-center text-gray-400">
                <div class="text-4xl mb-3">🔍</div>
                <p class="font-medium">No listings found.</p>
                <?php if (is_logged_in()): ?>
                    <a href="/create-listing.php" class="btn btn-primary mt-4">Post the first one</a>
                <?php else: ?>
                    <a href="/register.php" class="btn btn-primary mt-4">Register to sell</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($listings as $item): ?>
                    <a href="/listing.php?id=<?= $item['id'] ?>" class="listing-card card block">
                        <?php if ($item['image']): ?>
                            <img src="/uploads/<?= htmlspecialchars($item['image']) ?>"
                                 alt="<?= htmlspecialchars($item['title']) ?>">
                        <?php else: ?>
                            <div class="no-image">📦</div>
                        <?php endif; ?>

                        <div class="p-3">
                            <h3 class="font-semibold text-sm text-gray-900 truncate mb-1">
                                <?= htmlspecialchars($item['title']) ?>
                            </h3>

                            <p class="text-base font-bold text-blue-600 mb-1">
                                <?php if ($item['price_type'] === 'wanted'): ?>
                                    <span class="price-wanted">Wanted</span>
                                <?php elseif ($item['price'] !== null): ?>
                                    R <?= number_format((float)$item['price'], 2) ?>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">Price TBD</span>
                                <?php endif; ?>
                            </p>

                            <p class="text-xs text-gray-500 truncate">
                                <?= htmlspecialchars($item['category']) ?> &bull;
                                <?= htmlspecialchars($item['seller_name']) ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
