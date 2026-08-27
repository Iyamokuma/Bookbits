import { Link } from 'react-router-dom';
import { Spinner, Alert, StatusBadge } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { formatMoney, formatDate } from '../../lib/format';

function StatCard({ label, value, sub, tone = 'default' }) {
  const tones = {
    default: 'text-slate-900',
    up: 'text-emerald-600',
    down: 'text-rose-600',
  };
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5">
      <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</p>
      <p className="mt-2 text-2xl font-bold text-slate-900">{value}</p>
      {sub && <p className={`mt-1 text-xs ${tones[tone]}`}>{sub}</p>}
    </div>
  );
}

export default function Dashboard() {
  const { data, loading, error } = useFetch('/admin/dashboard');

  if (loading) return <Spinner />;
  if (error) return <Alert>{error.message}</Alert>;

  const { stats, recentOrders, pipeline, topCategories, lowStock, salesByDay } = data;

  const growth =
    stats.lastMonth > 0
      ? ((stats.thisMonth - stats.lastMonth) / stats.lastMonth) * 100
      : stats.thisMonth > 0
        ? 100
        : 0;

  const peak = Math.max(...salesByDay.map((d) => d.revenue), 1);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="font-display text-2xl text-slate-900">Overview</h1>
        <p className="mt-1 text-sm text-slate-500">How the store is performing right now.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Revenue"
          value={formatMoney(stats.revenue)}
          sub={`${formatMoney(stats.thisMonth)} this month`}
        />
        <StatCard
          label="This month"
          value={formatMoney(stats.thisMonth)}
          sub={`${growth >= 0 ? '↑' : '↓'} ${Math.abs(growth).toFixed(0)}% vs last month`}
          tone={growth >= 0 ? 'up' : 'down'}
        />
        <StatCard
          label="Paid orders"
          value={stats.paidOrders}
          sub={`${formatMoney(stats.avgOrder)} average`}
        />
        <StatCard label="Customers" value={stats.customers} sub={`${stats.books} titles listed`} />
      </div>

      <div className="grid gap-5 lg:grid-cols-3">
        <section className="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
          <h2 className="text-sm font-semibold text-slate-900">Last 7 days</h2>
          <div className="mt-6 flex h-44 items-end gap-2">
            {salesByDay.map((d, i) => (
              <div key={i} className="flex flex-1 flex-col items-center gap-2">
                <div className="relative flex w-full flex-1 items-end">
                  <div
                    className="w-full rounded-t-md bg-brand-600 transition-all hover:bg-brand-700"
                    style={{ height: `${Math.max((d.revenue / peak) * 100, 2)}%` }}
                    title={formatMoney(d.revenue)}
                  />
                </div>
                <span className="text-[11px] text-slate-500">{d.label}</span>
              </div>
            ))}
          </div>
        </section>

        <section className="rounded-2xl border border-slate-200 bg-white p-5">
          <h2 className="text-sm font-semibold text-slate-900">Order pipeline</h2>
          <ul className="mt-4 space-y-2.5">
            {['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'].map((s) => (
              <li key={s} className="flex items-center justify-between">
                <StatusBadge status={s} />
                <span className="text-sm font-semibold text-slate-900">{pipeline[s] || 0}</span>
              </li>
            ))}
          </ul>
        </section>
      </div>

      <div className="grid gap-5 lg:grid-cols-3">
        <section className="rounded-2xl border border-slate-200 bg-white lg:col-span-2">
          <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 className="text-sm font-semibold text-slate-900">Recent orders</h2>
            <Link to="/admin/orders" className="text-xs font-semibold text-brand-700 hover:underline">
              View all →
            </Link>
          </div>

          {recentOrders.length === 0 ? (
            <p className="px-5 py-10 text-center text-sm text-slate-500">No orders yet.</p>
          ) : (
            <table className="w-full text-sm">
              <tbody className="divide-y divide-slate-100">
                {recentOrders.map((o) => (
                  <tr key={o.id} className="hover:bg-slate-50">
                    <td className="px-5 py-3.5">
                      <Link to={`/admin/orders/${o.id}`} className="font-semibold text-brand-700 hover:underline">
                        #{o.id}
                      </Link>
                    </td>
                    <td className="px-5 py-3.5 text-slate-700">{o.userName}</td>
                    <td className="hidden px-5 py-3.5 text-slate-500 sm:table-cell">
                      {formatDate(o.createdAt)}
                    </td>
                    <td className="px-5 py-3.5"><StatusBadge status={o.status} /></td>
                    <td className="px-5 py-3.5 text-right font-semibold text-slate-900">
                      {formatMoney(o.total)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>

        <div className="space-y-5">
          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="text-sm font-semibold text-slate-900">Running low</h2>
            {lowStock.length === 0 ? (
              <p className="mt-4 text-sm text-slate-500">Everything is well stocked.</p>
            ) : (
              <ul className="mt-4 space-y-2.5">
                {lowStock.map((b) => (
                  <li key={b.id} className="flex items-center justify-between gap-3">
                    <Link
                      to={`/admin/products/${b.id}/edit`}
                      className="line-clamp-1 text-sm text-slate-700 hover:text-brand-700"
                    >
                      {b.title}
                    </Link>
                    <span
                      className={`shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold ${
                        b.stock_qty === 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800'
                      }`}
                    >
                      {b.stock_qty} left
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="text-sm font-semibold text-slate-900">Best selling categories</h2>
            {topCategories.length === 0 ? (
              <p className="mt-4 text-sm text-slate-500">No sales data yet.</p>
            ) : (
              <ul className="mt-4 space-y-2.5">
                {topCategories.map((c) => (
                  <li key={c.name} className="flex items-center justify-between text-sm">
                    <span className="text-slate-700">{c.name}</span>
                    <span className="font-semibold text-slate-900">{c.sold}</span>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </div>
      </div>
    </div>
  );
}
