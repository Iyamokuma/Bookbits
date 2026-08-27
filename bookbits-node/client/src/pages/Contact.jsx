import { useApp } from '../context/AppContext';

export default function Contact() {
  const { store } = useApp();

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
