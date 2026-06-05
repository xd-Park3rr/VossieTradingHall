<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_admin();

$userForm = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'is_admin' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action     = $_POST['action'] ?? '';
    $listing_id = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT);
    $user_id    = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($action === 'delete_listing' && $listing_id) {
        $row = $pdo->prepare("SELECT image FROM listings WHERE id = :id");
        $row->execute([':id' => $listing_id]);
        $img = $row->fetchColumn();

        if ($img && file_exists(UPLOAD_DIR . $img)) {
            unlink(UPLOAD_DIR . $img);
        }

        $pdo->prepare("DELETE FROM listings WHERE id = :id")->execute([':id' => $listing_id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Listing #$listing_id deleted."];
    } elseif ($action === 'create_user') {
        $userForm['name'] = trim($_POST['name'] ?? '');
        $userForm['email'] = strtolower(trim($_POST['email'] ?? ''));
        $userForm['phone'] = trim($_POST['phone'] ?? '');
        $userForm['is_admin'] = (($_POST['is_admin'] ?? '0') === '1') ? '1' : '0';
        $password = $_POST['password'] ?? '';

        if ($userForm['name'] === '') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Full name is required.'];
        } elseif (!is_allowed_email($userForm['email'])) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Only @vossie.net email addresses are allowed.'];
        } elseif (strlen($password) < 8) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $check->execute([':email' => $userForm['email']]);

            if ($check->fetch()) {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'A user with that email already exists.'];
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, phone, is_admin)
                    VALUES (:name, :email, :hash, :phone, :is_admin)
                ")->execute([
                    ':name' => $userForm['name'],
                    ':email' => $userForm['email'],
                    ':hash' => $hash,
                    ':phone' => $userForm['phone'],
                    ':is_admin' => (int)$userForm['is_admin'],
                ]);

                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User created successfully.'];
            }
        }
    } elseif ($action === 'delete_user' && $user_id) {
        if ($user_id === current_user_id()) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'You cannot delete your own admin account.'];
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $user_id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "User #$user_id deleted."];
        }
    } elseif ($action === 'update_user_role' && $user_id) {
        $newRole = (($_POST['is_admin'] ?? '0') === '1') ? 1 : 0;

        if ($user_id === current_user_id() && $newRole === 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'You cannot remove admin role from your own account.'];
        } else {
            $pdo->prepare("UPDATE users SET is_admin = :is_admin WHERE id = :id")
                ->execute([':is_admin' => $newRole, ':id' => $user_id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User role updated.'];
        }
    }

    header('Location: /admin.php');
    exit;
}

$listings = $pdo->query("
    SELECT l.*, u.name AS seller_name, u.email AS seller_email
    FROM listings l
    JOIN users u ON u.id = l.user_id
    ORDER BY l.created_at DESC
")->fetchAll();

$users = $pdo->query("
    SELECT id, name, email, phone, is_admin, created_at
    FROM users
    ORDER BY created_at DESC
")->fetchAll();

$totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalListings  = (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$totalTx        = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$activeListings = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'available'")->fetchColumn();

$pageTitle = 'Admin – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Add User Modal -->
<div id="add-user-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-gray-900">Add User</h2>
            <button type="button" onclick="closeAddUserModal()"
                    class="text-gray-400 hover:text-gray-600 transition-colors text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">
            <div>
                <label class="form-label" for="admin-name">Full name</label>
                <input id="admin-name" type="text" name="name" required class="form-input"
                       value="<?= htmlspecialchars($userForm['name']) ?>">
            </div>
            <div>
                <label class="form-label" for="admin-email">Eduvos email</label>
                <input id="admin-email" type="email" name="email" required class="form-input"
                       placeholder="user@vossie.net"
                       value="<?= htmlspecialchars($userForm['email']) ?>">
            </div>
            <div>
                <label class="form-label" for="admin-phone">Phone / WhatsApp</label>
                <input id="admin-phone" type="tel" name="phone" class="form-input"
                       value="<?= htmlspecialchars($userForm['phone']) ?>">
            </div>
            <div>
                <label class="form-label" for="admin-password">Temporary password</label>
                <input id="admin-password" type="password" name="password" required class="form-input"
                       placeholder="Minimum 8 characters">
            </div>
            <div>
                <label class="form-label" for="admin-role">Role</label>
                <select id="admin-role" name="is_admin" class="form-input">
                    <option value="0" <?= $userForm['is_admin'] === '0' ? 'selected' : '' ?>>Student user</option>
                    <option value="1" <?= $userForm['is_admin'] === '1' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="btn btn-primary flex-1">Create user</button>
                <button type="button" onclick="closeAddUserModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Admin Panel</h1>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php foreach ([
            ['Users',           $totalUsers,    '#2563eb'],
            ['Total Listings',  $totalListings, '#374151'],
            ['Active Listings', $activeListings,'#15803d'],
            ['Transactions',    $totalTx,       '#7c3aed'],
        ] as [$label, $val, $color]): ?>
            <div class="stat-card">
                <p class="stat-value" style="color:<?= $color ?>"><?= $val ?></p>
                <p class="stat-label"><?= $label ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- User Management -->
    <div class="card overflow-hidden mb-8">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-gray-700">User Management</h2>
                <span class="text-sm text-gray-400"><?= count($users) ?> total</span>
            </div>
            <button type="button" onclick="openAddUserModal()"
                    class="btn btn-primary btn-sm">+ Add User</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Name</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Email</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Role</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Joined</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                <?php if (!empty($user['phone'])): ?>
                                    <div class="text-xs text-gray-400"><?= htmlspecialchars($user['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="px-4 py-3">
                                <span class="badge <?= !empty($user['is_admin']) ? 'badge-pending' : 'badge-available' ?>">
                                    <?= !empty($user['is_admin']) ? 'Admin' : 'Student' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-400"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="POST" class="inline-flex items-center gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_user_role">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="is_admin" class="form-input text-xs py-1.5 px-2 w-auto">
                                            <option value="0" <?= empty($user['is_admin']) ? 'selected' : '' ?>>Student</option>
                                            <option value="1" <?= !empty($user['is_admin']) ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <button type="submit" class="btn btn-outline btn-sm">Save</button>
                                    </form>

                                    <?php if ((int)$user['id'] !== current_user_id()): ?>
                                        <form method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button name="action" value="delete_user"
                                                    class="btn btn-danger btn-sm"
                                                    data-confirm="Delete user <?= htmlspecialchars($user['email']) ?>? This cannot be undone.">
                                                Delete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Current account</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">No users yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Listings -->
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
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
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

<script>
function openAddUserModal() {
    const m = document.getElementById('add-user-modal');
    if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
}
function closeAddUserModal() {
    const m = document.getElementById('add-user-modal');
    if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
}
document.getElementById('add-user-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddUserModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAddUserModal();
});
<?php if ($userForm['name'] !== '' || $userForm['email'] !== ''): ?>
openAddUserModal();
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
