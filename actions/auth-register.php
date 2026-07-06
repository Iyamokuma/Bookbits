<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/redirect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/register.php');
    exit;
}

$name     = trim((string) ($_POST['name'] ?? ''));
$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$redirect = isset($_POST['redirect']) ? (string) $_POST['redirect'] : null;

if ($name === '' || $email === '' || strlen($password) < 8) {
    $_SESSION['flash_error'] = 'Please fill all fields. Password must be at least 8 characters.';
    header('Location: ' . BOOKBITS_BASE . '/register.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

$st = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
if ($st->fetch() !== false) {
    $_SESSION['flash_error'] = 'An account with that email already exists.';
    header('Location: ' . BOOKBITS_BASE . '/register.php?redirect=' . rawurlencode((string) $redirect));
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins  = db()->prepare(
    'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
);
$ins->execute([$name, $email, $hash, 'customer']);

$user = [
    'id'       => (int) (db()->lastInsertId()),
    'name'     => $name,
    'email'    => $email,
    'password' => $hash,
    'role'     => 'customer',
];

bb_login_user($user);
bb_cart_merge_guest_into_user((int) $user['id']);

header('Location: ' . bb_safe_redirect_target($redirect));
exit;
