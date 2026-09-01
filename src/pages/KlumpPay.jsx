import { useEffect, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Spinner, Alert } from '../components/ui';
import { useFetch } from '../lib/useFetch';

const SDK_URL = 'https://js.useklump.com/klump.js';

function loadKlumpSdk() {
  if (window.Klump) return Promise.resolve();
  return new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src="${SDK_URL}"]`);
    if (existing) {
      existing.addEventListener('load', resolve);
      existing.addEventListener('error', reject);
      return;
    }
    const script = document.createElement('script');
    script.src = SDK_URL;
    script.async = true;
    script.onload = resolve;
    script.onerror = () => reject(new Error('Could not load the Klump checkout.'));
    document.body.appendChild(script);
  });
}

export default function KlumpPay() {
  const [params] = useSearchParams();
  const orderId = params.get('order');
  const { data, loading, error } = useFetch(`/payment/klump/${orderId}`);
  const opened = useRef(false);

  useEffect(() => {
    if (!data || opened.current) return;
    opened.current = true;

    loadKlumpSdk()
      .then(() => {
        // eslint-disable-next-line no-new, new-cap
        new window.Klump({
          publicKey: data.publicKey,
          data: {
            amount: data.order.total,
            currency: data.currency,
            merchant_reference: `klump_${data.order.id}`,
            redirect_url: `${window.location.origin}/api/payment/callback?gateway=klump&order=${data.order.id}`,
            meta_data: { order_id: data.order.id },
            customer: {
              email: data.customer?.email,
              first_name: (data.customer?.name || '').split(' ')[0],
            },
          },
          onSuccess: () => {
            // The server callback verifies the charge and fulfils the order
            // before redirecting on to the result page, so this must not point
            // at the SPA route directly.
            window.location.href = `/api/payment/callback?gateway=klump&order=${data.order.id}`;
          },
          onError: () => {
            window.location.href = `/payment/result?status=failed&order=${data.order.id}`;
          },
        });
      })
      .catch(() => {
        window.location.href = `/payment/result?status=failed&order=${orderId}`;
      });
  }, [data, orderId]);

  if (loading) return <Spinner label="Opening secure checkout…" />;

  if (error) {
    return (
      <div className="mx-auto max-w-lg px-4 py-20">
        <Alert>{error.message}</Alert>
      </div>
    );
  }

  return <Spinner label="Opening secure checkout…" />;
}
