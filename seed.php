<?php
/**
 * Seed script — creates test users and sample listings.
 * Safe to run multiple times (skips existing records).
 *
 * Usage (CLI):  php seed.php
 * Usage (web):  /seed.php?seed_key=YOUR_SEED_KEY  (set SEED_KEY env var in Azure)
 *
 * Test accounts created:
 *   admin@vossie.net     / Admin123!      (admin)
 *   alice@vossie.net     / Student123!    (seller with listings)
 *   bob@vossie.net       / Student123!    (buyer)
 *   carol@vossie.net     / Student123!    (buyer/seller)
 */

// ------------------------------------------------------------------
// Guard: CLI only, OR browser with correct SEED_KEY
// ------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    $envKey = getenv('SEED_KEY');
    $reqKey = $_GET['seed_key'] ?? '';
    if (!$envKey || !hash_equals($envKey, $reqKey)) {
        http_response_code(403);
        die('Forbidden. Run from CLI or set SEED_KEY environment variable.');
    }
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$isCli = PHP_SAPI === 'cli';
function out(string $msg): void {
    global $isCli;
    echo $isCli ? "$msg\n" : "<p>$msg</p>\n";
}

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Seed</title></head><body><pre>';
}

out('=== Eduvos Marketplace Seeder ===');

// ------------------------------------------------------------------
// Users
// ------------------------------------------------------------------
$usersToSeed = [
    [
        'name'     => 'Admin User',
        'email'    => 'admin@vossie.net',
        'password' => 'Admin123!',
        'phone'    => '+27 82 000 0001',
        'is_admin' => 1,
    ],
    [
        'name'     => 'Alice Nkosi',
        'email'    => 'alice@vossie.net',
        'password' => 'Student123!',
        'phone'    => '+27 82 111 1111',
        'is_admin' => 0,
    ],
    [
        'name'     => 'Bob Dlamini',
        'email'    => 'bob@vossie.net',
        'password' => 'Student123!',
        'phone'    => '+27 82 222 2222',
        'is_admin' => 0,
    ],
    [
        'name'     => 'Carol van der Merwe',
        'email'    => 'carol@vossie.net',
        'password' => 'Student123!',
        'phone'    => '+27 82 333 3333',
        'is_admin' => 0,
    ],
];

$userIds = [];

foreach ($usersToSeed as $u) {
    $existing = $pdo->prepare("SELECT id FROM users WHERE email = :e");
    $existing->execute([':e' => $u['email']]);
    $id = $existing->fetchColumn();

    if ($id) {
        out("  [skip] User already exists: {$u['email']} (id: $id)");
        $userIds[$u['email']] = (int)$id;
        continue;
    }

    $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $ins = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, phone, is_admin)
        VALUES (:name, :email, :hash, :phone, :admin)
    ");
    $ins->execute([
        ':name'  => $u['name'],
        ':email' => $u['email'],
        ':hash'  => $hash,
        ':phone' => $u['phone'],
        ':admin' => $u['is_admin'],
    ]);
    $id = (int)$pdo->lastInsertId();
    $userIds[$u['email']] = $id;
    out("  [created] {$u['email']} (id: $id, password: {$u['password']})");
}

// ------------------------------------------------------------------
// Listings (owned by Alice)
// ------------------------------------------------------------------
$aliceId = $userIds['alice@vossie.net'] ?? null;
$carolId = $userIds['carol@vossie.net'] ?? null;

$listingsToSeed = [
    [
        'user_id'     => $aliceId,
        'title'       => 'Introduction to Economics — 5th Edition',
        'description' => "Barely used, no highlighting.\nPearson textbook, perfect for ECO1101.\nPicked up from Pretoria campus.",
        'price'       => 250.00,
        'price_type'  => 'fixed',
        'category'    => 'Textbooks & Study Material',
        'phone'       => '+27 82 111 1111',
    ],
    [
        'user_id'     => $aliceId,
        'title'       => 'Samsung Galaxy Tab A7 — 32GB',
        'description' => "Good condition, minor scratches on back. Comes with original charger.\nGreat for lectures.",
        'price'       => 1800.00,
        'price_type'  => 'fixed',
        'category'    => 'Electronics & Gadgets',
        'phone'       => '+27 82 111 1111',
    ],
    [
        'user_id'     => $aliceId,
        'title'       => 'Homemade Peri-Peri Sauce (250ml)',
        'description' => "Hand-crafted hot sauce. Mild, medium and hot available.\nR40 each or 3 for R100.",
        'price'       => 40.00,
        'price_type'  => 'fixed',
        'category'    => 'Food & Snacks',
        'phone'       => '+27 82 111 1111',
    ],
    [
        'user_id'     => $carolId,
        'title'       => 'Looking for: Business Management textbook',
        'description' => "Need the recommended BUS1101 textbook for this semester.\nWilling to pay reasonable price.",
        'price'       => null,
        'price_type'  => 'wanted',
        'category'    => 'Textbooks & Study Material',
        'phone'       => '+27 82 333 3333',
    ],
    [
        'user_id'     => $carolId,
        'title'       => 'Braided Hair Services — R150/session',
        'description' => "Knotless braids, box braids, and cornrows.\nCome to campus or arrange location.\nBookings via WhatsApp.",
        'price'       => 150.00,
        'price_type'  => 'fixed',
        'category'    => 'Beauty & Hair',
        'phone'       => '+27 82 333 3333',
    ],
];

$listingCount = 0;
foreach ($listingsToSeed as $l) {
    if (!$l['user_id']) {
        out("  [skip] No user ID for listing: {$l['title']}");
        continue;
    }

    // Avoid duplicates by title + user
    $dup = $pdo->prepare("SELECT id FROM listings WHERE user_id = :uid AND title = :t LIMIT 1");
    $dup->execute([':uid' => $l['user_id'], ':t' => $l['title']]);
    if ($dup->fetchColumn()) {
        out("  [skip] Listing already exists: {$l['title']}");
        continue;
    }

    $token = generate_qr_token();
    $ins   = $pdo->prepare("
        INSERT INTO listings (user_id, title, description, price, price_type, category, phone, qr_token)
        VALUES (:uid, :title, :desc, :price, :pt, :cat, :phone, :token)
    ");
    $ins->execute([
        ':uid'   => $l['user_id'],
        ':title' => $l['title'],
        ':desc'  => $l['description'],
        ':price' => $l['price'],
        ':pt'    => $l['price_type'],
        ':cat'   => $l['category'],
        ':phone' => $l['phone'],
        ':token' => $token,
    ]);
    $listingCount++;
    out("  [created] Listing: {$l['title']}");
}

out('');
out('=== Done ===');
out("Users ready. Test credentials:");
out("  admin@vossie.net  /  Admin123!");
out("  alice@vossie.net  /  Student123!");
out("  bob@vossie.net    /  Student123!");
out("  carol@vossie.net  /  Student123!");

if (!$isCli) {
    echo '</pre></body></html>';
}
