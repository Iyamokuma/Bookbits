<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$gateway = strtolower(trim((string) ($_GET['gateway'] ?? '')));
$orderId = (int) ($_GET['order'] ?? 0);
$reference = trim((string) ($_GET['reference'] ?? $_GET['trxref'] ?? $_GET['tx_ref'] ?? ''));

if ($orderId < 1 || $gateway === '') {
    header('Location: ' . BOOKBITS_BASE . '/payment-result.php?ok=0&msg=invalid_callback');
    exit;
}

$st = db()->prepare(
    'SELECT o.id AS order_id, o.user_id, p.id AS payment_id, p.transaction_ref
     FROM orders o
     INNER JOIN payments p ON p.order_id = o.id
     WHERE o.id = ?
     LIMIT 1'
);
$st->execute([$orderId]);
$row = $st->fetch();
if ($row === false) {
    header('Location: ' . BOOKBITS_BASE . '/payment-result.php?ok=0&msg=order_not_found');
    exit;
}

$storedReference = (string) ($row['transaction_ref'] ?? '');
if ($reference === '') {
    $reference = $storedReference;
}
if ($reference === '') {
    header('Location: ' . BOOKBITS_BASE . '/payment-result.php?ok=0&msg=missing_reference&order=' . $orderId);
    exit;
}

$verify = bb_verify_gateway_payment($gateway, $reference);
$paid = $verify['ok'] && $verify['paid'];

db()->beginTransaction();
try {
    bb_payment_apply_verification_result(
        (int) $row['payment_id'],
        $orderId,
        (int) $row['user_id'],
        $paid,
        (string) $verify['transaction_ref'],
        is_array($verify['gateway_response'] ?? null) ? $verify['gateway_response'] : []
    );
    db()->commit();
} catch (Throwable $e) {
    db()->rollBack();
    header('Location: ' . BOOKBITS_BASE . '/payment-result.php?ok=0&msg=save_failed&order=' . $orderId);
    exit;
}

if ($paid) {
    $_SESSION['flash_success'] = 'Payment successful for Order #' . $orderId . '.';
    header('Location: ' . BOOKBITS_BASE . '/payment-result.php?ok=1&order=' . $orderId);
    exit;
}

$_SESSION['flash_checkout'] = 'Payment was not completed. You can retry from checkout.';
header('Location: ' . BOOKBITS_BASE . '/payment-result.php?ok=0&order=' . $orderId . '&msg=not_paid');
exit;

