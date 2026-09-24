import { useEffect, useRef } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { api } from '../lib/api';
import { purchaseEventId, trackMeta } from '../lib/metaPixel';

export default function PaymentResult() {
  const [params] = useSearchParams();
  const { loadSession, store } = useApp();
  const purchaseTracked = useRef(false);

  const success = params.get('status') === 'success';
  const orderId = params.get('order');

  // The cart was emptied server-side during fulfilment; refresh the badge.
  useEffect(() => {
    loadSession().catch(() => {});
  }, [loadSession]);

  // Browser Purchase (deduplicated with server CAPI via eventID).
  useEffect(() => {
    if (!success || !orderId || purchaseTracked.current) return;
    purchaseTracked.current = true;

    (async () => {
      try {
        const detail = await api.get(`/orders/${orderId}`);
        const order = detail.order;
        const items = detail.items || [];
        trackMeta(
          'Purchase',
          {
            currency: store.currencyCode,
            value: order.total,
            num_items: items.reduce((n, i) => n + i.qty, 0),
            content_ids: items
              .map((i) => (i.bookId != null ? String(i.bookId) : null))
              .filter(Boolean),
            content_type: 'product',
          },
          purchaseEventId(order.id)
        );
      } catch {
        trackMeta(
          'Purchase',
          { currency: store.currencyCode },
          purchaseEventId(orderId)
        );
      }
    })();
  }, [success, orderId, store.currencyCode]);

  return (
    <div className="mx-auto max-w-lg px-4 py-20 text-center">
      <div
        className={`mx-auto grid h-16 w-16 place-items-center rounded-full ${
          success ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'
        }`}
      >
        {success ? (
          <svg className="h-8 w-8" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
            <path d="m5 13 4 4L19 7" />
          </svg>
        ) : (
          <svg className="h-8 w-8" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        )}
      </div>

      <h1 className="mt-6 font-display text-2xl text-slate-900">
        {success ? 'Payment received — thank you!' : 'That payment did not go through'}
      </h1>

      <p className="mt-3 text-sm leading-relaxed text-slate-600">
        {success ? (
          <>
            Your order {orderId && <span className="font-semibold">#{orderId}</span>} is confirmed and
            we've sent a receipt to your email. We'll be in touch as soon as it ships.
          </>
        ) : (
          <>
            No money has left your account. You can try again from your order page, use a different
            payment method, or contact us and we'll help you complete it.
          </>
        )}
      </p>

      <div className="mt-8 flex flex-wrap justify-center gap-3">
        {orderId ? (
          <Link
            to={`/orders/${orderId}`}
            className="rounded-full bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
          >
            View your order
          </Link>
        ) : (
          <Link
            to="/account"
            className="rounded-full bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
          >
            Go to my account
          </Link>
        )}
        <Link
          to="/shop"
          className="rounded-full border border-slate-300 px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
          Continue shopping
        </Link>
      </div>
    </div>
  );
}
