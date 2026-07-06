<?php

declare(strict_types=1);

if (!defined('BOOKBITS_BASE')) {
    // Web path to project root (no trailing slash).
    // Auto-detects cPanel public_html (base = '') and local /Bookbits installs.
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($scriptDir === '/' || $scriptDir === '.') {
        $scriptDir = '';
    }
    foreach (['/admin', '/actions'] as $runtimeDir) {
        if (substr($scriptDir, -strlen($runtimeDir)) === $runtimeDir) {
            $scriptDir = substr($scriptDir, 0, -strlen($runtimeDir));
            break;
        }
    }
    define('BOOKBITS_BASE', $scriptDir);
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

// Admin dashboard (/admin/login.php) — override in config/app.local.php (not committed).
if (!defined('BOOKBITS_ADMIN_EMAIL')) {
    define('BOOKBITS_ADMIN_EMAIL', 'admin@bookbits.com');
}
if (!defined('BOOKBITS_ADMIN_PASSWORD')) {
    define('BOOKBITS_ADMIN_PASSWORD', 'change-me');
}

// Storefront currency (amounts in DB are stored in this currency)
if (!defined('BOOKBITS_CURRENCY_SYMBOL')) {
    define('BOOKBITS_CURRENCY_SYMBOL', '₦');
}
if (!defined('BOOKBITS_CURRENCY_CODE')) {
    define('BOOKBITS_CURRENCY_CODE', 'NGN');
}

if (!defined('BOOKBITS_BASE_URL')) {
    $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    define('BOOKBITS_BASE_URL', ($https ? 'https://' : 'http://') . $host);
}

// Store contact phone (display + tel: links site-wide).
if (!defined('BOOKBITS_STORE_PHONE')) {
    define('BOOKBITS_STORE_PHONE', '+234 906 003 1555');
}

/**
 * Phone number formatted for display (e.g. +234 906 003 1555).
 */
function bb_store_phone_display(): string
{
    return defined('BOOKBITS_STORE_PHONE') ? trim((string) BOOKBITS_STORE_PHONE) : '';
}

/**
 * Phone number for tel: links (e.g. +2349060031555).
 */
function bb_store_phone_tel(): string
{
    $digits = preg_replace('/\D+/', '', bb_store_phone_display());
    if ($digits === '') {
        return '';
    }

    return '+' . $digits;
}

// WhatsApp: prefer a full link (e.g. wa.me/message/... from WhatsApp Business). If empty, BOOKBITS_WHATSAPP_PHONE builds https://wa.me/ plus digits.
if (!defined('BOOKBITS_WHATSAPP_URL')) {
    define('BOOKBITS_WHATSAPP_URL', '');
}
if (!defined('BOOKBITS_WHATSAPP_PHONE')) {
    define('BOOKBITS_WHATSAPP_PHONE', '2349060031555');
}
if (!defined('BOOKBITS_FACEBOOK_URL')) {
    define('BOOKBITS_FACEBOOK_URL', 'https://www.facebook.com/share/1Baxort7ES/');
}
if (!defined('BOOKBITS_INSTAGRAM_URL')) {
    define('BOOKBITS_INSTAGRAM_URL', 'https://www.instagram.com/booksbitsandco?igsh=ODB0ejY1emQzZXVu');
}

/**
 * Resolved WhatsApp chat URL for buttons and footer (message link or phone-based wa.me).
 */
function bb_whatsapp_chat_url(): string
{
    if (defined('BOOKBITS_WHATSAPP_URL')) {
        $u = trim((string) BOOKBITS_WHATSAPP_URL);
        if ($u !== '') {
            return $u;
        }
    }
    if (!defined('BOOKBITS_WHATSAPP_PHONE')) {
        return '';
    }
    $digits = preg_replace('/\D+/', '', (string) BOOKBITS_WHATSAPP_PHONE);
    if ($digits === '') {
        return '';
    }

    return 'https://wa.me/' . $digits . '?text=' . rawurlencode("Hi Bookbits, I'd like some help with an order.");
}

// Store identity for transactional emails.
if (!defined('BOOKBITS_STORE_NAME')) {
    define('BOOKBITS_STORE_NAME', 'Books, Bits & Co');
}
if (!defined('BOOKBITS_STORE_EMAIL')) {
    define('BOOKBITS_STORE_EMAIL', 'orders@booksandbits.com.ng');
}

// Payment gateways: set keys in config/app.local.php (see app.local.php.example).
if (!defined('BOOKBITS_PAYSTACK_PUBLIC_KEY')) {
    define('BOOKBITS_PAYSTACK_PUBLIC_KEY', '');
}
if (!defined('BOOKBITS_PAYSTACK_SECRET_KEY')) {
    define('BOOKBITS_PAYSTACK_SECRET_KEY', '');
}

if (!defined('BOOKBITS_KLUMP_PUBLIC_KEY')) {
    define('BOOKBITS_KLUMP_PUBLIC_KEY', '');
}
if (!defined('BOOKBITS_KLUMP_SECRET_KEY')) {
    define('BOOKBITS_KLUMP_SECRET_KEY', '');
}

if (!defined('BOOKBITS_KORAPAY_PUBLIC_KEY')) {
    define('BOOKBITS_KORAPAY_PUBLIC_KEY', '');
}
if (!defined('BOOKBITS_KORAPAY_SECRET_KEY')) {
    define('BOOKBITS_KORAPAY_SECRET_KEY', '');
}

$appLocal = __DIR__ . '/app.local.php';
if (is_file($appLocal)) {
    $local = require $appLocal;
    if (is_array($local)) {
        foreach ($local as $const => $value) {
            if (is_string($const) && $const !== '' && !defined($const)) {
                define($const, $value);
            }
        }
    }
}
