<?php
// --- Site Configuration ---
define('ALLOWED_DOMAIN', '@vossie.net');
define('ADMIN_EMAIL',    'admin@vossie.net'); // change to real admin email
define('SITE_NAME',      'Eduvos Marketplace');
define('BASE_URL',       'http://localhost:8080');

define('CATEGORIES', [
    'Textbooks & Study Material',
    'Electronics & Gadgets',
    'Clothing & Accessories',
    'Food & Snacks',
    'Art & Crafts',
    'Beauty & Hair',
    'Tutoring & Services',
    'Sports & Gaming',
    'Other',
]);

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIME',  ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
