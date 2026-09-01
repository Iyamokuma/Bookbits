import { useState } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import BookCard from '../components/BookCard';
import { Spinner, EmptyState, Alert } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { useApp } from '../context/AppContext';
import { HeartIcon } from '../components/icons';
import { api } from '../lib/api';
import { formatMoney } from '../lib/format';

export default function Product() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { setCartCount, notify, wishlist, toggleWishlist } = useApp();
  const { data, loading, error } = useFetch(`/books/${id}`);

  const [coverType, setCoverType] = useState('paperback');
  const [qty, setQty] = useState(1);
  const [busy, setBusy] = useState(false);

  if (loading) return <Spinner />;
  if (error) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <EmptyState title="Book not found" message={error.message} actionLabel="Browse all books" actionTo="/shop" />
      </div>
    );
  }

  const { book, related } = data;
  const saved = wishlist.includes(book.id);
  const price = book.hasCoverOptions
    ? coverType === 'hardcover'
      ? book.hardcoverPrice
      : book.paperbackPrice
    : book.price;

  const addToCart = async (thenCheckout = false) => {
    setBusy(true);
    try {
      const res = await api.post('/cart/items', {
        bookId: book.id,
        qty,
        coverType: book.hasCoverOptions ? coverType : null,
      });
      setCartCount(res.count);
      if (thenCheckout) navigate('/cart');
      else notify(`“${book.title}” added to your cart.`);
    } catch (err) {
      notify(err.message, 'error');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      <nav className="mb-6 text-xs text-slate-500">
        <Link to="/" className="hover:text-brand-700">Home</Link>
        <span className="mx-2">/</span>
        <Link to={`/shop?cat=${book.slugCategory}`} className="hover:text-brand-700">{book.categoryName}</Link>
        <span className="mx-2">/</span>
        <span className="text-slate-700">{book.title}</span>
      </nav>

      <div className="grid gap-10 lg:grid-cols-2">
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <img src={book.coverUrl} alt={book.title} className="aspect-[3/4] w-full object-cover" />
        </div>

        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-brand-600">
            {book.categoryName}
          </p>
          <h1 className="mt-2 font-display text-3xl leading-tight text-slate-900">{book.title}</h1>
          <p className="mt-2 text-sm text-slate-500">by {book.author}</p>

          <div className="mt-5 flex items-baseline gap-3">
            <span className="text-3xl font-bold text-slate-900">{formatMoney(price)}</span>
            {book.wasPrice && (
              <span className="text-base text-slate-400 line-through">{formatMoney(book.wasPrice)}</span>
            )}
          </div>

          <p className="mt-3 text-sm">
            {book.inStock ? (
              <span className="font-medium text-emerald-700">
                In stock{book.stock <= 5 && ` — only ${book.stock} left`}
              </span>
            ) : (
              <span className="font-medium text-rose-600">Out of stock</span>
            )}
          </p>

          {book.hasCoverOptions && (
            <div className="mt-6">
              <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                Choose a cover
              </p>
              <div className="grid grid-cols-2 gap-3">
                {[
                  { id: 'paperback', label: 'Paperback', price: book.paperbackPrice },
                  { id: 'hardcover', label: 'Hardcover', price: book.hardcoverPrice },
                ]
                  .filter((o) => o.price != null)
                  .map((o) => (
                    <button
                      key={o.id}
                      onClick={() => setCoverType(o.id)}
                      className={`rounded-xl border p-3 text-left transition ${
                        coverType === o.id
                          ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-200'
                          : 'border-slate-300 hover:border-slate-400'
                      }`}
                    >
                      <span className="block text-sm font-semibold text-slate-900">{o.label}</span>
                      <span className="mt-0.5 block text-sm text-slate-600">{formatMoney(o.price)}</span>
                    </button>
                  ))}
              </div>
            </div>
          )}

          {book.inStock && (
            <div className="mt-6 flex flex-wrap items-center gap-3">
              <div className="flex h-11 items-center rounded-lg border border-slate-300">
                <button
                  onClick={() => setQty((q) => Math.max(1, q - 1))}
                  className="grid h-full w-11 place-items-center text-lg text-slate-600 hover:bg-slate-50"
                  aria-label="Decrease quantity"
                >
                  −
                </button>
                <span className="w-10 text-center text-sm font-semibold">{qty}</span>
                <button
                  onClick={() => setQty((q) => Math.min(book.stock, q + 1))}
                  className="grid h-full w-11 place-items-center text-lg text-slate-600 hover:bg-slate-50"
                  aria-label="Increase quantity"
                >
                  +
                </button>
              </div>

              <button
                onClick={() => addToCart(false)}
                disabled={busy}
                className="h-11 rounded-lg bg-brand-700 px-7 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
              >
                {busy ? 'Adding…' : 'Add to cart'}
              </button>
              <button
                onClick={() => addToCart(true)}
                disabled={busy}
                className="h-11 rounded-lg border border-brand-700 px-7 text-sm font-semibold text-brand-700 hover:bg-brand-50 disabled:opacity-60"
              >
                Buy now
              </button>
              <button
                onClick={() => toggleWishlist(book.id)}
                aria-pressed={saved}
                className={`inline-flex h-11 items-center gap-2 rounded-lg border px-5 text-sm font-semibold transition ${
                  saved
                    ? 'border-rose-200 bg-rose-50 text-rose-600'
                    : 'border-slate-300 text-slate-700 hover:bg-slate-50'
                }`}
              >
                <HeartIcon filled={saved} />
                {saved ? 'Saved' : 'Save'}
              </button>
            </div>
          )}

          {book.description && (
            <div className="mt-8 border-t border-slate-200 pt-6">
              <h2 className="mb-2 text-sm font-semibold text-slate-900">About this book</h2>
              <p className="whitespace-pre-line text-sm leading-relaxed text-slate-600">
                {book.description}
              </p>
            </div>
          )}

          <dl className="mt-8 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-slate-200 pt-6 text-sm">
            <Detail label="Publisher" value={book.publisher} />
            <Detail label="Published" value={book.publishedYear} />
            <Detail label="Pages" value={book.pages} />
            <Detail label="Language" value={book.language} />
            <Detail label="ISBN" value={book.isbn} />
          </dl>
        </div>
      </div>

      {related.length > 0 && (
        <section className="mt-16">
          <h2 className="mb-6 font-display text-2xl text-slate-900">You may also like</h2>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {related.map((b) => <BookCard key={b.id} book={b} />)}
          </div>
        </section>
      )}
    </div>
  );
}

const Detail = ({ label, value }) =>
  value ? (
    <div>
      <dt className="text-xs text-slate-400">{label}</dt>
      <dd className="text-slate-700">{value}</dd>
    </div>
  ) : null;
