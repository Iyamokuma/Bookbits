<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../config/categories.php';
require_once __DIR__ . '/books_db.php';
require_once __DIR__ . '/icons.php';

$bbUser = bb_current_user();
$bbCartCount = bb_cart_total_qty();

try {
    $headerCategories = bb_fetch_categories_for_nav();
} catch (Throwable $e) {
    $headerCategories = [];
    foreach (BOOKBITS_CATEGORIES as $c) {
        $headerCategories[] = ['slug' => $c['slug'], 'name' => $c['name']];
    }
}

$pageTitle = $pageTitle ?? 'Bookbits — Online Bookstore';
$logoFs = __DIR__ . '/../assets/img/logo.png';
$logoWeb = rtrim(BOOKBITS_BASE, '/') . '/assets/img/logo.png';
$hasLogo = is_file($logoFs);
$base = BOOKBITS_BASE;
$bbWaSocial = bb_whatsapp_chat_url();
$bbFbUrl = defined('BOOKBITS_FACEBOOK_URL') ? trim((string) BOOKBITS_FACEBOOK_URL) : '';
$bbIgUrl = defined('BOOKBITS_INSTAGRAM_URL') ? trim((string) BOOKBITS_INSTAGRAM_URL) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:ital,wght@0,700;0,900;1,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#1d4ed8',
                            dark: '#1e40af',
                            light: '#3b82f6',
                            soft: '#e0f2fe',
                            muted: '#f0f9ff',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        serif: ['Merriweather', 'Georgia', 'serif'],
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased" data-base="<?= htmlspecialchars(BOOKBITS_BASE, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Utility bar (desktop only) -->
    <div class="hidden border-b border-slate-200/80 bg-slate-100 text-slate-600 md:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-sm sm:px-6 lg:px-8">
            <a href="tel:<?= htmlspecialchars(bb_store_phone_tel(), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 hover:text-brand-dark">
                <svg class="h-4 w-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span><?= htmlspecialchars(bb_store_phone_display(), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <div class="flex items-center gap-3">
                <span class="text-slate-400">Follow us</span>
                <div class="flex gap-2">
                    <?php if ($bbFbUrl !== '') : ?>
                        <a href="<?= htmlspecialchars($bbFbUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rounded-full p-1 text-slate-500 transition hover:bg-white hover:text-brand" aria-label="Facebook"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg></a>
                    <?php endif; ?>
                    <?php if ($bbIgUrl !== '') : ?>
                        <a href="<?= htmlspecialchars($bbIgUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rounded-full p-1 text-slate-500 transition hover:bg-white hover:text-brand" aria-label="Instagram"><?= bb_svg_instagram('h-4 w-4') ?></a>
                    <?php endif; ?>
                    <?php if ($bbWaSocial !== '') : ?>
                        <a href="<?= htmlspecialchars($bbWaSocial, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rounded-full p-1 text-[#25D366] transition hover:bg-white hover:text-[#20BD5A]" aria-label="WhatsApp"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main header -->
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">

            <!-- Hamburger (mobile only) -->
            <button type="button" id="bb-hamburger" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-600 transition hover:bg-brand-muted hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30 md:hidden" aria-expanded="false" aria-controls="bb-mobile-menu" aria-label="Open menu">
                <svg id="bb-ham-open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg id="bb-ham-close" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Logo -->
            <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/index.php" class="flex shrink-0 items-center gap-2">
                <?php if ($hasLogo) : ?>
                    <img src="<?= htmlspecialchars($logoWeb, ENT_QUOTES, 'UTF-8') ?>" alt="Bookbits" class="h-9 w-auto max-w-[140px] object-contain sm:h-10 md:h-12 md:max-w-[180px]">
                <?php else : ?>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-soft text-brand">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </span>
                    <span class="font-serif text-lg font-bold tracking-tight text-slate-900">BOOKBITS</span>
                <?php endif; ?>
            </a>

            <!-- Desktop nav (hidden on mobile) -->
            <nav class="hidden md:flex md:items-center md:gap-5" aria-label="Primary">
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/index.php" class="text-sm font-medium text-slate-700 transition hover:text-brand">Home</a>
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/shop.php" class="text-sm font-medium text-slate-700 transition hover:text-brand">Books</a>
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/blog.php" class="text-sm font-medium text-slate-700 transition hover:text-brand">Blog</a>
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/shop.php" class="text-sm font-medium text-slate-700 transition hover:text-brand">Shop</a>
            </nav>

            <!-- Right icons (always visible) -->
            <div class="flex items-center gap-1">
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/cart.php" class="relative rounded-lg p-1.5 text-slate-600 transition hover:bg-brand-muted hover:text-brand" aria-label="Shopping cart">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span id="header-cart-count" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white"><?= (int) $bbCartCount ?></span>
                </a>
                <div class="relative z-50" id="bb-profile-wrap">
                    <button type="button" id="bb-profile-trigger" class="rounded-lg p-2 text-slate-600 transition hover:bg-brand-muted hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30" aria-expanded="false" aria-haspopup="true" aria-controls="bb-profile-panel" title="Account">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </button>
                    <div id="bb-profile-panel" role="menu" class="absolute right-0 top-full mt-2 hidden w-[min(18rem,calc(100vw-2rem))] rounded-xl border border-slate-200 bg-white py-3 shadow-lg ring-1 ring-black/5">
                        <?php if ($bbUser === null) : ?>
                            <div class="px-4 pb-2">
                                <p class="text-sm font-semibold text-slate-900">Welcome</p>
                                <p class="mt-1 text-xs leading-relaxed text-slate-600">Sign in to track orders and checkout faster, or create an account.</p>
                            </div>
                            <div class="mt-1 border-t border-slate-100 px-3 pt-3">
                                <a href="<?= htmlspecialchars($base . '/login.php', ENT_QUOTES, 'UTF-8') ?>" role="menuitem" class="block rounded-lg bg-brand px-3 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark">Sign in</a>
                                <a href="<?= htmlspecialchars($base . '/register.php', ENT_QUOTES, 'UTF-8') ?>" role="menuitem" class="mt-2 block rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Create account</a>
                            </div>
                        <?php else : ?>
                            <div class="px-4">
                                <p class="truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) $bbUser['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-0.5 truncate text-xs text-slate-500"><?= htmlspecialchars((string) $bbUser['email'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="mt-3 border-t border-slate-100 px-4 pt-3">
                                <p class="text-xs font-medium text-slate-700">Sign out?</p>
                                <p class="mt-1 text-xs text-slate-500">You'll need to sign in again to view your account.</p>
                                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <button type="button" class="bb-profile-cancel order-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:order-1 sm:w-auto">Stay signed in</button>
                                    <a href="<?= htmlspecialchars($base . '/logout.php', ENT_QUOTES, 'UTF-8') ?>" role="menuitem" class="order-1 w-full rounded-lg bg-slate-900 px-3 py-2 text-center text-sm font-semibold text-white transition hover:bg-slate-800 sm:order-2 sm:w-auto">Sign out</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile slide-down menu -->
        <div id="bb-mobile-menu" class="hidden border-t border-slate-100 bg-white md:hidden">
            <div class="mx-auto max-w-7xl px-4 pb-5 pt-3 sm:px-6">

                <!-- Mobile nav links -->
                <nav class="space-y-1" aria-label="Mobile navigation">
                    <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/index.php" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Home
                    </a>
                    <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/shop.php" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        Books
                    </a>
                    <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/blog.php" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        Blog
                    </a>
                    <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/shop.php" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Shop
                    </a>
                    <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/cart.php" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        Cart
                        <?php if ($bbCartCount > 0) : ?>
                            <span class="ml-auto rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold text-white"><?= (int) $bbCartCount ?></span>
                        <?php endif; ?>
                    </a>
                </nav>

                <!-- Mobile search -->
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <form action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/shop.php" method="get" role="search">
                        <div class="flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <label class="sr-only" for="q-mobile">Search products</label>
                            <input id="q-mobile" name="q" type="search" placeholder="Search products…" class="min-w-0 flex-1 border-0 px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0">
                            <button type="submit" class="inline-flex items-center justify-center bg-brand px-4 text-white transition hover:bg-brand-dark" aria-label="Search">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Mobile account -->
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <?php if ($bbUser === null) : ?>
                        <div class="flex gap-2">
                            <a href="<?= htmlspecialchars($base . '/login.php', ENT_QUOTES, 'UTF-8') ?>" class="flex-1 rounded-lg bg-brand px-3 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark">Sign in</a>
                            <a href="<?= htmlspecialchars($base . '/register.php', ENT_QUOTES, 'UTF-8') ?>" class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Register</a>
                        </div>
                    <?php else : ?>
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) $bbUser['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="truncate text-xs text-slate-500"><?= htmlspecialchars((string) $bbUser['email'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <a href="<?= htmlspecialchars($base . '/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="shrink-0 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Sign out</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile contact -->
                <?php if (bb_store_phone_display() !== '') : ?>
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <a href="tel:<?= htmlspecialchars(bb_store_phone_tel(), ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                        <svg class="h-5 w-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <?= htmlspecialchars(bb_store_phone_display(), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Mobile socials -->
                <div class="mt-4 flex items-center justify-center gap-4 border-t border-slate-100 pt-4">
                    <?php if ($bbFbUrl !== '') : ?>
                        <a href="<?= htmlspecialchars($bbFbUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rounded-full p-2 text-slate-400 transition hover:bg-brand-muted hover:text-brand" aria-label="Facebook"><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg></a>
                    <?php endif; ?>
                    <?php if ($bbIgUrl !== '') : ?>
                        <a href="<?= htmlspecialchars($bbIgUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rounded-full p-2 text-slate-400 transition hover:bg-brand-muted hover:text-brand" aria-label="Instagram"><?= bb_svg_instagram('h-5 w-5') ?></a>
                    <?php endif; ?>
                    <?php if ($bbWaSocial !== '') : ?>
                        <a href="<?= htmlspecialchars($bbWaSocial, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rounded-full p-2 text-[#25D366] transition hover:bg-emerald-50 hover:text-[#20BD5A]" aria-label="WhatsApp"><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
                    <?php endif; ?>
                    <a href="tel:<?= htmlspecialchars(bb_store_phone_tel(), ENT_QUOTES, 'UTF-8') ?>" class="rounded-full p-2 text-slate-400 transition hover:bg-brand-muted hover:text-brand" aria-label="Call us"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></a>
                </div>
            </div>
        </div>

        <!-- Hamburger toggle script -->
        <script>
        (function () {
            var btn = document.getElementById('bb-hamburger');
            var menu = document.getElementById('bb-mobile-menu');
            var iconOpen = document.getElementById('bb-ham-open');
            var iconClose = document.getElementById('bb-ham-close');
            if (!btn || !menu) return;
            btn.addEventListener('click', function () {
                var open = menu.classList.contains('hidden');
                menu.classList.toggle('hidden', !open);
                iconOpen.classList.toggle('hidden', open);
                iconClose.classList.toggle('hidden', !open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        })();
        </script>

    </header>

    <main>
