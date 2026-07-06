<?php

declare(strict_types=1);

$pageTitle = 'Bookbits — Your Next Great Read Awaits';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/config/categories.php';
require_once __DIR__ . '/includes/books_db.php';

$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

/**
 * @param list<array<string,mixed>> $rows
 * @return list<array{id:int,title:string,author:string,img:string}>
 */
function bb_homepage_book_cards(array $rows): array
{
    $cards = [];
    foreach ($rows as $row) {
        $cards[] = [
            'id'     => (int) $row['id'],
            'title'  => (string) $row['title'],
            'author' => (string) $row['author'],
            'img'    => bb_book_cover_url($row),
        ];
    }

    return $cards;
}

try {
    $dealCovers = bb_homepage_book_cards(bb_fetch_deal_books(4));
} catch (Throwable $e) {
    $dealCovers = [];
}

try {
    $newArrivalCovers = bb_homepage_book_cards(bb_fetch_new_arrival_books(4));
} catch (Throwable $e) {
    $newArrivalCovers = [];
}

try {
    $bookBundleCovers = bb_homepage_book_cards(bb_fetch_book_bundle_books(4));
} catch (Throwable $e) {
    $bookBundleCovers = [];
}

$categoryShowcase = BOOKBITS_CATEGORIES;

$categoryIconText = static function (string $bgClass): string {
    if (preg_match('/bg-([a-z]+)-/', $bgClass, $m)) {
        return 'text-' . $m[1] . '-600';
    }

    return 'text-brand';
};

require __DIR__ . '/includes/header.php';

// Hero slides: each has copy + one featured image
$heroSlides = [
    [
        'eyebrow' => 'Daily deals',
        'heading' => "Handpicked Titles\nAt Lower Prices",
        'sub'     => 'Books marked down on our store today — fiction, faith, business, biography, and more.',
        'cta_label'=> 'See Daily Deals',
        'cta_url'  => BOOKBITS_BASE . '/index.php#deals-heading',
        'badge'    => 'DEALS',
        'bg_from'  => '#0f172a',
        'bg_to'    => '#1e3a5f',
        'accent'   => '#38bdf8',
        'image'    => BOOKBITS_BASE . '/assets/img/hero/hero-1.png',
        'image_alt'=> 'Woman holding a stack of popular books',
    ],
    [
        'eyebrow' => 'Fresh arrivals',
        'heading' => "New Books\nJust Landed",
        'sub'     => 'Explore the latest in Fiction, Memoir, and Leadership.',
        'cta_label'=> 'Browse New Arrivals',
        'cta_url'  => BOOKBITS_BASE . '/shop.php',
        'badge'    => 'NEW',
        'bg_from'  => '#172554',
        'bg_to'    => '#1e40af',
        'accent'   => '#7dd3fc',
        'image'    => BOOKBITS_BASE . '/assets/img/hero/hero-2.png',
        'image_alt'=> 'Reader with a stack of new arrivals',
    ],
    [
        'eyebrow' => 'Bestsellers',
        'heading' => "Books That\nChange Lives",
        'sub'     => 'Our most-loved titles in Business, Faith, and Biography.',
        'cta_label'=> 'View Bestsellers',
        'cta_url'  => BOOKBITS_BASE . '/shop.php?cat=biography',
        'badge'    => 'TOP',
        'bg_from'  => '#0c1a2e',
        'bg_to'    => '#0e4d8c',
        'accent'   => '#bae6fd',
        'image'    => BOOKBITS_BASE . '/assets/img/hero/hero-3.png',
        'image_alt'=> 'Book club meeting with friends reading together',
    ],
];

?>

        <?php if ($flashSuccess !== '') : ?>
        <div class="border-b border-emerald-100 bg-emerald-50">
            <div class="mx-auto max-w-7xl px-4 py-3 text-center text-sm font-medium text-emerald-900 sm:px-6 lg:px-8"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════
             HERO CAROUSEL
        ═══════════════════════════════════════════════ -->
        <section id="hero" class="relative min-h-[560px] overflow-hidden md:h-[480px] md:max-h-[520px]" aria-label="Featured promotions">

            <!-- Slide track -->
            <div id="hero-track" class="flex h-full transition-transform duration-700 ease-in-out will-change-transform" style="width:300%">

                <?php foreach ($heroSlides as $si => $slide) :
                    $isFirst = ($si === 0);
                ?>
                <!-- Slide <?= $si + 1 ?> -->
                <div class="relative flex h-full shrink-0 items-center overflow-hidden" style="width:33.3333%;background:linear-gradient(135deg,<?= htmlspecialchars($slide['bg_from'], ENT_QUOTES, 'UTF-8') ?> 0%,<?= htmlspecialchars($slide['bg_to'], ENT_QUOTES, 'UTF-8') ?> 100%);">

                    <!-- Decorative blobs -->
                    <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full opacity-20 blur-3xl" style="background:<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>;" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -bottom-16 left-1/3 h-48 w-48 rounded-full opacity-10 blur-2xl" style="background:<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>;" aria-hidden="true"></div>

                    <div class="relative z-10 mx-auto flex w-full max-w-7xl items-center gap-8 px-5 sm:gap-12 sm:px-8 lg:px-10">

                        <!-- Copy -->
                        <div class="flex-1 py-8">
                            <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-widest" style="background:<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>20;color:<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>;">
                                <span class="inline-block h-1.5 w-1.5 rounded-full" style="background:<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>;"></span>
                                <?= htmlspecialchars($slide['eyebrow'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <h1 class="mt-3 font-serif text-3xl font-black leading-[1.1] text-white sm:text-4xl lg:text-[2.75rem]">
                                <?php
                                $lines = explode("\n", $slide['heading']);
                                foreach ($lines as $li => $line) :
                                    if ($li === 1) : ?>
                                        <span class="relative whitespace-nowrap">
                                            <?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?>
                                            <svg class="absolute -bottom-1 left-0 w-full" height="6" viewBox="0 0 200 6" preserveAspectRatio="none" aria-hidden="true"><path d="M0 5 Q50 0 100 4 Q150 8 200 3" stroke="<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>" stroke-width="2.5" fill="none" stroke-linecap="round"/></svg>
                                        </span>
                                    <?php else :
                                        echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
                                    endif;
                                    if ($li < count($lines) - 1) echo '<br>';
                                endforeach;
                                ?>
                            </h1>

                            <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/70 sm:max-w-sm sm:text-base">
                                <?= htmlspecialchars($slide['sub'], ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <div class="mt-6 flex flex-wrap items-center gap-3">
                                <a href="<?= htmlspecialchars($slide['cta_url'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-900"
                                   style="background:<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>;color:#0f172a;">
                                    <?= htmlspecialchars($slide['cta_label'], ENT_QUOTES, 'UTF-8') ?>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </a>
                                <span class="rounded-full border border-white/25 px-3 py-1.5 text-xs font-semibold text-white/80"><?= htmlspecialchars($slide['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="relative mt-6 overflow-hidden rounded-2xl shadow-lg ring-1 ring-white/20 md:hidden">
                                <img
                                    src="<?= htmlspecialchars($slide['image'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($slide['image_alt'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="aspect-square w-full max-w-xs object-cover"
                                    loading="<?= $isFirst ? 'eager' : 'lazy' ?>"
                                    width="320"
                                    height="320"
                                >
                            </div>
                        </div>

                        <!-- Featured hero image -->
                        <div class="relative hidden shrink-0 md:block" style="width:380px;height:380px;">
                            <div class="absolute inset-0 overflow-hidden rounded-2xl shadow-[0_28px_55px_rgba(2,6,23,0.45)] ring-1 ring-white/20">
                                <img
                                    src="<?= htmlspecialchars($slide['image'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($slide['image_alt'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="h-full w-full object-cover"
                                    loading="<?= $isFirst ? 'eager' : 'lazy' ?>"
                                    width="380"
                                    height="380"
                                >
                            </div>
                            <div class="absolute -bottom-3 left-1/2 h-10 w-56 -translate-x-1/2 rounded-full blur-xl opacity-40" style="background:<?= htmlspecialchars($slide['accent'], ENT_QUOTES, 'UTF-8') ?>;" aria-hidden="true"></div>
                        </div>

                    </div><!-- /inner -->
                </div><!-- /slide -->
                <?php endforeach; ?>

            </div><!-- /track -->

            <!-- Prev / Next arrows -->
            <button id="hero-prev" type="button" aria-label="Previous slide"
                class="absolute left-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white sm:left-5">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button id="hero-next" type="button" aria-label="Next slide"
                class="absolute right-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white sm:right-5">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>

            <!-- Dot indicators + progress bar -->
            <div class="absolute bottom-5 left-1/2 z-20 -translate-x-1/2 flex items-center gap-2" role="tablist" aria-label="Slide indicators">
                <?php foreach ($heroSlides as $di => $_) : ?>
                <button type="button" role="tab"
                    class="hero-dot relative h-2 overflow-hidden rounded-full bg-white/30 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white"
                    data-index="<?= $di ?>"
                    aria-label="Go to slide <?= $di + 1 ?>"
                    aria-selected="<?= $di === 0 ? 'true' : 'false' ?>"
                    style="width:<?= $di === 0 ? '32px' : '8px' ?>;">
                    <span class="hero-dot-bar absolute inset-0 rounded-full bg-white transition-transform duration-[5000ms] ease-linear" style="transform-origin:left;transform:<?= $di === 0 ? 'scaleX(1)' : 'scaleX(0)' ?>;"></span>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Slide counter -->
            <div class="absolute right-4 top-4 z-20 rounded-full bg-black/30 px-3 py-1 text-xs font-bold text-white backdrop-blur-sm sm:right-6 sm:top-5" aria-live="polite">
                <span id="hero-counter">1</span> / <?= count($heroSlides) ?>
            </div>

        </section>

        <script>
        (function () {
            var total    = <?= count($heroSlides) ?>;
            var current  = 0;
            var autoMs   = 5000;
            var track    = document.getElementById('hero-track');
            var dots     = document.querySelectorAll('.hero-dot');
            var bars     = document.querySelectorAll('.hero-dot-bar');
            var counter  = document.getElementById('hero-counter');
            var timer    = null;

            function goTo(idx, restart) {
                // wrap
                idx = ((idx % total) + total) % total;
                current = idx;

                track.style.transform = 'translateX(-' + (idx * 33.3333) + '%)';
                counter.textContent   = idx + 1;

                dots.forEach(function (d, i) {
                    var active = (i === idx);
                    d.setAttribute('aria-selected', active ? 'true' : 'false');
                    d.style.width = active ? '32px' : '8px';
                    bars[i].style.transition = 'none';
                    bars[i].style.transform  = 'scaleX(0)';
                });

                // trigger progress bar animation on active dot
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        bars[idx].style.transition = 'transform ' + autoMs + 'ms linear';
                        bars[idx].style.transform  = 'scaleX(1)';
                    });
                });

                if (restart !== false) {
                    clearInterval(timer);
                    timer = setInterval(function () { goTo(current + 1); }, autoMs);
                }
            }

            document.getElementById('hero-prev').addEventListener('click', function () { goTo(current - 1); });
            document.getElementById('hero-next').addEventListener('click', function () { goTo(current + 1); });

            dots.forEach(function (d) {
                d.addEventListener('click', function () { goTo(parseInt(this.dataset.index, 10)); });
            });

            // pause on hover
            var section = document.getElementById('hero');
            section.addEventListener('mouseenter', function () { clearInterval(timer); });
            section.addEventListener('mouseleave', function () {
                timer = setInterval(function () { goTo(current + 1); }, autoMs);
            });

            // Touch / swipe support
            var touchX = 0;
            section.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, {passive:true});
            section.addEventListener('touchend',   function (e) {
                var dx = e.changedTouches[0].clientX - touchX;
                if (Math.abs(dx) > 40) goTo(dx < 0 ? current + 1 : current - 1);
            }, {passive:true});

            goTo(0, false);
            timer = setInterval(function () { goTo(current + 1); }, autoMs);
        })();
        </script>

        <!-- Categories -->
        <section class="border-t border-slate-100 bg-gradient-to-b from-slate-50 to-white py-10 sm:py-12" aria-labelledby="categories-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 id="categories-heading" class="font-serif text-2xl font-black text-slate-900 sm:text-3xl">Browse by category</h2>
                        <p class="mt-1 text-sm text-slate-500">Find your next read across fiction, faith, business, and more.</p>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <button type="button" id="cat-scroll-prev" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-brand/30 hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30" aria-label="Scroll categories left">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" id="cat-scroll-next" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-brand/30 hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30" aria-label="Scroll categories right">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="ml-1 text-sm font-semibold text-brand transition hover:text-brand-dark">See all</a>
                    </div>
                </div>

                <?php if (count($categoryShowcase) > 0) : ?>
                    <div id="cat-scroll" class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <?php foreach ($categoryShowcase as $catCard) :
                            $iconBg = (string) ($catCard['bg'] ?? 'bg-sky-100');
                            $iconText = $categoryIconText($iconBg);
                            ?>
                            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php?cat=' . $catCard['slug'], ENT_QUOTES, 'UTF-8') ?>"
                               class="group relative flex w-[220px] shrink-0 snap-start flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-brand/25 hover:shadow-md sm:w-[240px]">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl <?= htmlspecialchars($iconBg, ENT_QUOTES, 'UTF-8') ?> ring-1 ring-black/5">
                                        <svg class="h-6 w-6 <?= htmlspecialchars($iconText, ENT_QUOTES, 'UTF-8') ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="<?= htmlspecialchars((string) $catCard['icon'], ENT_QUOTES, 'UTF-8') ?>"/>
                                        </svg>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 transition group-hover:bg-brand/10 group-hover:text-brand">Shop</span>
                                </div>
                                <h3 class="mt-4 font-serif text-lg font-bold leading-tight text-slate-900 transition group-hover:text-brand"><?= htmlspecialchars((string) $catCard['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="mt-2 line-clamp-2 flex-1 text-xs leading-relaxed text-slate-500"><?= htmlspecialchars((string) $catCard['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-brand">
                                    Browse books
                                    <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <script>
                    (function () {
                        var track = document.getElementById('cat-scroll');
                        var prev = document.getElementById('cat-scroll-prev');
                        var next = document.getElementById('cat-scroll-next');
                        if (!track || !prev || !next) return;
                        function scrollByDir(dir) {
                            var card = track.querySelector('a');
                            var gap = 16;
                            var amount = card ? card.offsetWidth + gap : 260;
                            track.scrollBy({ left: dir * amount, behavior: 'smooth' });
                        }
                        prev.addEventListener('click', function () { scrollByDir(-1); });
                        next.addEventListener('click', function () { scrollByDir(1); });
                    })();
                    </script>
                <?php else : ?>
                    <p class="rounded-xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-500">Add books to categories to show them here.</p>
                <?php endif; ?>
            </div>
        </section>

        <?php require __DIR__ . '/includes/banner-editorial.php'; ?>

        <!-- Daily Deals -->
        <section class="border-t border-slate-100 bg-gradient-to-b from-[#f0f9ff] to-white py-12 sm:py-14" aria-labelledby="deals-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Section header -->
                <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="deals-heading" class="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">Daily Deals</h2>
                        <p class="mt-1 text-sm text-slate-500">Handpicked titles at irresistible prices.</p>
                    </div>
                    <!-- Countdown -->
                    <div class="inline-flex items-center gap-3 self-start rounded-2xl border border-blue-100 bg-white px-4 py-2.5 shadow-sm sm:self-auto">
                        <svg class="h-4 w-4 shrink-0 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div id="deal-flip" class="flex gap-1 font-mono text-xs font-bold tabular-nums text-slate-800">
                            <span class="flex flex-col items-center"><span id="dc-h" class="rounded bg-brand px-1.5 py-0.5 text-white">00</span><span class="mt-0.5 text-[9px] text-slate-400">HRS</span></span>
                            <span class="mt-0.5 text-slate-300">:</span>
                            <span class="flex flex-col items-center"><span id="dc-m" class="rounded bg-brand px-1.5 py-0.5 text-white">00</span><span class="mt-0.5 text-[9px] text-slate-400">MIN</span></span>
                            <span class="mt-0.5 text-slate-300">:</span>
                            <span class="flex flex-col items-center"><span id="dc-s" class="rounded bg-brand px-1.5 py-0.5 text-white">00</span><span class="mt-0.5 text-[9px] text-slate-400">SEC</span></span>
                        </div>
                    </div>
                </div>

                <!-- Book grid -->
                <?php if (count($dealCovers) === 0) : ?>
                    <p class="rounded-xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-600">Mark books as &ldquo;Daily deal&rdquo; in the admin dashboard to show them here.</p>
                <?php else : ?>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:gap-5">
                    <?php foreach ($dealCovers as $book) : ?>
                        <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/product.php?id=' . $book['id'], ENT_QUOTES, 'UTF-8') ?>" class="group block">
                            <div class="relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-100 shadow-sm ring-1 ring-slate-900/5 transition group-hover:-translate-y-1 group-hover:shadow-md">
                                <img src="<?= htmlspecialchars($book['img'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-r from-black/20 to-transparent" aria-hidden="true"></div>
                                <span class="absolute left-2 top-2 rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow">Deal</span>
                            </div>
                            <div class="mt-2 px-0.5">
                                <p class="line-clamp-1 text-xs font-bold text-slate-800 transition group-hover:text-brand sm:text-sm"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-0.5 text-[11px] text-slate-500 sm:text-xs"><?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mt-8 text-center">
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-full border border-brand/20 bg-white px-7 py-2.5 text-sm font-semibold text-brand shadow-sm transition hover:bg-brand hover:text-white">
                        Browse All Books
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>

            </div>
        </section>

        <script>
        (function () {
            var end  = <?= time() + 23 * 3600 + 59 * 60 + 59 ?> * 1000;
            var elH  = document.getElementById('dc-h');
            var elM  = document.getElementById('dc-m');
            var elS  = document.getElementById('dc-s');
            function pad(n) { return n < 10 ? '0' + n : n; }
            function tick() {
                var diff = Math.max(0, Math.floor((end - Date.now()) / 1000));
                elH.textContent = pad(Math.floor(diff / 3600));
                elM.textContent = pad(Math.floor((diff % 3600) / 60));
                elS.textContent = pad(diff % 60);
            }
            tick();
            setInterval(tick, 1000);
        })();
        </script>

        <!-- New Arrivals -->
        <section class="border-t border-slate-100 bg-white py-12 sm:py-14" aria-labelledby="new-arrivals-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="new-arrivals-heading" class="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">New Arrivals</h2>
                        <p class="mt-1 text-sm text-slate-500">Fresh titles recently added to Bookbits.</p>
                    </div>
                </div>

                <?php if (count($newArrivalCovers) === 0) : ?>
                    <p class="rounded-xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-600">Mark books as &ldquo;New arrival&rdquo; in the admin dashboard to show them here.</p>
                <?php else : ?>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:gap-5">
                        <?php foreach ($newArrivalCovers as $book) : ?>
                            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/product.php?id=' . $book['id'], ENT_QUOTES, 'UTF-8') ?>" class="group block">
                                <div class="relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-100 shadow-sm ring-1 ring-slate-900/5 transition group-hover:-translate-y-1 group-hover:shadow-md">
                                    <img src="<?= htmlspecialchars($book['img'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-r from-black/20 to-transparent" aria-hidden="true"></div>
                                    <span class="absolute left-2 top-2 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow">New</span>
                                </div>
                                <div class="mt-2 px-0.5">
                                    <p class="line-clamp-1 text-xs font-bold text-slate-800 transition group-hover:text-emerald-700 sm:text-sm"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 sm:text-xs"><?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Book Bundles -->
        <section class="border-t border-slate-100 bg-gradient-to-b from-purple-50 to-white py-12 sm:py-14" aria-labelledby="book-bundles-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="book-bundles-heading" class="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">Book Bundles</h2>
                        <p class="mt-1 text-sm text-slate-500">Special bundle picks curated to read together.</p>
                    </div>
                </div>

                <?php if (count($bookBundleCovers) === 0) : ?>
                    <p class="rounded-xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-600">Mark books as &ldquo;Book bundle&rdquo; in the admin dashboard to show them here.</p>
                <?php else : ?>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:gap-5">
                        <?php foreach ($bookBundleCovers as $book) : ?>
                            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/product.php?id=' . $book['id'], ENT_QUOTES, 'UTF-8') ?>" class="group block">
                                <div class="relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-100 shadow-sm ring-1 ring-slate-900/5 transition group-hover:-translate-y-1 group-hover:shadow-md">
                                    <img src="<?= htmlspecialchars($book['img'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-r from-black/20 to-transparent" aria-hidden="true"></div>
                                    <span class="absolute left-2 top-2 rounded-full bg-purple-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow">Bundle</span>
                                </div>
                                <div class="mt-2 px-0.5">
                                    <p class="line-clamp-1 text-xs font-bold text-slate-800 transition group-hover:text-purple-700 sm:text-sm"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 sm:text-xs"><?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

<?php
require __DIR__ . '/includes/footer.php';
