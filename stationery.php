<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/books_db.php';

$pageTitle = 'Stationery — Books, Bits & Co';
$search = trim($_GET['q'] ?? '');

try {
    $items = bb_fetch_stationery_books(0);
} catch (Throwable $e) {
    $items = [];
}

if ($search !== '') {
    $q = mb_strtolower($search);
    $items = array_values(array_filter($items, static function (array $row) use ($q): bool {
        $hay = mb_strtolower((string) ($row['title'] ?? '') . ' ' . (string) ($row['author'] ?? ''));

        return strpos($hay, $q) !== false;
    }));
}

foreach ($items as &$item) {
    $item['cover_url'] = bb_book_cover_url($item);
}
unset($item);

require __DIR__ . '/includes/header.php';
?>

        <!-- Stationery-only page hero -->
        <section class="relative overflow-hidden border-b border-violet-100 bg-gradient-to-br from-violet-950 via-slate-900 to-indigo-900 text-white" aria-label="Stationery">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-violet-400/20 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/4 h-48 w-48 rounded-full bg-indigo-400/15 blur-3xl" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                <nav class="mb-6 flex items-center gap-2 text-sm text-violet-200/80" aria-label="Breadcrumb">
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="hover:text-white">Home</a>
                    <span aria-hidden="true">/</span>
                    <span class="font-medium text-white">Stationery</span>
                </nav>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-300">Dedicated stationery shop</p>
                <h1 class="mt-3 font-serif text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">Stationery</h1>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-violet-100/85 sm:text-base">
                    Journals, notebooks, pens, and writing essentials — this page lists stationery only, separate from our book collection.
                </p>
                <form class="mt-6 flex max-w-md overflow-hidden rounded-xl border border-white/15 bg-white/10 shadow-lg backdrop-blur" action="" method="get" role="search">
                    <label class="sr-only" for="stationery-q">Search stationery</label>
                    <input id="stationery-q" type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search stationery…" class="min-w-0 flex-1 border-0 bg-transparent px-4 py-3 text-sm text-white placeholder:text-violet-200/60 focus:ring-0">
                    <button type="submit" class="border-l border-white/15 bg-white px-5 text-sm font-bold text-violet-950 transition hover:bg-violet-100" aria-label="Search">Search</button>
                </form>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">
                    <?= count($items) ?> stationery <?= count($items) === 1 ? 'item' : 'items' ?>
                    <?php if ($search !== '') : ?>
                        <span class="text-slate-400">matching “<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>”</span>
                    <?php endif; ?>
                </p>
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-slate-600 transition hover:text-brand">Looking for books? → Shop books</a>
            </div>

            <?php if (count($items) === 0) : ?>
                <div class="rounded-2xl border border-dashed border-violet-200 bg-violet-50/50 px-6 py-16 text-center">
                    <p class="text-lg font-semibold text-slate-800">No stationery listed yet</p>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-6 inline-flex rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark">Browse books instead</a>
                </div>
            <?php else : ?>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-3 lg:gap-5 xl:grid-cols-4">
                    <?php foreach ($items as $book) :
                        $hasCoverOptions = bb_book_supports_cover_options($book);
                        $paperPrice = $book['paperback_price'] !== null ? (float) $book['paperback_price'] : null;
                        $displayPrice = $hasCoverOptions && $paperPrice !== null
                            ? $paperPrice
                            : ($book['sale_price'] !== null ? (float) $book['sale_price'] : (float) $book['price']);
                        $isOnSale     = $book['sale_price'] !== null;
                        $productUrl   = htmlspecialchars(BOOKBITS_BASE . '/product.php?id=' . (int) $book['id'], ENT_QUOTES, 'UTF-8');
                        $bid          = (int) $book['id'];
                        $cover        = $book['cover_url'];
                        ?>
                        <article class="group flex flex-col rounded-xl border border-violet-100/80 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">
                            <a href="<?= $productUrl ?>" class="relative block aspect-[3/4] overflow-hidden rounded-t-xl bg-slate-100">
                                <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                                <span class="absolute left-2 top-2 rounded-full bg-violet-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow">Stationery</span>
                                <?php if ($isOnSale) : ?>
                                    <span class="absolute right-2 top-2 rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow">Sale</span>
                                <?php endif; ?>
                            </a>
                            <div class="flex flex-1 flex-col gap-1.5 p-3">
                                <a href="<?= $productUrl ?>">
                                    <h2 class="line-clamp-2 text-xs font-bold leading-snug text-slate-900 transition group-hover:text-violet-700 sm:text-sm"><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                </a>
                                <p class="text-[11px] text-slate-500 sm:text-xs"><?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="mt-auto pt-2">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-sm font-bold text-brand"><?= bb_format_money($displayPrice) ?></span>
                                        <?php if ($isOnSale && !$hasCoverOptions) : ?>
                                            <span class="text-[11px] text-slate-400 line-through"><?= bb_format_money((float) $book['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" data-add-cart="<?= $bid ?>" data-has-cover-options="<?= $hasCoverOptions ? '1' : '0' ?>" class="bb-add-cart-btn mt-2 inline-flex h-9 w-full items-center justify-center rounded-lg bg-violet-700 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-800 disabled:cursor-not-allowed disabled:opacity-50">Add to cart</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
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
            document.querySelectorAll('[data-add-cart]').forEach(function (btn) {
                btn.addEventListener('click', async function (e) {
                    e.preventDefault();
                    var id = btn.getAttribute('data-add-cart');
                    var hasCoverOptions = btn.getAttribute('data-has-cover-options') === '1';
                    var coverType = '';
                    if (hasCoverOptions) {
                        var pick = window.prompt('Choose cover type: paperback or hardcover', 'paperback');
                        if (pick === null) {
                            return;
                        }
                        pick = String(pick).toLowerCase().trim();
                        if (pick !== 'paperback' && pick !== 'hardcover') {
                            alert('Please enter "paperback" or "hardcover".');
                            return;
                        }
                        coverType = pick;
                    }
                    btn.disabled = true;
                    try {
                        var fd = new FormData();
                        fd.append('book_id', id);
                        fd.append('qty', '1');
                        if (coverType) {
                            fd.append('cover_type', coverType);
                        }
                        var r = await fetch(base + '/actions/cart-add.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                        var j = await r.json();
                        if (j.ok) {
                            setCount(j.count);
                            btn.textContent = 'Added';
                            setTimeout(function () { btn.textContent = 'Add to cart'; btn.disabled = false; }, 1200);
                        } else {
                            alert(j.message || 'Could not add to cart');
                            btn.disabled = false;
                        }
                    } catch (err) {
                        btn.disabled = false;
                        alert('Network error');
                    }
                });
            });
        })();
        </script>

<?php require __DIR__ . '/includes/footer.php'; ?>
