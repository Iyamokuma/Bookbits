import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Spinner, EmptyState, Alert } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { useApp } from '../context/AppContext';
import { api } from '../lib/api';
import { formatMoney } from '../lib/format';

export default function Cart() {
  const { user, setCartCount, notify } = useApp();
  const { data, loading, error, setData } = useFetch('/cart');
  const [busyKey, setBusyKey] = useState(null);
  const navigate = useNavigate();

  const apply = (result) => {
    setData(result);
    setCartCount(result.count);
  };

  const change = async (key, qty) => {
    setBusyKey(key);
    try {
      apply(await api.patch('/cart/items', { key, qty }));
    } catch (err) {
      notify(err.message, 'error');
    } finally {
      setBusyKey(null);
    }
  };

  const remove = async (key) => {
    setBusyKey(key);
    try {
      apply(await api.delete('/cart/items', { key }));
    } catch (err) {
      notify(err.message, 'error');
    } finally {
      setBusyKey(null);
    }
  };

  if (loading) return <Spinner />;
  if (error) return <div className="mx-auto max-w-3xl px-4 py-16"><Alert>{error.message}</Alert></div>;

  if (data.lines.length === 0) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <EmptyState
          title="Your cart is empty"
          message="Browse the collection and add a few titles to get started."
          actionLabel="Start shopping"
          actionTo="/shop"
        />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl px-4 py-10">
      <h1 className="mb-8 font-display text-3xl text-slate-900">Your cart</h1>

      <div className="grid gap-8 lg:grid-cols-[1fr_22rem]">
        <div className="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
          {data.lines.map((line) => (
            <div key={line.key} className="flex gap-4 p-4 sm:p-5">
              <Link to={`/book/${line.book.id}`} className="w-20 shrink-0 sm:w-24">
                <img
                  src={line.book.coverUrl}
                  alt={line.book.title}
                  className="aspect-[3/4] w-full rounded-lg object-cover"
                />
              </Link>

              <div className="flex flex-1 flex-col">
                <div className="flex justify-between gap-3">
                  <div>
                    <h2 className="text-sm font-semibold text-slate-900">
                      <Link to={`/book/${line.book.id}`} className="hover:text-brand-700">
                        {line.book.title}
                      </Link>
                    </h2>
                    <p className="mt-0.5 text-xs text-slate-500">by {line.book.author}</p>
                    {line.coverType && (
                      <p className="mt-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[11px] capitalize text-slate-600">
                        {line.coverType}
                      </p>
                    )}
                  </div>
                  <p className="whitespace-nowrap text-sm font-semibold text-slate-900">
                    {formatMoney(line.lineTotal)}
                  </p>
                </div>

                <div className="mt-auto flex items-center justify-between pt-3">
                  <div className="flex h-9 items-center rounded-lg border border-slate-300">
                    <button
                      onClick={() => change(line.key, line.qty - 1)}
                      disabled={busyKey === line.key}
                      className="grid h-full w-9 place-items-center text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                      aria-label="Decrease quantity"
                    >
                      −
                    </button>
                    <span className="w-9 text-center text-sm font-semibold">{line.qty}</span>
                    <button
                      onClick={() => change(line.key, line.qty + 1)}
                      disabled={busyKey === line.key || line.qty >= line.book.stock}
                      className="grid h-full w-9 place-items-center text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                      aria-label="Increase quantity"
                    >
                      +
                    </button>
                  </div>

                  <button
                    onClick={() => remove(line.key)}
                    disabled={busyKey === line.key}
                    className="text-xs font-medium text-rose-600 hover:underline disabled:opacity-40"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        <aside className="h-fit rounded-2xl border border-slate-200 bg-white p-6">
          <h2 className="mb-4 text-sm font-semibold text-slate-900">Order summary</h2>
          <div className="flex justify-between border-b border-slate-200 pb-4 text-sm">
            <span className="text-slate-600">Subtotal</span>
            <span className="font-semibold text-slate-900">{formatMoney(data.subtotal)}</span>
          </div>
          <p className="pt-4 text-xs text-slate-500">
            Delivery is calculated at checkout based on your address.
          </p>

          <button
            onClick={() => navigate(user ? '/checkout' : '/login')}
            className="mt-5 h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800"
          >
            {user ? 'Proceed to checkout' : 'Sign in to check out'}
          </button>
          <Link
            to="/shop"
            className="mt-3 block text-center text-xs font-medium text-brand-700 hover:underline"
          >
            Continue shopping
          </Link>
        </aside>
      </div>
    </div>
  );
}
