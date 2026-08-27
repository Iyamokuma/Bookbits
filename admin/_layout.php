<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * @param string $pageTitle
 * @param string $active dashboard|products|categories|blogs|payments
 */
function bb_admin_header(string $pageTitle, string $active = 'dashboard'): void
{
    bb_require_admin();

    $b = BOOKBITS_BASE;
    $user = bb_current_user();
    $email = (string) ($user['email'] ?? '');
    $name = (string) ($user['name'] ?? 'Admin');
    $initial = strtoupper(substr($name !== '' ? $name : 'A', 0, 1));

    $navItem = static function (string $key, string $label, string $href, string $icon, string $active, string $b, ?int $badge = null): string {
        $isOn = $active === $key;
        $row = $isOn
            ? 'relative bg-brand text-white shadow-lg shadow-brand/25'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
        $badgeHtml = '';
        if ($badge !== null && $badge > 0) {
            $badgeHtml = '<span class="ml-auto inline-flex min-w-[1.35rem] items-center justify-center rounded-md bg-slate-900 px-1.5 py-0.5 text-[10px] font-bold text-white">'
                . htmlspecialchars((string) ($badge > 99 ? '99+' : $badge), ENT_QUOTES, 'UTF-8')
                . '</span>';
            if ($isOn) {
                $badgeHtml = '<span class="ml-auto inline-flex min-w-[1.35rem] items-center justify-center rounded-md bg-white/20 px-1.5 py-0.5 text-[10px] font-bold text-white">'
                    . htmlspecialchars((string) ($badge > 99 ? '99+' : $badge), ENT_QUOTES, 'UTF-8')
                    . '</span>';
            }
        }

        return '<a href="' . htmlspecialchars($b . $href, ENT_QUOTES, 'UTF-8') . '" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ' . $row . '">'
            . '<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ' . ($isOn ? 'bg-white/15' : 'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-brand') . '">' . $icon . '</span>'
            . '<span class="flex-1">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
            . $badgeHtml
            . '</a>';
    };

    $icoDash = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>';
    $icoBox = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
    $icoTag = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>';
    $icoBlog = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>';
    $icoOrders = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
    $icoStore = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>';

    $processingBadge = null;
    try {
        $processingBadge = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
    } catch (Throwable $e) {
        $processingBadge = null;
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#1d4ed8', dark: '#1e40af', soft: '#eff6ff', muted: '#dbeafe' },
                        ink: { DEFAULT: '#0f172a', soft: '#334155' },
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        serif: ['Merriweather', 'Georgia', 'serif'],
                    },
                    boxShadow: {
                        card: '0 1px 2px rgba(15,23,42,.04), 0 8px 24px rgba(15,23,42,.06)',
                        lift: '0 12px 40px rgba(29,78,216,.18)',
                    },
                },
            },
        };
    </script>
    <style>
        .bb-admin-scroll::-webkit-scrollbar { width: 6px; }
        .bb-admin-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        @keyframes bb-fade-up {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .bb-anim { animation: bb-fade-up .45s ease-out both; }
        .bb-anim-d1 { animation-delay: .05s; }
        .bb-anim-d2 { animation-delay: .1s; }
        .bb-anim-d3 { animation-delay: .15s; }
        .bb-anim-d4 { animation-delay: .2s; }
    </style>
</head>
<body class="min-h-screen bg-[#f3f5f9] font-sans text-ink antialiased">
    <div class="flex min-h-screen">
        <!-- Mobile top bar -->
        <div class="fixed inset-x-0 top-0 z-40 flex items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 py-3 backdrop-blur lg:hidden">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand text-sm font-extrabold text-white shadow-lift">B</span>
                <span class="text-sm font-extrabold tracking-tight">Bookbits Admin</span>
            </div>
            <button type="button" id="bb-admin-menu-btn" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700" aria-label="Open menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        <!-- Sidebar overlay (mobile) -->
        <div id="bb-admin-overlay" class="fixed inset-0 z-40 hidden bg-slate-900/40 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

        <!-- Sidebar -->
        <aside id="bb-admin-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-[17.5rem] -translate-x-full flex-col border-r border-slate-200/80 bg-white transition-transform duration-300 lg:static lg:translate-x-0">
            <div class="flex items-center gap-3 px-5 pb-2 pt-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand to-brand-dark text-base font-extrabold text-white shadow-lift">B</span>
                <div class="min-w-0">
                    <p class="truncate text-[15px] font-extrabold tracking-tight text-ink">Bookbits</p>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Admin console</p>
                </div>
            </div>

            <nav class="bb-admin-scroll mt-6 flex-1 space-y-6 overflow-y-auto px-3 pb-4">
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Menu</p>
                    <div class="space-y-1">
                        <?= $navItem('dashboard', 'Dashboard', '/admin/index.php', $icoDash, $active, $b) ?>
                        <?= $navItem('payments', 'Orders', '/admin/payments.php', $icoOrders, $active, $b, $processingBadge) ?>
                        <?= $navItem('products', 'Products', '/admin/products.php', $icoBox, $active, $b) ?>
                        <?= $navItem('categories', 'Categories', '/admin/categories.php', $icoTag, $active, $b) ?>
                        <?= $navItem('blogs', 'Blog', '/admin/blogs.php', $icoBlog, $active, $b) ?>
                    </div>
                </div>
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">General</p>
                    <div class="space-y-1">
                        <a href="<?= htmlspecialchars($b . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-brand"><?= $icoStore ?></span>
                            <span>View store</span>
                        </a>
                        <a href="<?= htmlspecialchars($b . '/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-rose-50 hover:text-rose-700">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-rose-100 group-hover:text-rose-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </span>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </nav>

            <div class="relative m-3 mt-auto overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-brand-dark to-brand p-4 text-white shadow-lift">
                <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <p class="relative text-sm font-bold leading-snug">Grow your catalog</p>
                <p class="relative mt-1 text-xs leading-relaxed text-blue-100/90">Add books, mark stationery, and keep stock healthy.</p>
                <a href="<?= htmlspecialchars($b . '/admin/product-form.php', ENT_QUOTES, 'UTF-8') ?>" class="relative mt-3 inline-flex items-center rounded-lg bg-white px-3 py-2 text-xs font-bold text-brand transition hover:bg-brand-soft">+ Add product</a>
            </div>

            <div class="border-t border-slate-100 px-4 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-soft text-sm font-extrabold text-brand"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-ink"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="truncate text-[11px] text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col pt-[3.75rem] lg:pt-0">
            <main class="mx-auto w-full max-w-[1400px] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <?php
}

function bb_admin_footer(): void
{
    ?>
            </main>
            <footer class="border-t border-slate-200/70 px-4 py-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                Bookbits Admin · <?= date('Y') ?>
            </footer>
        </div>
    </div>
    <script>
        (function () {
            var btn = document.getElementById('bb-admin-menu-btn');
            var side = document.getElementById('bb-admin-sidebar');
            var overlay = document.getElementById('bb-admin-overlay');
            if (!btn || !side || !overlay) return;
            function open() {
                side.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
            function close() {
                side.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
            btn.addEventListener('click', open);
            overlay.addEventListener('click', close);
            side.querySelectorAll('a').forEach(function (a) {
                a.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 1023px)').matches) close();
                });
            });
        })();
    </script>
</body>
</html>
    <?php
}
