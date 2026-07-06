<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/config/categories.php';
require_once __DIR__ . '/includes/books_db.php';

$slug = trim($_GET['cat'] ?? '');
$activeCat = null;
if ($slug !== '') {
    $activeCat = bookbits_category_by_slug($slug);
    if ($activeCat === null) {
        $st = db()->prepare('SELECT name, slug, description FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1');
        $st->execute([$slug]);
        $row = $st->fetch();
        if ($row) {
            $activeCat = [
                'slug'        => (string) $row['slug'],
                'name'        => (string) $row['name'],
                'description' => (string) ($row['description'] ?? ''),
                'bg'          => 'bg-sky-50',
                'icon'        => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            ];
        }
    }
}

$shopNavCategories = bb_fetch_categories_for_nav();
$pageTitle = $activeCat
    ? htmlspecialchars($activeCat['name'], ENT_QUOTES, 'UTF-8') . ' — Bookbits'
    : 'All Books — Bookbits';

$search = trim($_GET['q'] ?? '');
$books  = bb_fetch_books_for_shop($slug !== '' ? $slug : null, $search);

foreach ($books as &$b) {
    $b['cover_url'] = bb_book_cover_url($b);
}
unset($b);

?>
<?php require __DIR__ . '/includes/header.php'; ?>

        <!-- Breadcrumb -->
        <nav class="border-b border-slate-100 bg-white" aria-label="Breadcrumb">
            <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 text-sm text-slate-600 sm:px-6 lg:px-8">
                <a href="<?= htmlspecialchars(BOOKBITS_BASE, ENT_QUOTES, 'UTF-8') ?>/index.php" class="hover:text-brand">Home</a>
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <?php if ($activeCat) : ?>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="hover:text-brand">Shop</a>
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="font-medium text-slate-900"><?= htmlspecialchars($activeCat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php else : ?>
                    <span class="font-medium text-slate-900">All Books</span>
                <?php endif; ?>
            </div>
        </nav>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-start">

                <aside class="w-full shrink-0 lg:w-60" aria-label="Categories">
                    <div class="sticky top-[7.5rem] rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-slate-500">Categories</h2>
                        <ul class="space-y-1">
                            <li>
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>"
                                   class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition <?= $slug === '' ? 'bg-brand text-white' : 'text-slate-700 hover:bg-brand-muted hover:text-brand' ?>">
                                    <span>All Books</span>
                                </a>
                            </li>
                            <?php foreach ($shopNavCategories as $cat) :
                                $isActive = ($slug === $cat['slug']);
                            ?>
                            <li>
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php?cat=' . $cat['slug'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition <?= $isActive ? 'bg-brand text-white' : 'text-slate-700 hover:bg-brand-muted hover:text-brand' ?>">
                                    <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>

                <div class="min-w-0 flex-1">
                    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="font-serif text-3xl font-bold text-slate-900 sm:text-4xl">
                                <?= $activeCat ? htmlspecialchars($activeCat['name'], ENT_QUOTES, 'UTF-8') : 'All Books' ?>
                            </h1>
                            <?php if ($activeCat) : ?>
                                <p class="mt-1 text-slate-600"><?= htmlspecialchars($activeCat['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <form class="flex overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" action="" method="get">
                            <?php if ($slug !== '') : ?>
                                <input type="hidden" name="cat" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                            <input type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search books…" class="min-w-0 flex-1 border-0 px-4 py-2.5 text-sm placeholder:text-slate-400 focus:ring-0">
                            <button type="submit" class="border-l border-slate-200 bg-brand px-4 text-white transition hover:bg-brand-dark" aria-label="Search">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </button>
                        </form>
                    </div>

                    <p class="mb-6 text-sm text-slate-500">
                        <?= count($books) ?> <?= count($books) === 1 ? 'book' : 'books' ?> found
                    </p>

                    <?php if (count($books) === 0) : ?>
                        <div class="flex flex-col items-center py-20 text-center text-slate-500">
                            <p class="text-lg font-semibold text-slate-700">No books found</p>
                            <p class="mt-1 text-sm">Add products in the admin dashboard or try another search.</p>
                            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-5 rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark">Browse all</a>
                        </div>
                    <?php else : ?>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-3 lg:gap-5 xl:grid-cols-4">
                            <?php foreach ($books as $book) :
                                $hasCoverOptions = bb_book_supports_cover_options($book);
                                $paperPrice = $book['paperback_price'] !== null ? (float) $book['paperback_price'] : null;
                                $hardPrice  = $book['hardcover_price'] !== null ? (float) $book['hardcover_price'] : null;
                                $displayPrice = $hasCoverOptions && $paperPrice !== null
                                    ? $paperPrice
                                    : ($book['sale_price'] !== null ? (float) $book['sale_price'] : (float) $book['price']);
                                $isOnSale     = $book['sale_price'] !== null;
                                $productUrl   = htmlspecialchars(BOOKBITS_BASE . '/product.php?id=' . (int) $book['id'], ENT_QUOTES, 'UTF-8');
                                $bid          = (int) $book['id'];
                                $cover        = $book['cover_url'];
                            ?>
                                <article class="group flex flex-col rounded-xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                    <a href="<?= $productUrl ?>" class="relative block aspect-[3/4] overflow-hidden rounded-t-xl bg-slate-100">
                                        <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                                        <?php if ($isOnSale) : ?>
                                            <span class="absolute left-2 top-2 rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow">Sale</span>
                                        <?php endif; ?>
                                        <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-white/20 to-slate-300/10" aria-hidden="true"></div>
                                    </a>
                                    <div class="flex flex-1 flex-col gap-1.5 p-3">
                                        <a href="<?= $productUrl ?>">
                                            <h3 class="line-clamp-2 text-xs font-bold leading-snug text-slate-900 transition group-hover:text-brand sm:text-sm"><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        </a>
                                        <p class="text-[11px] text-slate-500 sm:text-xs"><?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if ($hasCoverOptions && $paperPrice !== null && $hardPrice !== null) : ?>
                                            <p class="text-[10px] font-medium text-slate-500 sm:text-[11px]">PB: <?= bb_format_money($paperPrice) ?> · HC: <?= bb_format_money($hardPrice) ?></p>
                                        <?php endif; ?>
                                        <div class="mt-auto pt-2">
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-sm font-bold text-brand"><?= bb_format_money($displayPrice) ?></span>
                                                <?php if ($isOnSale && !$hasCoverOptions) : ?>
                                                    <span class="text-[11px] text-slate-400 line-through"><?= bb_format_money((float) $book['price']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" data-add-cart="<?= $bid ?>" data-has-cover-options="<?= $hasCoverOptions ? '1' : '0' ?>" class="bb-add-cart-btn mt-2 inline-flex h-9 w-full items-center justify-center rounded-lg bg-brand px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50">Add to cart</button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
