import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner, Alert, StatusBadge, Field, inputClass, textareaClass } from '../../components/ui';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { formatMoney, formatDateTime } from '../../lib/format';

export default function AdminOrderDetail() {
  const { id } = useParams();
  const { notify } = useApp();
  const { data, loading, error, reload } = useFetch(`/admin/orders/${id}`);

  const [form, setForm] = useState({ status: '', trackingNumber: '', recipient: '', subject: '', message: '' });
  const [busy, setBusy] = useState(false);
  const [formError, setFormError] = useState(null);

  // Seed the form once the order arrives, and again after a successful save.
  useEffect(() => {
    if (!data) return;
    setForm({
      status: data.order.status,
      trackingNumber: data.order.trackingNumber || '',
      recipient: data.customer?.email || '',
      subject: '',
      message: '',
    });
  }, [data]);

  if (loading) return <Spinner />;
  if (error) return <Alert>{error.message}</Alert>;

  const { order, items, customer, payment, statuses } = data;
  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }));

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setFormError(null);
    try {
      const res = await api.post(`/admin/orders/${id}/update`, form);
      notify(res.message);
      reload();
    } catch (err) {
      setFormError(err.message);
    } finally {
      setBusy(false);
    }
  };

  const statusChanged = form.status !== order.status;

  return (
    <div className="space-y-6">
      <Link to="/admin/orders" className="text-sm font-medium text-brand-700 hover:underline">
        ← All orders
      </Link>

      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl text-slate-900">Order #{order.id}</h1>
          <p className="mt-1 text-sm text-slate-500">Placed {formatDateTime(order.createdAt)}</p>
        </div>
        <StatusBadge status={order.status} />
      </div>

      <div className="grid gap-5 lg:grid-cols-[1fr_22rem]">
        <div className="space-y-5">
          <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <h2 className="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">
              Items
            </h2>
            <table className="w-full text-sm">
              <tbody className="divide-y divide-slate-100">
                {items.map((i) => (
                  <tr key={i.id}>
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-slate-900">{i.title}</p>
                      <p className="text-xs text-slate-500">
                        by {i.author}
                        {i.coverType && <span className="capitalize"> · {i.coverType}</span>}
                      </p>
                    </td>
                    <td className="px-5 py-3.5 text-center text-slate-600">×{i.qty}</td>
                    <td className="px-5 py-3.5 text-right font-medium">{formatMoney(i.subtotal)}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="border-t border-slate-200 bg-slate-50">
                <tr>
                  <td colSpan={2} className="px-5 py-2 text-right text-slate-600">Subtotal</td>
                  <td className="px-5 py-2 text-right">{formatMoney(order.subtotal)}</td>
                </tr>
                <tr>
                  <td colSpan={2} className="px-5 py-2 text-right text-slate-600">Delivery</td>
                  <td className="px-5 py-2 text-right">{formatMoney(order.shippingFee)}</td>
                </tr>
                <tr>
                  <td colSpan={2} className="px-5 py-3 text-right font-semibold">Total</td>
                  <td className="px-5 py-3 text-right text-base font-bold">{formatMoney(order.total)}</td>
                </tr>
              </tfoot>
            </table>
          </section>

          <form onSubmit={submit} className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="text-sm font-semibold text-slate-900">Update &amp; notify the customer</h2>
            <p className="mt-1 text-xs text-slate-500">
              Changing the status to processing, shipped, or delivered sends the matching email
              automatically. Add a message below to send your own note as well.
            </p>

            {formError && <div className="mt-4"><Alert>{formError}</Alert></div>}

            <div className="mt-5 grid gap-4 sm:grid-cols-2">
              <Field label="Order status">
                <select className={inputClass} value={form.status} onChange={set('status')}>
                  {statuses.map((s) => (
                    <option key={s} value={s} className="capitalize">{s}</option>
                  ))}
                </select>
              </Field>

              <Field label="Tracking number" hint="Included in the shipped email.">
                <input className={inputClass} value={form.trackingNumber} onChange={set('trackingNumber')} />
              </Field>

              <div className="sm:col-span-2">
                <Field label="Send to" hint="Defaults to the customer's account email.">
                  <input type="email" className={inputClass} value={form.recipient} onChange={set('recipient')} />
                </Field>
              </div>

              <div className="sm:col-span-2">
                <Field label="Subject (optional)">
                  <input
                    className={inputClass}
                    value={form.subject}
                    onChange={set('subject')}
                    placeholder={`Update on your order #${order.id}`}
                  />
                </Field>
              </div>

              <div className="sm:col-span-2">
                <Field label="Your message (optional)">
                  <textarea
                    rows={5}
                    className={textareaClass}
                    value={form.message}
                    onChange={set('message')}
                    placeholder="Write a note to the customer…"
                  />
                </Field>
              </div>
            </div>

            <button
              type="submit"
              disabled={busy || (!statusChanged && !form.message.trim() && form.trackingNumber === (order.trackingNumber || ''))}
              className="mt-5 h-11 rounded-lg bg-brand-700 px-7 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-50"
            >
              {busy ? 'Saving…' : 'Save & send'}
            </button>
          </form>
        </div>

        <aside className="space-y-5">
          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Customer</h2>
            <p className="text-sm font-medium text-slate-800">{customer?.name}</p>
            <a href={`mailto:${customer?.email}`} className="break-all text-sm text-brand-700 hover:underline">
              {customer?.email}
            </a>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Delivery address</h2>
            <address className="text-sm not-italic leading-relaxed text-slate-600">
              {order.shipping.name}<br />
              {order.shipping.address}<br />
              {order.shipping.city}, {order.shipping.state} {order.shipping.zip}<br />
              {order.shipping.country}
            </address>
            <a href={`tel:${order.shipping.phone}`} className="mt-2 block text-sm text-brand-700 hover:underline">
              {order.shipping.phone}
            </a>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Payment</h2>
            {payment ? (
              <dl className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <dt className="text-slate-500">Status</dt>
                  <dd><StatusBadge status={payment.status} /></dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-slate-500">Method</dt>
                  <dd className="capitalize text-slate-700">{payment.method}</dd>
                </div>
                {payment.transactionRef && (
                  <div>
                    <dt className="text-slate-500">Reference</dt>
                    <dd className="mt-0.5 break-all font-mono text-xs text-slate-700">
                      {payment.transactionRef}
                    </dd>
                  </div>
                )}
              </dl>
            ) : (
              <p className="text-sm text-slate-500">No payment recorded yet.</p>
            )}
          </section>

          {order.notes && (
            <section className="rounded-2xl border border-slate-200 bg-white p-5">
              <h2 className="mb-2 text-sm font-semibold text-slate-900">Customer notes</h2>
              <p className="whitespace-pre-line text-sm leading-relaxed text-slate-600">{order.notes}</p>
            </section>
          )}
        </aside>
      </div>
    </div>
  );
}
