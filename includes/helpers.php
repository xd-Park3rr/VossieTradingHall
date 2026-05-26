<?php
require_once __DIR__ . '/config.php';

/**
 * Upload a listing image.
 * Returns the stored filename on success, or throws on failure.
 */
function upload_listing_image(array $file): string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('File exceeds 5 MB limit.');
    }

    // Validate MIME by reading actual file content
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, ALLOWED_MIME, true)) {
        throw new RuntimeException('Only JPEG, PNG, WebP and GIF images are allowed.');
    }

    $ext      = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    };

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    return $filename;
}

/**
 * Generate a secure QR token for a listing.
 */
function generate_qr_token(): string {
    return bin2hex(random_bytes(32)); // 64 hex chars
}

/**
 * Return the QR code image URL for a given confirm URL.
 */
function qr_image_url(string $token): string {
    $confirmUrl = BASE_URL . '/confirm-transaction.php?token=' . urlencode($token);
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($confirmUrl);
}

/**
 * Resolve a listing image value to a displayable src URL.
 * Supports:
 *   - full external URLs (https://picsum.photos/...)
 *   - local filenames stored in /uploads/
 *   - null → returns null
 */
function listing_image_src(?string $image): ?string {
    if (!$image) return null;
    if (str_starts_with($image, 'https://') || str_starts_with($image, 'http://')) {
        return $image;
    }
    return '/uploads/' . $image;
}

/**
 * Emoji icon for a category — used in fallback cards and pills.
 */
function category_icon(string $category): string {
    return match(true) {
        str_contains($category, 'Textbook') || str_contains($category, 'Study') => '📚',
        str_contains($category, 'Electronic') || str_contains($category, 'Gadget') => '💻',
        str_contains($category, 'Cloth') || str_contains($category, 'Accessor') => '👗',
        str_contains($category, 'Food') || str_contains($category, 'Snack') => '🍱',
        str_contains($category, 'Art') || str_contains($category, 'Craft') => '🎨',
        str_contains($category, 'Beauty') || str_contains($category, 'Hair') => '💅',
        str_contains($category, 'Tutor') || str_contains($category, 'Service') => '🎓',
        str_contains($category, 'Sport') || str_contains($category, 'Gaming') => '🎮',
        default => '📦',
    };
}
