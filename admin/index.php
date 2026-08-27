<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

$pdo = db();
$adminUser = bb_current_user();
$adminName = (string) ($adminUser['name'] ?? 'Admin');
$firstName = trim(explode(' ', $adminName)[0] ?: 'Admin');

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
    'revenue_prev'    => 0.0,
    'orders_today'    => 0,
    'orders_month'    => 0,
    'orders_prev'     => 0,
    'avg_order'       => 0.0,
    'processing'      => 0,
    'delivered'       => 0,
    'pending_orders'  => 0,
    'shipped'         => 0,
    'low_stock'       => 0,
];

$orderStatusRows = [];
$recentOrders = [];
$topCategories = [];
$lowStockBooks = [];
$weekLabels = [];
$weekOrders = [];
$weekRevenue = [];

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
    $paidCol = $hasPaidAt ? 'paid_at' : 'created_at';

    $stats['revenue_month'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND {$paidCol} >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();
    $stats['revenue_prev'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(amount), 0) FROM payments
         WHERE status = 'completed'
           AND {$paidCol} >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
           AND {$paidCol} < DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();

    $stats['orders_today'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
    $stats['orders_month'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();
    $stats['orders_prev'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders
         WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
           AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();

    $stats['processing'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
    $stats['delivered'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
    $stats['pending_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $stats['shipped'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
    $stats['low_stock'] = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE is_active = 1 AND stock_qty <= 5')->fetchColumn();

    if ($stats['completed_pay'] > 0) {
        $stats['avg_order'] = $stats['total_revenue'] / $stats['completed_pay'];
    }

    $orderStatusRows = $pdo->query(
        "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY FIELD(status,'pending','processing','shipped','delivered','cancelled','refunded'), cnt DESC"
    )->fetchAll();

    $recentOrders = $pdo->query(
        'SELECT o.id, o.status, o.total, o.created_at, u.name AS customer_name, p.status AS pay_status
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         LEFT JOIN payments p ON p.order_id = o.id
         ORDER BY o.created_at DESC
         LIMIT 7'
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

    $lowStockBooks = $pdo->query(
        'SELECT id, title, stock_qty FROM books WHERE is_active = 1 AND stock_qty <= 5 ORDER BY stock_qty ASC, title ASC LIMIT 5'
    )->fetchAll();

    // Last 7 days analytics
    $dayMap = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $dayMap[$d] = ['orders' => 0, 'revenue' => 0.0];
        $weekLabels[] = date('D', strtotime($d));
    }
    $weekRows = $pdo->query(
        "SELECT DATE(o.created_at) AS d, COUNT(*) AS cnt, COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) AS rev
         FROM orders o
         LEFT JOIN payments p ON p.order_id = o.id
         WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(o.created_at)"
    )->fetchAll();
    foreach ($weekRows as $wr) {
        $d = (string) $wr['d'];
        if (isset($dayMap[$d])) {
            $dayMap[$d]['orders'] = (int) $wr['cnt'];
            $dayMap[$d]['revenue'] = (float) $wr['rev'];
        }
    }
    foreach ($dayMap as $vals) {
        $weekOrders[] = $vals['orders'];
        $weekRevenue[] = $vals['revenue'];
    }
} catch (Throwable $e) {
    // Dashboard still renders with zeroed stats if DB is incomplete.
    if ($weekLabels === []) {
        for ($i = 6; $i >= 0; $i--) {
            $weekLabels[] = date('D', strtotime("-{$i} days"));
            $weekOrders[] = 0;
            $weekRevenue[] = 0.0;
        }
    }
}

$revDelta = $stats['revenue_prev'] > 0
    ? (($stats['revenue_month'] - $stats['revenue_prev']) / $stats['revenue_prev']) * 100
    : ($stats['revenue_month'] > 0 ? 100.0 : 0.0);
$ordDelta = $stats['orders_prev'] > 0
    ? (($stats['orders_month'] - $stats['orders_prev']) / $stats['orders_prev']) * 100
    : ($stats['orders_month'] > 0 ? 100.0 : 0.0);

$statusMax = 1;
foreach ($orderStatusRows as $sr) {
    $statusMax = max($statusMax, (int) $sr['cnt']);
}

$totalPipeline = max(1, $stats['orders']);
$endedPct = (int) round(($stats['delivered'] / $totalPipeline) * 100);

$barMax = max(1, ...(count($weekOrders) > 0 ? array_map('intval', $weekOrders) : [1]));
$revBarMax = max(1.0, ...(count($weekRevenue) > 0 ? array_map('floatval', $weekRevenue) : [1.0]));

$statusMeta = [
    'pending'    => ['label' => 'Pending', 'dot' => 'bg-amber-400', 'bar' => 'bg-amber-400', 'pill' => 'bg-amber-50 text-amber-700'],
    'processing' => ['label' => 'Processing', 'dot' => 'bg-sky-500', 'bar' => 'bg-sky-500', 'pill' => 'bg-sky-50 text-sky-700'],
    'shipped'    => ['label' => 'Shipped', 'dot' => 'bg-indigo-400', 'bar' => 'bg-indigo-400', 'pill' => 'bg-indigo-50 text-indigo-700'],
    'delivered'  => ['label' => 'Delivered', 'dot' => 'bg-emerald-500', 'bar' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700'],
    'cancelled'  => ['label' => 'Cancelled', 'dot' => 'bg-slate-400', 'bar' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
    'refunded'   => ['label' => 'Refunded', 'dot' => 'bg-rose-400', 'bar' => 'bg-rose-400', 'pill' => 'bg-rose-50 text-rose-700'],
];

$pageTitle = 'Admin — Dashboard';
bb_admin_header($pageTitle, 'dashboard');
?>

        <!-- Header -->
        <div class="bb-anim flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand">Overview</p>
                <h1 class="mt-1 font-serif text-3xl font-black tracking-tight text-ink sm:text-4xl">Dashboard</h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-500">
                    Welcome back, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?> — track sales, fulfill orders, and keep inventory healthy. <?= date('l, F j') ?>.
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/product-form.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-bold text-white shadow-lift transition hover:bg-brand-dark">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add book
                </a>
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-card transition hover:border-brand/30 hover:text-brand">
                    View orders
                </a>
            </div>
        </div>

        <!-- KPI row -->
        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <!-- Featured: Revenue -->
            <div class="bb-anim bb-anim-d1 relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand via-[#2563eb] to-brand-dark p-5 text-white shadow-lift">
                <div class="pointer-events-none absolute -right-8 -top-10 h-36 w-36 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-12 left-8 h-28 w-28 rounded-full bg-sky-300/20 blur-2xl" aria-hidden="true"></div>
                <div class="relative flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100/90">Total revenue</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight"><?= bb_format_money($stats['total_revenue']) ?></p>
                        <p class="mt-2 inline-flex items-center gap-1 rounded-lg bg-white/15 px-2 py-1 text-[11px] font-semibold backdrop-blur">
                            <?php if ($revDelta >= 0) : ?>
                                <span class="text-emerald-200">↑ <?= number_format(abs($revDelta), 0) ?>%</span>
                            <?php else : ?>
                                <span class="text-rose-200">↓ <?= number_format(abs($revDelta), 0) ?>%</span>
                            <?php endif; ?>
                            <span class="text-blue-100/80">vs last month</span>
                        </p>
                    </div>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="flex h-9 w-9 items-center justify-center rounded-full border border-white/25 bg-white/10 text-white transition hover:bg-white/20" aria-label="Open orders">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/></svg>
                    </a>
                </div>
                <p class="relative mt-4 text-xs text-blue-100/85"><?= bb_format_money($stats['revenue_month']) ?> earned this month</p>
            </div>

            <div class="bb-anim bb-anim-d2 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Orders</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-ink"><?= $stats['orders'] ?></p>
                        <p class="mt-2 inline-flex items-center gap-1 rounded-lg bg-brand-soft px-2 py-1 text-[11px] font-semibold text-brand">
                            <?php if ($ordDelta >= 0) : ?>↑ <?= number_format(abs($ordDelta), 0) ?>%<?php else : ?>↓ <?= number_format(abs($ordDelta), 0) ?>%<?php endif; ?>
                            <span class="font-medium text-slate-500">this month</span>
                        </p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                </div>
                <p class="mt-4 text-xs text-slate-500"><?= $stats['orders_today'] ?> today · <?= $stats['orders_month'] ?> this month</p>
            </div>

            <div class="bb-anim bb-anim-d3 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Avg. paid order</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-ink"><?= bb_format_money($stats['avg_order']) ?></p>
                        <p class="mt-2 inline-flex items-center rounded-lg bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">
                            <?= $stats['completed_pay'] ?> completed
                        </p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </span>
                </div>
                <p class="mt-4 text-xs text-slate-500">Across all successful payments</p>
            </div>

            <div class="bb-anim bb-anim-d4 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Customers</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-ink"><?= $stats['customers'] ?></p>
                        <p class="mt-2 inline-flex items-center rounded-lg bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">
                            <?= $stats['pending_pay'] ?> pending pay
                        </p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                </div>
                <p class="mt-4 text-xs text-slate-500">Unique buyers who placed orders</p>
            </div>
        </div>

        <!-- Middle grid -->
        <div class="mt-6 grid gap-5 xl:grid-cols-12">
            <!-- Analytics bars -->
            <div class="bb-anim bb-anim-d1 rounded-2xl border border-slate-200/70 bg-white p-6 shadow-card xl:col-span-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-extrabold text-ink">Sales analytics</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Orders over the last 7 days</p>
                    </div>
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">7D</span>
                </div>
                <div class="mt-8 flex h-44 items-end justify-between gap-2 sm:gap-3">
                    <?php foreach ($weekOrders as $i => $cnt) :
                        $h = (int) max(8, round(($cnt / $barMax) * 100));
                        $rev = $weekRevenue[$i] ?? 0;
                        $isToday = $i === count($weekOrders) - 1;
                        ?>
                        <div class="group flex flex-1 flex-col items-center gap-2">
                            <div class="relative flex h-36 w-full items-end justify-center">
                                <div class="absolute -top-7 hidden whitespace-nowrap rounded-lg bg-ink px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover:block">
                                    <?= (int) $cnt ?> · <?= bb_format_money((float) $rev) ?>
                                </div>
                                <div class="w-full max-w-[2.25rem] rounded-full <?= $isToday ? 'bg-brand shadow-lg shadow-brand/30' : 'bg-gradient-to-t from-brand/80 to-brand/40' ?>" style="height:<?= $h ?>%"></div>
                            </div>
                            <span class="text-[11px] font-semibold <?= $isToday ? 'text-brand' : 'text-slate-400' ?>"><?= htmlspecialchars($weekLabels[$i] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Attention / fulfill -->
            <div class="bb-anim bb-anim-d2 rounded-2xl border border-slate-200/70 bg-white p-6 shadow-card xl:col-span-3">
                <h2 class="text-base font-extrabold text-ink">Needs attention</h2>
                <p class="mt-0.5 text-xs text-slate-500">What to handle next</p>
                <div class="mt-5 space-y-3">
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-3 transition hover:border-brand/20 hover:bg-brand-soft/50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-ink">Processing</p>
                            <p class="text-xs text-slate-500">Ready to fulfill</p>
                        </div>
                        <span class="text-lg font-extrabold text-sky-600"><?= $stats['processing'] ?></span>
                    </a>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/products.php', ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-3 transition hover:border-amber-200 hover:bg-amber-50/60">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-ink">Low stock</p>
                            <p class="text-xs text-slate-500">≤ 5 units left</p>
                        </div>
                        <span class="text-lg font-extrabold text-amber-600"><?= $stats['low_stock'] ?></span>
                    </a>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-3 transition hover:border-rose-200 hover:bg-rose-50/50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-ink">Pending pay</p>
                            <p class="text-xs text-slate-500">Awaiting payment</p>
                        </div>
                        <span class="text-lg font-extrabold text-rose-600"><?= $stats['pending_pay'] ?></span>
                    </a>
                </div>
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-brand px-4 py-3 text-sm font-bold text-white shadow-lift transition hover:bg-brand-dark">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                    Fulfill orders
                </a>
            </div>

            <!-- Progress gauge -->
            <div class="bb-anim bb-anim-d3 rounded-2xl border border-slate-200/70 bg-white p-6 shadow-card xl:col-span-4">
                <h2 class="text-base font-extrabold text-ink">Fulfillment progress</h2>
                <p class="mt-0.5 text-xs text-slate-500">Share of delivered orders</p>
                <div class="relative mx-auto mt-6 flex h-40 w-full max-w-[240px] items-end justify-center">
                    <?php
                    $pct = max(0, min(100, $endedPct));
                    $deg = (int) round(($pct / 100) * 180);
                    ?>
                    <div class="relative h-[120px] w-[240px] overflow-hidden">
                        <div class="absolute inset-x-0 bottom-0 h-[240px] w-[240px] rounded-full" style="background: conic-gradient(from 180deg, #1d4ed8 0deg, #1d4ed8 <?= $deg ?>deg, #e2e8f0 <?= $deg ?>deg, #e2e8f0 180deg, transparent 180deg);"></div>
                        <div class="absolute bottom-0 left-1/2 h-[168px] w-[168px] -translate-x-1/2 rounded-full bg-white"></div>
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 text-center">
                            <p class="text-3xl font-extrabold tracking-tight text-ink"><?= $pct ?>%</p>
                            <p class="text-[11px] font-semibold text-slate-500">Delivered</p>
                        </div>
                    </div>
                </div>
                <ul class="mt-4 space-y-2.5">
                    <li class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-medium text-slate-700"><span class="h-2.5 w-2.5 rounded-full bg-brand"></span> Delivered</span>
                        <span class="font-bold text-ink"><?= $stats['delivered'] ?></span>
                    </li>
                    <li class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-medium text-slate-700"><span class="h-2.5 w-2.5 rounded-full bg-sky-400"></span> Processing</span>
                        <span class="font-bold text-ink"><?= $stats['processing'] ?></span>
                    </li>
                    <li class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-medium text-slate-700"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span> Pending</span>
                        <span class="font-bold text-ink"><?= $stats['pending_orders'] ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom grid -->
        <div class="mt-5 grid gap-5 lg:grid-cols-12">
            <!-- Recent orders -->
            <div class="bb-anim bb-anim-d2 rounded-2xl border border-slate-200/70 bg-white p-6 shadow-card lg:col-span-7">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-extrabold text-ink">Recent orders</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Latest checkout activity</p>
                    </div>
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/payments.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1 rounded-lg bg-brand-soft px-3 py-1.5 text-xs font-bold text-brand transition hover:bg-brand hover:text-white">View all</a>
                </div>
                <?php if ($recentOrders === []) : ?>
                    <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-12 text-center">
                        <p class="text-sm font-semibold text-slate-600">No orders yet</p>
                        <p class="mt-1 text-xs text-slate-500">Sales will appear here once customers checkout.</p>
                    </div>
                <?php else : ?>
                    <ul class="mt-5 divide-y divide-slate-100">
                        <?php foreach ($recentOrders as $ro) :
                            $st = (string) ($ro['status'] ?? '');
                            $meta = $statusMeta[$st] ?? ['label' => ucfirst($st), 'pill' => 'bg-slate-100 text-slate-600'];
                            $payOk = ($ro['pay_status'] ?? '') === 'completed';
                            $initials = strtoupper(substr((string) ($ro['customer_name'] ?? 'C'), 0, 1));
                            ?>
                            <li class="flex items-center gap-3 py-3.5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-soft to-brand-muted text-sm font-extrabold text-brand"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-bold text-ink"><?= htmlspecialchars((string) ($ro['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <span class="font-mono text-[11px] font-semibold text-slate-400">#<?= (int) $ro['id'] ?></span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500"><?= htmlspecialchars(date('M j, g:ia', strtotime((string) $ro['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="hidden text-right sm:block">
                                    <p class="text-sm font-extrabold text-ink"><?= bb_format_money((float) $ro['total']) ?></p>
                                    <p class="text-[11px] font-semibold <?= $payOk ? 'text-emerald-600' : 'text-amber-600' ?>"><?= $payOk ? 'Paid' : htmlspecialchars((string) ($ro['pay_status'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="rounded-lg px-2.5 py-1 text-[11px] font-bold <?= $meta['pill'] ?>"><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Pipeline + catalog -->
            <div class="space-y-5 lg:col-span-5">
                <div class="bb-anim bb-anim-d3 rounded-2xl border border-slate-200/70 bg-white p-6 shadow-card">
                    <h2 class="text-base font-extrabold text-ink">Order pipeline</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Breakdown by status</p>
                    <ul class="mt-5 space-y-3.5">
                        <?php if ($orderStatusRows === []) : ?>
                            <li class="text-sm text-slate-500">No order data yet.</li>
                        <?php else : ?>
                            <?php foreach ($orderStatusRows as $sr) :
                                $st = (string) $sr['status'];
                                $cnt = (int) $sr['cnt'];
                                $pctBar = (int) round(($cnt / $statusMax) * 100);
                                $meta = $statusMeta[$st] ?? ['label' => ucfirst($st), 'bar' => 'bg-slate-400'];
                                ?>
                                <li>
                                    <div class="mb-1.5 flex items-center justify-between text-xs">
                                        <span class="font-semibold capitalize text-slate-700"><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="font-extrabold text-ink"><?= $cnt ?></span>
                                    </div>
                                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full <?= $meta['bar'] ?> transition-all duration-500" style="width:<?= $pctBar ?>%"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="bb-anim bb-anim-d4 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-brand-dark p-6 text-white shadow-lift">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-extrabold">Catalog health</h2>
                            <p class="mt-0.5 text-xs text-slate-300">Active inventory snapshot</p>
                        </div>
                        <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/products.php', ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg bg-white/10 px-2.5 py-1 text-[11px] font-bold text-white transition hover:bg-white/20">Manage</a>
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-white/10 p-3 backdrop-blur">
                            <p class="text-2xl font-extrabold"><?= $stats['active_books'] ?></p>
                            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-300">Active</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-3 backdrop-blur">
                            <p class="text-2xl font-extrabold"><?= $stats['categories'] ?></p>
                            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-300">Categories</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-3 backdrop-blur">
                            <p class="text-2xl font-extrabold text-amber-300"><?= $stats['low_stock'] ?></p>
                            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-300">Low stock</p>
                        </div>
                    </div>
                    <?php if ($lowStockBooks !== []) : ?>
                        <ul class="mt-4 space-y-2 border-t border-white/10 pt-4">
                            <?php foreach ($lowStockBooks as $lsb) : ?>
                                <li class="flex items-center justify-between gap-2 text-xs">
                                    <span class="truncate text-slate-200"><?= htmlspecialchars((string) $lsb['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="shrink-0 rounded-md bg-amber-400/20 px-2 py-0.5 font-bold text-amber-200"><?= (int) $lsb['stock_qty'] ?> left</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($topCategories !== []) : ?>
                        <ul class="mt-4 space-y-2 border-t border-white/10 pt-4">
                            <?php foreach ($topCategories as $tc) : ?>
                                <li class="flex items-center justify-between gap-2 text-xs">
                                    <span class="truncate text-slate-200"><?= htmlspecialchars((string) $tc['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="shrink-0 font-bold text-blue-200"><?= (int) $tc['book_count'] ?> books</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php bb_admin_footer(); ?>
