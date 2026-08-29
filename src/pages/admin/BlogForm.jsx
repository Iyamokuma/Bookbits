import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Spinner, Alert, Field, inputClass, textareaClass } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';

export default function BlogForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { notify } = useApp();

  const { data: existing, loading } = useFetch(isEdit ? `/admin/blogs/${id}` : '/session');

  const [form, setForm] = useState({
    title: '', slug: '', excerpt: '', body: '', coverImage: '', isActive: true, published: true,
  });
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!isEdit || !existing?.id) return;
    setForm({
      title: existing.title || '',
      slug: existing.slug || '',
      excerpt: existing.excerpt || '',
      body: existing.body || '',
      coverImage: existing.cover_image || '',
      isActive: existing.is_active,
      published: Boolean(existing.published_at),
    });
  }, [isEdit, existing]);

  const set = (key) => (e) =>
    setForm((f) => ({
      ...f,
      [key]: e.target.type === 'checkbox' ? e.target.checked : e.target.value,
    }));

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      const res = await api.post(isEdit ? `/admin/blogs/${id}` : '/admin/blogs', form);
      notify(res.message);
      navigate('/admin/blogs');
    } catch (err) {
      setError(err.message);
      setBusy(false);
    }
  };

  if (isEdit && loading) return <Spinner />;

  return (
    <div className="space-y-6">
      <Link to="/admin/blogs" className="text-sm font-medium text-brand-700 hover:underline">
        ← All posts
      </Link>

      <h1 className="font-display text-2xl text-slate-900">
        {isEdit ? 'Edit post' : 'New post'}
      </h1>

      {error && <Alert>{error}</Alert>}

      <form onSubmit={submit} className="grid gap-5 lg:grid-cols-[1fr_18rem]">
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
          <Field label="Title" required>
            <input required className={inputClass} value={form.title} onChange={set('title')} />
          </Field>

          <Field label="Address" hint="Leave blank to generate from the title.">
            <input className={inputClass} value={form.slug} onChange={set('slug')} placeholder="auto" />
          </Field>

          <Field label="Excerpt" hint="A short summary shown in listings.">
            <textarea rows={2} className={textareaClass} value={form.excerpt} onChange={set('excerpt')} />
          </Field>

          <Field label="Body" required>
            <textarea rows={16} required className={textareaClass} value={form.body} onChange={set('body')} />
          </Field>
        </section>

        <aside className="space-y-5">
          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Publishing</h2>
            <div className="space-y-2.5">
              <label className="flex items-center gap-2.5 text-sm text-slate-700">
                <input type="checkbox" checked={form.published} onChange={set('published')} className="h-4 w-4 accent-brand-700" />
                Published
              </label>
              <label className="flex items-center gap-2.5 text-sm text-slate-700">
                <input type="checkbox" checked={form.isActive} onChange={set('isActive')} className="h-4 w-4 accent-brand-700" />
                Visible on the site
              </label>
            </div>
            <p className="mt-3 text-[11px] leading-relaxed text-slate-400">
              A post appears on the blog only when both boxes are ticked.
            </p>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <Field label="Cover image URL">
              <input className={inputClass} value={form.coverImage} onChange={set('coverImage')} />
            </Field>
          </section>

          <button
            type="submit"
            disabled={busy}
            className="h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
          >
            {busy ? 'Saving…' : isEdit ? 'Save changes' : 'Create post'}
          </button>
        </aside>
      </form>
    </div>
  );
}
