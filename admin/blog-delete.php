<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
bb_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/admin/blogs.php');
    exit;
}
if (!bb_blog_table_exists()) {
    $_SESSION['admin_flash'] = 'Blog table is missing. Run upgrade statements in database/schema.sql first.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/blogs.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    $_SESSION['admin_flash'] = 'Invalid blog post.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/blogs.php');
    exit;
}

$st = db()->prepare('SELECT cover_image FROM blogs WHERE id = ? LIMIT 1');
$st->execute([$id]);
$row = $st->fetch();

$del = db()->prepare('DELETE FROM blogs WHERE id = ?');
$del->execute([$id]);

if ($row !== false) {
    bb_delete_blog_cover_file((string) ($row['cover_image'] ?? ''));
}

$_SESSION['admin_flash'] = 'Blog post deleted.';
header('Location: ' . BOOKBITS_BASE . '/admin/blogs.php');
exit;

