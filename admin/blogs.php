<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/_layout.php';

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$flashIsError = !empty($_SESSION['admin_flash_error']);
unset($_SESSION['admin_flash_error']);

$rows = [];
if (bb_blog_table_exists()) {
    $rows = db()->query(
        'SELECT id, title, slug, is_active, published_at, created_at
         FROM blogs
         ORDER BY COALESCE(published_at, created_at) DESC, id DESC'
    )->fetchAll();
}

$pageTitle = 'Admin — Blogs';
bb_admin_header($pageTitle, 'blogs');
?>

        <?php if ($flash !== '') : ?>
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= $flashIsError ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Blogs</h1>
                <p class="text-sm text-slate-600">Manage published stories on the blog page.</p>
            </div>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/blog-form.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-800">Add post</a>
        </div>

        <?php if (!bb_blog_table_exists()) : ?>
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                Blog table not found in database. Run the upgrade statements in <code class="rounded bg-white px-1 py-0.5 text-xs">database/schema.sql</code> to add <code class="rounded bg-white px-1 py-0.5 text-xs">blogs</code>.
            </div>
        <?php endif; ?>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Published</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($rows as $r) :
                        $id = (int) $r['id'];
                        $isActive = (int) $r['is_active'] === 1;
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars((string) $r['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) $r['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= $isActive ? 'Published' : 'Draft' ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) ($r['published_at'] ?? $r['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/blog-form.php?id=' . $id, ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-brand hover:underline">Edit</a>
                                <form action="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/blog-delete.php', ENT_QUOTES, 'UTF-8') ?>" method="post" class="inline" onsubmit="return confirm('Delete this post?');">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="ml-3 font-semibold text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($rows) === 0) : ?>
                <p class="px-4 py-10 text-center text-slate-500">No blog posts yet.</p>
            <?php endif; ?>
        </div>

<?php bb_admin_footer(); ?>

