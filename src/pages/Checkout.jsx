import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Spinner, EmptyState, Alert, Field, inputClass, textareaClass } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { useApp } from '../context/AppContext';
import { api, ApiError } from '../lib/api';
import { formatMoney } from '../lib/format';
import { trackMeta } from '../lib/metaPixel';

const NIGERIAN_STATES = [
  'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
  'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT - Abuja', 'Gombe',
  'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos',
  'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto',
  'Taraba', 'Yobe', 'Zamfara',
];

export default function Checkout() {
  const { user, notify, store } = useApp();
  const { data, loading, error } = useFetch('/checkout');
  const navigate = useNavigate();

  const [form, setForm] = useState({
    name: user?.name || '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    country: 'Nigeria',
  });
  const [gateway, setGateway] = useState('paystack');
  const [notes, setNotes] = useState('');
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  // Which gateways are live depends on server configuration, so fall back to
  // the first one actually on offer rather than assuming Paystack.
  const gateways = data?.gateways;
  useEffect(() => {
    if (gateways?.length && !gateways.some((g) => g.id === gateway)) {
      setGateway(gateways[0].id);
    }
  }, [gateways, gateway]);

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }));

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    setFormError(null);

    try {
      trackMeta('InitiateCheckout', {
        currency: store.currencyCode,
        value: data?.total,
        num_items: data?.itemCount,
      });
      const res = await api.post('/checkout', { shipping: form, gateway, notes });
      // Hand off to the payment gateway, which will send the customer back to
      // /payment/callback when they are done.
      window.location.href = res.redirectUrl;
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) {
        setErrors(err.details);
        setFormError('Please correct the highlighted fields.');
      } else {
        setFormError(err.message);
      }
      setSubmitting(false);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  if (loading) return <Spinner />;
  if (error) return <div className="mx-auto max-w-3xl px-4 py-16"><Alert>{error.message}</Alert></div>;

  if (data.itemCount === 0) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <EmptyState
          title="Your cart is empty"
          message="Add something to your cart before checking out."
          actionLabel="Start shopping"
          actionTo="/shop"
        />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl px-4 py-10">
      <h1 className="mb-2 font-display text-3xl text-slate-900">Checkout</h1>
      <p className="mb-8 text-sm text-slate-500">
        We need a complete delivery address so our courier can reach you.
      </p>

      {formError && <div className="mb-6"><Alert>{formError}</Alert></div>}

      <form onSubmit={submit} className="grid gap-8 lg:grid-cols-[1fr_22rem]">
        <div className="space-y-6">
          <section className="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 className="mb-5 text-sm font-semibold text-slate-900">Delivery address</h2>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <Field label="Full name of recipient" error={errors.name} required>
                  <input className={inputClass} value={form.name} onChange={set('name')} autoComplete="name" />
                </Field>
              </div>

              <Field label="Phone number" error={errors.phone} hint="We use this to arrange delivery." required>
                <input className={inputClass} value={form.phone} onChange={set('phone')} inputMode="tel" autoComplete="tel" placeholder="0801 234 5678" />
              </Field>

              <Field label="Country" error={errors.country} required>
                <input className={inputClass} value={form.country} onChange={set('country')} autoComplete="country-name" />
              </Field>

              <div className="sm:col-span-2">
                <Field
                  label="Street address"
                  error={errors.address}
                  hint="House number, street name, and a landmark if it helps."
                  required
                >
                  <textarea rows={2} className={textareaClass} value={form.address} onChange={set('address')} autoComplete="street-address" />
                </Field>
              </div>

              <Field label="City / town" error={errors.city} required>
                <input className={inputClass} value={form.city} onChange={set('city')} autoComplete="address-level2" />
              </Field>

              <Field label="State" error={errors.state} required>
                <select className={inputClass} value={form.state} onChange={set('state')} autoComplete="address-level1">
                  <option value="">Select a state…</option>
                  {NIGERIAN_STATES.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
              </Field>

              <Field label="Postal code" error={errors.zip} hint="Use 000000 if you don't have one." required>
                <input className={inputClass} value={form.zip} onChange={set('zip')} autoComplete="postal-code" />
              </Field>
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 className="mb-4 text-sm font-semibold text-slate-900">Payment method</h2>
            {errors.gateway && <p className="mb-3 text-xs text-rose-600">{errors.gateway}</p>}
            {data.gateways.length === 0 && (
              <Alert>
                Online payment is temporarily unavailable. Please contact us and we will help you
                complete this order.
              </Alert>
            )}
            <div className="space-y-2.5">
              {data.gateways.map((g) => (
                <label
                  key={g.id}
                  className={`flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition ${
                    gateway === g.id ? 'border-brand-600 bg-brand-50' : 'border-slate-300 hover:border-slate-400'
                  }`}
                >
                  <input
                    type="radio"
                    name="gateway"
                    value={g.id}
                    checked={gateway === g.id}
                    onChange={() => setGateway(g.id)}
                    className="h-4 w-4 accent-brand-700"
                  />
                  <span className="text-sm font-medium text-slate-900">{g.label}</span>
                </label>
              ))}
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-6">
            <Field label="Delivery notes (optional)">
              <textarea
                rows={3}
                className={textareaClass}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Anything the courier should know?"
              />
            </Field>
          </section>
        </div>

        <aside className="h-fit rounded-2xl border border-slate-200 bg-white p-6 lg:sticky lg:top-28">
          <h2 className="mb-4 text-sm font-semibold text-slate-900">Order summary</h2>
          {errors.cart && <div className="mb-4"><Alert>{errors.cart}</Alert></div>}

          <dl className="space-y-3 border-b border-slate-200 pb-4 text-sm">
            <Row label={`Items (${data.itemCount})`} value={formatMoney(data.subtotal)} />
            <Row label="Delivery" value={formatMoney(data.shippingFee)} />
          </dl>
          <div className="flex justify-between pt-4">
            <span className="text-sm font-semibold text-slate-900">Total</span>
            <span className="text-lg font-bold text-slate-900">{formatMoney(data.total)}</span>
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="mt-6 h-12 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
          >
            {submitting ? 'Starting payment…' : `Pay ${formatMoney(data.total)}`}
          </button>
          <p className="mt-3 text-center text-[11px] text-slate-400">
            You'll be taken to a secure page to complete your payment.
          </p>
        </aside>
      </form>
    </div>
  );
}

const Row = ({ label, value }) => (
  <div className="flex justify-between">
    <dt className="text-slate-600">{label}</dt>
    <dd className="font-medium text-slate-900">{value}</dd>
  </div>
);
