<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/_layout.php';

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$flashIsError = !empty($_SESSION['admin_flash_error']);
unset($_SESSION['admin_flash_error']);

$colPca = bb_table_has_column('orders', 'payment_confirmed_at') ? 'o.payment_confirmed_at' : 'NULL AS payment_confirmed_at';
$colCs = bb_table_has_column('orders', 'checkout_snapshot') ? 'o.checkout_snapshot' : 'NULL AS checkout_snapshot';
$colPaidAt = bb_table_has_column('payments', 'paid_at') ? 'p.paid_at' : 'NULL AS paid_at';

$rows = db()->query(
    'SELECT o.id AS order_id, o.status, o.total, o.created_at, ' . $colPca . ', ' . $colCs . ',
            o.shipping_name, o.shipping_phone, o.shipping_address, o.shipping_city, o.shipping_state, o.shipping_zip, o.shipping_country, o.notes,
            u.email AS customer, u.name AS customer_name,
            p.status AS pay_status, p.amount AS pay_amount, p.method AS pay_method, p.transaction_ref, p.gateway_response, ' . $colPaidAt . '
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     LEFT JOIN payments p ON p.order_id = o.id
     ORDER BY o.created_at DESC
     LIMIT 100'
)->fetchAll();

$orderIds = array_values(array_unique(array_map(static fn (array $r): int => (int) $r['order_id'], $rows)));
$itemsByOrder = [];
if ($orderIds !== []) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $hasOiCover = bb_table_has_column('order_items', 'cover_type');
    $sql = $hasOiCover
        ? 'SELECT order_id, title, author, cover_type, qty, unit_price, subtotal FROM order_items WHERE order_id IN (' . $placeholders . ') ORDER BY order_id ASC, id ASC'
        : 'SELECT order_id, title, author, qty, unit_price, subtotal FROM order_items WHERE order_id IN (' . $placeholders . ') ORDER BY order_id ASC, id ASC';
    $st = db()->prepare($sql);
    $st->execute($orderIds);
    foreach ($st->fetchAll() as $li) {
        $oid = (int) $li['order_id'];
        $itemsByOrder[$oid][] = $li;
    }
}

$coverLabel = static function (?string $t): string {
    if ($t === 'hardcover') {
        return 'Hardcover';
    }
    if ($t === 'paperback') {
        return 'Soft paperback';
    }

    return '';
};

$pageTitle = 'Admin — Orders & payments';
bb_admin_header($pageTitle, 'payments');
?>

        <?php if ($flash !== '') : ?>
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= $flashIsError ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <h1 class="text-2xl font-bold text-slate-900">Orders & payments</h1>
        <p class="text-sm text-slate-600">Customer checkout details (address, phone, notes, books) appear below. After payment is confirmed, a snapshot is stored on the order for your records.</p>

        <div class="mt-6 space-y-6">
            <?php foreach ($rows as $r) :
                $oid = (int) $r['order_id'];
                $gatewayMeta = [];
                if (!empty($r['gateway_response'])) {
                    $decoded = json_decode((string) $r['gateway_response'], true);
                    if (is_array($decoded)) {
                        $gatewayMeta = $decoded;
                    }
                }
                $displayMethod = (string) ($r['pay_method'] ?? '—');
                if ($displayMethod === 'card' && isset($gatewayMeta['gateway']) && is_string($gatewayMeta['gateway'])) {
                    $displayMethod = $gatewayMeta['gateway'];
                }
                $payOk = ($r['pay_status'] ?? '') === 'completed';
                $canMarkDelivered = $payOk && !in_array((string) $r['status'], ['delivered', 'cancelled', 'refunded'], true);
                $items = $itemsByOrder[$oid] ?? [];
                $snapDecoded = null;
                if (!empty($r['checkout_snapshot'])) {
                    $sd = json_decode((string) $r['checkout_snapshot'], true);
                    $snapDecoded = is_array($sd) ? $sd : null;
                }
                ?>
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <span class="font-mono text-lg font-bold text-slate-900">#<?= $oid ?></span>
                            <span class="ml-2 text-sm text-slate-600"><?= htmlspecialchars((string) $r['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $payOk ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' ?>">
                                Payment: <?= htmlspecialchars((string) ($r['pay_status'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-800">
                                Order: <?= htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>
                    <div class="grid gap-6 p-4 lg:grid-cols-2">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer</h2>
                            <p class="mt-1 font-semibold text-slate-900"><?= htmlspecialchars((string) ($r['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-sm text-slate-600"><?= htmlspecialchars((string) $r['customer'], ENT_QUOTES, 'UTF-8') ?></p>
                            <h2 class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-500">Payment</h2>
                            <p class="mt-1 text-sm text-slate-800">
                                <span class="font-semibold"><?= bb_format_money((float) $r['total']) ?></span>
                                <span class="text-slate-500"> via <?= htmlspecialchars($displayMethod, ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                            <?php if (!empty($r['transaction_ref'])) : ?>
                                <p class="mt-1 font-mono text-xs text-slate-600">Ref: <?= htmlspecialchars((string) $r['transaction_ref'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if (!empty($r['paid_at'])) : ?>
                                <p class="mt-1 text-xs text-slate-500">Paid at: <?= htmlspecialchars((string) $r['paid_at'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if (!empty($r['payment_confirmed_at'])) : ?>
                                <p class="mt-1 text-xs font-medium text-emerald-700">Checkout confirmed: <?= htmlspecialchars((string) $r['payment_confirmed_at'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Delivery &amp; checkout form</h2>
                            <div class="mt-2 rounded-xl border border-slate-100 bg-slate-50/80 p-3 text-sm text-slate-800">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($r['shipping_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-1"><?= htmlspecialchars((string) ($r['shipping_phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-2 whitespace-pre-line"><?= htmlspecialchars((string) ($r['shipping_address'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-2"><?= htmlspecialchars(trim((string) (($r['shipping_city'] ?? '') . ', ' . ($r['shipping_state'] ?? '') . ' ' . ($r['shipping_zip'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></p>
                                <p><?= htmlspecialchars((string) ($r['shipping_country'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($r['notes'])) : ?>
                                    <p class="mt-3 border-t border-slate-200 pt-3 text-slate-700"><span class="font-semibold text-slate-900">Delivery notes:</span> <?= nl2br(htmlspecialchars((string) $r['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($snapDecoded !== null && empty($snapDecoded['error'])) : ?>
                                <details class="mt-3 rounded-lg border border-emerald-100 bg-emerald-50/50 px-3 py-2 text-xs text-emerald-900">
                                    <summary class="cursor-pointer font-semibold">Payment-time snapshot (frozen)</summary>
                                    <p class="mt-2 text-emerald-800">Saved when payment succeeded — same fields as above, for your records.</p>
                                    <?php if (!empty($snapDecoded['captured_at'])) : ?>
                                        <p class="mt-1 text-emerald-700">Captured: <?= htmlspecialchars((string) $snapDecoded['captured_at'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                </details>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="border-t border-slate-100 px-4 py-4">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Books in this order</h2>
                        <?php if ($items === []) : ?>
                            <p class="mt-2 text-sm text-slate-500">No line items.</p>
                        <?php else : ?>
                            <ul class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-100">
                                <?php foreach ($items as $li) :
                                    $ct = isset($li['cover_type']) ? (string) $li['cover_type'] : '';
                                    $ctL = $coverLabel($ct !== '' ? $ct : null);
                                    ?>
                                    <li class="flex flex-wrap items-baseline justify-between gap-2 px-3 py-2 text-sm">
                                        <span class="text-slate-900">
                                            <?= htmlspecialchars((string) $li['title'], ENT_QUOTES, 'UTF-8') ?>
                                            <span class="text-slate-500"> — <?= htmlspecialchars((string) $li['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($ctL !== '') : ?>
                                                <span class="ml-1 rounded bg-sky-100 px-1.5 py-0.5 text-xs font-medium text-sky-900"><?= htmlspecialchars($ctL, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-slate-600">
                                            ×<?= (int) $li['qty'] ?>
                                            <span class="ml-2 font-semibold text-slate-900"><?= bb_format_money((float) $li['unit_price']) ?></span>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-4 py-3">
                        <?php if ($canMarkDelivered) : ?>
                            <form action="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/order-mark-delivered.php', ENT_QUOTES, 'UTF-8') ?>" method="post" class="inline" onsubmit="return confirm('Mark order #<?= $oid ?> as delivered?');">
                                <input type="hidden" name="order_id" value="<?= $oid ?>">
                                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-emerald-700">Mark delivered</button>
                            </form>
                        <?php elseif ($payOk && (string) $r['status'] === 'delivered') : ?>
                            <span class="text-sm font-semibold text-emerald-700">Delivered</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (count($rows) === 0) : ?>
                <p class="rounded-2xl border border-slate-200 bg-white py-12 text-center text-slate-500">No orders yet.</p>
            <?php endif; ?>
        </div>

<?php bb_admin_footer(); ?>
