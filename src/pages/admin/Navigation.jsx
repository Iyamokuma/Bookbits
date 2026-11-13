import { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { Spinner, Alert, inputClass } from '../../components/ui';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';

const STORE_PAGES = [
  { label: 'Home', url: '/' },
  { label: 'Books (all)', url: '/shop' },
  { label: 'Stationery', url: '/stationery' },
  { label: 'Blog', url: '/blog' },
  { label: 'Contact us', url: '/contact' },
  { label: 'Cart', url: '/cart' },
  { label: 'My account', url: '/account' },
  { label: 'Shipping & returns', url: '/shipping' },
  { label: 'Privacy policy', url: '/privacy' },
  { label: 'Terms of use', url: '/terms' },
];

const CUSTOM = '__custom__';

const Chevron = ({ up }) => (
  <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2"
       strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <path d={up ? 'M6 15l6-6 6 6' : 'M6 9l6 6 6-6'} />
  </svg>
);

let keySeq = 0;
const withKey = (item) => ({ ...item, newTab: Boolean(item.newTab), _key: ++keySeq, _custom: false });
const strip = (items) => items.map(({ label, url, newTab }) => ({ label, url, newTab: Boolean(newTab) }));

export default function Navigation() {
  const { notify, setNav } = useApp();
  const [data, setData] = useState(null);
  const [items, setItems] = useState([]);
  const [saved, setSaved] = useState('[]');
  const [loadError, setLoadError] = useState(null);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const lastLabelRef = useRef(null);

  useEffect(() => {
    api
      .get('/admin/navigation')
      .then((res) => {
        setData(res);
        setItems(res.items.map(withKey));
        // JSONB doesn't keep key order, so compare through strip()'s fixed order.
        setSaved(JSON.stringify(strip(res.items)));
      })
      .catch((err) => setLoadError(err.message));
  }, []);

  // Every known destination, for the link picker and for naming a link.
  const known = useMemo(() => {
    if (!data) return new Map();
    return new Map([
      ...STORE_PAGES.map((o) => [o.url, o.label]),
      ...data.pages.map((p) => [p.url, p.title]),
      ...data.categories.map((c) => [c.url, c.name]),
    ]);
  }, [data]);

  const dirty = JSON.stringify(strip(items)) !== saved;

  const update = (key, patch) =>
    setItems((list) => list.map((item) => (item._key === key ? { ...item, ...patch } : item)));

  const move = (index, delta) =>
    setItems((list) => {
      const next = [...list];
      const [item] = next.splice(index, 1);
      next.splice(index + delta, 0, item);
      return next;
    });

  const remove = (key) => setItems((list) => list.filter((item) => item._key !== key));

  const add = () => {
    setItems((list) => [...list, withKey({ label: '', url: '', newTab: false })]);
    // Focus the new row's label once it renders.
    setTimeout(() => lastLabelRef.current?.focus(), 0);
  };

  const pickDestination = (item, value) => {
    if (value === CUSTOM) {
      update(item._key, { _custom: true, url: known.has(item.url) ? '' : item.url });
      return;
    }
    // Name an unnamed link after where it goes.
    update(item._key, { _custom: false, url: value, label: item.label || known.get(value)?.replace(' (all)', '') || '' });
  };

  const restoreDefaults = () => {
    if (!window.confirm('Replace the current menu with the original one? Nothing is saved until you click Save.')) return;
    setItems(data.defaults.map(withKey));
  };

  const save = async () => {
    setBusy(true);
    setError(null);
    try {
      const res = await api.post('/admin/navigation', { items: strip(items) });
      setItems(res.items.map(withKey));
      // Normalised through strip() so key order can't make it look unsaved.
      setSaved(JSON.stringify(strip(res.items)));
      setNav(res.items);
      notify(res.message);
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  };

  if (loadError) return <Alert>{loadError}</Alert>;
  if (!data) return <Spinner />;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl text-slate-900">Navigation bar</h1>
          <p className="mt-1 max-w-2xl text-sm text-slate-500">
            Choose the links in the menu at the top of the store, in the order shoppers see them. To
            add a page of your own, create it under{' '}
            <Link to="/admin/pages" className="font-medium text-brand-700 hover:underline">Pages</Link> first.
          </p>
        </div>
        <div className="flex items-center gap-3">
          {dirty && <span className="text-xs font-medium text-amber-600">Unsaved changes</span>}
          <button
            onClick={save}
            disabled={busy || !dirty}
            className="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-50"
          >
            {busy ? 'Saving…' : 'Save menu'}
          </button>
        </div>
      </div>

      {error && <Alert>{error}</Alert>}

      {/* Preview */}
      <section className="rounded-2xl border border-slate-200 bg-white p-4">
        <p className="mb-3 text-[11px] font-medium uppercase tracking-wide text-slate-400">Preview</p>
        <div className="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
          <img src="/img/logo.png" alt="" className="mr-2 h-7 w-auto" />
          {items.length === 0 && <span className="text-sm text-slate-400">No links</span>}
          {items.map((item) => (
            <span key={item._key} className={`text-sm font-medium ${item.label ? 'text-slate-700' : 'italic text-slate-400'}`}>
              {item.label || 'Untitled'}
              {item.newTab && <span className="ml-0.5 text-[10px] text-slate-400">↗</span>}
            </span>
          ))}
        </div>
      </section>

      <section className="space-y-3">
        {items.map((item, index) => {
          const custom = item._custom || (item.url !== '' && !known.has(item.url));
          const selectValue = custom ? CUSTOM : item.url;
          return (
            <div key={item._key} className="rounded-2xl border border-slate-200 bg-white p-4">
              <div className="grid gap-3 md:grid-cols-[2rem_1fr_1.3fr_auto] md:items-start">
                <span className="hidden h-11 items-center text-sm font-semibold text-slate-400 md:flex">
                  {index + 1}
                </span>

                <label className="block">
                  <span className="text-xs font-medium text-slate-600">Label</span>
                  <input
                    ref={index === items.length - 1 ? lastLabelRef : undefined}
                    className={inputClass}
                    value={item.label}
                    maxLength={40}
                    placeholder="e.g. About us"
                    onChange={(e) => update(item._key, { label: e.target.value })}
                  />
                </label>

                <div>
                  <label className="block">
                    <span className="text-xs font-medium text-slate-600">Links to</span>
                    <select
                      className={inputClass}
                      value={selectValue}
                      onChange={(e) => pickDestination(item, e.target.value)}
                    >
                      {item.url === '' && !item._custom && <option value="">Choose a page…</option>}
                      <optgroup label="Store pages">
                        {STORE_PAGES.map((o) => <option key={o.url} value={o.url}>{o.label}</option>)}
                      </optgroup>
                      {data.pages.length > 0 && (
                        <optgroup label="Your pages">
                          {data.pages.map((p) => (
                            <option key={p.url} value={p.url}>
                              {p.title}{p.isPublished ? '' : ' (draft)'}
                            </option>
                          ))}
                        </optgroup>
                      )}
                      {data.categories.length > 0 && (
                        <optgroup label="Book categories">
                          {data.categories.map((c) => <option key={c.url} value={c.url}>{c.name}</option>)}
                        </optgroup>
                      )}
                      <optgroup label="Other">
                        <option value={CUSTOM}>Custom link…</option>
                      </optgroup>
                    </select>
                  </label>
                  {custom && (
                    <input
                      className={inputClass}
                      value={item.url}
                      placeholder="https://… or /shop?q=…"
                      onChange={(e) => update(item._key, { url: e.target.value.trim() })}
                    />
                  )}
                  <label className="mt-2 flex items-center gap-2 text-xs text-slate-600">
                    <input
                      type="checkbox"
                      checked={item.newTab}
                      onChange={(e) => update(item._key, { newTab: e.target.checked })}
                      className="h-3.5 w-3.5 accent-brand-700"
                    />
                    Open in a new tab
                  </label>
                </div>

                <div className="flex items-center gap-1 md:mt-6">
                  <button
                    type="button"
                    onClick={() => move(index, -1)}
                    disabled={index === 0}
                    aria-label={`Move ${item.label || 'link'} up`}
                    className="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30"
                  >
                    <Chevron up />
                  </button>
                  <button
                    type="button"
                    onClick={() => move(index, 1)}
                    disabled={index === items.length - 1}
                    aria-label={`Move ${item.label || 'link'} down`}
                    className="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30"
                  >
                    <Chevron />
                  </button>
                  <button
                    type="button"
                    onClick={() => remove(item._key)}
                    className="ml-1 h-9 rounded-lg px-3 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </section>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <button
          type="button"
          onClick={add}
          disabled={items.length >= data.maxItems}
          className="rounded-lg border border-dashed border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-brand-400 hover:text-brand-700 disabled:opacity-50"
        >
          + Add a link
        </button>
        <div className="flex items-center gap-4">
          <span className="text-xs text-slate-400">{items.length} of {data.maxItems} links</span>
          <button type="button" onClick={restoreDefaults} className="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:underline">
            Restore original menu
          </button>
        </div>
      </div>
    </div>
  );
}
