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

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_error'] = 'Please enter a valid email address.';
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
$token = bin2hex(random_bytes(32));

$ins = db()->prepare(
    'INSERT INTO users (name, email, password, role, remember_token, email_verified_at) VALUES (?, ?, ?, ?, ?, NULL)'
);
$ins->execute([$name, $email, $hash, 'customer', $token]);

$userId = (int) db()->lastInsertId();

$sent = bb_send_verification_email($email, $name, $token);
if (!$sent) {
    error_log('Bookbits: verification email failed for user #' . $userId);
}

$_SESSION['flash_success'] = $sent
    ? 'Account created! Check your inbox to verify your email, then sign in.'
    : 'Account created, but we could not send the verification email. Please sign in and request a new verification link, or contact support.';

$qs = ['registered' => '1'];
if ($redirect !== null && $redirect !== '') {
    $qs['redirect'] = $redirect;
}
header('Location: ' . BOOKBITS_BASE . '/login.php?' . http_build_query($qs));
exit;
