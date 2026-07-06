<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/books_db.php';

$bookId = (int) ($_GET['id'] ?? 0);
$book   = $bookId > 0 ? bb_fetch_book_by_id($bookId) : null;

if ($book === null) {
    http_response_code(404);
    $pageTitle = 'Book Not Found — Bookbits';
    require __DIR__ . '/includes/header.php';
    echo '<div class="mx-auto max-w-2xl px-4 py-32 text-center">
        <p class="text-6xl font-bold text-slate-200">404</p>
        <h1 class="mt-4 text-2xl font-bold text-slate-800">Book not found</h1>
        <a href="' . htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') . '" class="mt-6 inline-block rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">Browse books</a>
    </div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$cover = bb_book_cover_url($book);
$related = bb_fetch_related_books((int) $book['category_id'], (int) $book['id'], 4);

$pageTitle = htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') . ' — Bookbits';
$hasCoverOptions = bb_book_supports_cover_options($book);
$paperPrice = $book['paperback_price'] !== null ? (float) $book['paperback_price'] : null;
$hardPrice  = $book['hardcover_price'] !== null ? (float) $book['hardcover_price'] : null;
$displayPrice = $hasCoverOptions && $paperPrice !== null
    ? $paperPrice
    : ($book['sale_price'] !== null ? (float) $book['sale_price'] : (float) $book['price']);
$isOnSale     = $book['sale_price'] !== null;
$catSlug      = (string) $book['cat_slug'];
$catName      = (string) $book['cat_name'];
$stock        = (int) $book['stock_qty'];
$bid          = (int) $book['id'];

require __DIR__ . '/includes/header.php';
?>

        <nav class="border-b border-slate-100 bg-white" aria-label="Breadcrumb">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-3 text-sm text-slate-600 sm:px-6 lg:px-8">
                <a href="<?= htmlspecialchars(BOOKBITS_BASE, ENT_QUOTES, 'UTF-8') ?>/index.php" class="hover:text-brand">Home</a>
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php?cat=' . $catSlug, ENT_QUOTES, 'UTF-8') ?>" class="hover:text-brand"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></a>
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="font-medium text-slate-900 line-clamp-1"><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </nav>

        <section class="bg-gradient-to-b from-sky-50/60 to-white py-12 sm:py-16" aria-label="Book details">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 md:grid-cols-[280px_1fr] lg:grid-cols-[320px_1fr] lg:gap-16">
                    <div class="flex justify-center md:justify-start">
                        <div class="group relative w-full max-w-[280px]">
                            <div class="absolute -inset-3 rounded-3xl bg-brand-soft/60 blur-2xl" aria-hidden="true"></div>
                            <div class="relative aspect-[2/3] overflow-hidden rounded-r-xl rounded-l-sm bg-slate-200 shadow-[8px_16px_40px_rgba(15,23,42,0.15)] ring-1 ring-slate-900/5">
                                <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="Cover of <?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover" width="400" height="600">
                                <div class="absolute inset-y-0 left-0 w-3 bg-gradient-to-r from-black/20 to-transparent" aria-hidden="true"></div>
                            </div>
                            <?php if ($isOnSale && (float) $book['price'] > 0) : ?>
                                <span class="absolute -right-3 -top-3 z-10 flex h-14 w-14 flex-col items-center justify-center rounded-full bg-brand text-white shadow-lg shadow-brand/30 text-[11px] font-bold leading-tight text-center">
                                    SALE<br>
                                    <?= (int) round(100 - ((float) $book['sale_price'] / (float) $book['price'] * 100)) ?>%<br>OFF
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php?cat=' . $catSlug, ENT_QUOTES, 'UTF-8') ?>" class="mb-2 inline-flex w-fit items-center rounded-full bg-brand-soft px-3 py-1 text-xs font-semibold text-brand-dark transition hover:bg-brand hover:text-white">
                            <?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>
                        </a>

                        <h1 class="font-serif text-3xl font-black leading-tight text-slate-900 sm:text-4xl lg:text-5xl">
                            <?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>
                        </h1>
                        <p class="mt-2 text-lg text-slate-600">by <span class="font-semibold text-slate-800"><?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></span></p>

                        <div class="mt-6 flex items-baseline gap-3">
                            <span class="text-4xl font-black text-brand"><?= bb_format_money($displayPrice) ?></span>
                            <?php if ($isOnSale && !$hasCoverOptions) : ?>
                                <span class="text-xl text-slate-400 line-through"><?= bb_format_money((float) $book['price']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasCoverOptions && $paperPrice !== null && $hardPrice !== null) : ?>
                            <p class="mt-2 text-sm font-medium text-slate-600">
                                Soft paperback: <span class="font-semibold text-slate-800"><?= bb_format_money($paperPrice) ?></span>
                                &nbsp;|&nbsp;
                                Hardcover: <span class="font-semibold text-slate-800"><?= bb_format_money($hardPrice) ?></span>
                            </p>
                        <?php endif; ?>

                        <p class="mt-2 text-sm text-slate-600"><?= $stock > 0 ? $stock . ' in stock' : 'Out of stock' ?></p>

                        <dl class="mt-6 flex flex-wrap gap-3 text-sm">
                            <?php foreach ([
                                'ISBN'      => $book['isbn'],
                                'Pages'     => $book['pages'],
                                'Publisher' => $book['publisher'],
                                'Year'      => $book['published_year'],
                                'Language'  => $book['language'],
                            ] as $label => $value) : if ($value === null || $value === '') {
                                continue;
                            } ?>
                                <div class="flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 shadow-sm">
                                    <dt class="font-semibold text-slate-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>:</dt>
                                    <dd class="text-slate-800"><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>

                        <div class="mt-6 max-w-2xl leading-relaxed text-slate-700">
                            <?= $book['description'] !== null && $book['description'] !== '' ? nl2br(htmlspecialchars((string) $book['description'], ENT_QUOTES, 'UTF-8')) : '<p class="text-slate-500">No description yet.</p>' ?>
                        </div>

                        <div class="mt-8 flex flex-wrap items-center gap-2.5">
                            <div class="flex h-9 items-center overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                                <button type="button" class="qty-btn w-8 text-center text-sm text-slate-600 transition hover:bg-slate-100" data-delta="-1" aria-label="Decrease quantity">−</button>
                                <input type="number" id="qty" value="1" min="1" max="<?= max(1, $stock) ?>" class="h-9 w-10 border-0 text-center text-xs font-semibold text-slate-800 focus:ring-0" aria-label="Quantity" <?= $stock < 1 ? 'disabled' : '' ?>>
                                <button type="button" class="qty-btn w-8 text-center text-sm text-slate-600 transition hover:bg-slate-100" data-delta="1" aria-label="Increase quantity">+</button>
                            </div>
                            <?php if ($hasCoverOptions && $paperPrice !== null && $hardPrice !== null) : ?>
                                <select id="cover-type" class="h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand" <?= $stock < 1 ? 'disabled' : '' ?>>
                                    <option value="paperback">Soft paperback — <?= bb_format_money($paperPrice) ?></option>
                                    <option value="hardcover">Hardcover — <?= bb_format_money($hardPrice) ?></option>
                                </select>
                            <?php endif; ?>
                            <button type="button" id="add-cart-detail" data-book-id="<?= $bid ?>" class="bb-add-cart-btn inline-flex h-9 min-w-[7.5rem] items-center justify-center rounded-lg bg-brand px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50" <?= $stock < 1 ? 'disabled' : '' ?>>
                                Add to cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (count($related) > 0) : ?>
        <section class="border-t border-slate-100 bg-white py-14 sm:py-16" aria-labelledby="related-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex items-center justify-between">
                    <h2 id="related-heading" class="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">More in <?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></h2>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php?cat=' . $catSlug, ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-brand transition hover:text-brand-dark">View all &rsaquo;</a>
                </div>
                <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4 xl:gap-6">
                    <?php foreach ($related as $rel) :
                        $relPrice = $rel['sale_price'] !== null ? (float) $rel['sale_price'] : (float) $rel['price'];
                        $relUrl   = htmlspecialchars(BOOKBITS_BASE . '/product.php?id=' . (int) $rel['id'], ENT_QUOTES, 'UTF-8');
                        $relCover = bb_book_cover_url($rel);
                    ?>
                        <article class="group flex flex-col rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <a href="<?= $relUrl ?>" class="block aspect-[2/3] overflow-hidden rounded-t-2xl bg-slate-100">
                                <img src="<?= htmlspecialchars($relCover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $rel['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" width="300" height="450">
                            </a>
                            <div class="flex flex-1 flex-col gap-1 p-4">
                                <a href="<?= $relUrl ?>"><h3 class="line-clamp-2 text-sm font-bold text-slate-900 group-hover:text-brand"><?= htmlspecialchars((string) $rel['title'], ENT_QUOTES, 'UTF-8') ?></h3></a>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars((string) $rel['author'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-auto pt-2 text-sm font-bold text-brand"><?= bb_format_money($relPrice) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <script>
        (function () {
            var base = document.body.getAttribute('data-base') || '';
            var maxQ = <?= max(1, $stock) ?>;
            var bid = <?= $bid ?>;
            var qtyInput = document.getElementById('qty');
            function setCount(n) {
                var el = document.getElementById('header-cart-count');
                if (el) el.textContent = n;
            }
            document.querySelectorAll('.qty-btn').forEach(function (b) {
                b.addEventListener('click', function () {
                    var d = parseInt(b.getAttribute('data-delta'), 10);
                    var v = Math.max(1, Math.min(maxQ, (parseInt(qtyInput.value, 10) || 1) + d));
                    qtyInput.value = v;
                });
            });
            var addBtn = document.getElementById('add-cart-detail');
            var coverTypeInput = document.getElementById('cover-type');
            if (addBtn && !addBtn.disabled) {
                addBtn.addEventListener('click', async function () {
                    var q = Math.max(1, Math.min(maxQ, parseInt(qtyInput.value, 10) || 1));
                    var coverType = coverTypeInput ? coverTypeInput.value : '';
                    addBtn.disabled = true;
                    try {
                        var fd = new FormData();
                        fd.append('book_id', String(bid));
                        fd.append('qty', String(q));
                        if (coverType) {
                            fd.append('cover_type', coverType);
                        }
                        var r = await fetch(base + '/actions/cart-add.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                        var j = await r.json();
                        if (j.ok) {
                            setCount(j.count);
                            addBtn.textContent = 'Added to cart';
                            setTimeout(function () { addBtn.textContent = 'Add to cart'; addBtn.disabled = false; }, 1400);
                        } else {
                            alert(j.message || 'Could not add');
                            addBtn.disabled = false;
                        }
                    } catch (e) {
                        addBtn.disabled = false;
                        alert('Network error');
                    }
                });
            }
        })();
        </script>

<?php require __DIR__ . '/includes/footer.php'; ?>
