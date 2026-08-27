<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '';
$from     = isset($_GET['from']) ? (string) $_GET['from'] : '';
$checkoutGate = ($from === 'checkout' || $from === 'cart');
$fromQuery = $from !== '' && preg_match('/^[a-z]+$/', $from) ? '&from=' . rawurlencode($from) : '';
$registerQs = [];
if ($redirect !== '') {
    $registerQs['redirect'] = $redirect;
} elseif ($checkoutGate) {
    $registerQs['redirect'] = 'checkout.php';
}
if ($from !== '' && preg_match('/^[a-z]+$/', $from)) {
    $registerQs['from'] = $from;
}
$registerHref = BOOKBITS_BASE . '/register.php' . ($registerQs !== [] ? '?' . http_build_query($registerQs) : '');
$err      = $_SESSION['flash_error'] ?? '';
$okMsg    = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
if ($okMsg === '' && isset($_GET['registered'])) {
    $okMsg = 'Account created! Check your email to verify, then sign in.';
}

$pageTitle = 'Sign in — Bookbits';
require __DIR__ . '/includes/header.php';
?>

        <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-center font-serif text-3xl font-bold text-slate-900">Sign in</h1>
            <p class="mt-2 text-center text-sm text-slate-600">Welcome back. Continue shopping or checkout.</p>

            <?php if ($checkoutGate) : ?>
                <div class="mt-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950">
                    <strong>Checkout</strong> requires an account. Sign in below, or
                    <a href="<?= htmlspecialchars($registerHref, ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-brand underline decoration-sky-300 hover:text-brand-dark">create an account</a>
                    if you’re new — then you’ll fill in your full delivery address.
                </div>
            <?php endif; ?>

            <?php if ($okMsg !== '') : ?>
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= htmlspecialchars($okMsg, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($err !== '') : ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars(BOOKBITS_BASE, ENT_QUOTES, 'UTF-8') ?>/actions/auth-login.php" method="post" class="mt-8 space-y-4">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/forgot-password.php', ENT_QUOTES, 'UTF-8') ?>" class="text-xs font-semibold text-brand hover:text-brand-dark">Forgot password?</a>
                    </div>
                    <input type="password" id="password" name="password" required autocomplete="current-password" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-dark">Sign in</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                New here?
                <a href="<?= htmlspecialchars($registerHref, ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-brand hover:text-brand-dark">Create an account</a>
            </p>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
