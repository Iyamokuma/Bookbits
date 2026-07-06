<?php

declare(strict_types=1);

function bb_blog_table_exists(): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        $st = db()->query("SHOW TABLES LIKE 'blogs'");
        $exists = $st !== false && $st->fetch() !== false;
    } catch (Throwable $e) {
        $exists = false;
    }

    return $exists;
}

/**
 * @return list<array<string,mixed>>
 */
function bb_fetch_blog_posts(int $limit = 5): array
{
    if (!bb_blog_table_exists()) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $st = db()->query(
        'SELECT id, title, slug, excerpt, body, cover_image, is_active, published_at, created_at
         FROM blogs
         WHERE is_active = 1
         ORDER BY COALESCE(published_at, created_at) DESC
         LIMIT ' . $limit
    );

    return $st !== false ? $st->fetchAll() : [];
}

/**
 * @return array<string,mixed>|null
 */
function bb_fetch_blog_post_by_slug(string $slug): ?array
{
    if (!bb_blog_table_exists()) {
        return null;
    }
    $st = db()->prepare(
        'SELECT id, title, slug, excerpt, body, cover_image, is_active, published_at, created_at
         FROM blogs
         WHERE slug = ? AND is_active = 1
         LIMIT 1'
    );
    $st->execute([$slug]);
    $row = $st->fetch();

    return $row !== false ? $row : null;
}

function bb_blog_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'post-' . date('YmdHis');
}

function bb_blog_cover_url(?string $value): string
{
    $c = trim((string) $value);
    if ($c === '') {
        return 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=900&q=80';
    }
    if (preg_match('#^https?://#i', $c)) {
        return $c;
    }
    if (str_starts_with($c, 'uploads/')) {
        return rtrim(BOOKBITS_BASE, '/') . '/assets/' . ltrim($c, '/');
    }

    return rtrim(BOOKBITS_BASE, '/') . '/assets/img/' . ltrim($c, '/');
}

/**
 * @return array{ok:bool,path:?string,message:?string}
 */
function bb_blog_cover_upload(): array
{
    if (!isset($_FILES['blog_cover_upload']['error'])
        || (int) $_FILES['blog_cover_upload']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'message' => null];
    }

    require_once __DIR__ . '/books_db.php';

    $err = (int) $_FILES['blog_cover_upload']['error'];
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'message' => bb_upload_error_message($err)];
    }
    $tmp = (string) ($_FILES['blog_cover_upload']['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'path' => null, 'message' => 'Invalid blog cover upload.'];
    }
    if ((int) $_FILES['blog_cover_upload']['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'path' => null, 'message' => 'Blog cover image must be 5 MB or smaller.'];
    }

    $mime = bb_detect_upload_image_mime($tmp, (string) ($_FILES['blog_cover_upload']['name'] ?? ''));
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($extMap[$mime])) {
        return ['ok' => false, 'path' => null, 'message' => 'Blog cover must be JPEG, PNG, WebP, or GIF.'];
    }

    $dir = dirname(__DIR__) . '/assets/uploads/blog';
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        return ['ok' => false, 'path' => null, 'message' => 'Could not create uploads/blog directory.'];
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
        if (!is_writable($dir)) {
            return ['ok' => false, 'path' => null, 'message' => 'uploads/blog directory is not writable.'];
        }
    }
    $name = 'blog_' . bin2hex(random_bytes(8)) . '.' . $extMap[$mime];
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'path' => null, 'message' => 'Could not save blog cover image.'];
    }
    @chmod($dest, 0644);

    return ['ok' => true, 'path' => 'uploads/blog/' . $name, 'message' => null];
}

function bb_delete_blog_cover_file(?string $relative): void
{
    if ($relative === null || $relative === '') {
        return;
    }
    if (!str_starts_with($relative, 'uploads/blog/')) {
        return;
    }
    $full = dirname(__DIR__) . '/assets/' . str_replace(['\\', '..'], ['/', ''], $relative);
    if (is_file($full)) {
        @unlink($full);
    }
}

