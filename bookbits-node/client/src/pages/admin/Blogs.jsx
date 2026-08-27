import { Link, useSearchParams } from 'react-router-dom';
import { Spinner, Alert, Pagination } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { formatDate } from '../../lib/format';

export default function AdminBlogs() {
  const [params, setParams] = useSearchParams();
  const page = params.get('page') || '1';
  const { data, loading, error, reload } = useFetch(`/admin/blogs?page=${page}`);
  const { notify } = useApp();

  const remove = async (post) => {
    if (!window.confirm(`Delete “${post.title}”?`)) return;
    try {
      const res = await api.delete(`/admin/blogs/${post.id}`);
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
          <h1 className="font-display text-2xl text-slate-900">Blog</h1>
          <p className="mt-1 text-sm text-slate-500">Write and publish articles for your readers.</p>
        </div>
        <Link
          to="/admin/blogs/new"
          className="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
        >
          New post
        </Link>
      </div>

      {loading ? (
        <Spinner />
      ) : error ? (
        <Alert>{error.message}</Alert>
      ) : (
        <>
          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            {data.posts.length === 0 ? (
              <p className="py-16 text-center text-sm text-slate-500">
                No posts yet. Write your first one above.
              </p>
            ) : (
              <table className="w-full text-sm">
                <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                  <tr>
                    <th className="px-5 py-3 font-semibold">Title</th>
                    <th className="px-5 py-3 font-semibold">Status</th>
                    <th className="px-5 py-3 font-semibold">Published</th>
                    <th className="px-5 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {data.posts.map((p) => (
                    <tr key={p.id} className="hover:bg-slate-50">
                      <td className="px-5 py-3.5">
                        <p className="font-medium text-slate-900">{p.title}</p>
                        <p className="font-mono text-xs text-slate-500">/{p.slug}</p>
                      </td>
                      <td className="px-5 py-3.5">
                        <span
                          className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${
                            p.is_active && p.published_at
                              ? 'bg-emerald-100 text-emerald-700'
                              : 'bg-slate-200 text-slate-600'
                          }`}
                        >
                          {p.is_active && p.published_at ? 'Live' : 'Draft'}
                        </span>
                      </td>
                      <td className="px-5 py-3.5 text-slate-600">
                        {p.published_at ? formatDate(p.published_at) : '—'}
                      </td>
                      <td className="whitespace-nowrap px-5 py-3.5 text-right">
                        <Link
                          to={`/admin/blogs/${p.id}/edit`}
                          className="text-xs font-semibold text-brand-700 hover:underline"
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
