<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/redirect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/admin/login.php');
    exit;
}

$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$redirect = isset($_POST['redirect']) ? (string) $_POST['redirect'] : null;

if ($email === '' || $password === '') {
    $_SESSION['admin_login_error'] = 'Please enter your email and password.';
    header('Location: ' . BOOKBITS_BASE . '/admin/login.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

try {
    $st = db()->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();
} catch (Throwable $e) {
    $_SESSION['admin_login_error'] = 'Database error: check DB_NAME / DB_USER / DB_PASS in config/database.php, and import database/schema.sql into that database. On cPanel, MySQL host is usually localhost.';
    header('Location: ' . BOOKBITS_BASE . '/admin/login.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

$passwordOk = false;
if ($user !== false) {
    if (strcasecmp((string) $user['email'], BOOKBITS_ADMIN_EMAIL) === 0) {
        $passwordOk = hash_equals(BOOKBITS_ADMIN_PASSWORD, $password);
    } else {
        $passwordOk = password_verify($password, (string) $user['password']);
    }
}

if ($user === false || ! $passwordOk) {
    $_SESSION['admin_login_error'] = 'Invalid email or password.';
    header('Location: ' . BOOKBITS_BASE . '/admin/login.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

if (($user['role'] ?? '') !== 'admin') {
    $_SESSION['admin_login_error'] = 'This area is for administrators only. Customers should use the store sign-in page.';
    header('Location: ' . BOOKBITS_BASE . '/admin/login.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

bb_login_user($user);
try {
    bb_cart_merge_guest_into_user((int) $user['id']);
} catch (Throwable $e) {
    // Admin login should still succeed if cart tables or merge step fail (e.g. partial import).
}

$dest = bb_safe_redirect_target($redirect, 'admin/index.php');
header('Location: ' . $dest);
exit;
