<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

// Already an admin: go straight to dashboard
if (bb_is_admin()) {
    header('Location: ' . BOOKBITS_BASE . '/admin/index.php');
    exit;
}

$redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : 'admin/index.php';
$err      = $_SESSION['admin_login_error'] ?? '';
unset($_SESSION['admin_login_error']);

$b = BOOKBITS_BASE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin sign in — Bookbits</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#1d4ed8', dark: '#1e40af' } } } } };</script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100">
    <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-12">
        <div class="mb-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bookbits</p>
            <h1 class="mt-2 text-2xl font-bold text-white">Administrator sign in</h1>
            <p class="mt-2 text-sm text-slate-400">This page is separate from the customer store login.</p>
        </div>

        <div class="rounded-2xl border border-slate-700 bg-slate-800/50 p-8 shadow-xl backdrop-blur">
            <?php if ($err !== '') : ?>
                <div class="mb-6 rounded-xl border border-red-500/40 bg-red-950/50 px-4 py-3 text-sm text-red-200"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?>/actions/auth-admin-login.php" method="post" class="space-y-4">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="username" class="mt-1 w-full rounded-xl border border-slate-600 bg-slate-900/80 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" class="mt-1 w-full rounded-xl border border-slate-600 bg-slate-900/80 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/40 transition hover:bg-brand-dark">Sign in to admin</button>
            </form>
        </div>

        <p class="mt-8 text-center text-sm text-slate-500">
            Shopping as a customer?
            <a href="<?= htmlspecialchars($b . '/login.php', ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-sky-400 hover:text-sky-300">Store sign in</a>
            &nbsp;·&nbsp;
            <a href="<?= htmlspecialchars($b . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-400 hover:text-white">Back to storefront</a>
        </p>
    </div>
</body>
</html>
