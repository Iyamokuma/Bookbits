import { useEffect, useState } from 'react';
import { Spinner, Alert, Field, textareaClass } from '../../components/ui';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { formatMoney } from '../../lib/format';

export default function Settings() {
  const { notify, store } = useApp();
  const [settings, setSettings] = useState(null);
  const [form, setForm] = useState({ shippingFee: '', notificationEmails: '' });
  const [loadError, setLoadError] = useState(null);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState(null);

  const apply = (s) => {
    setSettings(s);
    setForm({ shippingFee: String(s.shippingFee), notificationEmails: s.notificationEmails.join('\n') });
  };

  useEffect(() => {
    api.get('/admin/settings').then(apply).catch((err) => setLoadError(err.message));
  }, []);

  const save = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    setTestResult(null);
    try {
      const res = await api.post('/admin/settings', form);
      apply(res);
      notify(res.message);
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  };

  const sendTest = async () => {
    setTesting(true);
    setTestResult(null);
    try {
      const res = await api.post('/admin/settings/test-email');
      setTestResult({ tone: 'success', message: res.message });
    } catch (err) {
      setTestResult({ tone: 'error', message: err.message });
    } finally {
      setTesting(false);
    }
  };

  if (loadError) return <Alert>{loadError}</Alert>;
  if (!settings) return <Spinner />;

  const savedEmails = settings.notificationEmails.join('\n');
  const emailsChanged = form.notificationEmails.trim() !== savedEmails;
  const fee = Number(String(form.shippingFee).replace(/[,\s]/g, ''));

  return (
    <div className="max-w-3xl space-y-6">
      <div>
        <h1 className="font-display text-2xl text-slate-900">Settings</h1>
        <p className="mt-1 text-sm text-slate-500">Delivery charges and where the shop's emails go.</p>
      </div>

      {error && <Alert>{error}</Alert>}

      <form onSubmit={save} className="space-y-5">
        <section className="rounded-2xl border border-slate-200 bg-white p-5">
          <h2 className="text-sm font-semibold text-slate-900">Delivery fee</h2>
          <p className="mt-1 text-xs text-slate-500">
            Added to every order at checkout. Orders already placed keep the fee they were charged.
          </p>

          <div className="mt-4 max-w-xs">
            <label className="block text-xs font-medium text-slate-600" htmlFor="shipping-fee">
              Fee per order
            </label>
            <div className="mt-1.5 flex h-11 overflow-hidden rounded-lg border border-slate-300 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-100">
              <span className="flex items-center border-r border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">
                {store.currencySymbol}
              </span>
              <input
                id="shipping-fee"
                inputMode="decimal"
                className="min-w-0 flex-1 border-0 px-3 text-sm focus:outline-none focus:ring-0"
                value={form.shippingFee}
                onChange={(e) => setForm((f) => ({ ...f, shippingFee: e.target.value }))}
              />
            </div>
            <p className="mt-1 text-[11px] text-slate-400">
              {Number.isFinite(fee) && fee >= 0
                ? fee === 0
                  ? 'Delivery will be free.'
                  : `Shoppers will see “Delivery ${formatMoney(fee)}” at checkout.`
                : 'Enter a number, e.g. 1500. Use 0 for free delivery.'}
            </p>
          </div>
        </section>

        <section className="rounded-2xl border border-slate-200 bg-white p-5">
          <h2 className="text-sm font-semibold text-slate-900">Order notifications</h2>
          <p className="mt-1 text-xs text-slate-500">
            Every paid order is emailed here, along with contact-form messages. When customers reply
            to their order emails, the reply comes here too. Customers also get their own
            confirmation email automatically.
          </p>

          <div className="mt-4">
            <Field label="Send to" hint="One address per line, up to 5.">
              <textarea
                rows={3}
                className={`${textareaClass} font-mono`}
                value={form.notificationEmails}
                onChange={(e) => setForm((f) => ({ ...f, notificationEmails: e.target.value }))}
              />
            </Field>
          </div>

          {settings.undeliverable.length > 0 && (
            <div className="mt-4">
              <Alert tone="warning">
                <strong>
                  {settings.undeliverable.map((d) => `@${d}`).join(', ')} can't receive email.
                </strong>{' '}
                {settings.undeliverable.length === 1 ? 'That domain has' : 'Those domains have'} no
                mail server set up, so anything sent there bounces. Use an inbox
                you can actually open, such as a Gmail address or one on a domain with email hosting.
              </Alert>
            </div>
          )}

          {!settings.mailConfigured && (
            <div className="mt-4">
              <Alert tone="warning">
                Email sending isn't configured on the server (RESEND_API_KEY is missing), so no emails
                will go out until it is set.
              </Alert>
            </div>
          )}

          <div className="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
            <button
              type="button"
              onClick={sendTest}
              disabled={testing || emailsChanged || !settings.mailConfigured}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            >
              {testing ? 'Sending…' : 'Send a test email'}
            </button>
            <span className="text-[11px] text-slate-400">
              {emailsChanged
                ? 'Save your changes first, then send a test.'
                : `Sent from ${settings.fromEmail}.`}
            </span>
          </div>
          {testResult && (
            <div className="mt-3">
              <Alert tone={testResult.tone}>{testResult.message}</Alert>
            </div>
          )}
        </section>

        <button
          type="submit"
          disabled={busy}
          className="h-11 rounded-lg bg-brand-700 px-8 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
        >
          {busy ? 'Saving…' : 'Save settings'}
        </button>
      </form>
    </div>
  );
}
