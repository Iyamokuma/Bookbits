<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/redirect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/login.php');
    exit;
}

$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$redirect = isset($_POST['redirect']) ? (string) $_POST['redirect'] : null;

if ($email === '' || $password === '') {
    $_SESSION['flash_error'] = 'Please enter your email and password.';
    header('Location: ' . BOOKBITS_BASE . '/login.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

$st = db()->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
$user = $st->fetch();

if ($user !== false && ($user['role'] ?? '') === 'admin') {
    $_SESSION['flash_error'] = 'Invalid email or password.';
    header('Location: ' . BOOKBITS_BASE . '/login.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

if ($user === false || !password_verify($password, (string) $user['password'])) {
    $_SESSION['flash_error'] = 'Invalid email or password.';
    header('Location: ' . BOOKBITS_BASE . '/login.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

bb_login_user($user);
bb_cart_merge_guest_into_user((int) $user['id']);

header('Location: ' . bb_safe_redirect_target($redirect));
exit;
