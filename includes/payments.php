<?php

declare(strict_types=1);

function bb_payment_method_labels(): array
{
    return [
        'paystack'    => 'Paystack',
        'klump'       => 'Klump',
        'korapayment' => 'Korapay',
    ];
}

function bb_payment_db_method(string $gateway): string
{
    $gateway = strtolower(trim($gateway));
    if (!in_array($gateway, ['paystack', 'klump', 'korapayment'], true)) {
        return 'card';
    }

    $st = db()->query("SHOW COLUMNS FROM `payments` LIKE 'method'");
    $row = $st !== false ? $st->fetch() : false;
    if ($row === false) {
        return 'card';
    }
    $colType = (string) ($row['Type'] ?? '');
    if (stripos($colType, $gateway) !== false) {
        return $gateway;
    }

    return 'card';
}

function bb_payment_reference(string $gateway, int $orderId): string
{
    $prefix = match ($gateway) {
        'paystack' => 'PST',
        'klump' => 'KLP',
        'korapayment' => 'KRP',
        default => 'PAY',
    };

    return $prefix . '-' . $orderId . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * @return array{ok:bool,status_code:int,body:array<string,mixed>|null,error:?string}
 */
function bb_http_json(string $method, string $url, array $headers = [], ?array $payload = null): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'status_code' => 0, 'body' => null, 'error' => 'Could not initialize HTTP client.'];
    }
    $headerLines = [];
    foreach ($headers as $k => $v) {
        $headerLines[] = $k . ': ' . $v;
    }
    if ($payload !== null) {
        $headerLines[] = 'Content-Type: application/json';
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headerLines,
        CURLOPT_TIMEOUT        => 40,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'status_code' => $code, 'body' => null, 'error' => $err !== '' ? $err : 'Gateway request failed.'];
    }
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        $body = ['raw' => $raw];
    }

    return ['ok' => $code >= 200 && $code < 300, 'status_code' => $code, 'body' => $body, 'error' => null];
}

/**
 * @param array<string,mixed> $order
 * @param array<string,mixed> $user
 * @return array{ok:bool,redirect_url:?string,error:?string,response:array<string,mixed>}
 */
function bb_initiate_gateway_payment(string $gateway, array $order, array $user, string $reference): array
{
    $gateway = strtolower($gateway);
    $amount = (float) ($order['total'] ?? 0);
    $orderId = (int) $order['id'];
    $base = rtrim(BOOKBITS_BASE, '/');
    $callback = bb_absolute_url($base . '/actions/payment-callback.php?gateway=' . rawurlencode($gateway) . '&order=' . $orderId);

    if ($gateway === 'paystack') {
        if (BOOKBITS_PAYSTACK_SECRET_KEY === '') {
            return ['ok' => false, 'redirect_url' => null, 'error' => 'Paystack keys are not configured.', 'response' => []];
        }
        $r = bb_http_json('POST', 'https://api.paystack.co/transaction/initialize', [
            'Authorization' => 'Bearer ' . BOOKBITS_PAYSTACK_SECRET_KEY,
        ], [
            'email' => (string) $user['email'],
            'amount' => (int) round($amount * 100),
            'currency' => BOOKBITS_CURRENCY_CODE,
            'reference' => $reference,
            'callback_url' => $callback,
            'metadata' => [
                'order_id' => $orderId,
                'user_id' => (int) $user['id'],
            ],
        ]);
        $authUrl = (string) (($r['body']['data']['authorization_url'] ?? ''));
        if (!$r['ok'] || $authUrl === '') {
            return ['ok' => false, 'redirect_url' => null, 'error' => 'Could not initialize Paystack transaction.', 'response' => $r['body'] ?? []];
        }

        return ['ok' => true, 'redirect_url' => $authUrl, 'error' => null, 'response' => $r['body'] ?? []];
    }

    if ($gateway === 'korapayment') {
        if (BOOKBITS_KORAPAY_SECRET_KEY === '') {
            return ['ok' => false, 'redirect_url' => null, 'error' => 'Korapay keys are not configured.', 'response' => []];
        }
        $r = bb_http_json('POST', 'https://api.korapay.com/merchant/api/v1/charges/initialize', [
            'Authorization' => 'Bearer ' . BOOKBITS_KORAPAY_SECRET_KEY,
        ], [
            'amount' => (int) round($amount),
            'currency' => BOOKBITS_CURRENCY_CODE,
            'reference' => $reference,
            'redirect_url' => $callback,
            'notification_url' => bb_absolute_url($base . '/actions/payment-webhook.php?gateway=korapayment'),
            'customer' => [
                'name' => (string) $user['name'],
                'email' => (string) $user['email'],
            ],
        ]);
        $checkoutUrl = (string) (($r['body']['data']['checkout_url'] ?? ''));
        if (!$r['ok'] || $checkoutUrl === '') {
            return ['ok' => false, 'redirect_url' => null, 'error' => 'Could not initialize Korapay transaction.', 'response' => $r['body'] ?? []];
        }

        return ['ok' => true, 'redirect_url' => $checkoutUrl, 'error' => null, 'response' => $r['body'] ?? []];
    }

    if ($gateway === 'klump') {
        $url = rtrim(BOOKBITS_BASE_URL, '/') . rtrim(BOOKBITS_BASE, '/') . '/klump-pay.php?order=' . $orderId . '&reference=' . rawurlencode($reference);
        return ['ok' => true, 'redirect_url' => $url, 'error' => null, 'response' => []];
    }

    return ['ok' => false, 'redirect_url' => null, 'error' => 'Unsupported payment gateway selected.', 'response' => []];
}

/**
 * @return array{ok:bool,paid:bool,transaction_ref:string,gateway_response:array<string,mixed>,error:?string}
 */
function bb_verify_gateway_payment(string $gateway, string $reference): array
{
    $gateway = strtolower($gateway);

    if ($gateway === 'paystack') {
        if (BOOKBITS_PAYSTACK_SECRET_KEY === '') {
            return ['ok' => false, 'paid' => false, 'transaction_ref' => $reference, 'gateway_response' => [], 'error' => 'Paystack key missing'];
        }
        $r = bb_http_json('GET', 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference), [
            'Authorization' => 'Bearer ' . BOOKBITS_PAYSTACK_SECRET_KEY,
        ]);
        $status = (string) ($r['body']['data']['status'] ?? '');
        return [
            'ok' => $r['ok'],
            'paid' => $r['ok'] && strtolower($status) === 'success',
            'transaction_ref' => (string) ($r['body']['data']['reference'] ?? $reference),
            'gateway_response' => $r['body'] ?? [],
            'error' => $r['ok'] ? null : 'Paystack verification failed',
        ];
    }

    if ($gateway === 'korapayment') {
        if (BOOKBITS_KORAPAY_SECRET_KEY === '') {
            return ['ok' => false, 'paid' => false, 'transaction_ref' => $reference, 'gateway_response' => [], 'error' => 'Korapay key missing'];
        }
        $r = bb_http_json('GET', 'https://api.korapay.com/merchant/api/v1/charges/' . rawurlencode($reference), [
            'Authorization' => 'Bearer ' . BOOKBITS_KORAPAY_SECRET_KEY,
        ]);
        $status = strtolower((string) ($r['body']['data']['status'] ?? ''));
        return [
            'ok' => $r['ok'],
            'paid' => $r['ok'] && $status === 'success',
            'transaction_ref' => (string) ($r['body']['data']['reference'] ?? $reference),
            'gateway_response' => $r['body'] ?? [],
            'error' => $r['ok'] ? null : 'Korapay verification failed',
        ];
    }

    if ($gateway === 'klump') {
        if (BOOKBITS_KLUMP_SECRET_KEY === '') {
            return ['ok' => false, 'paid' => false, 'transaction_ref' => $reference, 'gateway_response' => [], 'error' => 'Klump key missing'];
        }
        $r = bb_http_json('GET', 'https://api.useklump.com/v1/transactions/' . rawurlencode($reference) . '/verify', [
            'klump-secret-key' => BOOKBITS_KLUMP_SECRET_KEY,
        ]);
        $status = strtolower((string) ($r['body']['data']['status'] ?? ''));
        return [
            'ok' => $r['ok'],
            'paid' => $r['ok'] && in_array($status, ['success', 'successful', 'completed'], true),
            'transaction_ref' => (string) ($r['body']['data']['reference'] ?? $reference),
            'gateway_response' => $r['body'] ?? [],
            'error' => $r['ok'] ? null : 'Klump verification failed',
        ];
    }

    return ['ok' => false, 'paid' => false, 'transaction_ref' => $reference, 'gateway_response' => [], 'error' => 'Unsupported gateway'];
}

function bb_absolute_url(string $path): string
{
    return rtrim(BOOKBITS_BASE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * JSON snapshot of checkout form fields + order lines (for admin after payment).
 */
function bb_order_checkout_snapshot_json(int $orderId): string
{
    $pdo = db();
    $hasPhone = bb_table_has_column('orders', 'shipping_phone');
    $fields = 'shipping_name, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country, notes, subtotal, discount, shipping_fee, total';
    if ($hasPhone) {
        $fields = 'shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country, notes, subtotal, discount, shipping_fee, total';
    }
    $o = $pdo->prepare('SELECT ' . $fields . ' FROM orders WHERE id = ? LIMIT 1');
    $o->execute([$orderId]);
    $orderRow = $o->fetch(PDO::FETCH_ASSOC);
    if ($orderRow === false) {
        return json_encode(['error' => 'order_not_found', 'order_id' => $orderId], JSON_UNESCAPED_SLASHES);
    }

    $hasCover = bb_table_has_column('order_items', 'cover_type');
    $sql = $hasCover
        ? 'SELECT title, author, cover_type, qty, unit_price, subtotal FROM order_items WHERE order_id = ? ORDER BY id ASC'
        : 'SELECT title, author, qty, unit_price, subtotal FROM order_items WHERE order_id = ? ORDER BY id ASC';
    $it = $pdo->prepare($sql);
    $it->execute([$orderId]);
    $lines = $it->fetchAll(PDO::FETCH_ASSOC);

    return json_encode([
        'shipping' => $orderRow,
        'items' => $lines,
        'captured_at' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Persist payment verification: payment row, order status, optional snapshot & payment_confirmed_at, clear cart if paid.
 * Call inside an open DB transaction.
 */
function bb_payment_apply_verification_result(
    int $paymentId,
    int $orderId,
    int $userId,
    bool $paid,
    string $transactionRef,
    array $gatewayResponse
): void {
    $gatewayJson = json_encode($gatewayResponse, JSON_UNESCAPED_SLASHES);
    $payStatus = $paid ? 'completed' : 'failed';
    $orderStatus = $paid ? 'processing' : 'pending';

    if ($paid) {
        db()->prepare('UPDATE payments SET status = ?, transaction_ref = ?, gateway_response = ?, paid_at = COALESCE(paid_at, NOW()) WHERE id = ?')
            ->execute([$payStatus, $transactionRef, $gatewayJson, $paymentId]);
    } else {
        db()->prepare('UPDATE payments SET status = ?, transaction_ref = ?, gateway_response = ? WHERE id = ?')
            ->execute([$payStatus, $transactionRef, $gatewayJson, $paymentId]);
    }

    $hasPca = bb_table_has_column('orders', 'payment_confirmed_at');
    $hasSnap = bb_table_has_column('orders', 'checkout_snapshot');

    if ($paid && $hasPca && $hasSnap) {
        $snap = bb_order_checkout_snapshot_json($orderId);
        db()->prepare('UPDATE orders SET status = ?, payment_confirmed_at = COALESCE(payment_confirmed_at, NOW()), checkout_snapshot = ? WHERE id = ?')
            ->execute([$orderStatus, $snap, $orderId]);
    } elseif ($paid && $hasPca) {
        db()->prepare('UPDATE orders SET status = ?, payment_confirmed_at = COALESCE(payment_confirmed_at, NOW()) WHERE id = ?')
            ->execute([$orderStatus, $orderId]);
    } elseif ($paid && $hasSnap) {
        $snap = bb_order_checkout_snapshot_json($orderId);
        db()->prepare('UPDATE orders SET status = ?, checkout_snapshot = ? WHERE id = ?')
            ->execute([$orderStatus, $snap, $orderId]);
    } else {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$orderStatus, $orderId]);
    }

    if ($paid) {
        bb_cart_clear_for_user($userId);

        if (function_exists('bb_send_order_confirmation')) {
            bb_send_order_confirmation($orderId);
            bb_send_admin_order_notification($orderId);
        }
    }
}

