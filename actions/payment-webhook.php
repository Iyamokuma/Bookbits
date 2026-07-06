<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$gateway = strtolower(trim((string) ($_GET['gateway'] ?? '')));
$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo 'invalid payload';
    exit;
}

if ($gateway === 'paystack') {
    $sig = (string) ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '');
    $expected = hash_hmac('sha512', (string) $raw, BOOKBITS_PAYSTACK_SECRET_KEY);
    if ($sig === '' || !hash_equals($expected, $sig)) {
        http_response_code(401);
        echo 'invalid signature';
        exit;
    }
    $event = (string) ($payload['event'] ?? '');
    if ($event !== 'charge.success') {
        http_response_code(200);
        echo 'ignored';
        exit;
    }
    $reference = (string) ($payload['data']['reference'] ?? '');
} elseif ($gateway === 'korapayment') {
    $reference = (string) ($payload['data']['reference'] ?? '');
    $status = strtolower((string) ($payload['data']['status'] ?? ''));
    if ($status !== 'success') {
        http_response_code(200);
        echo 'ignored';
        exit;
    }
} else {
    http_response_code(400);
    echo 'unsupported gateway';
    exit;
}

if ($reference === '') {
    http_response_code(400);
    echo 'missing reference';
    exit;
}

$pay = db()->prepare(
    'SELECT p.id AS payment_id, p.order_id, o.user_id
     FROM payments p
     INNER JOIN orders o ON o.id = p.order_id
     WHERE p.transaction_ref = ?
     LIMIT 1'
);
$pay->execute([$reference]);
$row = $pay->fetch();
if ($row === false) {
    http_response_code(200);
    echo 'unknown reference';
    exit;
}

$verify = bb_verify_gateway_payment($gateway, $reference);
if (!($verify['ok'] && $verify['paid'])) {
    http_response_code(200);
    echo 'not paid';
    exit;
}

db()->beginTransaction();
try {
    bb_payment_apply_verification_result(
        (int) $row['payment_id'],
        (int) $row['order_id'],
        (int) $row['user_id'],
        true,
        (string) $verify['transaction_ref'],
        is_array($verify['gateway_response'] ?? null) ? $verify['gateway_response'] : []
    );
    db()->commit();
} catch (Throwable $e) {
    db()->rollBack();
    http_response_code(500);
    echo 'error';
    exit;
}

http_response_code(200);
echo 'ok';

