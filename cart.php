<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/books_db.php';

$bbUser = bb_current_user();

$pageTitle = 'Your cart — Bookbits';
require __DIR__ . '/includes/header.php';

$lines = bb_cart_lines_with_books();
$sub   = bb_cart_subtotal($lines);
$base  = BOOKBITS_BASE;
?>

        <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
            <h1 class="font-serif text-3xl font-bold text-slate-900">Shopping cart</h1>
            <p class="mt-1 text-sm text-slate-600">Review your books before checkout.</p>

            <?php if (count($lines) === 0) : ?>
                <div class="mt-12 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-16 text-center">
                    <p class="text-lg font-semibold text-slate-700">Your cart is empty</p>
                    <a href="<?= htmlspecialchars($base . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-4 inline-flex rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">Browse books</a>
                </div>
            <?php else : ?>
                <ul class="mt-8 divide-y divide-slate-100 rounded-2xl border border-slate-100 bg-white shadow-sm">
                    <?php foreach ($lines as $line) :
                        $b   = $line['book'];
                        $qty = (int) $line['qty'];
                        $coverType = isset($line['cover_type']) && is_string($line['cover_type']) ? $line['cover_type'] : null;
                        $cartKey = isset($line['key']) ? (string) $line['key'] : (string) ((int) $b['id']);
                        $bid = (int) $b['id'];
                        $price = bb_book_cover_price($b, $coverType);
                        $lineTotal = $price * $qty;
                        $cover = bb_book_cover_url($b);
                    ?>
                        <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center" data-cart-row="<?= htmlspecialchars($cartKey, ENT_QUOTES, 'UTF-8') ?>">
                            <a href="<?= htmlspecialchars($base . '/product.php?id=' . $bid, ENT_QUOTES, 'UTF-8') ?>" class="flex shrink-0 gap-4">
                                <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-28 w-20 rounded-md object-cover ring-1 ring-slate-100" width="80" height="112">
                                <div>
                                    <p class="font-semibold text-slate-900"><?= htmlspecialchars((string) $b['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-sm text-slate-500"><?= htmlspecialchars((string) $b['author'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php if ($coverType !== null && $coverType !== '') : ?>
                                        <p class="mt-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500"><?= htmlspecialchars($coverType === 'hardcover' ? 'Hardcover' : 'Soft paperback', ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                    <p class="mt-1 text-sm font-bold text-brand"><?= bb_format_money($price) ?> each</p>
                                </div>
                            </a>
                            <div class="flex flex-1 flex-wrap items-center justify-end gap-3 sm:justify-end">
                                <label class="flex items-center gap-2 text-sm text-slate-600">
                                    Qty
                                    <input type="number" min="1" max="<?= (int) $b['stock_qty'] ?>" value="<?= $qty ?>"
                                           class="w-16 rounded-lg border border-slate-200 px-2 py-1 text-center text-sm font-semibold"
                                           data-cart-qty="<?= $bid ?>" data-cover-type="<?= htmlspecialchars((string) ($coverType ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </label>
                                <button type="button" class="text-sm font-semibold text-red-600 hover:underline" data-cart-remove="<?= $bid ?>" data-cover-type="<?= htmlspecialchars((string) ($coverType ?? ''), ENT_QUOTES, 'UTF-8') ?>">Remove</button>
                                <p class="min-w-[5rem] text-right text-lg font-bold text-slate-900"><?= bb_format_money($lineTotal) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="mt-8 flex flex-col items-stretch gap-4 border-t border-slate-100 pt-6 sm:items-end">
                    <p class="text-lg text-slate-700 sm:mr-auto">Subtotal <span class="font-bold text-slate-900"><?= bb_format_money($sub) ?></span></p>
                    <?php if ($bbUser === null) : ?>
                        <div class="w-full max-w-lg rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:ml-auto sm:max-w-md">
                            <p class="text-sm font-semibold text-amber-950">Account required to checkout</p>
                            <p class="mt-1 text-sm text-amber-900/90">Create a free account or sign in to enter your delivery details and place your order. Your cart is kept when you register.</p>
                            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                <a href="<?= htmlspecialchars($base . '/register.php?redirect=' . rawurlencode('checkout.php') . '&from=cart', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex flex-1 items-center justify-center rounded-xl bg-brand px-5 py-3 text-center text-sm font-bold text-white shadow-md transition hover:bg-brand-dark">Create account</a>
                                <a href="<?= htmlspecialchars($base . '/login.php?redirect=' . rawurlencode('checkout.php') . '&from=cart', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex flex-1 items-center justify-center rounded-xl border border-amber-300 bg-white px-5 py-3 text-center text-sm font-semibold text-amber-950 shadow-sm transition hover:bg-amber-100/80">Sign in</a>
                            </div>
                        </div>
                    <?php else : ?>
                        <a href="<?= htmlspecialchars($base . '/checkout.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex w-full items-center justify-center rounded-full bg-brand px-8 py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-dark sm:w-auto">Proceed to checkout</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        (function () {
            var base = document.body.getAttribute('data-base') || '';
            function setCount(n) {
                var el = document.getElementById('header-cart-count');
                if (el) el.textContent = n;
            }
            async function post(url, body) {
                var r = await fetch(base + url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                return r.json();
            }
            document.querySelectorAll('[data-cart-qty]').forEach(function (input) {
                var bid = input.getAttribute('data-cart-qty');
                var t;
                input.addEventListener('change', function () {
                    clearTimeout(t);
                    t = setTimeout(async function () {
                        var fd = new FormData();
                        fd.append('book_id', bid);
                        fd.append('qty', input.value);
                        var coverType = input.getAttribute('data-cover-type') || '';
                        if (coverType) {
                            fd.append('cover_type', coverType);
                        }
                        var j = await post('/actions/cart-update.php', fd);
                        if (j.ok) { setCount(j.count); location.reload(); }
                    }, 400);
                });
            });
            document.querySelectorAll('[data-cart-remove]').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    var bid = btn.getAttribute('data-cart-remove');
                    var fd = new FormData();
                    fd.append('book_id', bid);
                    var coverType = btn.getAttribute('data-cover-type') || '';
                    if (coverType) {
                        fd.append('cover_type', coverType);
                    }
                    var j = await post('/actions/cart-remove.php', fd);
                    if (j.ok) { setCount(j.count); location.reload(); }
                });
            });
        })();
        </script>

<?php require __DIR__ . '/includes/footer.php'; ?>
