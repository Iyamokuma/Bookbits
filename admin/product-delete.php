<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/books_db.php';
bb_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/admin/products.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $st = db()->prepare('SELECT cover_image FROM books WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row !== false) {
        bb_delete_stored_cover_file(isset($row['cover_image']) ? (string) $row['cover_image'] : null);
    }
    db()->prepare('DELETE FROM books WHERE id = ?')->execute([$id]);
    $_SESSION['admin_flash'] = 'Product deleted.';
}

header('Location: ' . BOOKBITS_BASE . '/admin/products.php');
exit;
