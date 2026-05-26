<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

$error  = '';
$fields = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fields['title']       = trim($_POST['title']       ?? '');
    $fields['description'] = trim($_POST['description'] ?? '');
    $fields['price_type']  = ($_POST['price_type'] ?? 'fixed') === 'wanted' ? 'wanted' : 'fixed';
    $fields['price']       = is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : null;
    $fields['category']    = trim($_POST['category']    ?? '');
    $fields['phone']       = trim($_POST['phone']       ?? '');

    // Validate
    if ($fields['title'] === '') {
        $error = 'Title is required.';
    } elseif (!in_array($fields['category'], CATEGORIES, true)) {
        $error = 'Please select a valid category.';
    } elseif ($fields['price_type'] === 'fixed' && $fields['price'] === null) {
        $error = 'Price is required for fixed-price listings.';
    } elseif ($fields['price_type'] === 'fixed' && $fields['price'] < 0) {
        $error = 'Price cannot be negative.';
    } else {
        // Handle image upload
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            try {
                $imageName = upload_listing_image($_FILES['image']);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        if (!$error) {
            $qrToken = generate_qr_token();

            $stmt = $pdo->prepare("
                INSERT INTO listings
                    (user_id, title, description, price, price_type, category, image, phone, qr_token)
                VALUES
                    (:user_id, :title, :desc, :price, :price_type, :category, :image, :phone, :qr_token)
            ");
            $stmt->execute([
                ':user_id'    => current_user_id(),
                ':title'      => $fields['title'],
                ':desc'       => $fields['description'],
                ':price'      => $fields['price_type'] === 'wanted' ? null : $fields['price'],
                ':price_type' => $fields['price_type'],
                ':category'   => $fields['category'],
                ':image'      => $imageName,
                ':phone'      => $fields['phone'],
                ':qr_token'   => $qrToken,
            ]);

            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Listing posted!'];
            header('Location: /listing.php?id=' . $pdo->lastInsertId());
            exit;
        }
    }
}

$pageTitle = 'Create Listing – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Post a listing</h1>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card p-6">
        <form method="POST" action="/create-listing.php" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- Title -->
            <div class="form-group">
                <label class="form-label" for="title">Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" required maxlength="200"
                       placeholder="e.g. Introduction to Economics textbook"
                       class="form-input"
                       value="<?= htmlspecialchars($fields['title'] ?? '') ?>">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Condition, edition, any details…"
                          class="form-input resize-none"><?= htmlspecialchars($fields['description'] ?? '') ?></textarea>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label class="form-label" for="category">Category <span class="text-red-500">*</span></label>
                <select id="category" name="category" required class="form-input">
                    <option value="">Select a category…</option>
                    <?php foreach (CATEGORIES as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"
                            <?= ($fields['category'] ?? '') === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price type toggle -->
            <div class="form-group">
                <label class="form-label">Listing type <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="price_type" value="fixed"
                               <?= ($fields['price_type'] ?? 'fixed') === 'fixed' ? 'checked' : '' ?>>
                        <span class="text-sm font-medium">I'm selling at a price</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="price_type" value="wanted"
                               <?= ($fields['price_type'] ?? '') === 'wanted' ? 'checked' : '' ?>>
                        <span class="text-sm font-medium">I'm looking to buy (Wanted)</span>
                    </label>
                </div>
            </div>

            <!-- Price (hidden when Wanted) -->
            <div class="form-group" id="price-field"
                 <?= ($fields['price_type'] ?? 'fixed') === 'wanted' ? 'style="display:none"' : '' ?>>
                <label class="form-label" for="price">Price (R) <span class="text-red-500">*</span></label>
                <input type="number" id="price" name="price" step="0.01" min="0"
                       placeholder="0.00"
                       class="form-input"
                       <?= ($fields['price_type'] ?? 'fixed') !== 'wanted' ? 'required' : '' ?>
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label" for="phone">Contact number / WhatsApp</label>
                <input type="tel" id="phone" name="phone"
                       placeholder="+27 81 234 5678"
                       class="form-input"
                       value="<?= htmlspecialchars($fields['phone'] ?? current_user()['phone'] ?? '') ?>">
                <p class="text-xs text-gray-400 mt-1">Visible to all logged-in users on your listing.</p>
            </div>

            <!-- Image -->
            <div class="form-group">
                <label class="form-label" for="image">Image <span class="text-gray-400 font-normal">(JPEG / PNG / WebP, max 5 MB)</span></label>
                <input type="file" id="image" name="image" accept="image/*" class="form-input py-2">
                <img id="image-preview" src="" alt="Preview" class="hidden mt-3 rounded-lg max-h-48 object-cover">
            </div>

            <div class="flex gap-3 mt-2">
                <button type="submit" class="btn btn-primary px-8 py-3">Post listing</button>
                <a href="/index.php" class="btn btn-outline px-6 py-3">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
