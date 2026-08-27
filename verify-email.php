<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$token = trim((string) ($_GET['token'] ?? ''));
$ok = false;
$error = '';

if ($token === '' || strlen($token) < 32) {
    $error = 'This verification link is invalid or incomplete.';
} else {
    try {
        $st = db()->prepare(
            'SELECT id, name, email, role, email_verified_at FROM users WHERE remember_token = ? AND role = ? LIMIT 1'
        );
        $st->execute([$token, 'customer']);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            $error = 'This verification link is invalid or has already been used.';
        } elseif (!empty($user['email_verified_at'])) {
            $ok = true;
            bb_login_user($user);
        } else {
            db()->prepare(
                'UPDATE users SET email_verified_at = NOW(), remember_token = NULL WHERE id = ?'
            )->execute([(int) $user['id']]);
            $user['email_verified_at'] = date('Y-m-d H:i:s');
            bb_login_user($user);
            try {
                bb_cart_merge_guest_into_user((int) $user['id']);
            } catch (Throwable $e) {
            }
            $ok = true;
        }
    } catch (Throwable $e) {
        $error = 'We could not verify your email right now. Please try again later.';
        error_log('Bookbits verify-email: ' . $e->getMessage());
    }
}

$pageTitle = 'Verify email — Bookbits';
require __DIR__ . '/includes/header.php';
?>

        <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8 text-center">
            <?php if ($ok) : ?>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-10">
                    <h1 class="font-serif text-2xl font-bold text-emerald-900">Email verified</h1>
                    <p class="mt-3 text-sm text-emerald-800">You're all set. Your account is verified and you're signed in.</p>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-6 inline-flex rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark">Continue shopping</a>
                </div>
            <?php else : ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-6 py-10">
                    <h1 class="font-serif text-2xl font-bold text-red-900">Verification failed</h1>
                    <p class="mt-3 text-sm text-red-800"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/login.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-6 inline-flex rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark">Sign in</a>
                </div>
            <?php endif; ?>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
