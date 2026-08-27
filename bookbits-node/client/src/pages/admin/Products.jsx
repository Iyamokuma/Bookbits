import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Spinner, Alert, Pagination } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { formatMoney } from '../../lib/format';

export default function AdminProducts() {
  const [params, setParams] = useSearchParams();
  const page = params.get('page') || '1';
  const q = params.get('q') || '';

  const [term, setTerm] = useState(q);
  const { data, loading, error, reload } = useFetch(
    `/admin/products?page=${page}&q=${encodeURIComponent(q)}`
  );
  const { notify } = useApp();
  const [deletingId, setDeletingId] = useState(null);

  const search = (e) => {
    e.preventDefault();
    const p = new URLSearchParams();
    if (term.trim()) p.set('q', term.trim());
    setParams(p);
  };

  const goToPage = (n) => {
    const p = new URLSearchParams(params);
    p.set('page', String(n));
    setParams(p);
  };

  const remove = async (book) => {
    if (!window.confirm(`Delete “${book.title}”? This cannot be undone.`)) return;
    setDeletingId(book.id);
    try {
      const res = await api.delete(`/admin/products/${book.id}`);
      notify(res.message);
      reload();
    } catch (err) {
      notify(err.message, 'error');
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl text-slate-900">Products</h1>
          <p className="mt-1 text-sm text-slate-500">Manage your catalogue and stock levels.</p>
        </div>
        <Link
          to="/admin/products/new"
          className="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
        >
          Add product
        </Link>
      </div>

      <form onSubmit={search} className="flex max-w-sm gap-2">
        <input
          type="search"
          value={term}
          onChange={(e) => setTerm(e.target.value)}
          placeholder="Search by title or author…"
          className="h-10 flex-1 rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:outline-none"
        />
        <button className="rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Search
        </button>
      </form>

      {loading ? (
        <Spinner />
      ) : error ? (
        <Alert>{error.message}</Alert>
      ) : (
        <>
          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            {data.books.length === 0 ? (
              <p className="py-16 text-center text-sm text-slate-500">
                {q ? `Nothing matched “${q}”.` : 'No products yet. Add your first one above.'}
              </p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                      <th className="px-5 py-3 font-semibold">Product</th>
                      <th className="px-5 py-3 font-semibold">Category</th>
                      <th className="px-5 py-3 font-semibold">Price</th>
                      <th className="px-5 py-3 font-semibold">Stock</th>
                      <th className="px-5 py-3 font-semibold">Visible</th>
                      <th className="px-5 py-3" />
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {data.books.map((b) => (
                      <tr key={b.id} className="hover:bg-slate-50">
                        <td className="px-5 py-3.5">
                          <div className="flex items-center gap-3">
                            <img src={b.coverUrl} alt="" className="h-12 w-9 shrink-0 rounded object-cover" />
                            <div className="min-w-0">
                              <p className="truncate font-medium text-slate-900">{b.title}</p>
                              <p className="truncate text-xs text-slate-500">by {b.author}</p>
                            </div>
                          </div>
                        </td>
                        <td className="whitespace-nowrap px-5 py-3.5 text-slate-600">{b.categoryName}</td>
                        <td className="whitespace-nowrap px-5 py-3.5 font-medium text-slate-900">
                          {formatMoney(b.salePrice ?? b.price)}
                        </td>
                        <td className="px-5 py-3.5">
                          <span
                            className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${
                              b.stockQty === 0
                                ? 'bg-rose-100 text-rose-700'
                                : b.stockQty <= 5
                                  ? 'bg-amber-100 text-amber-800'
                                  : 'bg-emerald-100 text-emerald-700'
                            }`}
                          >
                            {b.stockQty}
                          </span>
                        </td>
                        <td className="px-5 py-3.5 text-slate-600">{b.isActive ? 'Yes' : 'Hidden'}</td>
                        <td className="whitespace-nowrap px-5 py-3.5 text-right">
                          <Link
                            to={`/admin/products/${b.id}/edit`}
                            className="text-xs font-semibold text-brand-700 hover:underline"
                          >
                            Edit
                          </Link>
                          <button
                            onClick={() => remove(b)}
                            disabled={deletingId === b.id}
                            className="ml-4 text-xs font-semibold text-rose-600 hover:underline disabled:opacity-40"
                          >
                            Delete
                          </button>
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
