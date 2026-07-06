<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
bb_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/admin/categories.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $st = db()->prepare('SELECT name FROM categories WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $cat = $st->fetch();

    if ($cat === false) {
        $_SESSION['admin_flash'] = 'Category not found.';
        $_SESSION['admin_flash_error'] = true;
    } else {
        $bookCount = db()->prepare('SELECT COUNT(*) FROM books WHERE category_id = ?');
        $bookCount->execute([$id]);
        $inUse = (int) $bookCount->fetchColumn();

        if ($inUse > 0) {
            $_SESSION['admin_flash'] = 'Cannot delete “' . (string) $cat['name'] . '”: ' . $inUse . ' book(s) still use this category. Reassign or delete those books first.';
            $_SESSION['admin_flash_error'] = true;
        } else {
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            $_SESSION['admin_flash'] = 'Category deleted.';
        }
    }
}

header('Location: ' . BOOKBITS_BASE . '/admin/categories.php');
exit;
