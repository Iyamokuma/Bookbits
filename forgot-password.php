<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$err = $_SESSION['flash_error'] ?? '';
$ok  = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$pageTitle = 'Forgot password — Bookbits';
require __DIR__ . '/includes/header.php';
?>

        <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-center font-serif text-3xl font-bold text-slate-900">Forgot password</h1>
            <p class="mt-2 text-center text-sm text-slate-600">Enter your account email and we'll send a reset link.</p>

            <?php if ($err !== '') : ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($ok !== '') : ?>
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars(BOOKBITS_BASE . '/actions/auth-forgot.php', ENT_QUOTES, 'UTF-8') ?>" method="post" class="mt-8 space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-dark">Send reset link</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/login.php', ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-brand hover:text-brand-dark">Back to sign in</a>
            </p>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
