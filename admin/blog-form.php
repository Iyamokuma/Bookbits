<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
bb_require_admin();
require_once __DIR__ . '/_layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$edit = bb_blog_table_exists() && $id > 0 ? db()->prepare('SELECT * FROM blogs WHERE id = ?') : null;
if ($edit) {
    $edit->execute([$id]);
    $post = $edit->fetch();
    if ($post === false) {
        http_response_code(404);
        exit('Post not found');
    }
} else {
    $post = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bb_blog_table_exists()) {
        $_SESSION['admin_flash'] = 'Blog table is missing. Run upgrade statements in database/schema.sql first.';
        $_SESSION['admin_flash_error'] = true;
        header('Location: ' . BOOKBITS_BASE . '/admin/blogs.php');
        exit;
    }
    $title = trim((string) ($_POST['title'] ?? ''));
    $slugRaw = trim((string) ($_POST['slug'] ?? ''));
    $slug = bb_blog_slugify($slugRaw !== '' ? $slugRaw : $title);
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $coverLegacy = trim((string) ($_POST['cover_image'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $publishedAtRaw = trim((string) ($_POST['published_at'] ?? ''));
    $publishedAt = $publishedAtRaw !== '' ? $publishedAtRaw : date('Y-m-d H:i:s');

    $upload = bb_blog_cover_upload();
    $saveErr = null;

    if (!$upload['ok']) {
        $saveErr = $upload['message'];
    } elseif ($title === '' || $body === '') {
        $saveErr = 'Title and body are required.';
    } else {
        $chk = db()->prepare('SELECT id FROM blogs WHERE slug = ? AND id <> ? LIMIT 1');
        $chk->execute([$slug, $id]);
        if ($chk->fetch() !== false) {
            $saveErr = 'Slug is already in use by another post.';
        }
    }

    if ($saveErr === null) {
        $coverDb = null;
        if ($upload['path'] !== null) {
            if ($post !== null) {
                bb_delete_blog_cover_file(isset($post['cover_image']) ? (string) $post['cover_image'] : null);
            }
            $coverDb = $upload['path'];
        } elseif ($coverLegacy !== '') {
            $coverDb = $coverLegacy;
        } elseif ($post !== null && isset($post['cover_image']) && (string) $post['cover_image'] !== '') {
            $coverDb = (string) $post['cover_image'];
        }

        try {
            if ($post !== null) {
                $up = db()->prepare(
                    'UPDATE blogs
                     SET title = ?, slug = ?, excerpt = ?, body = ?, cover_image = ?, is_active = ?, published_at = ?
                     WHERE id = ?'
                );
                $up->execute([$title, $slug, $excerpt !== '' ? $excerpt : null, $body, $coverDb, $isActive, $publishedAt, $id]);
                $_SESSION['admin_flash'] = 'Blog post updated.';
            } else {
                $ins = db()->prepare(
                    'INSERT INTO blogs (title, slug, excerpt, body, cover_image, is_active, published_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([$title, $slug, $excerpt !== '' ? $excerpt : null, $body, $coverDb, $isActive, $publishedAt]);
                $_SESSION['admin_flash'] = 'Blog post created.';
            }
        } catch (Throwable $e) {
            if ($upload['path'] !== null) {
                bb_delete_blog_cover_file($upload['path']);
            }
            $saveErr = 'Could not save blog post.';
        }

        if ($saveErr === null) {
            header('Location: ' . BOOKBITS_BASE . '/admin/blogs.php');
            exit;
        }
    }

    $_SESSION['admin_flash'] = $saveErr;
    $_SESSION['admin_flash_error'] = true;
}

$pageTitle = $post ? 'Edit blog post' : 'Add blog post';
bb_admin_header($post ? 'Admin — Edit blog post' : 'Admin — Add blog post', 'blogs');

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$flashIsError = !empty($_SESSION['admin_flash_error']);
unset($_SESSION['admin_flash_error']);
?>

        <?php if ($flash !== '') : ?>
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= $flashIsError ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900"><?= $post ? 'Edit blog post' : 'Add blog post' ?></h1>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/blogs.php', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-slate-600 hover:text-brand">← Back to blog list</a>
        </div>

        <form method="post" enctype="multipart/form-data" class="mt-8 max-w-4xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Title *</label>
                    <input type="text" name="title" required value="<?= $post ? htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Slug <span class="font-normal text-slate-500">(optional)</span></label>
                    <input type="text" name="slug" value="<?= $post ? htmlspecialchars((string) $post['slug'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="auto-generated from title if empty">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Excerpt</label>
                    <textarea name="excerpt" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"><?= $post && $post['excerpt'] ? htmlspecialchars((string) $post['excerpt'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Body *</label>
                    <textarea name="body" rows="12" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"><?= $post && $post['body'] ? htmlspecialchars((string) $post['body'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Featured image</label>
                    <input type="file" name="blog_cover_upload" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-dark">
                    <?php if ($post && !empty($post['cover_image'])) : ?>
                        <p class="mt-2 text-xs text-slate-600">Current: <code class="rounded bg-slate-100 px-1"><?= htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8') ?></code></p>
                    <?php endif; ?>
                    <p class="mt-3 text-xs font-medium text-slate-600">Or use an external image URL (optional)</p>
                    <input type="text" name="cover_image" value="<?= $post && $post['cover_image'] && !str_starts_with((string) $post['cover_image'], 'uploads/') ? htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="https://...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Published at</label>
                    <?php
                    $publishedAtForInput = $post !== null && !empty($post['published_at'])
                        ? (string) $post['published_at']
                        : date('Y-m-d H:i:s');
                    ?>
                    <input type="datetime-local" name="published_at" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($publishedAtForInput)), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div class="flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" <?= !$post || (int) $post['is_active'] ? ' checked' : '' ?>> Publish this post</label>
                </div>
            </div>
            <button type="submit" class="rounded-xl bg-brand px-6 py-3 text-sm font-bold text-white shadow hover:bg-brand-dark"><?= $post ? 'Save post' : 'Create post' ?></button>
        </form>

<?php bb_admin_footer(); ?>

