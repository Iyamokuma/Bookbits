<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = bb_current_user();
if ($user === null) {
    header('Location: ' . BOOKBITS_BASE . '/login.php?redirect=' . rawurlencode('checkout.php') . '&from=checkout');
    exit;
}

$orderId = (int) ($_GET['order'] ?? 0);
$reference = trim((string) ($_GET['reference'] ?? ''));
if ($orderId < 1 || $reference === '') {
    header('Location: ' . BOOKBITS_BASE . '/checkout.php');
    exit;
}

$st = db()->prepare('SELECT id, total FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$st->execute([$orderId, (int) $user['id']]);
$order = $st->fetch();
if ($order === false) {
    header('Location: ' . BOOKBITS_BASE . '/checkout.php');
    exit;
}

$pageTitle = 'Klump payment — Bookbits';
require __DIR__ . '/includes/header.php';
?>

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="font-serif text-3xl font-bold text-slate-900">Complete payment with Klump</h1>
    <p class="mt-2 text-sm text-slate-600">Order #<?= (int) $order['id'] ?> · <?= bb_format_money((float) $order['total']) ?></p>
    <?php if (BOOKBITS_KLUMP_PUBLIC_KEY === '') : ?>
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Klump public key is not configured. Add it in <code>config/app.php</code>.
        </div>
    <?php else : ?>
        <button id="pay-klump-btn" class="mt-6 inline-flex rounded-xl bg-brand px-6 py-3 text-sm font-bold text-white shadow hover:bg-brand-dark">Pay now</button>
        <p class="mt-3 text-xs text-slate-500">You can close this page to cancel and return to checkout.</p>
    <?php endif; ?>
</div>

<?php if (BOOKBITS_KLUMP_PUBLIC_KEY !== '') : ?>
<script src="https://js.useklump.com/klump.js"></script>
<script>
(function () {
    var btn = document.getElementById('pay-klump-btn');
    if (!btn || !window.Klump) return;
    btn.addEventListener('click', function () {
        window.Klump?.init?.({
            publicKey: "<?= htmlspecialchars(BOOKBITS_KLUMP_PUBLIC_KEY, ENT_QUOTES, 'UTF-8') ?>",
            email: "<?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?>",
            amount: <?= (int) round(((float) $order['total']) * 100) ?>,
            currency: "<?= htmlspecialchars(BOOKBITS_CURRENCY_CODE, ENT_QUOTES, 'UTF-8') ?>",
            merchant_reference: "<?= htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') ?>",
            redirect_url: "<?= htmlspecialchars(rtrim(BOOKBITS_BASE_URL, '/') . BOOKBITS_BASE . '/actions/payment-callback.php?gateway=klump&order=' . (int) $order['id'], ENT_QUOTES, 'UTF-8') ?>"
        });
    });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

