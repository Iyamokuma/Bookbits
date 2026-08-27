import { useState } from 'react';
import BookCard from '../components/BookCard';
import { Spinner, EmptyState, Alert } from '../components/ui';
import { useFetch } from '../lib/useFetch';

export default function Stationery() {
  const [term, setTerm] = useState('');
  const [query, setQuery] = useState('');
  const { data, loading, error } = useFetch(`/stationery?q=${encodeURIComponent(query)}`);

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
          onSubmit={(e) => { e.preventDefault(); setQuery(term.trim()); }}
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
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {data.books.map((b) => <BookCard key={b.id} book={b} />)}
        </div>
      )}
    </div>
  );
}
