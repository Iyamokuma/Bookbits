<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/forgot-password.php');
    exit;
}

$token = trim((string) ($_POST['token'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['password_confirm'] ?? '');

if ($token === '' || strlen($password) < 8) {
    $_SESSION['flash_error'] = 'Password must be at least 8 characters.';
    header('Location: ' . BOOKBITS_BASE . '/reset-password.php?token=' . rawurlencode($token));
    exit;
}

if (!hash_equals($password, $confirm)) {
    $_SESSION['flash_error'] = 'Passwords do not match.';
    header('Location: ' . BOOKBITS_BASE . '/reset-password.php?token=' . rawurlencode($token));
    exit;
}

bb_ensure_password_resets_table();

try {
    $st = db()->prepare(
        'SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1'
    );
    $st->execute([$token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        $_SESSION['flash_error'] = 'This reset link is invalid or has expired.';
        header('Location: ' . BOOKBITS_BASE . '/forgot-password.php');
        exit;
    }

    $email = (string) $row['email'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    db()->prepare('UPDATE users SET password = ? WHERE email = ? AND role = ?')->execute([$hash, $email, 'customer']);
    db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);

    $_SESSION['flash_success'] = 'Password updated. You can sign in with your new password.';
    header('Location: ' . BOOKBITS_BASE . '/login.php');
    exit;
} catch (Throwable $e) {
    error_log('Bookbits reset password: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Could not update password. Please try again.';
    header('Location: ' . BOOKBITS_BASE . '/reset-password.php?token=' . rawurlencode($token));
    exit;
}
