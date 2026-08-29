import { Link, useSearchParams } from 'react-router-dom';
import { Spinner, Alert, StatusBadge, Pagination } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { formatMoney, formatDate } from '../../lib/format';

export default function AdminOrders() {
  const [params, setParams] = useSearchParams();
  const page = params.get('page') || '1';
  const status = params.get('status') || '';

  const query = new URLSearchParams({ page });
  if (status) query.set('status', status);
  const { data, loading, error } = useFetch(`/admin/orders?${query}`);

  const setFilter = (next) => {
    const p = new URLSearchParams();
    if (next) p.set('status', next);
    setParams(p);
  };

  const goToPage = (n) => {
    const p = new URLSearchParams(params);
    p.set('page', String(n));
    setParams(p);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="font-display text-2xl text-slate-900">Orders</h1>
        <p className="mt-1 text-sm text-slate-500">Track and fulfil customer orders.</p>
      </div>

      <div className="flex flex-wrap gap-2">
        <FilterChip active={!status} onClick={() => setFilter('')}>All</FilterChip>
        {(data?.statuses || []).map((s) => (
          <FilterChip key={s} active={status === s} onClick={() => setFilter(s)}>
            {s}
          </FilterChip>
        ))}
      </div>

      {loading ? (
        <Spinner />
      ) : error ? (
        <Alert>{error.message}</Alert>
      ) : (
        <>
          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            {data.orders.length === 0 ? (
              <p className="py-16 text-center text-sm text-slate-500">No orders to show here.</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                      <th className="px-5 py-3 font-semibold">Order</th>
                      <th className="px-5 py-3 font-semibold">Customer</th>
                      <th className="px-5 py-3 font-semibold">Date</th>
                      <th className="px-5 py-3 font-semibold">Payment</th>
                      <th className="px-5 py-3 font-semibold">Status</th>
                      <th className="px-5 py-3 text-right font-semibold">Total</th>
                      <th className="px-5 py-3" />
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {data.orders.map((o) => (
                      <tr key={o.id} className="hover:bg-slate-50">
                        <td className="px-5 py-4 font-semibold text-slate-900">#{o.id}</td>
                        <td className="px-5 py-4">
                          <p className="font-medium text-slate-800">{o.userName}</p>
                          <p className="text-xs text-slate-500">{o.userEmail}</p>
                        </td>
                        <td className="whitespace-nowrap px-5 py-4 text-slate-600">
                          {formatDate(o.createdAt)}
                        </td>
                        <td className="px-5 py-4"><StatusBadge status={o.paymentStatus} /></td>
                        <td className="px-5 py-4"><StatusBadge status={o.status} /></td>
                        <td className="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900">
                          {formatMoney(o.total)}
                        </td>
                        <td className="px-5 py-4 text-right">
                          <Link
                            to={`/admin/orders/${o.id}`}
                            className="whitespace-nowrap text-xs font-semibold text-brand-700 hover:underline"
                          >
                            Manage
                          </Link>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          <Pagination pagination={data.pagination} onPage={goToPage} />
        </>
      )}
    </div>
  );
}

const FilterChip = ({ active, onClick, children }) => (
  <button
    onClick={onClick}
    className={`rounded-full px-4 py-1.5 text-xs font-semibold capitalize transition ${
      active ? 'bg-brand-700 text-white' : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
    }`}
  >
    {children}
  </button>
);
