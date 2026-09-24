import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import BookCard from '../components/BookCard';
import { Spinner, EmptyState, Alert, inputClass } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { useApp } from '../context/AppContext';
import Aos from '../components/Aos';

export default function Shop() {
  const [params, setParams] = useSearchParams();
  const { categories } = useApp();

  const cat = params.get('cat') || '';
  const q = params.get('q') || '';
  const query = new URLSearchParams();
  if (cat) query.set('cat', cat);
  if (q) query.set('q', q);

  const { data, loading, error } = useFetch(`/books?${query}`);

  const [term, setTerm] = useState(q);
  useEffect(() => setTerm(q), [q]);

  const submitSearch = (e) => {
    e.preventDefault();
    const next = new URLSearchParams();
    if (cat) next.set('cat', cat);
    if (term.trim()) next.set('q', term.trim());
    setParams(next);
  };

  const heading = data?.category?.name || (q ? `Results for “${q}”` : 'All books');

  return (
    <div className="mx-auto max-w-7xl px-4 py-10">
      <Aos className="mb-8" animation="fade-down">
        <h1 className="font-display text-3xl text-slate-900">{heading}</h1>
        {!loading && data && (
          <p className="mt-1 text-sm text-slate-500">
            {data.books.length} {data.books.length === 1 ? 'title' : 'titles'}
          </p>
        )}

        <form onSubmit={submitSearch} role="search" className="mt-5 flex max-w-lg gap-2">
          <label className="sr-only" htmlFor="q-shop">Search books</label>
          <input
            id="q-shop"
            type="search"
            value={term}
            onChange={(e) => setTerm(e.target.value)}
            placeholder="Search by title, author or ISBN…"
            className={inputClass}
          />
          <button type="submit"
                  className="shrink-0 rounded-lg bg-brand px-5 text-sm font-semibold text-white transition hover:bg-brand-dark">
            Search
          </button>
        </form>

        {/* The sidebar is desktop-only, so small screens filter from here. */}
        <div className="mt-4 flex gap-2 overflow-x-auto pb-1 lg:hidden">
          <Link
            to="/shop"
            className={`shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium ${!cat ? 'border-brand bg-brand text-white' : 'border-slate-200 bg-white text-slate-600'}`}
          >
            All
          </Link>
          {categories.map((c) => (
            <Link
              key={c.slug}
              to={`/shop?cat=${c.slug}`}
              className={`shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium ${cat === c.slug ? 'border-brand bg-brand text-white' : 'border-slate-200 bg-white text-slate-600'}`}
            >
              {c.name}
            </Link>
          ))}
        </div>
      </Aos>

      <Aos animation="fade-up" className="grid gap-8 lg:grid-cols-[13rem_1fr]">
        <aside className="hidden lg:block">
          <h2 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
            Categories
          </h2>
          <nav className="space-y-1 text-sm">
            <Link
              to="/shop"
              className={`block rounded-lg px-3 py-2 ${!cat ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600 hover:bg-slate-100'}`}
            >
              All books
            </Link>
            {categories.map((c) => (
              <Link
                key={c.slug}
                to={`/shop?cat=${c.slug}`}
                className={`block rounded-lg px-3 py-2 ${cat === c.slug ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600 hover:bg-slate-100'}`}
              >
                {c.name}
              </Link>
            ))}
          </nav>
        </aside>

        <div>
          {loading ? (
            <Spinner />
          ) : error ? (
            <Alert>{error.message}</Alert>
          ) : data.books.length === 0 ? (
            <EmptyState
              title="Nothing matched your search"
              message={q ? `We couldn't find anything for “${q}”. Try a different title or author.` : 'There are no titles in this category yet.'}
              actionLabel="Browse all books"
              actionTo="/shop"
            />
          ) : (
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
              {data.books.map((b) => <BookCard key={b.id} book={b} />)}
            </div>
          )}
        </div>
      </Aos>
    </div>
  );
}
