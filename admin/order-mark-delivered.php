<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
bb_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php');
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$listPage = max(1, (int) ($_POST['page'] ?? 1));
if ($orderId < 1) {
    $_SESSION['admin_flash'] = 'Invalid order.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage);
    exit;
}

$st = db()->prepare(
    'SELECT o.status AS order_status, p.status AS pay_status
     FROM orders o
     LEFT JOIN payments p ON p.order_id = o.id
     WHERE o.id = ?
     LIMIT 1'
);
$st->execute([$orderId]);
$row = $st->fetch();

if ($row === false) {
    $_SESSION['admin_flash'] = 'Order not found.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage);
    exit;
}

$payStatus = (string) ($row['pay_status'] ?? '');
$orderStatus = (string) ($row['order_status'] ?? '');

if ($payStatus !== 'completed') {
    $_SESSION['admin_flash'] = 'Only orders with completed payment can be marked delivered.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage . '#order-' . $orderId);
    exit;
}

if (in_array($orderStatus, ['delivered', 'cancelled', 'refunded'], true)) {
    $_SESSION['admin_flash'] = 'This order cannot be marked delivered (already finalized or cancelled).';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage . '#order-' . $orderId);
    exit;
}

db()->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?")->execute([$orderId]);

$emailed = bb_send_delivery_notice($orderId);
$_SESSION['admin_flash'] = 'Order #' . $orderId . ' marked as delivered.'
    . ($emailed ? ' Customer notified by email.' : ' (Email could not be sent — check Resend settings.)');
if (!$emailed) {
    $_SESSION['admin_flash_error'] = true;
}

header('Location: ' . BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage . '#order-' . $orderId);
exit;
