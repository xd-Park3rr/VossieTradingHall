<?php
/**
 * Seed script — creates test users and sample listings.
 * Safe to run multiple times (skips existing records).
 *
 * Usage (CLI):  php seed.php
 * Usage (web):  /seed.php?seed_key=YOUR_SEED_KEY  (set SEED_KEY env var in Azure)
 *
 * Test accounts:
 *   admin@vossie.net      / Admin123!      (admin)
 *   alice@vossie.net      / Student123!    (active seller)
 *   bob@vossie.net        / Student123!    (buyer/seller)
 *   carol@vossie.net      / Student123!    (service provider)
 *   david@vossie.net      / Student123!    (tech seller)
 *   emma@vossie.net       / Student123!    (creative seller)
 */

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
    echo $isCli ? "$msg\n" : "<p style='font-family:monospace;font-size:.85rem'>$msg</p>";
}

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Seed</title></head><body>';
}

out('=== Eduvos Marketplace Seeder ===');
out('');

// ─────────────────────────────────────────────────────────
// USERS
// ─────────────────────────────────────────────────────────
$usersToSeed = [
    ['name' => 'Admin User',           'email' => 'admin@vossie.net',  'password' => 'Admin123!',   'phone' => '+27 82 000 0001', 'is_admin' => 1],
    ['name' => 'Alice Nkosi',          'email' => 'alice@vossie.net',  'password' => 'Student123!', 'phone' => '+27 82 111 1111', 'is_admin' => 0],
    ['name' => 'Bob Dlamini',          'email' => 'bob@vossie.net',    'password' => 'Student123!', 'phone' => '+27 82 222 2222', 'is_admin' => 0],
    ['name' => 'Carol van der Merwe',  'email' => 'carol@vossie.net',  'password' => 'Student123!', 'phone' => '+27 82 333 3333', 'is_admin' => 0],
    ['name' => 'David Sithole',        'email' => 'david@vossie.net',  'password' => 'Student123!', 'phone' => '+27 82 444 4444', 'is_admin' => 0],
    ['name' => 'Emma Botha',           'email' => 'emma@vossie.net',   'password' => 'Student123!', 'phone' => '+27 82 555 5555', 'is_admin' => 0],
];

$userIds = [];
foreach ($usersToSeed as $u) {
    $existing = $pdo->prepare("SELECT id FROM users WHERE email = :e");
    $existing->execute([':e' => $u['email']]);
    $id = $existing->fetchColumn();
    if ($id) {
        out("  [skip]    {$u['email']} already exists (id: $id)");
        $userIds[$u['email']] = (int)$id;
        continue;
    }
    $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, is_admin) VALUES (:n,:e,:h,:p,:a)");
    $ins->execute([':n'=>$u['name'],':e'=>$u['email'],':h'=>$hash,':p'=>$u['phone'],':a'=>$u['is_admin']]);
    $id = (int)$pdo->lastInsertId();
    $userIds[$u['email']] = $id;
    out("  [created]  {$u['email']} (id: $id)");
}

$alice = $userIds['alice@vossie.net'] ?? null;
$bob   = $userIds['bob@vossie.net']   ?? null;
$carol = $userIds['carol@vossie.net'] ?? null;
$david = $userIds['david@vossie.net'] ?? null;
$emma  = $userIds['emma@vossie.net']  ?? null;

out('');
out('--- Listings ---');

// ─────────────────────────────────────────────────────────
// LISTINGS  (picsum seeds are stable — same image every time)
// ─────────────────────────────────────────────────────────
$listingsToSeed = [

    /* ── Alice ── Textbooks & Study Material */
    [
        'user_id'    => $alice,
        'title'      => 'Introduction to Economics — 5th Edition',
        'description'=> "Barely used, no highlighting.\nPearson textbook, perfect for ECO1101.\nPickup from Pretoria campus.",
        'price'      => 250.00, 'price_type' => 'fixed',
        'category'   => 'Textbooks & Study Material',
        'phone'      => '+27 82 111 1111',
        'image'      => 'https://picsum.photos/seed/book101/600/450',
    ],
    [
        'user_id'    => $alice,
        'title'      => 'Calculus: Early Transcendentals — 8th Ed.',
        'description'=> "Some pencil notes, still very readable.\nIncludes a solutions manual.",
        'price'      => 320.00, 'price_type' => 'fixed',
        'category'   => 'Textbooks & Study Material',
        'phone'      => '+27 82 111 1111',
        'image'      => 'https://picsum.photos/seed/book202/600/450',
    ],

    /* ── Alice ── Electronics */
    [
        'user_id'    => $alice,
        'title'      => 'Samsung Galaxy Tab A7 — 32 GB WiFi',
        'description'=> "Good condition, minor scratches on back.\nComes with original charger and case.\nGreat for lecture notes and PDFs.",
        'price'      => 1800.00, 'price_type' => 'fixed',
        'category'   => 'Electronics & Gadgets',
        'phone'      => '+27 82 111 1111',
        'image'      => 'https://picsum.photos/seed/tablet01/600/450',
    ],
    [
        'user_id'    => $alice,
        'title'      => 'HP Laptop Charger — 65W USB-C',
        'description'=> "Universal USB-C charger, works with most HP and Dell laptops.\nSelling because I got a new laptop.",
        'price'      => 150.00, 'price_type' => 'fixed',
        'category'   => 'Electronics & Gadgets',
        'phone'      => '+27 82 111 1111',
        'image'      => 'https://picsum.photos/seed/charger1/600/450',
    ],

    /* ── Alice ── Food */
    [
        'user_id'    => $alice,
        'title'      => 'Homemade Peri-Peri Sauce — 3 Heat Levels',
        'description'=> "Hand-crafted hot sauce made fresh weekly.\nMild, Medium and Hot available.\nR40 each or 3 for R100. Delivery on campus.",
        'price'      => 40.00, 'price_type' => 'fixed',
        'category'   => 'Food & Snacks',
        'phone'      => '+27 82 111 1111',
        'image'      => 'https://picsum.photos/seed/sauce101/600/450',
    ],

    /* ── Alice ── Art & Crafts */
    [
        'user_id'    => $alice,
        'title'      => 'Handmade Beaded Earrings — Sets of 3',
        'description'=> "Unique beaded earrings, hand-crafted.\nMultiple colour sets available.\nR80 per set. Can customise colours.",
        'price'      => 80.00, 'price_type' => 'fixed',
        'category'   => 'Art & Crafts',
        'phone'      => '+27 82 111 1111',
        'image'      => 'https://picsum.photos/seed/jewelry1/600/450',
    ],

    /* ── Bob ── Textbooks */
    [
        'user_id'    => $bob,
        'title'      => 'Business Management 101 — Official Textbook',
        'description'=> "Recommended BUS1101 textbook.\nGood condition, some highlighting in chapter 3.",
        'price'      => 180.00, 'price_type' => 'fixed',
        'category'   => 'Textbooks & Study Material',
        'phone'      => '+27 82 222 2222',
        'image'      => 'https://picsum.photos/seed/biz101/600/450',
    ],
    [
        'user_id'    => $bob,
        'title'      => 'WANTED: Anatomy & Physiology Textbook',
        'description'=> "Looking for the recommended A&P textbook for 2nd year.\nWilling to pay up to R400. Please WhatsApp me.",
        'price'      => null, 'price_type' => 'wanted',
        'category'   => 'Textbooks & Study Material',
        'phone'      => '+27 82 222 2222',
        'image'      => null,
    ],

    /* ── Bob ── Gaming */
    [
        'user_id'    => $bob,
        'title'      => 'Call of Duty: Modern Warfare 3 — PS4',
        'description'=> "Disc in perfect condition.\nBox slightly worn but game is flawless.",
        'price'      => 350.00, 'price_type' => 'fixed',
        'category'   => 'Sports & Gaming',
        'phone'      => '+27 82 222 2222',
        'image'      => 'https://picsum.photos/seed/game101/600/450',
    ],

    /* ── Bob ── Clothing */
    [
        'user_id'    => $bob,
        'title'      => 'Nike Air Force 1 — Size 10 (barely worn)',
        'description'=> "Wore these twice, size 10.\nComes with original box and extra laces.",
        'price'      => 600.00, 'price_type' => 'fixed',
        'category'   => 'Clothing & Accessories',
        'phone'      => '+27 82 222 2222',
        'image'      => 'https://picsum.photos/seed/shoes201/600/450',
    ],
    [
        'user_id'    => $bob,
        'title'      => 'Bundle: 3× Eduvos Hoodies (M)',
        'description'=> "Three lightly used hoodies, size medium.\nNavy, grey and black. R120 each or all 3 for R300.",
        'price'      => 120.00, 'price_type' => 'fixed',
        'category'   => 'Clothing & Accessories',
        'phone'      => '+27 82 222 2222',
        'image'      => 'https://picsum.photos/seed/hoodie11/600/450',
    ],

    /* ── Carol ── Services */
    [
        'user_id'    => $carol,
        'title'      => 'Braided Hair — Knotless, Box & Cornrows',
        'description'=> "Professional braiding by 2nd-year student.\nKnotless braids, box braids, cornrows and more.\nBookings via WhatsApp. Campus or negotiate location.",
        'price'      => 150.00, 'price_type' => 'fixed',
        'category'   => 'Beauty & Hair',
        'phone'      => '+27 82 333 3333',
        'image'      => 'https://picsum.photos/seed/hair201/600/450',
    ],
    [
        'user_id'    => $carol,
        'title'      => 'Gel Nail Art — Full Set from R80',
        'description'=> "Custom nail art, gel and acrylic available.\nFull set from R80, infills from R50.\nCheck my Instagram for designs.",
        'price'      => 80.00, 'price_type' => 'fixed',
        'category'   => 'Beauty & Hair',
        'phone'      => '+27 82 333 3333',
        'image'      => 'https://picsum.photos/seed/nails101/600/450',
    ],

    /* ── Carol ── Art & Crafts */
    [
        'user_id'    => $carol,
        'title'      => 'Handcrafted Soy Candles — Various Scents',
        'description'=> "Made in small batches with soy wax and essential oils.\nLavender, vanilla, citrus and more.\nR95 each, bulk discount available.",
        'price'      => 95.00, 'price_type' => 'fixed',
        'category'   => 'Art & Crafts',
        'phone'      => '+27 82 333 3333',
        'image'      => 'https://picsum.photos/seed/candle11/600/450',
    ],

    /* ── Carol ── Food */
    [
        'user_id'    => $carol,
        'title'      => 'Meal Prep Service — Weekly Lunches',
        'description'=> "Healthy meal prep delivered to campus.\nR120 per week (5 lunches).\nMenu changes weekly. Order by Sunday.",
        'price'      => 120.00, 'price_type' => 'fixed',
        'category'   => 'Food & Snacks',
        'phone'      => '+27 82 333 3333',
        'image'      => 'https://picsum.photos/seed/meals201/600/450',
    ],

    /* ── David ── Electronics */
    [
        'user_id'    => $david,
        'title'      => 'Beats Studio3 Wireless Headphones — Black',
        'description'=> "Used for 6 months, excellent condition.\nIncludes carry case, USB-C cable and aux cable.\nBattery life still 90%+.",
        'price'      => 800.00, 'price_type' => 'fixed',
        'category'   => 'Electronics & Gadgets',
        'phone'      => '+27 82 444 4444',
        'image'      => 'https://picsum.photos/seed/headphones1/600/450',
    ],
    [
        'user_id'    => $david,
        'title'      => 'Logitech MX Keys Mini Keyboard',
        'description'=> "Compact wireless keyboard, backlit.\nWorks with Mac, Windows and Linux.\nPairs with up to 3 devices.",
        'price'      => 950.00, 'price_type' => 'fixed',
        'category'   => 'Electronics & Gadgets',
        'phone'      => '+27 82 444 4444',
        'image'      => 'https://picsum.photos/seed/keyboard1/600/450',
    ],
    [
        'user_id'    => $david,
        'title'      => 'WANTED: Scientific Calculator (CASIO fx-991)',
        'description'=> "Need the CASIO fx-991ZA Plus for engineering modules.\nWilling to pay R200–R350 depending on condition.",
        'price'      => null, 'price_type' => 'wanted',
        'category'   => 'Electronics & Gadgets',
        'phone'      => '+27 82 444 4444',
        'image'      => null,
    ],

    /* ── David ── Sports & Gaming */
    [
        'user_id'    => $david,
        'title'      => 'PS4 DualShock Controller — Midnight Blue',
        'description'=> "Official Sony controller, fully working.\nSome minor scuffs on thumbsticks from use.",
        'price'      => 400.00, 'price_type' => 'fixed',
        'category'   => 'Sports & Gaming',
        'phone'      => '+27 82 444 4444',
        'image'      => 'https://picsum.photos/seed/controller1/600/450',
    ],
    [
        'user_id'    => $david,
        'title'      => 'Cricket Bat — Grade 3 English Willow',
        'description'=> "Full-size bat, lightly used for one season.\nHas been oiled and knocked in. Good for outdoor cricket.",
        'price'      => 500.00, 'price_type' => 'fixed',
        'category'   => 'Sports & Gaming',
        'phone'      => '+27 82 444 4444',
        'image'      => 'https://picsum.photos/seed/cricket1/600/450',
    ],

    /* ── David ── Textbooks */
    [
        'user_id'    => $david,
        'title'      => 'Accounting Bundle — 3 Textbooks (1st Year)',
        'description'=> "All three 1st-year accounting textbooks bundled.\nFrankly & Uys, IFRS and Tax Legislation.\nR450 for the bundle — saving R200.",
        'price'      => 450.00, 'price_type' => 'fixed',
        'category'   => 'Textbooks & Study Material',
        'phone'      => '+27 82 444 4444',
        'image'      => 'https://picsum.photos/seed/accbooks/600/450',
    ],

    /* ── Emma ── Tutoring */
    [
        'user_id'    => $emma,
        'title'      => 'Python Programming Tutoring — R200/hr',
        'description'=> "3rd-year IT student offering Python tutoring.\nCovers basics to OOP and web scraping.\nOnline or on-campus sessions.",
        'price'      => 200.00, 'price_type' => 'fixed',
        'category'   => 'Tutoring & Services',
        'phone'      => '+27 82 555 5555',
        'image'      => 'https://picsum.photos/seed/tutor201/600/450',
    ],
    [
        'user_id'    => $emma,
        'title'      => 'Mathematics Tutoring — Grade 11/12 & 1st Year',
        'description'=> "Experienced math tutor, scored 92% for 1st year maths.\nAvailable weekdays after 15:00 and weekends.",
        'price'      => 150.00, 'price_type' => 'fixed',
        'category'   => 'Tutoring & Services',
        'phone'      => '+27 82 555 5555',
        'image'      => 'https://picsum.photos/seed/mathtutor/600/450',
    ],

    /* ── Emma ── Art & Crafts */
    [
        'user_id'    => $emma,
        'title'      => 'Watercolour Painting Starter Kit',
        'description'=> "24-colour Winsor & Newton watercolour set.\nIncludes 10 brushes, 2 pads and a mixing palette.\nPerfect for beginners.",
        'price'      => 200.00, 'price_type' => 'fixed',
        'category'   => 'Art & Crafts',
        'phone'      => '+27 82 555 5555',
        'image'      => 'https://picsum.photos/seed/art301/600/450',
    ],

    /* ── Emma ── Clothing */
    [
        'user_id'    => $emma,
        'title'      => 'Vintage Denim Jacket — Women\'s M',
        'description'=> "Oversized vintage denim jacket, women's medium.\nAuthentic 90s cut. Perfect condition, barely worn.",
        'price'      => 300.00, 'price_type' => 'fixed',
        'category'   => 'Clothing & Accessories',
        'phone'      => '+27 82 555 5555',
        'image'      => 'https://picsum.photos/seed/denim201/600/450',
    ],

    /* ── Emma ── Sports */
    [
        'user_id'    => $emma,
        'title'      => 'Protein Powder — 1kg Chocolate Whey',
        'description'=> "Half-used tub (approx 500g remaining).\nBought the wrong flavour. BSN Syntha-6 Chocolate.",
        'price'      => 250.00, 'price_type' => 'fixed',
        'category'   => 'Sports & Gaming',
        'phone'      => '+27 82 555 5555',
        'image'      => 'https://picsum.photos/seed/protein1/600/450',
    ],
];

$created = 0;
$skipped = 0;
foreach ($listingsToSeed as $l) {
    if (!$l['user_id']) { out("  [skip]   No user ID for: {$l['title']}"); $skipped++; continue; }

    $dup = $pdo->prepare("SELECT id FROM listings WHERE user_id = :uid AND title = :t LIMIT 1");
    $dup->execute([':uid' => $l['user_id'], ':t' => $l['title']]);
    if ($dup->fetchColumn()) {
        out("  [skip]   Already exists: {$l['title']}");
        $skipped++;
        continue;
    }

    $token = generate_qr_token();
    $ins   = $pdo->prepare("
        INSERT INTO listings (user_id, title, description, price, price_type, category, image, phone, qr_token)
        VALUES (:uid, :title, :desc, :price, :pt, :cat, :img, :phone, :token)
    ");
    $ins->execute([
        ':uid'   => $l['user_id'],
        ':title' => $l['title'],
        ':desc'  => $l['description'] ?? '',
        ':price' => $l['price'],
        ':pt'    => $l['price_type'],
        ':cat'   => $l['category'],
        ':img'   => $l['image'],
        ':phone' => $l['phone'],
        ':token' => $token,
    ]);
    $created++;
    out("  [created] {$l['title']}");
}

out('');
out("=== Done — $created created, $skipped skipped ===");
out('');
out('Test credentials:');
out('  admin@vossie.net  /  Admin123!');
out('  alice@vossie.net  /  Student123!');
out('  bob@vossie.net    /  Student123!');
out('  carol@vossie.net  /  Student123!');
out('  david@vossie.net  /  Student123!');
out('  emma@vossie.net   /  Student123!');

if (!$isCli) echo '</body></html>';
