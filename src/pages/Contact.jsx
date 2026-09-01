import { useState } from 'react';
import { useApp } from '../context/AppContext';
import { api, ApiError } from '../lib/api';
import { Alert, Field, inputClass, textareaClass } from '../components/ui';

const BLANK = { name: '', email: '', phone: '', subject: '', message: '' };

export default function Contact() {
  const { store, user, notify } = useApp();

  const [form, setForm] = useState({ ...BLANK, name: user?.name || '', email: user?.email || '' });
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState(null);
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(null);

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }));

  const submit = async (e) => {
    e.preventDefault();
    setSending(true);
    setErrors({});
    setFormError(null);
    try {
      const res = await api.post('/contact', form);
      setSent(res.message);
      setForm({ ...BLANK, name: user?.name || '', email: user?.email || '' });
      notify(res.message);
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.details || {});
      else setFormError(err.message);
    } finally {
      setSending(false);
    }
  };

  const channels = [
    store.whatsapp && {
      label: 'WhatsApp',
      value: store.phone,
      href: `https://wa.me/${store.whatsapp}`,
      note: 'Fastest way to reach us',
    },
    store.phone && { label: 'Call us', value: store.phone, href: `tel:${store.phoneTel}` },
    store.email && { label: 'Email', value: store.email, href: `mailto:${store.email}` },
    { label: 'Instagram', value: '@booksbitsandco', href: store.instagramUrl },
  ].filter(Boolean);

  return (
    <div className="mx-auto max-w-3xl px-4 py-14">
      <h1 className="font-display text-3xl text-slate-900">Get in touch</h1>
      <p className="mt-2 text-sm text-slate-500">
        Questions about an order, a book, or a return? We'd love to hear from you.
      </p>

      <div className="mt-8 grid gap-4 sm:grid-cols-2">
        {channels.map((c) => (
          <a
            key={c.label}
            href={c.href}
            target={c.href.startsWith('http') ? '_blank' : undefined}
            rel="noopener noreferrer"
            className="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-md"
          >
            <p className="text-xs font-semibold uppercase tracking-wide text-brand-600">{c.label}</p>
            <p className="mt-1.5 break-all font-medium text-slate-900">{c.value}</p>
            {c.note && <p className="mt-1 text-xs text-slate-500">{c.note}</p>}
          </a>
        ))}
      </div>

      <form onSubmit={submit} className="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
        <h2 className="text-sm font-semibold text-slate-900">Send us a message</h2>
        <p className="mt-1 text-xs text-slate-500">We reply within one business day.</p>

        {sent && <div className="mt-4"><Alert tone="success">{sent}</Alert></div>}
        {formError && <div className="mt-4"><Alert>{formError}</Alert></div>}

        <div className="mt-5 space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Your name" error={errors.name} required>
              <input required className={inputClass} value={form.name} onChange={set('name')} autoComplete="name" />
            </Field>
            <Field label="Email address" error={errors.email} required>
              <input required type="email" className={inputClass} value={form.email} onChange={set('email')} autoComplete="email" />
            </Field>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Phone" hint="Optional">
              <input className={inputClass} value={form.phone} onChange={set('phone')} autoComplete="tel" />
            </Field>
            <Field label="Subject" error={errors.subject} required>
              <input required className={inputClass} value={form.subject} onChange={set('subject')} />
            </Field>
          </div>

          <Field label="Message" error={errors.message} required>
            <textarea
              required
              rows={5}
              className={textareaClass}
              value={form.message}
              onChange={set('message')}
              placeholder="How can we help?"
            />
          </Field>
        </div>

        <button
          type="submit"
          disabled={sending}
          className="mt-5 h-11 rounded-lg bg-brand-700 px-7 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:opacity-60"
        >
          {sending ? 'Sending…' : 'Send message'}
        </button>
      </form>

      <div className="mt-8 rounded-2xl bg-brand-50 p-6">
        <h2 className="text-sm font-semibold text-slate-900">Customer service hours</h2>
        <p className="mt-1.5 text-sm text-slate-600">
          Monday – Saturday, 9:00 AM – 6:00 PM (WAT). Messages received outside these hours are
          answered the next working day.
        </p>
      </div>
    </div>
  );
}
