<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '';
$from     = isset($_GET['from']) ? (string) $_GET['from'] : '';
$checkoutGate = ($from === 'checkout' || $from === 'cart' || $redirect === 'checkout.php');
$loginQs = [];
if ($redirect !== '') {
    $loginQs['redirect'] = $redirect;
}
if ($from !== '' && preg_match('/^[a-z]+$/', $from)) {
    $loginQs['from'] = $from;
}
$loginHref = BOOKBITS_BASE . '/login.php' . ($loginQs !== [] ? '?' . http_build_query($loginQs) : '');
$err      = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$pageTitle = 'Create account — Bookbits';
require __DIR__ . '/includes/header.php';
?>

        <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-center font-serif text-3xl font-bold text-slate-900">Create account</h1>
            <p class="mt-2 text-center text-sm text-slate-600">Your cart is saved — sign up to checkout anytime.</p>

            <?php if ($checkoutGate) : ?>
                <div class="mt-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950">
                    <strong>Register to continue checkout.</strong> After you create your account, you’ll go to a secure page to enter your <strong>full delivery address</strong>, phone number, and any delivery notes.
                </div>
            <?php endif; ?>

            <?php if ($err !== '') : ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars(BOOKBITS_BASE, ENT_QUOTES, 'UTF-8') ?>/actions/auth-register.php" method="post" class="mt-8 space-y-4">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Full name</label>
                    <input type="text" id="name" name="name" required autocomplete="name" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password (min 8 characters)</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-dark">Create account</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Already have an account?
                <a href="<?= htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-brand hover:text-brand-dark">Sign in</a>
            </p>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
