import { Link } from 'react-router-dom';
import { useApp } from '../context/AppContext';

export default function Footer() {
  const { store, categories } = useApp();

  return (
    <footer className="mt-16 bg-brand-900 text-slate-300">
      <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <img src="/img/logo.png" alt={store.name} className="mb-4 h-10 w-auto brightness-0 invert" />
          <p className="text-sm leading-relaxed text-slate-400">
            A Nigerian online bookstore making quality books accessible and rebuilding the culture
            of reading.
          </p>
          <div className="mt-5 flex items-center gap-3">
            <Social href={store.instagramUrl} label="Instagram">
              <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                <rect x="2.5" y="2.5" width="19" height="19" rx="5.5" />
                <circle cx="12" cy="12" r="4.2" />
                <circle cx="17.6" cy="6.4" r="1.1" fill="currentColor" stroke="none" />
              </svg>
            </Social>
            <Social href={store.facebookUrl} label="Facebook">
              <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.25-1.5 1.55-1.5h1.65V4.6c-.29-.04-1.27-.12-2.41-.12-2.38 0-4.01 1.45-4.01 4.13v2.29H7.5V14h2.78v8h3.22z" />
              </svg>
            </Social>
            {store.whatsapp && (
              <Social href={`https://wa.me/${store.whatsapp}`} label="WhatsApp">
                <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.04 2C6.6 2 2.2 6.4 2.2 11.84c0 1.74.46 3.44 1.32 4.94L2 22l5.36-1.4a9.8 9.8 0 0 0 4.68 1.19c5.43 0 9.84-4.4 9.84-9.84S17.47 2 12.04 2z" />
                </svg>
              </Social>
            )}
          </div>
        </div>

        <FooterColumn title="Shop">
          <FooterLink to="/shop">All books</FooterLink>
          <FooterLink to="/stationery">Stationery</FooterLink>
          {categories.slice(0, 4).map((c) => (
            <FooterLink key={c.slug} to={`/shop?cat=${c.slug}`}>{c.name}</FooterLink>
          ))}
        </FooterColumn>

        <FooterColumn title="Help">
          <FooterLink to="/shipping">Shipping &amp; returns</FooterLink>
          <FooterLink to="/privacy">Privacy policy</FooterLink>
          <FooterLink to="/terms">Terms of use</FooterLink>
          <FooterLink to="/contact">Contact us</FooterLink>
        </FooterColumn>

        <FooterColumn title="Get in touch">
          {store.phone && (
            <li><a href={`tel:${store.phoneTel}`} className="hover:text-white">{store.phone}</a></li>
          )}
          {store.email && (
            <li><a href={`mailto:${store.email}`} className="break-all hover:text-white">{store.email}</a></li>
          )}
          <li className="pt-1 text-slate-400">Mon–Sat, 9:00 AM – 6:00 PM (WAT)</li>
        </FooterColumn>
      </div>

      <div className="border-t border-white/10">
        <div className="mx-auto max-w-7xl px-4 py-5 text-center text-xs text-slate-400">
          © {new Date().getFullYear()} {store.name}. All rights reserved.
        </div>
      </div>
    </footer>
  );
}

const Social = ({ href, label, children }) => (
  <a
    href={href || '#'}
    target="_blank"
    rel="noopener noreferrer"
    aria-label={label}
    className="rounded-full bg-white/10 p-2 hover:bg-white/20"
  >
    {children}
  </a>
);

const FooterColumn = ({ title, children }) => (
  <div>
    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-white">{title}</h3>
    <ul className="space-y-2.5 text-sm">{children}</ul>
  </div>
);

const FooterLink = ({ to, children }) => (
  <li>
    <Link to={to} className="hover:text-white">{children}</Link>
  </li>
);
