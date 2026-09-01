import BookCard from '../../components/BookCard';
import { Spinner, EmptyState, Alert } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';

export default function Wishlist() {
  const { wishlist: savedIds } = useApp();
  const { data, loading, error } = useFetch('/wishlist');

  // Filtering against the live id list means unsaving a book removes its card
  // straight away, without waiting for a re-fetch.
  const books = (data || []).filter((b) => savedIds.includes(b.id));

  return (
    <div className="mx-auto max-w-6xl px-4 py-12">
      <h1 className="font-display text-3xl text-slate-900">Your wishlist</h1>
      <p className="mt-1 text-sm text-slate-500">
        {loading || error
          ? 'Books you saved for later.'
          : `${books.length} saved ${books.length === 1 ? 'book' : 'books'}.`}
      </p>

      <div className="mt-8">
        {loading ? (
          <Spinner />
        ) : error ? (
          <Alert>{error.message}</Alert>
        ) : books.length === 0 ? (
          <EmptyState
            title="Your wishlist is empty"
            message="Tap the heart on any book to save it here for later."
            actionLabel="Browse books"
            actionTo="/shop"
          />
        ) : (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {books.map((b) => <BookCard key={b.id} book={b} />)}
          </div>
        )}
      </div>
    </div>
  );
}
