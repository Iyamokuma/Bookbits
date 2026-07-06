<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$ok = (int) ($_GET['ok'] ?? 0) === 1;
$orderId = (int) ($_GET['order'] ?? 0);
$msg = trim((string) ($_GET['msg'] ?? ''));

$pageTitle = 'Payment result — Bookbits';
require __DIR__ . '/includes/header.php';
?>

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-2xl border p-6 <?= $ok ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' ?>">
        <h1 class="font-serif text-3xl font-bold <?= $ok ? 'text-emerald-900' : 'text-red-900' ?>">
            <?= $ok ? 'Payment successful' : 'Payment not completed' ?>
        </h1>
        <?php if ($orderId > 0) : ?>
            <p class="mt-2 text-sm <?= $ok ? 'text-emerald-800' : 'text-red-800' ?>">Order #<?= $orderId ?></p>
        <?php endif; ?>
        <?php if (!$ok && $msg !== '') : ?>
            <p class="mt-2 text-sm text-red-800">Reason: <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-brand-dark">Go to home</a>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/checkout.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to checkout</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

