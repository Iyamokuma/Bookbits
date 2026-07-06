<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = $slug !== '' ? bb_fetch_blog_post_by_slug($slug) : null;
if ($post === null) {
    http_response_code(404);
    $pageTitle = 'Post not found — Bookbits';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8 text-center">
        <h1 class="font-serif text-3xl font-bold text-slate-900">Post not found</h1>
        <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/blog.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-4 inline-block text-sm font-semibold text-brand hover:text-brand-dark">Back to blog</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') . ' — Blog';
$cover = bb_blog_cover_url((string) ($post['cover_image'] ?? ''));
require __DIR__ . '/includes/header.php';
?>

<article class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/blog.php', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-brand hover:text-brand-dark">&larr; Back to blog</a>
    <h1 class="mt-3 font-serif text-4xl font-bold text-slate-900"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="mt-2 text-sm text-slate-500"><?= htmlspecialchars(date('F j, Y', strtotime((string) ($post['published_at'] ?? $post['created_at']))), ENT_QUOTES, 'UTF-8') ?></p>

    <div class="mt-6 overflow-hidden rounded-2xl bg-slate-100">
        <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover">
    </div>

    <div class="prose prose-slate mt-8 max-w-none leading-relaxed text-slate-700">
        <?= nl2br(htmlspecialchars((string) $post['body'], ENT_QUOTES, 'UTF-8')) ?>
    </div>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>

