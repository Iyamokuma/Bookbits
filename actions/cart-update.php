<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$bookId = (int) ($_POST['book_id'] ?? 0);
$qty    = (int) ($_POST['qty'] ?? 0);
$coverType = trim((string) ($_POST['cover_type'] ?? ''));

$result = bb_cart_set_qty($bookId, $qty, $coverType === '' ? null : $coverType);

echo json_encode([
    'ok'      => $result['ok'],
    'message' => $result['message'],
    'count'   => bb_cart_total_qty(),
]);
