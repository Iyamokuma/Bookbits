<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$ok = (int) ($_GET['ok'] ?? 0) === 1;
$orderId = (int) ($_GET['order'] ?? 0);
$msgCode = trim((string) ($_GET['msg'] ?? ''));

$friendlyFail = 'Your payment wasn’t completed. You can return to checkout and try again. If you were charged, contact us with your order number and we’ll sort it out.';
if ($msgCode !== '') {
    // Map internal codes to soft copy — never surface raw codes on the page.
    $friendlyFail = 'Something went wrong while confirming your payment. Please try again from checkout, or contact us if you need help.';
}

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
        <?php if ($ok) : ?>
            <p class="mt-2 text-sm text-emerald-800">Thank you — we’ve received your payment and will process your order shortly. A confirmation email is on its way.</p>
        <?php else : ?>
            <p class="mt-2 text-sm text-red-800"><?= htmlspecialchars($friendlyFail, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-brand-dark">Go to home</a>
            <?php if (!$ok) : ?>
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/checkout.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to checkout</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

