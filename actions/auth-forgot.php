<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BOOKBITS_BASE . '/forgot-password.php');
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_error'] = 'Please enter a valid email address.';
    header('Location: ' . BOOKBITS_BASE . '/forgot-password.php');
    exit;
}

bb_ensure_password_resets_table();

// Always show the same success message (do not leak whether the email exists).
$generic = 'If an account exists for that email, we sent a password reset link. Check your inbox.';

try {
    $st = db()->prepare('SELECT id, name, email, role FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch(PDO::FETCH_ASSOC);

    if ($user !== false && ($user['role'] ?? '') === 'customer') {
        $token = bin2hex(random_bytes(32));
        db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
        db()->prepare(
            'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        )->execute([$email, $token]);

        $sent = bb_send_password_reset_email($email, (string) ($user['name'] ?? ''), $token);
        if (!$sent) {
            error_log('Bookbits: password reset email failed for ' . $email);
            $_SESSION['flash_error'] = 'We could not send the reset email right now. Please try again shortly.';
            header('Location: ' . BOOKBITS_BASE . '/forgot-password.php');
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('Bookbits forgot password: ' . $e->getMessage());
}

$_SESSION['flash_success'] = $generic;
header('Location: ' . BOOKBITS_BASE . '/forgot-password.php');
exit;
