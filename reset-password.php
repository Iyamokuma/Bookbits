<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$err = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$valid = false;
if ($token !== '') {
    bb_ensure_password_resets_table();
    try {
        $st = db()->prepare(
            'SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1'
        );
        $st->execute([$token]);
        $valid = $st->fetch() !== false;
    } catch (Throwable $e) {
        $valid = false;
    }
}

$pageTitle = 'Reset password — Bookbits';
require __DIR__ . '/includes/header.php';
?>

        <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-center font-serif text-3xl font-bold text-slate-900">Reset password</h1>

            <?php if (!$valid) : ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    This reset link is invalid or has expired.
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/forgot-password.php', ENT_QUOTES, 'UTF-8') ?>" class="font-semibold underline">Request a new one</a>.
                </div>
            <?php else : ?>
                <?php if ($err !== '') : ?>
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form action="<?= htmlspecialchars(BOOKBITS_BASE . '/actions/auth-reset.php', ENT_QUOTES, 'UTF-8') ?>" method="post" class="mt-8 space-y-4">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">New password (min 8 characters)</label>
                        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div>
                        <label for="password2" class="block text-sm font-medium text-slate-700">Confirm password</label>
                        <input type="password" id="password2" name="password_confirm" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-brand py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-dark">Update password</button>
                </form>
            <?php endif; ?>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
