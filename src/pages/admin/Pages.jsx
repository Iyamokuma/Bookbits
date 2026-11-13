import { Link, useSearchParams } from 'react-router-dom';
import { Spinner, Alert, Pagination } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { formatDate } from '../../lib/format';

export default function AdminPages() {
  const [params, setParams] = useSearchParams();
  const page = params.get('page') || '1';
  const { data, loading, error, reload } = useFetch(`/admin/pages?page=${page}`);
  const { notify, setNav } = useApp();

  const remove = async (p) => {
    const warning = p.inNav ? '\n\nIts link will also be removed from the navigation bar.' : '';
    if (!window.confirm(`Delete the page “${p.title}”?${warning}`)) return;
    try {
      const res = await api.delete(`/admin/pages/${p.id}`);
      if (res.nav) setNav(res.nav);
      notify(res.message);
      reload();
    } catch (err) {
      notify(err.message, 'error');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl text-slate-900">Pages</h1>
          <p className="mt-1 text-sm text-slate-500">
            Add pages such as About us, FAQ or Book club, and write what goes on them.
          </p>
        </div>
        <Link
          to="/admin/pages/new"
          className="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
        >
          New page
        </Link>
      </div>

      {loading ? (
        <Spinner />
      ) : error ? (
        <Alert>{error.message}</Alert>
      ) : (
        <>
          <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            {data.pages.length === 0 ? (
              <div className="py-16 text-center">
                <p className="text-sm font-semibold text-slate-900">No pages yet</p>
                <p className="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                  Create your first page, then tick “Show in navigation bar” to link it from the menu.
                </p>
              </div>
            ) : (
              <table className="w-full min-w-[36rem] text-sm">
                <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                  <tr>
                    <th className="px-5 py-3 font-semibold">Page</th>
                    <th className="px-5 py-3 font-semibold">Status</th>
                    <th className="px-5 py-3 font-semibold">In menu</th>
                    <th className="px-5 py-3 font-semibold">Updated</th>
                    <th className="px-5 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {data.pages.map((p) => (
                    <tr key={p.id} className="hover:bg-slate-50">
                      <td className="px-5 py-3.5">
                        <p className="font-medium text-slate-900">{p.title}</p>
                        <p className="font-mono text-xs text-slate-500">/pages/{p.slug}</p>
                      </td>
                      <td className="px-5 py-3.5">
                        <span
                          className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${
                            p.isPublished ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'
                          }`}
                        >
                          {p.isPublished ? 'Live' : 'Draft'}
                        </span>
                      </td>
                      <td className="px-5 py-3.5 text-slate-600">{p.inNav ? 'Yes' : '—'}</td>
                      <td className="px-5 py-3.5 text-slate-600">{formatDate(p.updatedAt)}</td>
                      <td className="whitespace-nowrap px-5 py-3.5 text-right">
                        {p.isPublished && (
                          <a
                            href={`/pages/${p.slug}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-xs font-semibold text-slate-600 hover:underline"
                          >
                            View
                          </a>
                        )}
                        <Link
                          to={`/admin/pages/${p.id}/edit`}
                          className="ml-4 text-xs font-semibold text-brand-700 hover:underline"
                        >
                          Edit
                        </Link>
                        <button
                          onClick={() => remove(p)}
                          className="ml-4 text-xs font-semibold text-rose-600 hover:underline"
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>

          <Pagination
            pagination={data.pagination}
            onPage={(n) => setParams(new URLSearchParams({ page: String(n) }))}
          />
        </>
      )}
    </div>
  );
}
