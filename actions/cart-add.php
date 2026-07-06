<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$bookId = (int) ($_POST['book_id'] ?? $_GET['book_id'] ?? 0);
$qty    = (int) ($_POST['qty'] ?? 1);
$coverType = trim((string) ($_POST['cover_type'] ?? ''));
if ($qty < 1) {
    $qty = 1;
}

$result = bb_cart_add($bookId, $qty, $coverType === '' ? null : $coverType);

header('X-Content-Type-Options: nosniff');
echo json_encode([
    'ok'      => $result['ok'],
    'message' => $result['message'],
    'count'   => bb_cart_total_qty(),
]);
