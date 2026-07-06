<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$posts = bb_fetch_blog_posts(5);
$pageTitle = 'Blog — Bookbits';
require __DIR__ . '/includes/header.php';
?>

<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="font-serif text-4xl font-bold text-slate-900">Blog</h1>
    <p class="mt-2 text-sm text-slate-600">Latest stories and reading insights from Bookbits.</p>

    <?php if (count($posts) === 0) : ?>
        <div class="mt-10 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center">
            <p class="text-slate-700">No blog posts yet.</p>
        </div>
    <?php else : ?>
        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <?php foreach ($posts as $post) :
                $url = BOOKBITS_BASE . '/blog-post.php?slug=' . rawurlencode((string) $post['slug']);
                $cover = bb_blog_cover_url((string) ($post['cover_image'] ?? ''));
            ?>
                <article class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                    <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="block aspect-[16/9] overflow-hidden bg-slate-100">
                        <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover transition hover:scale-105">
                    </a>
                    <div class="p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime((string) ($post['published_at'] ?? $post['created_at']))), ENT_QUOTES, 'UTF-8') ?></p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">
                            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="hover:text-brand"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></a>
                        </h2>
                        <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars((string) ($post['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="mt-4 inline-block text-sm font-semibold text-brand hover:text-brand-dark">Read full post &rarr;</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

