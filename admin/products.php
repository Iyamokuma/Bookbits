<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));

$hasCoverPricing = bb_table_has_column('books', 'has_cover_options')
    && bb_table_has_column('books', 'paperback_price')
    && bb_table_has_column('books', 'hardcover_price');
$hasNewArrival = bb_table_has_column('books', 'is_new_arrival');
$hasBookBundle = bb_table_has_column('books', 'is_book_bundle');

$fromSql = ' FROM books b INNER JOIN categories c ON c.id = b.category_id';
$total = (int) db()->query('SELECT COUNT(*)' . $fromSql)->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$selectSql = $hasCoverPricing
    ? 'SELECT b.id, b.title, b.author, b.price, b.sale_price, b.has_cover_options, b.paperback_price, b.hardcover_price, b.stock_qty, b.is_active, '
      . ($hasNewArrival ? 'b.is_new_arrival' : '0 AS is_new_arrival') . ', '
      . ($hasBookBundle ? 'b.is_book_bundle' : '0 AS is_book_bundle') . ',
       c.name AS cat_name'
    : 'SELECT b.id, b.title, b.author, b.price, b.sale_price, 0 AS has_cover_options, NULL AS paperback_price, NULL AS hardcover_price, b.stock_qty, b.is_active, '
      . ($hasNewArrival ? 'b.is_new_arrival' : '0 AS is_new_arrival') . ', '
      . ($hasBookBundle ? 'b.is_book_bundle' : '0 AS is_book_bundle') . ',
       c.name AS cat_name';

$sql = $selectSql . $fromSql . ' ORDER BY b.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$rows = db()->query($sql)->fetchAll();

$rangeStart = $total > 0 ? $offset + 1 : 0;
$rangeEnd = min($offset + count($rows), $total);

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$flashIsError = !empty($_SESSION['admin_flash_error']);
unset($_SESSION['admin_flash_error']);

$pageTitle = 'Admin — Products';
bb_admin_header($pageTitle, 'products');
?>

        <?php if ($flash !== '') : ?>
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= $flashIsError ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Products</h1>
                <p class="text-sm text-slate-600">All books available in the shop<?= $total > 0 ? ' — ' . $total . ' total' : '' ?>.</p>
            </div>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/product-form.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-800">Add product</a>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Stock</th>
                        <th class="px-4 py-3">Active</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($rows as $r) :
                        $pid = (int) $r['id'];
                        $hasCoverOptions = (int) ($r['has_cover_options'] ?? 0) === 1;
                        $price = $hasCoverOptions
                            ? (float) ($r['paperback_price'] ?? $r['price'])
                            : ($r['sale_price'] !== null ? (float) $r['sale_price'] : (float) $r['price']);
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500"><?= $pid ?></td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <?= htmlspecialchars((string) $r['title'], ENT_QUOTES, 'UTF-8') ?>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <?php if ((int) ($r['is_new_arrival'] ?? 0) === 1) : ?>
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">New arrival</span>
                                    <?php endif; ?>
                                    <?php if ((int) ($r['is_book_bundle'] ?? 0) === 1) : ?>
                                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-purple-700">Stationery</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) $r['cat_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <?php if ($hasCoverOptions) : ?>
                                    <div class="text-xs">
                                        <div class="font-semibold text-slate-800">PB: <?= bb_format_money((float) $r['paperback_price']) ?></div>
                                        <div class="font-semibold text-slate-800">HC: <?= bb_format_money((float) $r['hardcover_price']) ?></div>
                                    </div>
                                <?php else : ?>
                                    <?= bb_format_money($price) ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?= (int) $r['stock_qty'] ?></td>
                            <td class="px-4 py-3"><?= (int) $r['is_active'] ? 'Yes' : 'No' ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/product-form.php?id=' . $pid, ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-brand hover:underline">Edit</a>
                                <form action="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/product-delete.php', ENT_QUOTES, 'UTF-8') ?>" method="post" class="inline" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="id" value="<?= $pid ?>">
                                    <button type="submit" class="ml-3 font-semibold text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($rows) === 0) : ?>
                <p class="px-4 py-10 text-center text-slate-500">No products yet. Add your first book.</p>
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
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/products.php?page=' . ($page - 1), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-brand/30 hover:text-brand">Previous</a>
                            <?php else : ?>
                                <span class="rounded-lg border border-slate-100 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Previous</span>
                            <?php endif; ?>
                            <?php if ($page < $totalPages) : ?>
                                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/products.php?page=' . ($page + 1), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark">Next</a>
                            <?php else : ?>
                                <span class="rounded-lg border border-slate-100 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Next</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

<?php bb_admin_footer(); ?>
