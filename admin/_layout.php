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
    $nav = function (string $key, string $label, string $href) use ($active, $b) {
        $on = $active === $key ? 'bg-brand text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';

        return '<a href="' . htmlspecialchars($b . $href, ENT_QUOTES, 'UTF-8') . '" class="rounded-lg px-3 py-2 text-sm font-medium ' . $on . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    };
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: { DEFAULT: '#1d4ed8', dark: '#1e40af', soft: '#eff6ff' } },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        serif: ['Merriweather', 'Georgia', 'serif'],
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-screen bg-[#f4f6fb] font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-800/80 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 text-white shadow-lg">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand text-sm font-black shadow-lg shadow-blue-900/40">B</span>
                <div>
                    <span class="block text-base font-bold tracking-tight">Bookbits Admin</span>
                    <span class="block text-[11px] text-slate-400"><?= htmlspecialchars(bb_current_user()['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
            <nav class="flex flex-wrap items-center gap-1">
                <?= $nav('dashboard', 'Dashboard', '/admin/index.php') ?>
                <?= $nav('products', 'Products', '/admin/products.php') ?>
                <?= $nav('categories', 'Categories', '/admin/categories.php') ?>
                <?= $nav('blogs', 'Blogs', '/admin/blogs.php') ?>
                <?= $nav('payments', 'Orders', '/admin/payments.php') ?>
                <a href="<?= htmlspecialchars($b . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="ml-2 rounded-lg border border-slate-600/80 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800">View store</a>
                <a href="<?= htmlspecialchars($b . '/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-400 transition hover:text-white">Sign out</a>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <?php
}

function bb_admin_footer(): void
{
    ?>
    </main>
</body>
</html>
    <?php
}
