<?php

declare(strict_types=1);

/**
 * Send an order confirmation email to the customer after successful payment.
 * Returns true on success, false on failure. Never throws — payment flow must not break.
 */
function bb_send_order_confirmation(int $orderId): bool
{
    try {
        $pdo = db();

        $st = $pdo->prepare(
            'SELECT o.*, u.email, u.name AS customer_name
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             WHERE o.id = ? LIMIT 1'
        );
        $st->execute([$orderId]);
        $order = $st->fetch(PDO::FETCH_ASSOC);
        if ($order === false || empty($order['email'])) {
            return false;
        }

        $hasCover = bb_table_has_column('order_items', 'cover_type');
        $sql = $hasCover
            ? 'SELECT title, author, cover_type, qty, unit_price, subtotal FROM order_items WHERE order_id = ? ORDER BY id ASC'
            : 'SELECT title, author, qty, unit_price, subtotal FROM order_items WHERE order_id = ? ORDER BY id ASC';
        $it = $pdo->prepare($sql);
        $it->execute([$orderId]);
        $items = $it->fetchAll(PDO::FETCH_ASSOC);

        $paySt = $pdo->prepare('SELECT method, transaction_ref, paid_at FROM payments WHERE order_id = ? AND status = ? LIMIT 1');
        $paySt->execute([$orderId, 'completed']);
        $payment = $paySt->fetch(PDO::FETCH_ASSOC);

        $html = bb_build_order_email_html($order, $items, $payment ?: []);
        $plain = bb_build_order_email_plain($order, $items, $payment ?: []);

        $to = (string) $order['email'];
        $subject = 'Order #' . $orderId . ' confirmed — ' . BOOKBITS_STORE_NAME;

        $boundary = 'BB_' . md5(uniqid((string) mt_rand(), true));
        $headers = implode("\r\n", [
            'From: ' . BOOKBITS_STORE_NAME . ' <' . BOOKBITS_STORE_EMAIL . '>',
            'Reply-To: ' . BOOKBITS_STORE_EMAIL,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: Bookbits/1.0',
        ]);

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $plain . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= "--$boundary--\r\n";

        return mail($to, $subject, $body, $headers);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Send an admin notification email when a new paid order comes in.
 */
function bb_send_admin_order_notification(int $orderId): bool
{
    if (BOOKBITS_STORE_EMAIL === '') {
        return false;
    }

    try {
        $pdo = db();

        $st = $pdo->prepare(
            'SELECT o.id, o.total, o.shipping_name, o.created_at, u.name AS customer_name, u.email
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             WHERE o.id = ? LIMIT 1'
        );
        $st->execute([$orderId]);
        $order = $st->fetch(PDO::FETCH_ASSOC);
        if ($order === false) {
            return false;
        }

        $to = BOOKBITS_STORE_EMAIL;
        $subject = 'New paid order #' . $orderId . ' — ' . bb_format_money((float) $order['total']);
        $headers = implode("\r\n", [
            'From: ' . BOOKBITS_STORE_NAME . ' <' . BOOKBITS_STORE_EMAIL . '>',
            'Content-Type: text/plain; charset=UTF-8',
        ]);

        $msg  = "New paid order received!\n\n";
        $msg .= "Order:    #$orderId\n";
        $msg .= "Customer: " . ($order['customer_name'] ?? '') . " (" . ($order['email'] ?? '') . ")\n";
        $msg .= "Ship to:  " . ($order['shipping_name'] ?? '') . "\n";
        $msg .= "Total:    " . bb_format_money((float) $order['total']) . "\n";
        $msg .= "Date:     " . ($order['created_at'] ?? '') . "\n\n";
        $msg .= "View full details on your admin dashboard.\n";

        return mail($to, $subject, $msg, $headers);
    } catch (Throwable $e) {
        return false;
    }
}

function bb_build_order_email_html(array $order, array $items, array $payment): string
{
    $orderId   = (int) $order['id'];
    $name      = htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $storeName = htmlspecialchars(BOOKBITS_STORE_NAME, ENT_QUOTES, 'UTF-8');
    $currency  = BOOKBITS_CURRENCY_SYMBOL;
    $total     = bb_format_money((float) ($order['total'] ?? 0));
    $subtotal  = bb_format_money((float) ($order['subtotal'] ?? 0));
    $shipping  = bb_format_money((float) ($order['shipping_fee'] ?? 0));
    $discount  = bb_format_money((float) ($order['discount'] ?? 0));
    $date      = htmlspecialchars((string) ($order['created_at'] ?? date('Y-m-d H:i')), ENT_QUOTES, 'UTF-8');

    $shipName    = htmlspecialchars((string) ($order['shipping_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $shipPhone   = htmlspecialchars((string) ($order['shipping_phone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $shipAddress = htmlspecialchars((string) ($order['shipping_address'] ?? ''), ENT_QUOTES, 'UTF-8');
    $shipCity    = htmlspecialchars((string) ($order['shipping_city'] ?? ''), ENT_QUOTES, 'UTF-8');
    $shipState   = htmlspecialchars((string) ($order['shipping_state'] ?? ''), ENT_QUOTES, 'UTF-8');
    $shipZip     = htmlspecialchars((string) ($order['shipping_zip'] ?? ''), ENT_QUOTES, 'UTF-8');
    $shipCountry = htmlspecialchars((string) ($order['shipping_country'] ?? ''), ENT_QUOTES, 'UTF-8');

    $payMethod = htmlspecialchars(ucfirst((string) ($payment['method'] ?? 'N/A')), ENT_QUOTES, 'UTF-8');
    $payRef    = htmlspecialchars((string) ($payment['transaction_ref'] ?? ''), ENT_QUOTES, 'UTF-8');
    $paidAt    = htmlspecialchars((string) ($payment['paid_at'] ?? ''), ENT_QUOTES, 'UTF-8');

    $itemRows = '';
    foreach ($items as $li) {
        $title  = htmlspecialchars((string) ($li['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars((string) ($li['author'] ?? ''), ENT_QUOTES, 'UTF-8');
        $cover  = '';
        if (!empty($li['cover_type'])) {
            $cover = $li['cover_type'] === 'hardcover' ? ' (Hardcover)' : ' (Paperback)';
        }
        $qty   = (int) ($li['qty'] ?? 1);
        $price = bb_format_money((float) ($li['unit_price'] ?? 0));
        $lineTot = bb_format_money((float) ($li['subtotal'] ?? 0));

        $itemRows .= <<<ROW
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:14px;color:#334155;">
                <strong>{$title}</strong>{$cover}<br>
                <span style="color:#64748b;font-size:13px;">by {$author}</span>
            </td>
            <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;text-align:center;font-size:14px;color:#334155;">{$qty}</td>
            <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;text-align:right;font-size:14px;color:#334155;">{$price}</td>
            <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;text-align:right;font-size:14px;font-weight:600;color:#0f172a;">{$lineTot}</td>
        </tr>
ROW;
    }

    $cityLine = trim("$shipCity, $shipState $shipZip");

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">

    <!-- Header -->
    <tr>
        <td style="background-color:#0f172a;padding:28px 32px;text-align:center;">
            <h1 style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">{$storeName}</h1>
        </td>
    </tr>

    <!-- Confirmation banner -->
    <tr>
        <td style="background-color:#ecfdf5;padding:20px 32px;border-bottom:1px solid #d1fae5;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="font-size:14px;color:#065f46;">
                    <span style="font-size:20px;vertical-align:middle;">&#10003;</span>
                    <strong style="font-size:16px;margin-left:6px;">Payment confirmed</strong><br>
                    <span style="font-size:13px;color:#047857;">Order #{$orderId} &middot; {$date}</span>
                </td>
            </tr>
            </table>
        </td>
    </tr>

    <!-- Greeting -->
    <tr>
        <td style="padding:24px 32px 8px;">
            <p style="margin:0;font-size:15px;color:#334155;">Hi {$name},</p>
            <p style="margin:8px 0 0;font-size:15px;color:#334155;line-height:1.5;">Thank you for your order! We've received your payment and your order is now being processed. Here's a summary:</p>
        </td>
    </tr>

    <!-- Items table -->
    <tr>
        <td style="padding:16px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <tr style="background-color:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;">Item</th>
                    <th style="padding:10px 12px;text-align:center;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;">Qty</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;">Price</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;">Total</th>
                </tr>
                {$itemRows}
            </table>
        </td>
    </tr>

    <!-- Totals -->
    <tr>
        <td style="padding:0 32px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:6px 0;font-size:14px;color:#64748b;">Subtotal</td>
                    <td style="padding:6px 0;font-size:14px;color:#334155;text-align:right;">{$subtotal}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;font-size:14px;color:#64748b;">Shipping</td>
                    <td style="padding:6px 0;font-size:14px;color:#334155;text-align:right;">{$shipping}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;font-size:14px;color:#64748b;">Discount</td>
                    <td style="padding:6px 0;font-size:14px;color:#334155;text-align:right;">-{$discount}</td>
                </tr>
                <tr>
                    <td colspan="2" style="border-top:2px solid #0f172a;padding:0;"></td>
                </tr>
                <tr>
                    <td style="padding:10px 0;font-size:16px;font-weight:700;color:#0f172a;">Total paid</td>
                    <td style="padding:10px 0;font-size:16px;font-weight:700;color:#0f172a;text-align:right;">{$total}</td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Two-column: Shipping + Payment -->
    <tr>
        <td style="padding:0 32px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" valign="top" style="padding-right:12px;">
                    <div style="background-color:#f8fafc;border-radius:8px;padding:14px;border:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Shipping to</p>
                        <p style="margin:8px 0 0;font-size:14px;font-weight:600;color:#0f172a;">{$shipName}</p>
                        <p style="margin:2px 0 0;font-size:13px;color:#475569;">{$shipPhone}</p>
                        <p style="margin:6px 0 0;font-size:13px;color:#475569;">{$shipAddress}</p>
                        <p style="margin:2px 0 0;font-size:13px;color:#475569;">{$cityLine}</p>
                        <p style="margin:2px 0 0;font-size:13px;color:#475569;">{$shipCountry}</p>
                    </div>
                </td>
                <td width="50%" valign="top" style="padding-left:12px;">
                    <div style="background-color:#f8fafc;border-radius:8px;padding:14px;border:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Payment</p>
                        <p style="margin:8px 0 0;font-size:14px;font-weight:600;color:#0f172a;">{$payMethod}</p>
                        <p style="margin:4px 0 0;font-size:12px;color:#475569;font-family:monospace;">Ref: {$payRef}</p>
                        <p style="margin:4px 0 0;font-size:12px;color:#475569;">Paid: {$paidAt}</p>
                    </div>
                </td>
            </tr>
            </table>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background-color:#f8fafc;padding:20px 32px;border-top:1px solid #e2e8f0;text-align:center;">
            <p style="margin:0;font-size:13px;color:#64748b;">Questions about your order? Reach us on WhatsApp or reply to this email.</p>
            <p style="margin:8px 0 0;font-size:12px;color:#94a3b8;">&copy; <?= date('Y') ?> {$storeName}. All rights reserved.</p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

function bb_build_order_email_plain(array $order, array $items, array $payment): string
{
    $orderId = (int) $order['id'];
    $name    = (string) ($order['customer_name'] ?? '');
    $total   = bb_format_money((float) ($order['total'] ?? 0));
    $date    = (string) ($order['created_at'] ?? date('Y-m-d H:i'));

    $lines  = BOOKBITS_STORE_NAME . "\n";
    $lines .= str_repeat('=', 40) . "\n\n";
    $lines .= "PAYMENT CONFIRMED\n";
    $lines .= "Order #$orderId — $date\n\n";
    $lines .= "Hi $name,\n\n";
    $lines .= "Thank you for your order! We've received your payment and your order is now being processed.\n\n";
    $lines .= "ITEMS ORDERED\n";
    $lines .= str_repeat('-', 40) . "\n";

    foreach ($items as $li) {
        $title  = (string) ($li['title'] ?? '');
        $author = (string) ($li['author'] ?? '');
        $cover  = '';
        if (!empty($li['cover_type'])) {
            $cover = $li['cover_type'] === 'hardcover' ? ' (Hardcover)' : ' (Paperback)';
        }
        $qty     = (int) ($li['qty'] ?? 1);
        $price   = bb_format_money((float) ($li['unit_price'] ?? 0));
        $lineTot = bb_format_money((float) ($li['subtotal'] ?? 0));
        $lines  .= "$title$cover by $author\n";
        $lines  .= "  Qty: $qty × $price = $lineTot\n\n";
    }

    $lines .= str_repeat('-', 40) . "\n";
    $lines .= "Subtotal:  " . bb_format_money((float) ($order['subtotal'] ?? 0)) . "\n";
    $lines .= "Shipping:  " . bb_format_money((float) ($order['shipping_fee'] ?? 0)) . "\n";
    $lines .= "Discount: -" . bb_format_money((float) ($order['discount'] ?? 0)) . "\n";
    $lines .= "TOTAL:     $total\n\n";

    $lines .= "SHIPPING TO\n";
    $lines .= ($order['shipping_name'] ?? '') . "\n";
    if (!empty($order['shipping_phone'])) {
        $lines .= ($order['shipping_phone']) . "\n";
    }
    $lines .= ($order['shipping_address'] ?? '') . "\n";
    $lines .= trim(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? '') . ' ' . ($order['shipping_zip'] ?? '')) . "\n";
    $lines .= ($order['shipping_country'] ?? '') . "\n\n";

    $lines .= "PAYMENT\n";
    $lines .= "Method: " . ucfirst((string) ($payment['method'] ?? 'N/A')) . "\n";
    if (!empty($payment['transaction_ref'])) {
        $lines .= "Ref:    " . $payment['transaction_ref'] . "\n";
    }
    if (!empty($payment['paid_at'])) {
        $lines .= "Paid:   " . $payment['paid_at'] . "\n";
    }
    $lines .= "\n";

    $lines .= "Questions? Reach us on WhatsApp or reply to this email.\n";
    $lines .= '© ' . date('Y') . ' ' . BOOKBITS_STORE_NAME . "\n";

    return $lines;
}
