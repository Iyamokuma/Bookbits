import { useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../lib/api';
import { formatMoney } from '../lib/format';
import { useApp } from '../context/AppContext';
import { trackMeta } from '../lib/metaPixel';
import { HeartIcon } from './icons';

export default function BookCard({ book }) {
  const { setCartCount, notify, wishlist, toggleWishlist, store } = useApp();
  const [adding, setAdding] = useState(false);
  const saved = wishlist.includes(book.id);

  const addToCart = async () => {
    setAdding(true);
    try {
      const res = await api.post('/cart/items', { bookId: book.id, qty: 1 });
      setCartCount(res.count);
      trackMeta('AddToCart', {
        content_type: 'product',
        content_ids: [String(book.id)],
        content_name: book.title,
        value: book.price,
        currency: store.currencyCode,
        num_items: 1,
      });
      notify(`“${book.title}” added to your cart.`);
    } catch (err) {
      notify(err.message, 'error');
    } finally {
      setAdding(false);
    }
  };

  return (
    <article className="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg hover:shadow-slate-200/60">
      <Link to={`/book/${book.id}`} className="relative block aspect-[3/4] overflow-hidden bg-slate-100">
        <img
          src={book.coverUrl}
          alt={book.title}
          loading="lazy"
          className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
        />
        {!book.inStock ? (
          <span className="absolute left-3 top-3 rounded-full bg-slate-900/85 px-2.5 py-1 text-[11px] font-semibold text-white">
            Out of stock
          </span>
        ) : book.wasPrice ? (
          <span className="absolute left-3 top-3 rounded-full bg-accent-500 px-2.5 py-1 text-[11px] font-semibold text-white">
            Sale
          </span>
        ) : null}
      </Link>

      <button
        type="button"
        onClick={() => toggleWishlist(book.id)}
        aria-pressed={saved}
        aria-label={saved ? `Remove ${book.title} from your wishlist` : `Save ${book.title} to your wishlist`}
        title={saved ? 'Saved — click to remove' : 'Save for later'}
        className={`absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-white/90 shadow-sm ring-1 ring-slate-900/5 backdrop-blur transition hover:bg-white ${
          saved ? 'text-rose-600' : 'text-slate-400 hover:text-rose-500'
        }`}
      >
        <HeartIcon filled={saved} />
      </button>

      <div className="flex flex-1 flex-col p-4">
        <p className="text-[11px] font-semibold uppercase tracking-wide text-brand-600">
          {book.categoryName}
        </p>
        <h3 className="mt-1.5 line-clamp-2 text-sm font-semibold leading-snug text-slate-900">
          <Link to={`/book/${book.id}`} className="hover:text-brand-700">{book.title}</Link>
        </h3>
        <p className="mt-1 line-clamp-1 text-xs text-slate-500">by {book.author}</p>

        <div className="mt-3 flex items-baseline gap-2">
          <span className="text-base font-bold text-slate-900">{formatMoney(book.price)}</span>
          {book.wasPrice && (
            <span className="text-xs text-slate-400 line-through">{formatMoney(book.wasPrice)}</span>
          )}
          {book.hasCoverOptions && <span className="text-[11px] text-slate-500">from</span>}
        </div>

        <div className="mt-4 pt-1">
          {!book.inStock ? (
            <button disabled className="h-10 w-full rounded-lg bg-slate-100 text-sm font-semibold text-slate-400">
              Out of stock
            </button>
          ) : book.hasCoverOptions ? (
            <Link
              to={`/book/${book.id}`}
              className="grid h-10 w-full place-items-center rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800"
            >
              Choose cover
            </Link>
          ) : (
            <button
              onClick={addToCart}
              disabled={adding}
              className="h-10 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
            >
              {adding ? 'Adding…' : 'Add to cart'}
            </button>
          )}
        </div>
      </div>
    </article>
  );
}
