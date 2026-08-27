<?php

declare(strict_types=1);

/**
 * Last mail/Resend error (safe for admin flash; never includes API keys).
 */
function bb_mail_last_error(): string
{
    return (string) ($GLOBALS['bb_mail_last_error'] ?? '');
}

function bb_mail_set_last_error(string $message): void
{
    $GLOBALS['bb_mail_last_error'] = trim($message);
}

/**
 * Send email via Resend API. Falls back to PHP mail() only if Resend key is missing.
 * Never throws — callers must not break checkout/auth flows.
 *
 * @param string|list<string> $to
 */
function bb_send_email($to, string $subject, string $html, string $plain = ''): bool
{
    bb_mail_set_last_error('');

    $recipients = is_array($to) ? $to : [$to];
    $recipients = array_values(array_filter(array_map(static function ($e) {
        $e = trim((string) $e);

        return $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null;
    }, $recipients)));

    if ($recipients === [] || $subject === '') {
        bb_mail_set_last_error('Missing recipient or subject.');

        return false;
    }

    if ($plain === '') {
        $plain = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
    }

    $apiKey = defined('BOOKBITS_RESEND_API_KEY') ? trim((string) BOOKBITS_RESEND_API_KEY) : '';
    if ($apiKey !== '') {
        return bb_resend_send($apiKey, $recipients, $subject, $html, $plain);
    }

    bb_mail_set_last_error('Resend API key is not configured in config/app.local.php.');

    return bb_mail_fallback($recipients[0], $subject, $html, $plain);
}

/**
 * @param list<string> $recipients
 */
function bb_resend_send(string $apiKey, array $recipients, string $subject, string $html, string $plain): bool
{
    $fromEmail = defined('BOOKBITS_MAIL_FROM_EMAIL') ? trim((string) BOOKBITS_MAIL_FROM_EMAIL) : 'onboarding@resend.dev';
    $fromName  = defined('BOOKBITS_MAIL_FROM_NAME') ? trim((string) BOOKBITS_MAIL_FROM_NAME) : BOOKBITS_STORE_NAME;
    if ($fromEmail === '') {
        $fromEmail = 'onboarding@resend.dev';
    }

    $payload = [
        'from'    => $fromName . ' <' . $fromEmail . '>',
        'to'      => $recipients,
        'subject' => $subject,
        'html'    => $html,
        'text'    => $plain,
    ];

    // Only set reply_to when from uses a custom domain (avoids oddities with resend.dev test mode).
    $usingTestFrom = str_ends_with(strtolower($fromEmail), '@resend.dev');
    if (!$usingTestFrom) {
        $replyTo = defined('BOOKBITS_STORE_EMAIL') ? trim((string) BOOKBITS_STORE_EMAIL) : '';
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $payload['reply_to'] = $replyTo;
        }
    }

    if (!function_exists('curl_init')) {
        bb_mail_set_last_error('PHP cURL is not enabled. Enable the curl extension in XAMPP/php.ini.');
        error_log('Bookbits Resend: curl missing');

        return false;
    }

    $ch = curl_init('https://api.resend.com/emails');
    if ($ch === false) {
        bb_mail_set_last_error('Could not start HTTP client for Resend.');
        error_log('Bookbits Resend: could not init curl');

        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT        => 30,
    ]);

    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($raw === false) {
        bb_mail_set_last_error('Network error talking to Resend: ' . ($err !== '' ? $err : 'unknown'));
        error_log('Bookbits Resend curl error: ' . $err);

        return false;
    }

    if ($code < 200 || $code >= 300) {
        $decoded = json_decode($raw, true);
        $msg = '';
        if (is_array($decoded)) {
            $msg = (string) ($decoded['message'] ?? $decoded['error'] ?? '');
            if ($msg === '' && isset($decoded['name'])) {
                $msg = (string) $decoded['name'];
            }
        }
        if ($msg === '') {
            $msg = 'HTTP ' . $code;
        }

        // Friendlier copy for the most common Resend test-domain restriction.
        if ($usingTestFrom && (stripos($msg, 'only send testing emails') !== false || $code === 403)) {
            $msg = 'Resend test mode: with onboarding@resend.dev you can only email the address on your Resend account. '
                . 'Verify your domain at resend.com/domains, then set BOOKBITS_MAIL_FROM_EMAIL to an address on that domain '
                . '(e.g. orders@booksandbits.com.ng) in config/app.local.php.';
        }

        bb_mail_set_last_error($msg);
        error_log('Bookbits Resend HTTP ' . $code . ': ' . $raw);

        return false;
    }

    return true;
}

function bb_mail_fallback(string $to, string $subject, string $html, string $plain): bool
{
    $fromEmail = defined('BOOKBITS_MAIL_FROM_EMAIL') && BOOKBITS_MAIL_FROM_EMAIL !== ''
        ? BOOKBITS_MAIL_FROM_EMAIL
        : BOOKBITS_STORE_EMAIL;
    $fromName = defined('BOOKBITS_MAIL_FROM_NAME') ? BOOKBITS_MAIL_FROM_NAME : BOOKBITS_STORE_NAME;
    $boundary = 'BB_' . md5(uniqid((string) mt_rand(), true));
    $headers = implode("\r\n", [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
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

    return @mail($to, $subject, $body, $headers);
}

function bb_mail_public_url(string $path): string
{
    $path = ltrim($path, '/');
    $full = rtrim(BOOKBITS_BASE, '/') . '/' . $path;
    if (function_exists('bb_absolute_url')) {
        return bb_absolute_url($full);
    }

    return rtrim(BOOKBITS_BASE_URL, '/') . '/' . ltrim($full, '/');
}

function bb_ensure_password_resets_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS `password_resets` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(191) NOT NULL,
                `token` VARCHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_password_resets_token` (`token`),
                KEY `idx_password_resets_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        error_log('Bookbits password_resets table: ' . $e->getMessage());
    }
}

function bb_ensure_order_tracking_column(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        if (!bb_table_has_column('orders', 'tracking_number')) {
            db()->exec('ALTER TABLE `orders` ADD COLUMN `tracking_number` VARCHAR(120) NULL DEFAULT NULL AFTER `notes`');
        }
    } catch (Throwable $e) {
        // Column may already exist under race; ignore.
    }
}

function bb_email_shell(string $title, string $innerHtml): string
{
    $store = htmlspecialchars(BOOKBITS_STORE_NAME, ENT_QUOTES, 'UTF-8');
    $year  = date('Y');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>{$safeTitle}</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">
<tr><td style="background:#0f172a;padding:28px 32px;text-align:center;">
<h1 style="margin:0;font-size:22px;font-weight:700;color:#fff;">{$store}</h1>
</td></tr>
<tr><td style="padding:28px 32px;">{$innerHtml}</td></tr>
<tr><td style="background:#f8fafc;padding:20px 32px;border-top:1px solid #e2e8f0;text-align:center;">
<p style="margin:0;font-size:12px;color:#94a3b8;">&copy; {$year} {$store}</p>
</td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
}

/**
 * Send an order confirmation email to the customer after successful payment.
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

        return bb_send_email((string) $order['email'], 'Order #' . $orderId . ' confirmed — ' . BOOKBITS_STORE_NAME, $html, $plain);
    } catch (Throwable $e) {
        error_log('Bookbits order confirmation email: ' . $e->getMessage());

        return false;
    }
}

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

        $oid = (int) $order['id'];
        $total = bb_format_money((float) $order['total']);
        $name = htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) ($order['email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $ship = htmlspecialchars((string) ($order['shipping_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars((string) ($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $adminUrl = htmlspecialchars(bb_mail_public_url('admin/payments.php'), ENT_QUOTES, 'UTF-8');

        $inner = <<<HTML
<p style="margin:0;font-size:15px;color:#334155;">New paid order received.</p>
<p style="margin:16px 0 0;font-size:14px;color:#475569;line-height:1.6;">
<strong>Order:</strong> #{$oid}<br>
<strong>Customer:</strong> {$name} ({$email})<br>
<strong>Ship to:</strong> {$ship}<br>
<strong>Total:</strong> {$total}<br>
<strong>Date:</strong> {$date}
</p>
<p style="margin:20px 0 0;"><a href="{$adminUrl}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:700;font-size:14px;">Open in dashboard</a></p>
HTML;
        $html = bb_email_shell('New order #' . $oid, $inner);
        $plain = "New paid order #{$oid}\nCustomer: {$order['customer_name']} ({$order['email']})\nTotal: {$total}\n";

        return bb_send_email(BOOKBITS_STORE_EMAIL, 'New paid order #' . $oid . ' — ' . $total, $html, $plain);
    } catch (Throwable $e) {
        error_log('Bookbits admin order email: ' . $e->getMessage());

        return false;
    }
}

function bb_send_verification_email(string $email, string $name, string $token): bool
{
    $url = bb_mail_public_url('verify-email.php?token=' . rawurlencode($token));
    $safeName = htmlspecialchars($name !== '' ? $name : 'there', ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $inner = <<<HTML
<p style="margin:0;font-size:15px;color:#334155;">Hi {$safeName},</p>
<p style="margin:12px 0 0;font-size:15px;color:#334155;line-height:1.55;">Thanks for creating a Bookbits account. Please verify your email so we can confirm orders and keep your account secure.</p>
<p style="margin:24px 0 0;"><a href="{$safeUrl}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:700;font-size:14px;">Verify my email</a></p>
<p style="margin:16px 0 0;font-size:12px;color:#94a3b8;word-break:break-all;">Or open: {$safeUrl}</p>
HTML;
    $html = bb_email_shell('Verify your email', $inner);
    $plain = "Hi {$name},\n\nVerify your Bookbits email:\n{$url}\n";

    return bb_send_email($email, 'Verify your email — ' . BOOKBITS_STORE_NAME, $html, $plain);
}

function bb_send_password_reset_email(string $email, string $name, string $token): bool
{
    $url = bb_mail_public_url('reset-password.php?token=' . rawurlencode($token));
    $safeName = htmlspecialchars($name !== '' ? $name : 'there', ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $inner = <<<HTML
<p style="margin:0;font-size:15px;color:#334155;">Hi {$safeName},</p>
<p style="margin:12px 0 0;font-size:15px;color:#334155;line-height:1.55;">We received a request to reset your Bookbits password. This link expires in 1 hour.</p>
<p style="margin:24px 0 0;"><a href="{$safeUrl}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:700;font-size:14px;">Reset password</a></p>
<p style="margin:16px 0 0;font-size:13px;color:#64748b;">If you did not request this, you can ignore this email.</p>
<p style="margin:12px 0 0;font-size:12px;color:#94a3b8;word-break:break-all;">{$safeUrl}</p>
HTML;
    $html = bb_email_shell('Reset your password', $inner);
    $plain = "Hi {$name},\n\nReset your password (expires in 1 hour):\n{$url}\n";

    return bb_send_email($email, 'Reset your password — ' . BOOKBITS_STORE_NAME, $html, $plain);
}

/**
 * Admin-authored order update (status / tracking / custom message) to the customer.
 */
function bb_send_order_update_email(
    int $orderId,
    string $customerEmail,
    string $customerName,
    string $subject,
    string $message,
    string $orderStatus = '',
    string $trackingNumber = ''
): bool {
    $safeName = htmlspecialchars($customerName !== '' ? $customerName : 'there', ENT_QUOTES, 'UTF-8');
    $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $statusLine = $orderStatus !== ''
        ? '<p style="margin:12px 0 0;font-size:14px;color:#475569;"><strong>Order status:</strong> ' . htmlspecialchars(ucfirst($orderStatus), ENT_QUOTES, 'UTF-8') . '</p>'
        : '';
    $trackLine = $trackingNumber !== ''
        ? '<p style="margin:8px 0 0;font-size:14px;color:#475569;"><strong>Tracking:</strong> ' . htmlspecialchars($trackingNumber, ENT_QUOTES, 'UTF-8') . '</p>'
        : '';

    $inner = <<<HTML
<p style="margin:0;font-size:15px;color:#334155;">Hi {$safeName},</p>
<p style="margin:8px 0 0;font-size:13px;color:#64748b;">Update for order #{$orderId}</p>
{$statusLine}{$trackLine}
<div style="margin:18px 0 0;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;font-size:15px;color:#334155;line-height:1.55;">{$safeMsg}</div>
<p style="margin:20px 0 0;font-size:13px;color:#64748b;">Questions? Reply to this email or message us on WhatsApp.</p>
HTML;
    $html = bb_email_shell($subject, $inner);
    $plain = "Hi {$customerName},\n\nOrder #{$orderId}\n";
    if ($orderStatus !== '') {
        $plain .= "Status: {$orderStatus}\n";
    }
    if ($trackingNumber !== '') {
        $plain .= "Tracking: {$trackingNumber}\n";
    }
    $plain .= "\n{$message}\n";

    return bb_send_email($customerEmail, $subject, $html, $plain);
}

function bb_send_delivery_notice(int $orderId, string $trackingNumber = ''): bool
{
    try {
        $st = db()->prepare(
            'SELECT o.id, o.status, u.email, u.name AS customer_name
             FROM orders o INNER JOIN users u ON u.id = o.user_id
             WHERE o.id = ? LIMIT 1'
        );
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false || empty($row['email'])) {
            return false;
        }

        $msg = "Great news — your order #{$orderId} has been marked as delivered. We hope you enjoy your books!";
        if ($trackingNumber !== '') {
            $msg .= "\n\nTracking reference: {$trackingNumber}";
        }

        return bb_send_order_update_email(
            $orderId,
            (string) $row['email'],
            (string) ($row['customer_name'] ?? ''),
            'Your order #' . $orderId . ' has been delivered — ' . BOOKBITS_STORE_NAME,
            $msg,
            'delivered',
            $trackingNumber
        );
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Auto email when admin marks an order as shipped.
 * Includes the customer's delivery address so they know where the package is going.
 */
function bb_send_shipped_notice(int $orderId, string $trackingNumber = '', string $extraMessage = '', ?string $overrideEmail = null): bool
{
    try {
        $hasPhone = bb_table_has_column('orders', 'shipping_phone');
        $fields = 'o.id, o.status, o.shipping_name, o.shipping_address, o.shipping_city, o.shipping_state, o.shipping_zip, o.shipping_country, o.notes';
        if ($hasPhone) {
            $fields .= ', o.shipping_phone';
        }
        if (bb_table_has_column('orders', 'tracking_number')) {
            $fields .= ', o.tracking_number';
        }
        $st = db()->prepare(
            "SELECT {$fields}, u.email, u.name AS customer_name
             FROM orders o INNER JOIN users u ON u.id = o.user_id
             WHERE o.id = ? LIMIT 1"
        );
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return false;
        }

        $email = $overrideEmail !== null && trim($overrideEmail) !== ''
            ? trim($overrideEmail)
            : (string) ($row['email'] ?? '');
        if ($email === '') {
            return false;
        }

        if ($trackingNumber === '' && !empty($row['tracking_number'])) {
            $trackingNumber = (string) $row['tracking_number'];
        }

        $customerName = (string) ($row['customer_name'] ?? '');
        $shipName = trim((string) ($row['shipping_name'] ?? ''));
        $shipPhone = $hasPhone ? trim((string) ($row['shipping_phone'] ?? '')) : '';
        $shipAddr = trim((string) ($row['shipping_address'] ?? ''));
        $shipCity = trim((string) ($row['shipping_city'] ?? ''));
        $shipState = trim((string) ($row['shipping_state'] ?? ''));
        $shipZip = trim((string) ($row['shipping_zip'] ?? ''));
        $shipCountry = trim((string) ($row['shipping_country'] ?? ''));
        $cityLine = trim($shipCity . ($shipState !== '' ? ', ' . $shipState : '') . ($shipZip !== '' ? ' ' . $shipZip : ''));

        $safeName = htmlspecialchars($customerName !== '' ? $customerName : 'there', ENT_QUOTES, 'UTF-8');
        $hName = htmlspecialchars($shipName !== '' ? $shipName : $customerName, ENT_QUOTES, 'UTF-8');
        $hPhone = htmlspecialchars($shipPhone, ENT_QUOTES, 'UTF-8');
        $hAddr = htmlspecialchars($shipAddr, ENT_QUOTES, 'UTF-8');
        $hCity = htmlspecialchars($cityLine, ENT_QUOTES, 'UTF-8');
        $hCountry = htmlspecialchars($shipCountry, ENT_QUOTES, 'UTF-8');
        $hTrack = htmlspecialchars($trackingNumber, ENT_QUOTES, 'UTF-8');
        $hExtra = $extraMessage !== '' ? nl2br(htmlspecialchars($extraMessage, ENT_QUOTES, 'UTF-8')) : '';

        $trackBlock = $trackingNumber !== ''
            ? '<p style="margin:14px 0 0;font-size:14px;color:#475569;"><strong>Tracking number:</strong> ' . $hTrack . '</p>'
            : '';
        $phoneBlock = $shipPhone !== ''
            ? '<p style="margin:4px 0 0;font-size:14px;color:#475569;">' . $hPhone . '</p>'
            : '';
        $extraBlock = $hExtra !== ''
            ? '<div style="margin:18px 0 0;padding:14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;font-size:14px;color:#1e3a8a;line-height:1.55;">' . $hExtra . '</div>'
            : '';

        $subject = 'Your order #' . $orderId . ' has shipped — ' . BOOKBITS_STORE_NAME;
        $inner = <<<HTML
<p style="margin:0;font-size:15px;color:#334155;">Hi {$safeName},</p>
<p style="margin:12px 0 0;font-size:15px;color:#334155;line-height:1.55;">
    Great news — <strong>order #{$orderId}</strong> has been <strong>shipped</strong> and is on its way to the delivery address you provided at checkout.
</p>
<div style="margin:20px 0 0;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
    <p style="margin:0;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Delivering to</p>
    <p style="margin:10px 0 0;font-size:15px;font-weight:700;color:#0f172a;">{$hName}</p>
    {$phoneBlock}
    <p style="margin:8px 0 0;font-size:14px;color:#334155;line-height:1.5;">{$hAddr}</p>
    <p style="margin:4px 0 0;font-size:14px;color:#334155;">{$hCity}</p>
    <p style="margin:4px 0 0;font-size:14px;color:#334155;">{$hCountry}</p>
</div>
{$trackBlock}
{$extraBlock}
<p style="margin:20px 0 0;font-size:14px;color:#64748b;line-height:1.5;">We'll email you again when your order is marked as delivered. If this address looks wrong, contact us right away.</p>
HTML;

        $html = bb_email_shell($subject, $inner);

        $plain  = "Hi {$customerName},\n\n";
        $plain .= "Order #{$orderId} has been shipped and is on its way to the address you provided:\n\n";
        $plain .= ($shipName !== '' ? $shipName : $customerName) . "\n";
        if ($shipPhone !== '') {
            $plain .= $shipPhone . "\n";
        }
        $plain .= $shipAddr . "\n";
        $plain .= $cityLine . "\n";
        $plain .= $shipCountry . "\n";
        if ($trackingNumber !== '') {
            $plain .= "\nTracking: {$trackingNumber}\n";
        }
        if ($extraMessage !== '') {
            $plain .= "\n{$extraMessage}\n";
        }
        $plain .= "\nWe'll email you when it's delivered.\n";

        return bb_send_email($email, $subject, $html, $plain);
    } catch (Throwable $e) {
        error_log('Bookbits shipped notice: ' . $e->getMessage());

        return false;
    }
}

/**
 * Auto email when admin moves order to processing (order confirmed / being prepared).
 */
function bb_send_processing_notice(int $orderId, string $extraMessage = '', ?string $overrideEmail = null): bool
{
    try {
        $st = db()->prepare(
            'SELECT o.id, u.email, u.name AS customer_name
             FROM orders o INNER JOIN users u ON u.id = o.user_id
             WHERE o.id = ? LIMIT 1'
        );
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return false;
        }

        $email = $overrideEmail !== null && $overrideEmail !== ''
            ? $overrideEmail
            : (string) ($row['email'] ?? '');
        if ($email === '') {
            return false;
        }

        $msg = "We've confirmed your order #{$orderId} and our team is preparing it for shipment.";
        if ($extraMessage !== '') {
            $msg .= "\n\n" . $extraMessage;
        }

        return bb_send_order_update_email(
            $orderId,
            $email,
            (string) ($row['customer_name'] ?? ''),
            'Order #' . $orderId . ' confirmed — we\'re preparing it — ' . BOOKBITS_STORE_NAME,
            $msg,
            'processing',
            ''
        );
    } catch (Throwable $e) {
        return false;
    }
}

function bb_build_order_email_html(array $order, array $items, array $payment): string
{
    $orderId   = (int) $order['id'];
    $name      = htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $storeName = htmlspecialchars(BOOKBITS_STORE_NAME, ENT_QUOTES, 'UTF-8');
    $total     = bb_format_money((float) ($order['total'] ?? 0));
    $subtotal  = bb_format_money((float) ($order['subtotal'] ?? 0));
    $shipping  = bb_format_money((float) ($order['shipping_fee'] ?? 0));
    $discount  = bb_format_money((float) ($order['discount'] ?? 0));
    $date      = htmlspecialchars((string) ($order['created_at'] ?? date('Y-m-d H:i')), ENT_QUOTES, 'UTF-8');
    $year      = date('Y');

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
    <tr>
        <td style="background-color:#0f172a;padding:28px 32px;text-align:center;">
            <h1 style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">{$storeName}</h1>
        </td>
    </tr>
    <tr>
        <td style="background-color:#ecfdf5;padding:20px 32px;border-bottom:1px solid #d1fae5;">
            <span style="font-size:20px;vertical-align:middle;">&#10003;</span>
            <strong style="font-size:16px;margin-left:6px;color:#065f46;">Payment confirmed</strong><br>
            <span style="font-size:13px;color:#047857;">Order #{$orderId} &middot; {$date}</span>
        </td>
    </tr>
    <tr>
        <td style="padding:24px 32px 8px;">
            <p style="margin:0;font-size:15px;color:#334155;">Hi {$name},</p>
            <p style="margin:8px 0 0;font-size:15px;color:#334155;line-height:1.5;">Thank you for your order! We've received your payment and your order is now being processed. Here's a summary:</p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <tr style="background-color:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;">Item</th>
                    <th style="padding:10px 12px;text-align:center;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;">Qty</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;">Price</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;">Total</th>
                </tr>
                {$itemRows}
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:0 32px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="padding:6px 0;font-size:14px;color:#64748b;">Subtotal</td><td style="padding:6px 0;font-size:14px;color:#334155;text-align:right;">{$subtotal}</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#64748b;">Shipping</td><td style="padding:6px 0;font-size:14px;color:#334155;text-align:right;">{$shipping}</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#64748b;">Discount</td><td style="padding:6px 0;font-size:14px;color:#334155;text-align:right;">-{$discount}</td></tr>
                <tr><td style="padding:10px 0;font-size:16px;font-weight:700;color:#0f172a;">Total paid</td><td style="padding:10px 0;font-size:16px;font-weight:700;color:#0f172a;text-align:right;">{$total}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:0 32px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" valign="top" style="padding-right:12px;">
                    <div style="background-color:#f8fafc;border-radius:8px;padding:14px;border:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;">Shipping to</p>
                        <p style="margin:8px 0 0;font-size:14px;font-weight:600;color:#0f172a;">{$shipName}</p>
                        <p style="margin:2px 0 0;font-size:13px;color:#475569;">{$shipPhone}</p>
                        <p style="margin:6px 0 0;font-size:13px;color:#475569;">{$shipAddress}</p>
                        <p style="margin:2px 0 0;font-size:13px;color:#475569;">{$cityLine}</p>
                        <p style="margin:2px 0 0;font-size:13px;color:#475569;">{$shipCountry}</p>
                    </div>
                </td>
                <td width="50%" valign="top" style="padding-left:12px;">
                    <div style="background-color:#f8fafc;border-radius:8px;padding:14px;border:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;">Payment</p>
                        <p style="margin:8px 0 0;font-size:14px;font-weight:600;color:#0f172a;">{$payMethod}</p>
                        <p style="margin:4px 0 0;font-size:12px;color:#475569;font-family:monospace;">Ref: {$payRef}</p>
                        <p style="margin:4px 0 0;font-size:12px;color:#475569;">Paid: {$paidAt}</p>
                    </div>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="background-color:#f8fafc;padding:20px 32px;border-top:1px solid #e2e8f0;text-align:center;">
            <p style="margin:0;font-size:13px;color:#64748b;">Questions about your order? Reach us on WhatsApp or reply to this email.</p>
            <p style="margin:8px 0 0;font-size:12px;color:#94a3b8;">&copy; {$year} {$storeName}. All rights reserved.</p>
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
    $lines .= "\nQuestions? Reach us on WhatsApp or reply to this email.\n";
    $lines .= '© ' . date('Y') . ' ' . BOOKBITS_STORE_NAME . "\n";

    return $lines;
}
