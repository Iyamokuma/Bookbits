import { Link, useSearchParams } from 'react-router-dom';
import BookCard from '../components/BookCard';
import { Spinner, EmptyState, Alert } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { useApp } from '../context/AppContext';

export default function Shop() {
  const [params] = useSearchParams();
  const { categories } = useApp();

  const cat = params.get('cat') || '';
  const q = params.get('q') || '';
  const query = new URLSearchParams();
  if (cat) query.set('cat', cat);
  if (q) query.set('q', q);

  const { data, loading, error } = useFetch(`/books?${query}`);

  const heading = data?.category?.name || (q ? `Results for “${q}”` : 'All books');

  return (
    <div className="mx-auto max-w-7xl px-4 py-10">
      <div className="mb-8">
        <h1 className="font-display text-3xl text-slate-900">{heading}</h1>
        {!loading && data && (
          <p className="mt-1 text-sm text-slate-500">
            {data.books.length} {data.books.length === 1 ? 'title' : 'titles'}
          </p>
        )}
      </div>

      <div className="grid gap-8 lg:grid-cols-[13rem_1fr]">
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
      </div>
    </div>
  );
}
