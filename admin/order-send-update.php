<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
bb_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php');
    exit;
}

bb_ensure_order_tracking_column();

$orderId = (int) ($_POST['order_id'] ?? 0);
$message = trim((string) ($_POST['message'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$status  = strtolower(trim((string) ($_POST['status'] ?? '')));
$tracking = trim((string) ($_POST['tracking_number'] ?? ''));
$customEmail = trim((string) ($_POST['custom_email'] ?? ''));
$listPage = max(1, (int) ($_POST['page'] ?? 1));

$allowedStatus = ['', 'processing', 'shipped', 'delivered'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

$backToList = static function (int $orderId, int $listPage): string {
    return BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage . '#order-' . $orderId;
};

if ($orderId < 1) {
    $_SESSION['admin_flash'] = 'Invalid order.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage);
    exit;
}

// Status-only actions (no custom message) are allowed for pipeline moves.
// Custom message form requires either a message or a status change that triggers auto-email.
if ($message === '' && $status === '') {
    $_SESSION['admin_flash'] = 'Choose a status and/or write a message before sending.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . $backToList($orderId, $listPage));
    exit;
}

$st = db()->prepare(
    'SELECT o.id, o.status AS order_status, p.status AS pay_status, u.email, u.name AS customer_name
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     LEFT JOIN payments p ON p.order_id = o.id
     WHERE o.id = ?
     LIMIT 1'
);
$st->execute([$orderId]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    $_SESSION['admin_flash'] = 'Order not found.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . BOOKBITS_BASE . '/admin/payments.php?page=' . $listPage);
    exit;
}

$customerEmail = (string) ($row['email'] ?? '');
$customerName  = (string) ($row['customer_name'] ?? '');

// Prefer explicit custom recipient when provided; otherwise always use the customer's account email.
$toEmail = $customerEmail;
if ($customEmail !== '') {
    if (!filter_var($customEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['admin_flash'] = 'Custom email address is not valid.';
        $_SESSION['admin_flash_error'] = true;
        header('Location: ' . $backToList($orderId, $listPage));
        exit;
    }
    $toEmail = $customEmail;
}

if ($toEmail === '') {
    $_SESSION['admin_flash'] = 'This customer has no email on file. Enter a custom email to send.';
    $_SESSION['admin_flash_error'] = true;
    header('Location: ' . $backToList($orderId, $listPage));
    exit;
}

$currentStatus = (string) ($row['order_status'] ?? '');
$previousStatus = $currentStatus;

if ($status !== '' && !in_array($currentStatus, ['cancelled', 'refunded'], true)) {
    if (bb_table_has_column('orders', 'tracking_number') && $tracking !== '') {
        db()->prepare('UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?')
            ->execute([$status, $tracking, $orderId]);
    } else {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
    }
    $currentStatus = $status;
} elseif ($tracking !== '' && bb_table_has_column('orders', 'tracking_number')) {
    db()->prepare('UPDATE orders SET tracking_number = ? WHERE id = ?')->execute([$tracking, $orderId]);
}

$sent = false;
$parts = [];

// Auto notifications when status moves into shipped / processing / delivered
if ($status === 'shipped' && $previousStatus !== 'shipped') {
    $sent = bb_send_shipped_notice($orderId, $tracking, $message, $toEmail);
    $parts[] = $sent ? 'shipped notice sent' : 'shipped notice failed';
} elseif ($status === 'processing' && $previousStatus !== 'processing' && $message === '') {
    $sent = bb_send_processing_notice($orderId, '', $toEmail);
    $parts[] = $sent ? 'confirmation email sent' : 'confirmation email failed';
} elseif ($status === 'delivered' && $previousStatus !== 'delivered' && $message === '') {
    // Reuse delivery notice but to chosen recipient
    $msg = "Great news — your order #{$orderId} has been marked as delivered. We hope you enjoy your books!";
    if ($tracking !== '') {
        $msg .= "\n\nTracking reference: {$tracking}";
    }
    $sent = bb_send_order_update_email(
        $orderId,
        $toEmail,
        $customerName,
        'Your order #' . $orderId . ' has been delivered — ' . BOOKBITS_STORE_NAME,
        $msg,
        'delivered',
        $tracking
    );
    $parts[] = $sent ? 'delivered notice sent' : 'delivered notice failed';
} elseif ($message !== '') {
    // Custom message (and optional status already applied above)
    if ($subject === '') {
        $subject = 'Update on your order #' . $orderId . ' — ' . BOOKBITS_STORE_NAME;
    }
    // If just moved to shipped with a custom message, shipped notice already included the extra text.
    if (!($status === 'shipped' && $previousStatus !== 'shipped')) {
        $sent = bb_send_order_update_email(
            $orderId,
            $toEmail,
            $customerName,
            $subject,
            $message,
            $currentStatus,
            $tracking
        );
        $parts[] = $sent ? 'custom message sent' : 'custom message failed';
    }
}

if ($sent) {
    $_SESSION['admin_flash'] = 'Email sent to customer (' . $toEmail . ')'
        . ($status !== '' ? ' · Order marked ' . $status : '')
        . ($parts !== [] ? ' · ' . implode(', ', $parts) : '') . '.';
} else {
    $why = bb_mail_last_error();
    $_SESSION['admin_flash'] = 'Order was updated, but the email could not be sent.'
        . ($why !== '' ? ' ' . $why : ' Check Resend API key / from-address in config/app.local.php.');
    $_SESSION['admin_flash_error'] = true;
}

header('Location: ' . $backToList($orderId, $listPage));
exit;
