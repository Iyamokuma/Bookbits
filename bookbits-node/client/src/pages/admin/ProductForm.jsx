import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Spinner, Alert, Field, inputClass, textareaClass } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';

const BLANK = {
  title: '', author: '', categoryId: '', isbn: '', description: '',
  price: '', salePrice: '', hasCoverOptions: false, paperbackPrice: '', hardcoverPrice: '',
  stockQty: '0', pages: '', publisher: '', publishedYear: '', language: 'English',
  isFeatured: false, isDeal: false, isNewArrival: false, isStationery: false, isActive: true,
  coverImage: '',
};

export default function ProductForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { notify } = useApp();

  const { data: categories } = useFetch('/admin/categories/all');
  const { data: existing, loading } = useFetch(isEdit ? `/admin/products/${id}` : '/session');

  const [form, setForm] = useState(BLANK);
  const [coverFile, setCoverFile] = useState(null);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!isEdit || !existing?.id) return;
    setForm({
      title: existing.title || '',
      author: existing.author || '',
      categoryId: String(existing.categoryId || ''),
      isbn: existing.isbn || '',
      description: existing.description || '',
      price: existing.price ?? '',
      salePrice: existing.salePrice ?? '',
      hasCoverOptions: existing.hasCoverOptions,
      paperbackPrice: existing.paperbackPrice ?? '',
      hardcoverPrice: existing.hardcoverPrice ?? '',
      stockQty: String(existing.stockQty ?? 0),
      pages: existing.pages ?? '',
      publisher: existing.publisher || '',
      publishedYear: existing.publishedYear ?? '',
      language: existing.language || 'English',
      isFeatured: existing.isFeatured,
      isDeal: existing.isDeal,
      isNewArrival: existing.isNewArrival,
      isStationery: existing.isStationery,
      isActive: existing.isActive,
      coverImage: existing.coverImage || '',
    });
  }, [isEdit, existing]);

  // Default the category once the list arrives on a brand new product.
  useEffect(() => {
    if (!isEdit && categories?.length && !form.categoryId) {
      setForm((f) => ({ ...f, categoryId: String(categories[0].id) }));
    }
  }, [isEdit, categories, form.categoryId]);

  const set = (key) => (e) =>
    setForm((f) => ({
      ...f,
      [key]: e.target.type === 'checkbox' ? e.target.checked : e.target.value,
    }));

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError(null);

    const body = new FormData();
    Object.entries(form).forEach(([k, v]) => body.append(k, v === null ? '' : String(v)));
    if (coverFile) body.append('cover', coverFile);

    try {
      const res = await api.upload(isEdit ? `/admin/products/${id}` : '/admin/products', body);
      notify(res.message);
      navigate('/admin/products');
    } catch (err) {
      setError(err.message);
      setBusy(false);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  if (isEdit && loading) return <Spinner />;

  return (
    <div className="space-y-6">
      <Link to="/admin/products" className="text-sm font-medium text-brand-700 hover:underline">
        ← All products
      </Link>

      <h1 className="font-display text-2xl text-slate-900">
        {isEdit ? 'Edit product' : 'Add a product'}
      </h1>

      {error && <Alert>{error}</Alert>}

      <form onSubmit={submit} className="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <div className="space-y-5">
          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-4 text-sm font-semibold text-slate-900">Details</h2>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <Field label="Title" required>
                  <input required className={inputClass} value={form.title} onChange={set('title')} />
                </Field>
              </div>
              <Field label="Author" required>
                <input required className={inputClass} value={form.author} onChange={set('author')} />
              </Field>
              <Field label="Category" required>
                <select required className={inputClass} value={form.categoryId} onChange={set('categoryId')}>
                  <option value="">Select…</option>
                  {(categories || []).map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </Field>
              <div className="sm:col-span-2">
                <Field label="Description">
                  <textarea rows={5} className={textareaClass} value={form.description} onChange={set('description')} />
                </Field>
              </div>
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-4 text-sm font-semibold text-slate-900">Pricing &amp; stock</h2>

            <label className="mb-4 flex items-center gap-2.5 text-sm text-slate-700">
              <input
                type="checkbox"
                checked={form.hasCoverOptions}
                onChange={set('hasCoverOptions')}
                className="h-4 w-4 accent-brand-700"
              />
              Sell in both paperback and hardcover
            </label>

            <div className="grid gap-4 sm:grid-cols-3">
              {form.hasCoverOptions ? (
                <>
                  <Field label="Paperback price" required>
                    <input type="number" step="0.01" min="0" className={inputClass} value={form.paperbackPrice} onChange={set('paperbackPrice')} />
                  </Field>
                  <Field label="Hardcover price" required>
                    <input type="number" step="0.01" min="0" className={inputClass} value={form.hardcoverPrice} onChange={set('hardcoverPrice')} />
                  </Field>
                </>
              ) : (
                <>
                  <Field label="Price" required>
                    <input type="number" step="0.01" min="0" required className={inputClass} value={form.price} onChange={set('price')} />
                  </Field>
                  <Field label="Sale price" hint="Leave blank if not on offer.">
                    <input type="number" step="0.01" min="0" className={inputClass} value={form.salePrice} onChange={set('salePrice')} />
                  </Field>
                </>
              )}
              <Field label="Stock quantity" required>
                <input type="number" min="0" required className={inputClass} value={form.stockQty} onChange={set('stockQty')} />
              </Field>
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-4 text-sm font-semibold text-slate-900">Publication</h2>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Publisher">
                <input className={inputClass} value={form.publisher} onChange={set('publisher')} />
              </Field>
              <Field label="Year published">
                <input type="number" className={inputClass} value={form.publishedYear} onChange={set('publishedYear')} />
              </Field>
              <Field label="Pages">
                <input type="number" min="0" className={inputClass} value={form.pages} onChange={set('pages')} />
              </Field>
              <Field label="Language">
                <input className={inputClass} value={form.language} onChange={set('language')} />
              </Field>
              <div className="sm:col-span-2">
                <Field label="ISBN">
                  <input className={inputClass} value={form.isbn} onChange={set('isbn')} />
                </Field>
              </div>
            </div>
          </section>
        </div>

        <aside className="space-y-5">
          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Cover image</h2>

            {(coverFile || form.coverImage) && (
              <img
                src={coverFile ? URL.createObjectURL(coverFile) : `/${form.coverImage}`}
                alt=""
                className="mb-3 aspect-[3/4] w-28 rounded-lg object-cover"
              />
            )}

            <input
              type="file"
              accept="image/jpeg,image/png,image/webp,image/gif"
              onChange={(e) => setCoverFile(e.target.files?.[0] || null)}
              className="w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700"
            />
            <p className="mt-2 text-[11px] text-slate-400">JPEG, PNG, WebP or GIF, up to 5 MB.</p>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Visibility</h2>
            <div className="space-y-2.5">
              <Toggle label="Visible in the store" checked={form.isActive} onChange={set('isActive')} />
              <Toggle label="Featured" checked={form.isFeatured} onChange={set('isFeatured')} />
              <Toggle label="New arrival" checked={form.isNewArrival} onChange={set('isNewArrival')} />
              <Toggle label="On offer" checked={form.isDeal} onChange={set('isDeal')} />
              <Toggle label="Stationery" checked={form.isStationery} onChange={set('isStationery')} />
            </div>
          </section>

          <button
            type="submit"
            disabled={busy}
            className="h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
          >
            {busy ? 'Saving…' : isEdit ? 'Save changes' : 'Add product'}
          </button>
        </aside>
      </form>
    </div>
  );
}

const Toggle = ({ label, checked, onChange }) => (
  <label className="flex items-center gap-2.5 text-sm text-slate-700">
    <input type="checkbox" checked={checked} onChange={onChange} className="h-4 w-4 accent-brand-700" />
    {label}
  </label>
);
