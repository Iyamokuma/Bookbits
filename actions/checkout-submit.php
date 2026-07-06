<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/books_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/checkout.php');
    exit;
}

$user = bb_current_user();
if ($user === null) {
    header('Location: ' . BOOKBITS_BASE . '/login.php?redirect=' . rawurlencode('checkout.php') . '&from=checkout');
    exit;
}

$lines = bb_cart_lines_with_books();
if (count($lines) === 0) {
    $_SESSION['flash_checkout'] = 'Your cart is empty.';
    header('Location: ' . BOOKBITS_BASE . '/checkout.php');
    exit;
}

$sub = bb_cart_subtotal($lines);
$name    = trim((string) ($_POST['shipping_name'] ?? ''));
$phone   = trim((string) ($_POST['shipping_phone'] ?? ''));
$addr    = trim((string) ($_POST['shipping_address'] ?? ''));
$city    = trim((string) ($_POST['shipping_city'] ?? ''));
$state   = trim((string) ($_POST['shipping_state'] ?? ''));
$zip     = trim((string) ($_POST['shipping_zip'] ?? ''));
$country = trim((string) ($_POST['shipping_country'] ?? ''));
$notes   = trim((string) ($_POST['notes'] ?? ''));
$notesDb = $notes === '' ? null : $notes;
$gateway = strtolower(trim((string) ($_POST['payment_gateway'] ?? 'paystack')));
if (!array_key_exists($gateway, bb_payment_method_labels())) {
    $gateway = 'paystack';
}

if ($name === '' || $phone === '' || $addr === '' || $city === '' || $state === '' || $zip === '' || $country === '') {
    $_SESSION['checkout_draft'] = [
        'shipping_name'    => $name,
        'shipping_phone'   => $phone,
        'shipping_address' => $addr,
        'shipping_city'    => $city,
        'shipping_state'   => $state,
        'shipping_zip'     => $zip,
        'shipping_country' => $country,
        'notes'            => $notes,
        'payment_gateway'  => $gateway,
    ];
    $_SESSION['flash_checkout'] = 'Please complete all required delivery fields.';
    header('Location: ' . BOOKBITS_BASE . '/checkout.php');
    exit;
}

$hasShipPhone = bb_table_has_column('orders', 'shipping_phone');

$pdo = db();
$pdo->beginTransaction();
try {
    if ($hasShipPhone) {
        $insO = $pdo->prepare(
            'INSERT INTO orders (user_id, status, subtotal, discount, shipping_fee, total, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country, notes)
             VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insO->execute([
            $user['id'],
            'pending',
            $sub,
            $sub,
            $name,
            $phone,
            $addr,
            $city,
            $state,
            $zip,
            $country,
            $notesDb,
        ]);
    } else {
        $notesLegacy = 'Phone: ' . $phone . ($notesDb !== null ? "\n\n" . $notesDb : '');
        $insO = $pdo->prepare(
            'INSERT INTO orders (user_id, status, subtotal, discount, shipping_fee, total, shipping_name, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country, notes)
             VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insO->execute([
            $user['id'],
            'pending',
            $sub,
            $sub,
            $name,
            $addr,
            $city,
            $state,
            $zip,
            $country,
            $notesLegacy,
        ]);
    }
    $orderId = (int) $pdo->lastInsertId();

    $hasOrderItemCoverType = bb_table_has_column('order_items', 'cover_type');
    $insI = $hasOrderItemCoverType
        ? $pdo->prepare('INSERT INTO order_items (order_id, book_id, title, author, cover_type, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        : $pdo->prepare('INSERT INTO order_items (order_id, book_id, title, author, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($lines as $line) {
        $b   = $line['book'];
        $coverType = isset($line['cover_type']) && is_string($line['cover_type']) ? $line['cover_type'] : null;
        $qty = (int) $line['qty'];
        $unit = bb_book_cover_price($b, $coverType);
        $ls   = round($unit * $qty, 2);
        if ($hasOrderItemCoverType) {
            $insI->execute([
                $orderId,
                (int) $b['id'],
                (string) $b['title'],
                (string) $b['author'],
                $coverType,
                $qty,
                $unit,
                $ls,
            ]);
        } else {
            $insI->execute([
                $orderId,
                (int) $b['id'],
                (string) $b['title'],
                (string) $b['author'],
                $qty,
                $unit,
                $ls,
            ]);
        }
    }

    $reference = bb_payment_reference($gateway, $orderId);
    $dbMethod = bb_payment_db_method($gateway);
    $gatewayMeta = ['gateway' => $gateway, 'reference' => $reference];
    $insP = $pdo->prepare(
        'INSERT INTO payments (order_id, method, status, amount, currency, transaction_ref, gateway_response) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insP->execute([$orderId, $dbMethod, 'pending', $sub, BOOKBITS_CURRENCY_CODE, $reference, json_encode($gatewayMeta)]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    $_SESSION['flash_checkout'] = 'Could not place order. Please try again.';
    header('Location: ' . BOOKBITS_BASE . '/checkout.php');
    exit;
}

try {
    $gatewayInit = bb_initiate_gateway_payment($gateway, ['id' => $orderId, 'total' => $sub], $user, $reference);
    if (!$gatewayInit['ok'] || empty($gatewayInit['redirect_url'])) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute(['cancelled', $orderId]);
        db()->prepare('UPDATE payments SET status = ?, gateway_response = ? WHERE order_id = ?')
            ->execute(['failed', json_encode($gatewayInit['response'] ?? []), $orderId]);
        $_SESSION['checkout_draft'] = [
            'shipping_name'    => $name,
            'shipping_phone'   => $phone,
            'shipping_address' => $addr,
            'shipping_city'    => $city,
            'shipping_state'   => $state,
            'shipping_zip'     => $zip,
            'shipping_country' => $country,
            'notes'            => $notes,
            'payment_gateway'  => $gateway,
        ];
        $_SESSION['flash_checkout'] = 'Order was created, but payment could not start: ' . ($gatewayInit['error'] ?? 'Unknown gateway error');
        header('Location: ' . BOOKBITS_BASE . '/checkout.php');
        exit;
    }
} catch (Throwable $e) {
    db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute(['cancelled', $orderId]);
    db()->prepare('UPDATE payments SET status = ? WHERE order_id = ?')->execute(['failed', $orderId]);
    $_SESSION['checkout_draft'] = [
        'shipping_name'    => $name,
        'shipping_phone'   => $phone,
        'shipping_address' => $addr,
        'shipping_city'    => $city,
        'shipping_state'   => $state,
        'shipping_zip'     => $zip,
        'shipping_country' => $country,
        'notes'            => $notes,
        'payment_gateway'  => $gateway,
    ];
    $_SESSION['flash_checkout'] = 'Order was created, but payment initialization failed.';
    header('Location: ' . BOOKBITS_BASE . '/checkout.php');
    exit;
}

header('Location: ' . (string) $gatewayInit['redirect_url']);
exit;
