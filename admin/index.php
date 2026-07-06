<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

$pdo = db();
$adminUser = bb_current_user();
$adminName = (string) ($adminUser['name'] ?? 'Admin');

$stats = [
    'books'           => 0,
    'active_books'    => 0,
    'categories'      => 0,
    'orders'          => 0,
    'customers'       => 0,
    'pending_pay'     => 0,
    'completed_pay'   => 0,
    'total_revenue'   => 0.0,
    'revenue_month'   => 0.0,
    'orders_today'    => 0,
    'orders_month'    => 0,
    'avg_order'       => 0.0,
    'processing'      => 0,
    'delivered'       => 0,
    'low_stock'       => 0,
];

$orderStatusRows = [];
$recentOrders = [];
$topCategories = [];

try {
    $stats['books'] = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
    $stats['active_books'] = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE is_active = 1')->fetchColumn();
    $stats['categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
    $stats['orders'] = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $stats['customers'] = (int) $pdo->query('SELECT COUNT(DISTINCT user_id) FROM orders')->fetchColumn();
    $stats['pending_pay'] = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
    $stats['completed_pay'] = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetchColumn();
    $stats['total_revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

    $hasPaidAt = bb_table_has_column('payments', 'paid_at');
    if ($hasPaidAt) {
        $stats['revenue_month'] = (float) $pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        )->fetchColumn();
    } else {
        $stats['revenue_month'] = (float) $pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        )->fetchColumn();
    }
    $stats['orders_today'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
    $stats['orders_month'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();
    $stats['processing'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
    $stats['delivered'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
    $stats['low_stock'] = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE is_active = 1 AND stock_qty <= 5')->fetchColumn();

    if ($stats['completed_pay'] > 0) {
        $stats['avg_order'] = $stats['total_revenue'] / $stats['completed_pay'];
    }

    $orderStatusRows = $pdo->query(
        "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY cnt DESC"
    )->fetchAll();

    $recentOrders = $pdo->query(
        'SELECT o.id, o.status, o.total, o.created_at, u.name AS customer_name, p.status AS pay_status
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         LEFT JOIN payments p ON p.order_id = o.id
         ORDER BY o.created_at DESC
         LIMIT 6'
    )->fetchAll();

    $topCategories = $pdo->query(
        'SELECT c.name, COUNT(b.id) AS book_count
         FROM categories c
         LEFT JOIN books b ON b.category_id = c.id AND b.is_active = 1
         WHERE c.is_active = 1
         GROUP BY c.id, c.name
         ORDER BY book_count DESC, c.name ASC
         LIMIT 5'
    )->fetchAll();
} catch (Throwable $e) {
    // Dashboard still renders with zeroed stats if DB is incomplete.
}

$statusMax = 1;
foreach ($orderStatusRows as $sr) {
    $statusMax = max($statusMax, (int) $sr['cnt']);
}

$statusColors = [
    'pending'    => 'bg-amber-400',
    'processing' => 'bg-sky-500',
    'shipped'    => 'bg-indigo-400',
    'delivered'  => 'bg-emerald-500',
    'cancelled'  => 'bg-slate-400',
    'refunded'   => 'bg-rose-400',
];

$pageTitle = 'Admin — Dashboard';
bb_admin_header($pageTitle, 'dashboard');
?>

        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 p-6 text-white shadow-xl sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-blue-500/20 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-20 left-1/3 h-40 w-40 rounded-full bg-indigo-400/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200/80">Store overview</p>
                    <h1 class="mt-2 font-serif text-3xl font-black tracking-tight sm:text-4xl">Welcome back, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">Analytics, orders, and inventory at a glance — <?= date('l, F j, Y') ?>.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/product-form.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 shadow-lg transition hover:bg-blue-50">+ Add book</a>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/15">View orders</a>
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total revenue</p>
                        <p class="mt-2 text-2xl font-black text-slate-900"><?= bb_format_money($stats['total_revenue']) ?></p>
                        <p class="mt-1 text-xs text-emerald-600"><?= bb_format_money($stats['revenue_month']) ?> this month</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Orders</p>
                        <p class="mt-2 text-2xl font-black text-slate-900"><?= $stats['orders'] ?></p>
                        <p class="mt-1 text-xs text-slate-500"><?= $stats['orders_today'] ?> today · <?= $stats['orders_month'] ?> this month</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-brand">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Avg. paid order</p>
                        <p class="mt-2 text-2xl font-black text-slate-900"><?= bb_format_money($stats['avg_order']) ?></p>
                        <p class="mt-1 text-xs text-slate-500"><?= $stats['completed_pay'] ?> completed payments</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Customers</p>
                        <p class="mt-2 text-2xl font-black text-slate-900"><?= $stats['customers'] ?></p>
                        <p class="mt-1 text-xs text-amber-600"><?= $stats['pending_pay'] ?> pending payments</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm ring-1 ring-slate-900/5 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-900">Recent orders</h2>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-brand hover:underline">View all</a>
                </div>
                <?php if ($recentOrders === []) : ?>
                    <p class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">No orders yet. Sales will appear here once customers checkout.</p>
                <?php else : ?>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-3 pr-4">Order</th>
                                    <th class="py-3 pr-4">Customer</th>
                                    <th class="py-3 pr-4">Total</th>
                                    <th class="py-3 pr-4">Payment</th>
                                    <th class="py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($recentOrders as $ro) : ?>
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="py-3 pr-4 font-mono text-xs font-semibold text-slate-700">#<?= (int) $ro['id'] ?></td>
                                        <td class="py-3 pr-4 text-slate-800"><?= htmlspecialchars((string) ($ro['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="py-3 pr-4 font-semibold text-slate-900"><?= bb_format_money((float) $ro['total']) ?></td>
                                        <td class="py-3 pr-4">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= ($ro['pay_status'] ?? '') === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                                <?= htmlspecialchars((string) ($ro['pay_status'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="py-3 capitalize text-slate-600"><?= htmlspecialchars((string) $ro['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                    <h2 class="text-lg font-bold text-slate-900">Order pipeline</h2>
                    <p class="mt-1 text-xs text-slate-500">Breakdown by status</p>
                    <ul class="mt-5 space-y-3">
                        <?php if ($orderStatusRows === []) : ?>
                            <li class="text-sm text-slate-500">No order data yet.</li>
                        <?php else : ?>
                            <?php foreach ($orderStatusRows as $sr) :
                                $st = (string) $sr['status'];
                                $cnt = (int) $sr['cnt'];
                                $pct = (int) round(($cnt / $statusMax) * 100);
                                $bar = $statusColors[$st] ?? 'bg-slate-400';
                                ?>
                                <li>
                                    <div class="mb-1 flex items-center justify-between text-xs font-medium">
                                        <span class="capitalize text-slate-700"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="font-bold text-slate-900"><?= $cnt ?></span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full <?= $bar ?>" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                    <h2 class="text-lg font-bold text-slate-900">Top categories</h2>
                    <ul class="mt-4 space-y-3">
                        <?php if ($topCategories === []) : ?>
                            <li class="text-sm text-slate-500">No categories yet.</li>
                        <?php else : ?>
                            <?php foreach ($topCategories as $tc) : ?>
                                <li class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-800"><?= htmlspecialchars((string) $tc['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-600"><?= (int) $tc['book_count'] ?> books</span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/products.php', ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand/20 hover:shadow-md">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Catalog</p>
                <p class="mt-2 text-3xl font-black text-brand"><?= $stats['active_books'] ?></p>
                <p class="mt-1 text-sm text-slate-600"><?= $stats['books'] ?> total · <?= $stats['categories'] ?> categories</p>
                <span class="mt-3 inline-flex text-sm font-semibold text-brand group-hover:underline">Manage products →</span>
            </a>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/products.php', ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Low stock</p>
                <p class="mt-2 text-3xl font-black text-amber-600"><?= $stats['low_stock'] ?></p>
                <p class="mt-1 text-sm text-slate-600">Active books with ≤ 5 left</p>
                <span class="mt-3 inline-flex text-sm font-semibold text-brand group-hover:underline">Review inventory →</span>
            </a>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Processing</p>
                <p class="mt-2 text-3xl font-black text-sky-600"><?= $stats['processing'] ?></p>
                <p class="mt-1 text-sm text-slate-600"><?= $stats['delivered'] ?> delivered so far</p>
                <span class="mt-3 inline-flex text-sm font-semibold text-brand group-hover:underline">Fulfill orders →</span>
            </a>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/categories.php', ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Quick action</p>
                <p class="mt-2 text-lg font-black text-slate-900">Organize</p>
                <p class="mt-1 text-sm text-slate-600">Add or remove shop categories</p>
                <span class="mt-3 inline-flex text-sm font-semibold text-brand group-hover:underline">Categories →</span>
            </a>
        </div>

<?php bb_admin_footer(); ?>
