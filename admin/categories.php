<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
bb_require_admin();
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    if ($name !== '' && $slug !== '') {
        $slug = strtolower(preg_replace('/[^a-z0-9\-]+/i', '-', $slug));
        $ins = db()->prepare(
            'INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, 99)'
        );
        try {
            $ins->execute([$name, $slug, $desc === '' ? null : $desc]);
            $_SESSION['admin_flash'] = 'Category added.';
        } catch (Throwable $e) {
            $_SESSION['admin_flash'] = 'Could not add (duplicate slug?).';
            $_SESSION['admin_flash_error'] = true;
        }
    }
    header('Location: ' . BOOKBITS_BASE . '/admin/categories.php');
    exit;
}

$rows = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM books b WHERE b.category_id = c.id) AS book_count
     FROM categories c
     ORDER BY c.sort_order, c.name'
)->fetchAll();

$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total = count($rows);
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$pageRows = array_slice($rows, $offset, $perPage);
$rangeStart = $total > 0 ? $offset + 1 : 0;
$rangeEnd = min($offset + count($pageRows), $total);

$pageTitle = 'Admin — Categories';
bb_admin_header($pageTitle, 'categories');
?>

        <h1 class="text-2xl font-bold text-slate-900">Categories</h1>
        <p class="text-sm text-slate-600">Add or remove shop categories. Slugs appear in URLs (e.g. <code class="rounded bg-slate-200 px-1">shop.php?cat=productivity</code>)<?= $total > 0 ? ' — ' . $total . ' total' : '' ?>.</p>

        <?php
        $f = $_SESSION['admin_flash'] ?? '';
        unset($_SESSION['admin_flash']);
        $fErr = !empty($_SESSION['admin_flash_error']);
        unset($_SESSION['admin_flash_error']);
        ?>
        <?php if ($f !== '') : ?>
            <div class="mt-4 rounded-xl border px-4 py-3 text-sm <?= $fErr ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= htmlspecialchars($f, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="mt-8 max-w-xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-900">Add category</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700">Name</label>
                <input type="text" name="name" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="e.g. Self help">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Slug (URL)</label>
                <input type="text" name="slug" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="e.g. self-help">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Description</label>
                <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark">Add category</button>
        </form>

        <div class="mt-10 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Books</th>
                        <th class="px-4 py-3">Active</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($pageRows as $r) :
                        $cid = (int) $r['id'];
                        $bookCount = (int) ($r['book_count'] ?? 0);
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs"><?= $cid ?></td>
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars((string) $r['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) $r['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= $bookCount ?></td>
                            <td class="px-4 py-3"><?= (int) $r['is_active'] ? 'Yes' : 'No' ?></td>
                            <td class="px-4 py-3 text-right">
                                <?php if ($bookCount === 0) : ?>
                                    <form action="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/category-delete.php', ENT_QUOTES, 'UTF-8') ?>" method="post" class="inline" onsubmit="return confirm('Delete category “<?= htmlspecialchars((string) $r['name'], ENT_QUOTES, 'UTF-8') ?>”?');">
                                        <input type="hidden" name="id" value="<?= $cid ?>">
                                        <button type="submit" class="font-semibold text-red-600 hover:underline">Delete</button>
                                    </form>
                                <?php else : ?>
                                    <span class="text-xs text-slate-400" title="Remove or reassign books first">In use</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($pageRows) === 0) : ?>
                <p class="px-4 py-10 text-center text-slate-500">No categories yet.</p>
            <?php endif; ?>
            <?php if ($total > 0) : ?>
                <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-600">
                        Showing <?= $rangeStart ?>–<?= $rangeEnd ?> of <?= $total ?>
                        <?php if ($totalPages > 1) : ?>
                            <span class="text-slate-400">· Page <?= $page ?> of <?= $totalPages ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if ($totalPages > 1) : ?>
                        <div class="flex items-center gap-2">
                            <?php if ($page > 1) : ?>
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/categories.php?page=' . ($page - 1), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-brand/30 hover:text-brand">Previous</a>
                            <?php else : ?>
                                <span class="rounded-lg border border-slate-100 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Previous</span>
                            <?php endif; ?>
                            <?php if ($page < $totalPages) : ?>
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/categories.php?page=' . ($page + 1), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark">Next</a>
                            <?php else : ?>
                                <span class="rounded-lg border border-slate-100 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Next</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

<?php bb_admin_footer(); ?>
