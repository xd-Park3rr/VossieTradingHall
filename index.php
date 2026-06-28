<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Vossie Trading Hall – Buy &amp; Sell';

// --- Search / filter params ---
$q         = trim($_GET['q']        ?? '');
$category  = trim($_GET['category'] ?? '');
$min_price = is_numeric($_GET['min_price'] ?? '') ? (float)$_GET['min_price'] : null;
$max_price = is_numeric($_GET['max_price'] ?? '') ? (float)$_GET['max_price'] : null;
$sort      = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc']) ? $_GET['sort'] : 'newest';
$type      = trim($_GET['type'] ?? '');

// Build query
$params = [];
$where  = ["l.status = 'available'"];

if ($q !== '') {
    $where[]       = "(l.title LIKE :q OR l.description LIKE :q2)";
    $params[':q']  = "%$q%";
    $params[':q2'] = "%$q%";
}
if ($category !== '') {
    $where[]              = "l.category = :category";
    $params[':category']  = $category;
}
if ($min_price !== null) {
    $where[]               = "(l.price >= :min_price OR l.price_type = 'wanted')";
    $params[':min_price']  = $min_price;
}
if ($max_price !== null) {
    $where[]               = "(l.price <= :max_price OR l.price_type = 'wanted')";
    $params[':max_price']  = $max_price;
}
if ($type === 'business') {
    $where[] = "l.is_business = 1";
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

// Landing-page extras: only shown on the plain home view (no search/filter active).
$isLanding  = ($q === '' && $category === '' && $min_price === null && $max_price === null && $type === '');
$promotions = [];
$businesses = [];
if ($isLanding) {
    $promotions = $pdo->query("
        SELECT * FROM promotions WHERE active = 1 ORDER BY created_at DESC LIMIT 6
    ")->fetchAll();

    $bizStmt = $pdo->prepare("
        SELECT l.*, u.name AS seller_name
        FROM listings l
        JOIN users u ON u.id = l.user_id
        WHERE l.is_business = 1 AND l.status = 'available'
        ORDER BY l.created_at DESC
        LIMIT 4
    ");
    $bizStmt->execute();
    $businesses = $bizStmt->fetchAll();
}

// Total available count per category (for pills)
$catCounts = $pdo->query("
    SELECT category, COUNT(*) AS cnt
    FROM listings WHERE status = 'available'
    GROUP BY category
")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero-search">
    <div class="max-w-2xl mx-auto text-center">
        <h1 class="text-white text-3xl font-bold mb-1 tracking-tight">Vossie Trading Hall</h1>
        <p class="text-blue-200 text-sm mb-5">Buy &amp; sell with your fellow Vossie students</p>
        <form method="GET" action="/index.php" class="flex gap-2">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($q) ?>"
                   placeholder="Search for textbooks, electronics, services…"
                   class="flex-1 rounded-xl border-0 px-5 py-3 text-sm shadow-md
                          focus:outline-none focus:ring-2 focus:ring-brand-yellow">
            <button type="submit" class="btn btn-yellow px-6 py-3 rounded-xl font-semibold">
                Search
            </button>
        </form>
    </div>
</section>

<!-- Category pills row -->
<div class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="pill-row">
            <a href="/index.php"
               class="cat-pill <?= ($category === '' && $q === '') ? 'cat-pill-active' : '' ?>">
               All listings
            </a>
            <?php foreach (CATEGORIES as $cat):
                $count = $catCounts[$cat] ?? 0;
                if ($count === 0) continue;
            ?>
                <a href="/index.php?category=<?= urlencode($cat) ?>"
                   class="cat-pill <?= $category === $cat ? 'cat-pill-active' : '' ?>">
                   <?= category_icon($cat) ?> <?= htmlspecialchars($cat) ?>
                   <span class="<?= $category === $cat ? 'text-blue-100' : 'text-gray-400' ?> text-xs">
                       (<?= $count ?>)
                   </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($isLanding): ?>
<div class="max-w-7xl mx-auto px-4 pt-6 space-y-8">

    <!-- Books marketplace entry point -->
    <a href="/books.php"
       class="block card card-hover p-5 border border-blue-100"
       style="background:linear-gradient(90deg,#eef2ff,#eff6ff)">
        <div class="flex items-center gap-4">
            <span class="text-4xl">📚</span>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Books Marketplace</h2>
                <p class="text-sm text-gray-500">Search second-hand textbooks by ISBN, title or author.</p>
            </div>
            <span class="ml-auto text-blue-600 font-medium hidden sm:inline">Search books →</span>
        </div>
    </a>

    <?php if (!empty($promotions)): ?>
        <!-- Admin-managed promotional sales -->
        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">🔥 Campus Deals &amp; Sales</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($promotions as $promo):
                    $hasLink = !empty($promo['link_url']);
                ?>
                    <div class="card card-hover overflow-hidden">
                        <?php if (!empty($promo['image'])): ?>
                            <img src="<?= htmlspecialchars($promo['image']) ?>" alt=""
                                 class="w-full h-32 object-cover" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-32 flex items-center justify-center text-white text-4xl"
                                 style="background:linear-gradient(90deg,#2563eb,#4f46e5)">🏷️</div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900">
                                <?php if ($hasLink): ?>
                                    <a href="<?= htmlspecialchars($promo['link_url']) ?>" class="hover:text-blue-600"><?= htmlspecialchars($promo['title']) ?></a>
                                <?php else: ?>
                                    <?= htmlspecialchars($promo['title']) ?>
                                <?php endif; ?>
                            </h3>
                            <?php if (!empty($promo['description'])): ?>
                                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($promo['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($businesses)): ?>
        <!-- Student businesses -->
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-gray-900">🏪 Student Businesses</h2>
                <a href="/index.php?type=business" class="text-sm font-medium text-blue-600 hover:underline">View all →</a>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($businesses as $item):
                    $imgSrc = listing_image_src($item['image']);
                ?>
                    <a href="/listing.php?id=<?= $item['id'] ?>" class="listing-card card card-hover">
                        <div class="relative">
                            <?php if ($imgSrc): ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                                     class="listing-card-img" loading="lazy">
                            <?php else: ?>
                                <div class="listing-card-no-img"><?= category_icon($item['category']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h3 class="font-semibold text-sm text-gray-900 leading-snug mb-1 truncate">
                                <?= htmlspecialchars($item['title']) ?>
                            </h3>
                            <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($item['seller_name']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>
<?php endif; ?>

<div class="max-w-7xl mx-auto px-4 py-6 flex gap-6">

    <!-- Sidebar filters -->
    <aside class="w-56 shrink-0 hidden lg:block">
        <form method="GET" action="/index.php" id="filter-form">
            <?php if ($q): ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
            <?php endif; ?>
            <?php if ($category): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <?php endif; ?>

            <div class="card p-4 space-y-4">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Filter Results</h2>

                <!-- Price range -->
                <div>
                    <label class="form-label text-xs">Price Range (R)</label>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" placeholder="Min"
                               value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>"
                               class="form-input text-xs" min="0">
                        <input type="number" name="max_price" placeholder="Max"
                               value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>"
                               class="form-input text-xs" min="0">
                    </div>
                </div>

                <!-- Sort -->
                <div>
                    <label class="form-label text-xs">Sort By</label>
                    <select name="sort" class="form-input text-sm"
                            onchange="document.getElementById('filter-form').submit()">
                        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest first</option>
                        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low → High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-full">Apply filters</button>

                <?php if ($min_price !== null || $max_price !== null): ?>
                    <a href="/index.php<?= $q ? '?q='.urlencode($q) : '' ?><?= $category ? ($q?'&':'?').'category='.urlencode($category) : '' ?>"
                       class="btn btn-ghost btn-sm w-full text-center">Clear price filter</a>
                <?php endif; ?>
            </div>

            <!-- Category list -->
            <div class="card p-4 mt-3 space-y-0.5">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Categories</h2>
                <a href="/index.php"
                   class="flex items-center justify-between px-2 py-1.5 rounded-lg text-sm
                          transition-colors <?= $category === '' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <span>All</span>
                    <span class="text-xs text-gray-400"><?= array_sum($catCounts) ?></span>
                </a>
                <?php foreach (CATEGORIES as $cat): ?>
                    <a href="/index.php?category=<?= urlencode($cat) ?>"
                       class="flex items-center justify-between px-2 py-1.5 rounded-lg text-sm
                              transition-colors <?= $category === $cat ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <span><?= category_icon($cat) ?> <?= htmlspecialchars($cat) ?></span>
                        <span class="text-xs text-gray-400"><?= $catCounts[$cat] ?? 0 ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
    </aside>

    <!-- Main content -->
    <div class="flex-1 min-w-0">

        <?php if ($type === 'business'): ?>
            <h1 class="text-xl font-bold text-gray-900 mb-4">🏪 Student Businesses</h1>
        <?php endif; ?>

        <!-- Results header -->
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
                <?php if ($q || $category): ?>
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-800"><?= count($listings) ?></span>
                        result<?= count($listings) !== 1 ? 's' : '' ?>
                        <?= $q ? ' for "<span class="font-medium text-blue-600">'.htmlspecialchars($q).'</span>"' : '' ?>
                        <?= $category ? ' in <span class="font-medium">'.htmlspecialchars($category).'</span>' : '' ?>
                    </p>
                <?php else: ?>
                    <p class="text-sm font-semibold text-gray-700">
                        <?= count($listings) ?> available listing<?= count($listings) !== 1 ? 's' : '' ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Mobile sort -->
            <form method="GET" action="/index.php" class="lg:hidden">
                <?php if ($q): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
                <?php if ($category): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
                <select name="sort" onchange="this.form.submit()" class="form-input text-sm py-1.5 w-auto">
                    <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest</option>
                    <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price ↑</option>
                    <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price ↓</option>
                </select>
            </form>
        </div>

        <?php if (empty($listings)): ?>
            <div class="card empty-state">
                <div class="icon">🔍</div>
                <p><?= $q ? "No listings found for \"".htmlspecialchars($q)."\"." : "No listings in this category yet." ?></p>
                <a href="/create-listing.php" class="btn btn-primary mt-4">Post a listing</a>
                <?php if ($q || $category): ?>
                    <a href="/index.php" class="btn btn-outline mt-2">Clear filters</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <?php foreach ($listings as $item):
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
                                <div class="listing-card-no-img">
                                    <?= category_icon($item['category']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($item['price_type'] === 'wanted'): ?>
                                <span class="listing-card-status-overlay badge badge-wanted badge-dot">Wanted</span>
                            <?php endif; ?>
                        </div>

                        <div class="p-3">
                            <p class="text-xs text-gray-400 mb-0.5"><?= htmlspecialchars($item['category']) ?></p>
                            <h3 class="font-semibold text-sm text-gray-900 leading-snug mb-1.5
                                       overflow-hidden" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                                <?= htmlspecialchars($item['title']) ?>
                            </h3>

                            <div class="flex items-center justify-between">
                                <span class="text-base font-bold <?= $item['price_type']==='wanted' ? 'text-pink-600' : 'text-blue-600' ?>">
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
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
