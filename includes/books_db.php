<?php

declare(strict_types=1);

/**
 * @return list<array<string,mixed>>
 */
function bb_fetch_books_for_shop(?string $catSlug, string $search): array
{
    $pdo = db();
    $sql = 'SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
            FROM books b
            INNER JOIN categories c ON c.id = b.category_id
            WHERE b.is_active = 1';
    $params = [];
    if ($catSlug !== null && $catSlug !== '') {
        $sql .= ' AND c.slug = ?';
        $params[] = $catSlug;
    }
    if ($search !== '') {
        $sql .= ' AND (b.title LIKE ? OR b.author LIKE ? OR COALESCE(b.isbn, \'\') LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' ORDER BY b.created_at DESC LIMIT 120';
    $st = $pdo->prepare($sql);
    $st->execute($params);

    return $st->fetchAll();
}

/**
 * @return array<string,mixed>|null
 */
function bb_fetch_book_by_id(int $id): ?array
{
    $st = db()->prepare(
        'SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
         FROM books b
         INNER JOIN categories c ON c.id = b.category_id
         WHERE b.id = ? AND b.is_active = 1 LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch();

    return $row !== false ? $row : null;
}

/**
 * @return list<array<string,mixed>>
 */
function bb_fetch_related_books(int $categoryId, int $excludeId, int $limit = 4): array
{
    $st = db()->prepare(
        'SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
         FROM books b
         INNER JOIN categories c ON c.id = b.category_id
         WHERE b.category_id = ? AND b.id <> ? AND b.is_active = 1
         ORDER BY b.created_at DESC
         LIMIT ' . (int) $limit
    );
    $st->execute([$categoryId, $excludeId]);

    return $st->fetchAll();
}

/**
 * @return list<array<string,mixed>>
 */
function bb_fetch_deal_books(int $limit = 8): array
{
    $st = db()->prepare(
        'SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
         FROM books b
         INNER JOIN categories c ON c.id = b.category_id
         WHERE b.is_active = 1 AND b.is_deal = 1
         ORDER BY b.updated_at DESC
         LIMIT ' . (int) $limit
    );
    $st->execute();

    return $st->fetchAll();
}

/**
 * @return list<array<string,mixed>>
 */
function bb_fetch_new_arrival_books(int $limit = 8): array
{
    if (!bb_table_has_column('books', 'is_new_arrival')) {
        return [];
    }

    $st = db()->prepare(
        'SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
         FROM books b
         INNER JOIN categories c ON c.id = b.category_id
         WHERE b.is_active = 1 AND b.is_new_arrival = 1
         ORDER BY b.updated_at DESC
         LIMIT ' . (int) $limit
    );
    $st->execute();

    return $st->fetchAll();
}

/**
 * Stationery products (stored as is_book_bundle in DB for backwards compatibility).
 *
 * @return list<array<string,mixed>>
 */
function bb_fetch_stationery_books(int $limit = 0): array
{
    if (!bb_table_has_column('books', 'is_book_bundle')) {
        return [];
    }

    $sql = 'SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
         FROM books b
         INNER JOIN categories c ON c.id = b.category_id
         WHERE b.is_active = 1 AND b.is_book_bundle = 1
         ORDER BY b.updated_at DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $st = db()->prepare($sql);
    $st->execute();

    return $st->fetchAll();
}

/**
 * @deprecated Use bb_fetch_stationery_books()
 * @return list<array<string,mixed>>
 */
function bb_fetch_book_bundle_books(int $limit = 8): array
{
    return bb_fetch_stationery_books($limit);
}

/**
 * @param array<string,mixed> $row
 */
/**
 * @return list<array{slug:string,name:string}>
 */
function bb_fetch_categories_for_nav(): array
{
    return db()
        ->query('SELECT slug, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name')
        ->fetchAll();
}

/**
 * @param array<string,mixed> $row
 */
function bb_book_cover_url(array $row): string
{
    $c = trim((string) ($row['cover_image'] ?? ''));
    if ($c === '') {
        return 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=400&q=80';
    }
    if (preg_match('#^https?://#i', $c)) {
        return $c;
    }
    if (str_starts_with($c, 'uploads/')) {
        return rtrim(BOOKBITS_BASE, '/') . '/assets/' . ltrim($c, '/');
    }

    return rtrim(BOOKBITS_BASE, '/') . '/assets/img/covers/' . ltrim($c, '/');
}

/**
 * Remove a file under assets/uploads/covers/ when replacing or deleting a book.
 */
function bb_delete_stored_cover_file(?string $relative): void
{
    if ($relative === null || $relative === '') {
        return;
    }
    if (!str_starts_with($relative, 'uploads/covers/')) {
        return;
    }
    $full = dirname(__DIR__) . '/assets/' . str_replace(['\\', '..'], ['/', ''], $relative);
    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * Human-readable description of a PHP file-upload error code.
 */
function bb_upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE   => 'The file exceeds the server upload_max_filesize limit.',
        UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the form MAX_FILE_SIZE limit.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded — try again.',
        UPLOAD_ERR_NO_FILE    => 'No file was selected.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder (contact hosting).',
        UPLOAD_ERR_CANT_WRITE => 'Server could not write the temp file (disk full?).',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
        default               => 'Unknown upload error (code ' . $code . ').',
    };
}

/**
 * Handle admin cover upload (field name: cover_upload).
 *
 * @return array{ok: bool, path: ?string, message: ?string} path relative, e.g. uploads/covers/cover_xxx.jpg
 */
function bb_book_cover_upload(): array
{
    if (!isset($_FILES['cover_upload']['error'])
        || (int) $_FILES['cover_upload']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'message' => null];
    }

    $err = (int) $_FILES['cover_upload']['error'];
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'message' => bb_upload_error_message($err)];
    }

    $tmp = (string) ($_FILES['cover_upload']['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'path' => null, 'message' => 'Invalid upload (tmp file missing).'];
    }

    if ((int) $_FILES['cover_upload']['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'path' => null, 'message' => 'Cover image must be 5 MB or smaller.'];
    }

    $mime = bb_detect_upload_image_mime($tmp, (string) ($_FILES['cover_upload']['name'] ?? ''));
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($extMap[$mime])) {
        return ['ok' => false, 'path' => null, 'message' => 'Cover must be a JPEG, PNG, WebP, or GIF image.'];
    }

    $dir = dirname(__DIR__) . '/assets/uploads/covers';

    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            return ['ok' => false, 'path' => null, 'message' => 'Could not create uploads/covers/ directory. Check folder permissions.'];
        }
    }

    // Ensure the directory is writable (auto-fix on shared/XAMPP setups).
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
        if (!is_writable($dir)) {
            return ['ok' => false, 'path' => null, 'message' => 'Upload directory is not writable. Run: chmod -R 777 assets/uploads/'];
        }
    }

    $name = 'cover_' . bin2hex(random_bytes(8)) . '.' . $extMap[$mime];
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($tmp, $dest)) {
        $last = error_get_last();
        $detail = $last ? ' (' . $last['message'] . ')' : '';
        return ['ok' => false, 'path' => null, 'message' => 'Could not save cover image' . $detail . '.'];
    }

    @chmod($dest, 0644);

    return ['ok' => true, 'path' => 'uploads/covers/' . $name, 'message' => null];
}

/**
 * Detect image MIME from an uploaded temp file (works without ext-fileinfo if needed).
 */
function bb_detect_upload_image_mime(string $tmp, string $originalName): string
{
    if (class_exists(finfo::class)) {
        try {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $m  = $fi->file($tmp);
            if ($m !== false && $m !== '') {
                return $m;
            }
        } catch (Throwable $e) {
            // fall through
        }
    }
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($tmp);
        if ($m !== false && $m !== '') {
            return $m;
        }
    }
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $fromExt = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];

    return $fromExt[$ext] ?? '';
}
