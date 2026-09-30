import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import BookCard from '../components/BookCard';
import { Spinner, EmptyState, Alert, Pagination } from '../components/ui';
import { useFetch } from '../lib/useFetch';

export default function Stationery() {
  const [params, setParams] = useSearchParams();
  const page = params.get('page') || '1';
  const query = params.get('q') || '';
  const [term, setTerm] = useState(query);

  const qs = new URLSearchParams();
  if (query) qs.set('q', query);
  if (page !== '1') qs.set('page', page);

  const { data, loading, error } = useFetch(`/stationery?${qs}`);

  useEffect(() => setTerm(query), [query]);

  const goToPage = (n) => {
    const next = new URLSearchParams(params);
    if (n <= 1) next.delete('page');
    else next.set('page', String(n));
    setParams(next);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-10">
      <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="font-display text-3xl text-slate-900">Stationery</h1>
          <p className="mt-1 text-sm text-slate-500">
            Journals, pens, notebooks and supplies to write, plan and reflect.
          </p>
        </div>

        <form
          onSubmit={(e) => {
            e.preventDefault();
            const next = new URLSearchParams();
            if (term.trim()) next.set('q', term.trim());
            setParams(next);
          }}
          className="flex w-full max-w-xs gap-2"
        >
          <input
            type="search"
            value={term}
            onChange={(e) => setTerm(e.target.value)}
            placeholder="Search stationery…"
            className="h-10 flex-1 rounded-full border border-slate-300 px-4 text-sm focus:border-brand-500 focus:outline-none"
          />
          <button className="rounded-full bg-brand-700 px-5 text-sm font-semibold text-white hover:bg-brand-800">
            Search
          </button>
        </form>
      </div>

      {loading ? (
        <Spinner />
      ) : error ? (
        <Alert>{error.message}</Alert>
      ) : data.books.length === 0 ? (
        <EmptyState
          title={query ? 'Nothing matched your search' : 'Stationery is on its way'}
          message={
            query
              ? `We couldn't find any stationery for “${query}”.`
              : 'We are restocking our journals and supplies. Please check back shortly.'
          }
          actionLabel="Browse books"
          actionTo="/shop"
        />
      ) : (
        <>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {data.books.map((b) => <BookCard key={b.id} book={b} />)}
          </div>
          {data.pagination && <Pagination pagination={data.pagination} onPage={goToPage} />}
        </>
      )}
    </div>
  );
}
