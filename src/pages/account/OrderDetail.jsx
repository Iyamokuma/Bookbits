import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner, EmptyState, Alert, StatusBadge } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { formatMoney, formatDateTime } from '../../lib/format';

export default function OrderDetail() {
  const { id } = useParams();
  const { notify } = useApp();
  const { data, loading, error } = useFetch(`/orders/${id}`);
  const [gateway, setGateway] = useState('paystack');
  const [busy, setBusy] = useState(false);

  const retryPayment = async () => {
    setBusy(true);
    try {
      const res = await api.post(`/orders/${id}/pay`, { gateway });
      window.location.href = res.redirectUrl;
    } catch (err) {
      notify(err.message, 'error');
      setBusy(false);
    }
  };

  if (loading) return <Spinner />;
  if (error) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <EmptyState title="Order not found" message={error.message} actionLabel="My account" actionTo="/account" />
      </div>
    );
  }

  const { order, items, gateways } = data;

  return (
    <div className="mx-auto max-w-4xl px-4 py-12">
      <Link to="/account" className="text-sm font-medium text-brand-700 hover:underline">
        ← My orders
      </Link>

      <div className="mt-5 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-3xl text-slate-900">Order #{order.id}</h1>
          <p className="mt-1 text-sm text-slate-500">Placed {formatDateTime(order.createdAt)}</p>
        </div>
        <StatusBadge status={order.status} />
      </div>

      {!order.paid && (
        <div className="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
          <p className="text-sm font-semibold text-amber-900">This order hasn't been paid for yet.</p>
          <p className="mt-1 text-sm text-amber-800">
            Complete the payment below and we'll start preparing it right away.
          </p>
          <div className="mt-4 flex flex-wrap items-center gap-3">
            <select
              value={gateway}
              onChange={(e) => setGateway(e.target.value)}
              className="h-10 rounded-lg border border-amber-300 bg-white px-3 text-sm"
            >
              {gateways.map((g) => <option key={g.id} value={g.id}>{g.label}</option>)}
            </select>
            <button
              onClick={retryPayment}
              disabled={busy}
              className="h-10 rounded-lg bg-brand-700 px-6 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
            >
              {busy ? 'Starting…' : `Pay ${formatMoney(order.total)}`}
            </button>
          </div>
        </div>
      )}

      {order.trackingNumber && (
        <div className="mt-6">
          <Alert tone="info">
            Tracking number: <span className="font-semibold">{order.trackingNumber}</span>
          </Alert>
        </div>
      )}

      <div className="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table className="w-full text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th className="px-5 py-3 font-semibold">Item</th>
              <th className="px-5 py-3 text-center font-semibold">Qty</th>
              <th className="px-5 py-3 text-right font-semibold">Total</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {items.map((i) => (
              <tr key={i.id}>
                <td className="px-5 py-4">
                  <p className="font-medium text-slate-900">{i.title}</p>
                  <p className="mt-0.5 text-xs text-slate-500">
                    by {i.author}
                    {i.coverType && <span className="capitalize"> · {i.coverType}</span>}
                  </p>
                </td>
                <td className="px-5 py-4 text-center text-slate-600">{i.qty}</td>
                <td className="px-5 py-4 text-right font-medium text-slate-900">
                  {formatMoney(i.subtotal)}
                </td>
              </tr>
            ))}
          </tbody>
          <tfoot className="border-t border-slate-200 bg-slate-50">
            <tr>
              <td colSpan={2} className="px-5 py-2.5 text-right text-slate-600">Subtotal</td>
              <td className="px-5 py-2.5 text-right font-medium">{formatMoney(order.subtotal)}</td>
            </tr>
            <tr>
              <td colSpan={2} className="px-5 py-2.5 text-right text-slate-600">Delivery</td>
              <td className="px-5 py-2.5 text-right font-medium">{formatMoney(order.shippingFee)}</td>
            </tr>
            <tr>
              <td colSpan={2} className="px-5 py-3 text-right font-semibold text-slate-900">Total</td>
              <td className="px-5 py-3 text-right text-base font-bold text-slate-900">
                {formatMoney(order.total)}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div className="mt-6 grid gap-5 sm:grid-cols-2">
        <div className="rounded-2xl border border-slate-200 bg-white p-5">
          <h2 className="mb-3 text-sm font-semibold text-slate-900">Delivery address</h2>
          <address className="text-sm not-italic leading-relaxed text-slate-600">
            {order.shipping.name}<br />
            {order.shipping.address}<br />
            {order.shipping.city}, {order.shipping.state} {order.shipping.zip}<br />
            {order.shipping.country}<br />
            <span className="mt-2 block">{order.shipping.phone}</span>
          </address>
        </div>

        {order.notes && (
          <div className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Your notes</h2>
            <p className="whitespace-pre-line text-sm leading-relaxed text-slate-600">{order.notes}</p>
          </div>
        )}
      </div>
    </div>
  );
}
