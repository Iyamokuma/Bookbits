import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Spinner, EmptyState, Alert, StatusBadge } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { formatMoney, formatDate } from '../../lib/format';

export default function Account() {
  const { user, setUser, setCartCount, notify } = useApp();
  const { data: orders, loading, error } = useFetch('/orders');
  const navigate = useNavigate();
  const [resending, setResending] = useState(false);

  const resendVerification = async () => {
    setResending(true);
    try {
      const res = await api.post('/auth/resend-verification');
      notify(res.message);
    } catch (err) {
      notify(err.message, 'error');
    } finally {
      setResending(false);
    }
  };

  const signOut = async () => {
    await api.post('/auth/logout');
    setUser(null);
    setCartCount(0);
    notify('You have been signed out.');
    navigate('/');
  };

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-3xl text-slate-900">Hello, {user.name.split(' ')[0]}</h1>
          <p className="mt-1 text-sm text-slate-500">{user.email}</p>
        </div>
        <button
          onClick={signOut}
          className="rounded-full border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Sign out
        </button>
      </div>

      {!user.emailVerified && (
        <div className="mt-6">
          <Alert tone="warning">
            Your email address hasn't been verified yet. Check your inbox for the link we sent when
            you signed up.{' '}
            <button
              type="button"
              onClick={resendVerification}
              disabled={resending}
              className="font-semibold underline underline-offset-2 disabled:opacity-60"
            >
              {resending ? 'Sending…' : 'Send it again'}
            </button>
          </Alert>
        </div>
      )}

      <h2 className="mb-4 mt-10 text-sm font-semibold text-slate-900">Your orders</h2>

      {loading ? (
        <Spinner />
      ) : error ? (
        <Alert>{error.message}</Alert>
      ) : orders.length === 0 ? (
        <EmptyState
          title="No orders yet"
          message="When you place an order it will appear here so you can track it."
          actionLabel="Start shopping"
          actionTo="/shop"
        />
      ) : (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <table className="w-full text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th className="px-5 py-3 font-semibold">Order</th>
                <th className="px-5 py-3 font-semibold">Date</th>
                <th className="hidden px-5 py-3 font-semibold sm:table-cell">Status</th>
                <th className="px-5 py-3 text-right font-semibold">Total</th>
                <th className="px-5 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {orders.map((o) => (
                <tr key={o.id} className="hover:bg-slate-50">
                  <td className="px-5 py-4 font-semibold text-slate-900">#{o.id}</td>
                  <td className="px-5 py-4 text-slate-600">{formatDate(o.createdAt)}</td>
                  <td className="hidden px-5 py-4 sm:table-cell">
                    <StatusBadge status={o.status} />
                    {!o.paid && (
                      <span className="ml-2 text-[11px] font-medium text-amber-700">Unpaid</span>
                    )}
                  </td>
                  <td className="px-5 py-4 text-right font-semibold text-slate-900">
                    {formatMoney(o.total)}
                  </td>
                  <td className="px-5 py-4 text-right">
                    <Link to={`/orders/${o.id}`} className="text-xs font-semibold text-brand-700 hover:underline">
                      View
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
