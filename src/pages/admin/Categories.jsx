import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Spinner, Alert, Pagination, Field, inputClass } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';

export default function AdminCategories() {
  const [params, setParams] = useSearchParams();
  const page = params.get('page') || '1';
  const { data, loading, error, reload } = useFetch(`/admin/categories?page=${page}`);
  const { notify } = useApp();

  const [form, setForm] = useState({ name: '', slug: '', description: '', sortOrder: '0' });
  const [formError, setFormError] = useState(null);
  const [busy, setBusy] = useState(false);

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }));

  const add = async (e) => {
    e.preventDefault();
    setBusy(true);
    setFormError(null);
    try {
      const res = await api.post('/admin/categories', form);
      notify(res.message);
      setForm({ name: '', slug: '', description: '', sortOrder: '0' });
      reload();
    } catch (err) {
      setFormError(err.message);
    } finally {
      setBusy(false);
    }
  };

  const remove = async (category) => {
    if (!window.confirm(`Delete the “${category.name}” category?`)) return;
    try {
      const res = await api.delete(`/admin/categories/${category.id}`);
      notify(res.message);
      reload();
    } catch (err) {
      notify(err.message, 'error');
    }
  };

  const goToPage = (n) => setParams(new URLSearchParams({ page: String(n) }));

  return (
    <div className="space-y-6">
      <div>
        <h1 className="font-display text-2xl text-slate-900">Categories</h1>
        <p className="mt-1 text-sm text-slate-500">
          Organise your catalogue. A category can only be deleted once it has no products.
        </p>
      </div>

      <div className="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <div>
          {loading ? (
            <Spinner />
          ) : error ? (
            <Alert>{error.message}</Alert>
          ) : (
            <>
              <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <table className="w-full text-sm">
                  <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                      <th className="px-5 py-3 font-semibold">Name</th>
                      <th className="px-5 py-3 font-semibold">Address</th>
                      <th className="px-5 py-3 font-semibold">Products</th>
                      <th className="px-5 py-3" />
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {data.categories.map((c) => (
                      <tr key={c.id} className="hover:bg-slate-50">
                        <td className="px-5 py-3.5 font-medium text-slate-900">{c.name}</td>
                        <td className="px-5 py-3.5 font-mono text-xs text-slate-500">/{c.slug}</td>
                        <td className="px-5 py-3.5 text-slate-600">{c.bookCount}</td>
                        <td className="px-5 py-3.5 text-right">
                          <button
                            onClick={() => remove(c)}
                            disabled={c.bookCount > 0}
                            title={c.bookCount > 0 ? 'Move or delete its products first' : undefined}
                            className="text-xs font-semibold text-rose-600 hover:underline disabled:cursor-not-allowed disabled:text-slate-300 disabled:no-underline"
                          >
                            Delete
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <Pagination pagination={data.pagination} onPage={goToPage} />
            </>
          )}
        </div>

        <form onSubmit={add} className="h-fit rounded-2xl border border-slate-200 bg-white p-5">
          <h2 className="mb-4 text-sm font-semibold text-slate-900">Add a category</h2>

          {formError && <div className="mb-4"><Alert>{formError}</Alert></div>}

          <div className="space-y-4">
            <Field label="Name" required>
              <input required className={inputClass} value={form.name} onChange={set('name')} />
            </Field>
            <Field label="Address" hint="Leave blank to generate from the name.">
              <input className={inputClass} value={form.slug} onChange={set('slug')} placeholder="auto" />
            </Field>
            <Field label="Description">
              <input className={inputClass} value={form.description} onChange={set('description')} />
            </Field>
            <Field label="Sort order">
              <input type="number" className={inputClass} value={form.sortOrder} onChange={set('sortOrder')} />
            </Field>
          </div>

          <button
            type="submit"
            disabled={busy}
            className="mt-5 h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
          >
            {busy ? 'Adding…' : 'Add category'}
          </button>
        </form>
      </div>
    </div>
  );
}
