import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Spinner, Alert, Field, inputClass, textareaClass } from '../../components/ui';
import RichTextEditor from '../../components/RichTextEditor';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';

const BLANK = {
  title: '',
  slug: '',
  body: '',
  metaDescription: '',
  coverImage: '',
  isPublished: true,
  showInNav: false,
};

const slugify = (s) =>
  String(s).toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

// DOMParser documents are inert: unlike a detached <div>, they don't start
// loading every image in the content on each keystroke.
const textOf = (html) =>
  (
    new DOMParser().parseFromString(
      // Block boundaries become spaces, or list items run together.
      String(html || '').replace(/<(br|\/p|\/div|\/h\d|\/li|\/blockquote)\b[^>]*>/gi, ' $&'),
      'text/html'
    ).body.textContent || ''
  )
    .replace(/\s+/g, ' ')
    .trim();

export default function PageForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { notify, setNav } = useApp();

  const [form, setForm] = useState(BLANK);
  const [loading, setLoading] = useState(isEdit);
  const [loadError, setLoadError] = useState(null);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const [uploading, setUploading] = useState(false);
  const fileRef = useRef(null);

  useEffect(() => {
    if (!isEdit) return;
    let active = true;
    api
      .get(`/admin/pages/${id}`)
      .then((p) => {
        if (!active) return;
        setForm({
          title: p.title,
          slug: p.slug,
          body: p.body || '',
          metaDescription: p.metaDescription || '',
          coverImage: p.coverImage || '',
          isPublished: p.isPublished,
          showInNav: p.inNav,
        });
      })
      .catch((err) => active && setLoadError(err.message))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [id, isEdit]);

  const set = (key) => (e) =>
    setForm((f) => ({ ...f, [key]: e.target.type === 'checkbox' ? e.target.checked : e.target.value }));

  const uploadCover = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    setUploading(true);
    try {
      const fd = new FormData();
      fd.append('image', file);
      const { url } = await api.upload('/admin/uploads', fd);
      setForm((f) => ({ ...f, coverImage: url }));
    } catch (err) {
      notify(err.message, 'error');
    } finally {
      setUploading(false);
    }
  };

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      const res = await api.post(isEdit ? `/admin/pages/${id}` : '/admin/pages', form);
      if (res.nav) setNav(res.nav);
      notify(res.message);
      navigate('/admin/pages');
    } catch (err) {
      setError(err.message);
      setBusy(false);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  if (loading) return <Spinner />;
  if (loadError) return <Alert>{loadError}</Alert>;

  const slug = form.slug ? slugify(form.slug) : slugify(form.title);
  const previewDescription = form.metaDescription.trim() || textOf(form.body).slice(0, 160);

  return (
    <div className="space-y-6">
      <Link to="/admin/pages" className="text-sm font-medium text-brand-700 hover:underline">
        ← All pages
      </Link>

      <div className="flex flex-wrap items-baseline justify-between gap-3">
        <h1 className="font-display text-2xl text-slate-900">{isEdit ? 'Edit page' : 'New page'}</h1>
        {isEdit && form.isPublished && (
          <a
            href={`/pages/${slug}`}
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm font-semibold text-brand-700 hover:underline"
          >
            View live page ↗
          </a>
        )}
      </div>

      {error && <Alert>{error}</Alert>}

      <form onSubmit={submit} className="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <section className="min-w-0 space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
          <Field label="Title" required>
            <input required className={inputClass} value={form.title} onChange={set('title')}
                   placeholder="e.g. About us" />
          </Field>

          <Field
            label="Address"
            hint={slug ? `The page will live at /pages/${slug}` : 'Generated from the title if left blank.'}
          >
            <div className="mt-1.5 flex h-11 overflow-hidden rounded-lg border border-slate-300 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-100">
              <span className="flex items-center border-r border-slate-200 bg-slate-50 px-3 font-mono text-xs text-slate-500">
                /pages/
              </span>
              <input className="min-w-0 flex-1 border-0 px-3 text-sm focus:outline-none focus:ring-0"
                     value={form.slug} onChange={set('slug')} placeholder={slugify(form.title) || 'about-us'} />
            </div>
          </Field>

          <Field label="Content">
            <RichTextEditor
              value={form.body}
              onChange={(body) => setForm((f) => ({ ...f, body }))}
              placeholder="Write the page here. Use the toolbar for headings, lists, links and images."
            />
          </Field>
        </section>

        <aside className="space-y-5">
          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Publishing</h2>
            <div className="space-y-3">
              <label className="flex items-start gap-2.5 text-sm text-slate-700">
                <input type="checkbox" checked={form.isPublished} onChange={set('isPublished')}
                       className="mt-0.5 h-4 w-4 accent-brand-700" />
                <span>
                  Published
                  <span className="block text-[11px] text-slate-400">Untick to keep it as a draft.</span>
                </span>
              </label>
              <label className="flex items-start gap-2.5 text-sm text-slate-700">
                <input type="checkbox" checked={form.showInNav} onChange={set('showInNav')}
                       className="mt-0.5 h-4 w-4 accent-brand-700" />
                <span>
                  Show in navigation bar
                  <span className="block text-[11px] text-slate-400">
                    Adds a link at the end of the menu. Rename or reorder it under{' '}
                    <Link to="/admin/navigation" className="underline">Navigation</Link>.
                  </span>
                </span>
              </label>
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-1 text-sm font-semibold text-slate-900">Banner image</h2>
            <p className="mb-3 text-[11px] text-slate-400">
              Optional. Shown at the top of the page and in link previews.
            </p>
            {form.coverImage ? (
              <div className="space-y-2">
                <img src={form.coverImage} alt="" className="aspect-video w-full rounded-lg object-cover" />
                <button type="button" onClick={() => setForm((f) => ({ ...f, coverImage: '' }))}
                        className="text-xs font-semibold text-rose-600 hover:underline">
                  Remove image
                </button>
              </div>
            ) : (
              <button type="button" onClick={() => fileRef.current?.click()} disabled={uploading}
                      className="flex h-24 w-full items-center justify-center rounded-lg border-2 border-dashed border-slate-300 text-sm font-medium text-slate-500 hover:border-brand-400 hover:text-brand-700 disabled:opacity-60">
                {uploading ? 'Uploading…' : 'Upload an image'}
              </button>
            )}
            <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp,image/gif"
                   className="hidden" onChange={uploadCover} />
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Search &amp; sharing</h2>
            <Field
              label="Description"
              hint={`${form.metaDescription.length}/160 — shown on Google and when the link is shared. Taken from the content if left blank.`}
            >
              <textarea rows={3} maxLength={300} className={textareaClass}
                        value={form.metaDescription} onChange={set('metaDescription')} />
            </Field>

            <p className="mb-1.5 mt-4 text-[11px] font-medium uppercase tracking-wide text-slate-400">
              Link preview
            </p>
            <div className="overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
              {form.coverImage && (
                <img src={form.coverImage} alt="" className="aspect-[1.91/1] w-full object-cover" />
              )}
              <div className="p-3">
                <p className="truncate text-[11px] uppercase text-slate-400">{window.location.host}</p>
                <p className="mt-0.5 line-clamp-2 text-sm font-semibold text-slate-900">
                  {form.title || 'Page title'}
                </p>
                <p className="mt-0.5 line-clamp-2 text-xs text-slate-500">
                  {previewDescription || 'A short description of the page.'}
                </p>
              </div>
            </div>
          </section>

          <button
            type="submit"
            disabled={busy || uploading}
            className="h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
          >
            {busy ? 'Saving…' : isEdit ? 'Save changes' : 'Create page'}
          </button>
        </aside>
      </form>
    </div>
  );
}
