<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/books_db.php';

if (bb_current_user() === null) {
    header('Location: ' . BOOKBITS_BASE . '/login.php?redirect=' . rawurlencode('checkout.php') . '&from=checkout');
    exit;
}

$checkoutErr = $_SESSION['flash_checkout'] ?? '';
unset($_SESSION['flash_checkout']);

$formDraft = $_SESSION['checkout_draft'] ?? [];
unset($_SESSION['checkout_draft']);

$lines = bb_cart_lines_with_books();
$sub   = bb_cart_subtotal($lines);
$base  = BOOKBITS_BASE;

$u = bb_current_user();
$d = static function (string $key, string $default = '') use ($formDraft): string {
    return trim((string) ($formDraft[$key] ?? $default));
};
$selectedGateway = $d('payment_gateway') !== '' ? $d('payment_gateway') : 'paystack';

$pageTitle = 'Checkout — Bookbits';
require __DIR__ . '/includes/header.php';
?>

        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
            <h1 class="font-serif text-3xl font-bold text-slate-900">Checkout</h1>
            <p class="mt-1 text-sm text-slate-600">Signed in as <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>.</p>

            <?php if ($checkoutErr !== '') : ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($checkoutErr, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if (count($lines) === 0) : ?>
                <div class="mt-10 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center">
                    <p class="text-slate-700">Your cart is empty.</p>
                    <a href="<?= htmlspecialchars($base . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-4 inline-block font-semibold text-brand hover:underline">Continue shopping</a>
                </div>
            <?php else : ?>
                <div class="mt-8 space-y-3 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <?php foreach ($lines as $line) :
                        $b = $line['book'];
                        $qty = (int) $line['qty'];
                        $coverType = isset($line['cover_type']) && is_string($line['cover_type']) ? $line['cover_type'] : null;
                        $price = bb_book_cover_price($b, $coverType);
                    ?>
                        <div class="flex justify-between gap-4 text-sm">
                            <span class="text-slate-700">
                                <?= htmlspecialchars((string) $b['title'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($coverType !== null && $coverType !== '') : ?>
                                    (<?= htmlspecialchars($coverType === 'hardcover' ? 'Hardcover' : 'Soft paperback', ENT_QUOTES, 'UTF-8') ?>)
                                <?php endif; ?>
                                × <?= $qty ?>
                            </span>
                            <span class="font-semibold"><?= bb_format_money($price * $qty) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="flex justify-between border-t border-slate-100 pt-4 text-base font-bold">
                        <span>Total</span>
                        <span><?= bb_format_money($sub) ?></span>
                    </div>
                </div>

                <form action="<?= htmlspecialchars($base . '/actions/checkout-submit.php', ENT_QUOTES, 'UTF-8') ?>" method="post" class="mt-8 space-y-8">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Delivery details</h2>
                        <p class="mt-1 text-sm text-slate-500">We’ll use this to deliver your books. All fields marked * are required.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700" for="ship_name">Full name *</label>
                            <input id="ship_name" name="shipping_name" required autocomplete="name" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" value="<?= htmlspecialchars($d('shipping_name') !== '' ? $d('shipping_name') : (string) $u['name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700" for="ship_phone">Phone number *</label>
                            <input id="ship_phone" name="shipping_phone" type="tel" required autocomplete="tel" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" placeholder="<?= htmlspecialchars(bb_store_phone_display(), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($d('shipping_phone'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700" for="ship_addr">Street address *</label>
                            <textarea id="ship_addr" name="shipping_address" required rows="2" autocomplete="street-address" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" placeholder="House number, street name, area"><?= htmlspecialchars($d('shipping_address'), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="ship_city">City / town *</label>
                            <input id="ship_city" name="shipping_city" required autocomplete="address-level2" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" value="<?= htmlspecialchars($d('shipping_city'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="ship_state">State / region *</label>
                            <input id="ship_state" name="shipping_state" required autocomplete="address-level1" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" value="<?= htmlspecialchars($d('shipping_state'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="ship_zip">Postal / ZIP code *</label>
                            <input id="ship_zip" name="shipping_zip" required autocomplete="postal-code" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" value="<?= htmlspecialchars($d('shipping_zip'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="ship_country">Country *</label>
                            <input id="ship_country" name="shipping_country" required autocomplete="country-name" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" value="<?= htmlspecialchars($d('shipping_country') !== '' ? $d('shipping_country') : 'Nigeria', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700" for="ship_notes">Delivery notes <span class="font-normal text-slate-500">(optional)</span></label>
                            <textarea id="ship_notes" name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" placeholder="Landmark, gate code, preferred delivery time, etc."><?= htmlspecialchars($d('notes'), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700" for="payment_gateway">Payment gateway *</label>
                            <select id="payment_gateway" name="payment_gateway" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                                <?php foreach (bb_payment_method_labels() as $gatewayValue => $gatewayLabel) : ?>
                                    <option value="<?= htmlspecialchars($gatewayValue, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedGateway === $gatewayValue ? ' selected' : '' ?>><?= htmlspecialchars($gatewayLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-brand py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-dark">Proceed to payment</button>
                    <p class="text-xs text-slate-500">After placing this order, you will be redirected to your selected gateway to complete payment securely.</p>
                </form>
            <?php endif; ?>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
