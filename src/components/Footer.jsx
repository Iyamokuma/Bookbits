import { Link } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { FacebookIcon, InstagramIcon, WhatsAppIcon } from './icons';

const SHOP = [
  { to: '/shop', label: 'All books' },
  { to: '/stationery', label: 'Stationery' },
  { to: '/blog', label: 'Blog' },
  { to: '/cart', label: 'Cart' },
];

const HELP = [
  { to: '/shipping', label: 'Shipping & returns' },
  { to: '/privacy', label: 'Privacy policy' },
  { to: '/terms', label: 'Terms of use' },
];

export default function Footer() {
  const { store, user } = useApp();
  const whatsappUrl = store.whatsapp ? `https://wa.me/${store.whatsapp}` : '';

  return (
    <>
      <footer className="border-t border-slate-200 bg-slate-900 text-slate-300">
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-14">
          <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-12">
            <div className="sm:col-span-2 lg:col-span-1">
              <img src="/img/logo.png" alt={store.name} className="h-14 w-auto rounded-lg bg-white p-1.5" />
              <p className="mt-4 max-w-sm text-sm leading-relaxed text-slate-400">
                Curated books delivered with care. Discover fiction, non-fiction, children’s reads, and more.
              </p>

              {(store.facebookUrl || store.instagramUrl) && (
                <div className="mt-4 flex flex-wrap gap-3">
                  {store.facebookUrl && (
                    <a href={store.facebookUrl} target="_blank" rel="noopener noreferrer"
                       className="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                      <FacebookIcon className="h-5 w-5" /> Facebook
                    </a>
                  )}
                  {store.instagramUrl && (
                    <a href={store.instagramUrl} target="_blank" rel="noopener noreferrer"
                       className="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                      <InstagramIcon className="h-5 w-5" /> Instagram
                    </a>
                  )}
                </div>
              )}

              {whatsappUrl && (
                <a href={whatsappUrl} target="_blank" rel="noopener noreferrer"
                   className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-400 transition hover:text-emerald-300">
                  <WhatsAppIcon className="h-5 w-5 shrink-0" /> Message us on WhatsApp
                </a>
              )}
            </div>

            <div>
              <h2 className="text-xs font-bold uppercase tracking-wider text-slate-500">Shop</h2>
              <ul className="mt-4 space-y-3 text-sm">
                {SHOP.map((l) => (
                  <li key={l.to}><Link to={l.to} className="text-slate-300 transition hover:text-white">{l.label}</Link></li>
                ))}
              </ul>
            </div>

            <div>
              <h2 className="text-xs font-bold uppercase tracking-wider text-slate-500">Account</h2>
              <ul className="mt-4 space-y-3 text-sm">
                {user ? (
                  <li><Link to="/account" className="text-slate-300 transition hover:text-white">My orders</Link></li>
                ) : (
                  <>
                    <li><Link to="/login" className="text-slate-300 transition hover:text-white">Sign in</Link></li>
                    <li><Link to="/register" className="text-slate-300 transition hover:text-white">Register</Link></li>
                  </>
                )}
              </ul>
            </div>

            <div>
              <h2 className="text-xs font-bold uppercase tracking-wider text-slate-500">Help</h2>
              <ul className="mt-4 space-y-3 text-sm">
                {store.phone && (
                  <li><a href={`tel:${store.phoneTel}`} className="text-slate-300 transition hover:text-white">{store.phone}</a></li>
                )}
                <li><Link to="/contact" className="text-slate-300 transition hover:text-white">Contact us</Link></li>
                {HELP.map((l) => (
                  <li key={l.to}><Link to={l.to} className="text-slate-300 transition hover:text-white">{l.label}</Link></li>
                ))}
              </ul>
            </div>
          </div>

          <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-800 pt-8 sm:flex-row">
            <p className="text-center text-sm text-slate-500 sm:text-left">
              &copy; {new Date().getFullYear()} {store.name}. All rights reserved.
            </p>
            <nav className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-500" aria-label="Footer shortcuts">
              <Link to="/" className="transition hover:text-slate-300">Home</Link>
              <Link to="/shop" className="transition hover:text-slate-300">Shop</Link>
              <Link to="/blog" className="transition hover:text-slate-300">Blog</Link>
            </nav>
          </div>
        </div>
      </footer>

      {whatsappUrl && (
        <a
          href={whatsappUrl}
          target="_blank"
          rel="noopener noreferrer"
          aria-label="Chat with us on WhatsApp"
          className="fixed bottom-5 right-5 z-[60] flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-emerald-900/30 ring-2 ring-white transition hover:scale-105 hover:bg-[#20BD5A] focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2"
        >
          <WhatsAppIcon className="h-8 w-8" />
        </a>
      )}
    </>
  );
}
