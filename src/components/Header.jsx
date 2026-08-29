import { useEffect, useRef, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { api } from '../lib/api';
import {
  PhoneIcon, FacebookIcon, InstagramIcon, WhatsAppIcon, CartIcon, UserIcon,
  SearchIcon, HomeIcon, BookIcon, PencilIcon, NewspaperIcon, MenuIcon, CloseIcon,
} from './icons';

const NAV = [
  { to: '/', label: 'Home', Icon: HomeIcon },
  { to: '/shop', label: 'Books', Icon: BookIcon },
  { to: '/stationery', label: 'Stationery', Icon: PencilIcon },
  { to: '/blog', label: 'Blog', Icon: NewspaperIcon },
];

export default function Header() {
  const { user, cartCount, store, setUser, setCartCount, notify } = useApp();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);
  const [term, setTerm] = useState('');
  const profileRef = useRef(null);
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    setMobileOpen(false);
    setProfileOpen(false);
  }, [location.pathname, location.search]);

  // Close the account dropdown on an outside click or Escape.
  useEffect(() => {
    if (!profileOpen) return undefined;
    const onClick = (e) => {
      if (profileRef.current && !profileRef.current.contains(e.target)) setProfileOpen(false);
    };
    const onKey = (e) => e.key === 'Escape' && setProfileOpen(false);
    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onKey);
    };
  }, [profileOpen]);

  const whatsappUrl = store.whatsapp ? `https://wa.me/${store.whatsapp}` : '';

  const search = (e) => {
    e.preventDefault();
    navigate(term.trim() ? `/shop?q=${encodeURIComponent(term.trim())}` : '/shop');
  };

  const signOut = async () => {
    await api.post('/auth/logout');
    setUser(null);
    setCartCount(0);
    notify('You have been signed out.');
    navigate('/');
  };

  return (
    <>
      {/* Utility bar — desktop only */}
      <div className="hidden border-b border-slate-200/80 bg-slate-100 text-slate-600 md:block">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-sm sm:px-6 lg:px-8">
          {store.phone ? (
            <a href={`tel:${store.phoneTel}`} className="inline-flex items-center gap-2 hover:text-brand-dark">
              <PhoneIcon className="h-4 w-4 text-brand" />
              <span>{store.phone}</span>
            </a>
          ) : <span />}

          <div className="flex items-center gap-3">
            <span className="text-slate-400">Follow us</span>
            <div className="flex gap-2">
              {store.facebookUrl && (
                <a href={store.facebookUrl} target="_blank" rel="noopener noreferrer" aria-label="Facebook"
                   className="rounded-full p-1 text-slate-500 transition hover:bg-white hover:text-brand">
                  <FacebookIcon />
                </a>
              )}
              {store.instagramUrl && (
                <a href={store.instagramUrl} target="_blank" rel="noopener noreferrer" aria-label="Instagram"
                   className="rounded-full p-1 text-slate-500 transition hover:bg-white hover:text-brand">
                  <InstagramIcon />
                </a>
              )}
              {whatsappUrl && (
                <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"
                   className="rounded-full p-1 text-[#25D366] transition hover:bg-white hover:text-[#20BD5A]">
                  <WhatsAppIcon />
                </a>
              )}
            </div>
          </div>
        </div>
      </div>

      <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">

          <button
            type="button"
            onClick={() => setMobileOpen((v) => !v)}
            aria-expanded={mobileOpen}
            aria-label={mobileOpen ? 'Close menu' : 'Open menu'}
            className="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-600 transition hover:bg-brand-muted hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30 md:hidden"
          >
            {mobileOpen ? <CloseIcon /> : <MenuIcon />}
          </button>

          <Link to="/" className="flex shrink-0 items-center gap-2">
            <img
              src="/img/logo.png"
              alt="Books, Bits &amp; Co"
              className="h-9 w-auto max-w-[140px] object-contain sm:h-10 md:h-12 md:max-w-[180px]"
            />
          </Link>

          <nav className="hidden md:flex md:items-center md:gap-5" aria-label="Primary">
            {NAV.map((item) => (
              <Link key={item.to} to={item.to} className="text-sm font-medium text-slate-700 transition hover:text-brand">
                {item.label}
              </Link>
            ))}
            <Link to="/shop" className="text-sm font-medium text-slate-700 transition hover:text-brand">Shop</Link>
          </nav>

          <div className="flex items-center gap-1">
            <Link to="/cart" aria-label="Shopping cart"
                  className="relative rounded-lg p-1.5 text-slate-600 transition hover:bg-brand-muted hover:text-brand">
              <CartIcon />
              <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white">
                {cartCount}
              </span>
            </Link>

            <div className="relative z-50" ref={profileRef}>
              <button
                type="button"
                onClick={() => setProfileOpen((v) => !v)}
                aria-expanded={profileOpen}
                aria-haspopup="true"
                title="Account"
                className="rounded-lg p-2 text-slate-600 transition hover:bg-brand-muted hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30"
              >
                <UserIcon />
              </button>

              {profileOpen && (
                <div role="menu" className="absolute right-0 top-full mt-2 w-[min(18rem,calc(100vw-2rem))] rounded-xl border border-slate-200 bg-white py-3 shadow-lg ring-1 ring-black/5">
                  {!user ? (
                    <>
                      <div className="px-4 pb-2">
                        <p className="text-sm font-semibold text-slate-900">Welcome</p>
                        <p className="mt-1 text-xs leading-relaxed text-slate-600">
                          Sign in to track orders and checkout faster, or create an account.
                        </p>
                      </div>
                      <div className="mt-1 border-t border-slate-100 px-3 pt-3">
                        <Link to="/login" className="block rounded-lg bg-brand px-3 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark">
                          Sign in
                        </Link>
                        <Link to="/register" className="mt-2 block rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                          Create account
                        </Link>
                      </div>
                    </>
                  ) : (
                    <>
                      <div className="px-4">
                        <p className="truncate text-sm font-semibold text-slate-900">{user.name}</p>
                        <p className="mt-0.5 truncate text-xs text-slate-500">{user.email}</p>
                      </div>
                      <div className="mt-3 border-t border-slate-100 px-4 pt-3">
                        <Link to="/account" className="block rounded-lg bg-brand px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-dark">
                          My account
                        </Link>
                        <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end">
                          <button type="button" onClick={() => setProfileOpen(false)}
                                  className="order-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:order-1 sm:w-auto">
                            Stay signed in
                          </button>
                          <button type="button" onClick={signOut}
                                  className="order-1 w-full rounded-lg bg-slate-900 px-3 py-2 text-center text-sm font-semibold text-white transition hover:bg-slate-800 sm:order-2 sm:w-auto">
                            Sign out
                          </button>
                        </div>
                      </div>
                    </>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Mobile slide-down menu */}
        {mobileOpen && (
          <div className="border-t border-slate-100 bg-white md:hidden">
            <div className="mx-auto max-w-7xl px-4 pb-5 pt-3 sm:px-6">
              <nav className="space-y-1" aria-label="Mobile navigation">
                {NAV.map(({ to, label, Icon }) => (
                  <Link key={to} to={to}
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                    <Icon className="h-5 w-5 text-slate-400" />
                    {label}
                  </Link>
                ))}
                <Link to="/cart" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                  <CartIcon className="h-5 w-5 text-slate-400" />
                  Cart
                  {cartCount > 0 && (
                    <span className="ml-auto rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold text-white">{cartCount}</span>
                  )}
                </Link>
              </nav>

              <div className="mt-4 border-t border-slate-100 pt-4">
                <form onSubmit={search} role="search">
                  <div className="flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <label className="sr-only" htmlFor="q-mobile">Search products</label>
                    <input
                      id="q-mobile"
                      type="search"
                      value={term}
                      onChange={(e) => setTerm(e.target.value)}
                      placeholder="Search products…"
                      className="min-w-0 flex-1 border-0 px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0"
                    />
                    <button type="submit" aria-label="Search"
                            className="inline-flex items-center justify-center bg-brand px-4 text-white transition hover:bg-brand-dark">
                      <SearchIcon />
                    </button>
                  </div>
                </form>
              </div>

              <div className="mt-4 border-t border-slate-100 pt-4">
                {!user ? (
                  <div className="flex gap-2">
                    <Link to="/login" className="flex-1 rounded-lg bg-brand px-3 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark">Sign in</Link>
                    <Link to="/register" className="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Register</Link>
                  </div>
                ) : (
                  <div className="flex items-center justify-between gap-3">
                    <Link to="/account" className="min-w-0">
                      <p className="truncate text-sm font-semibold text-slate-900">{user.name}</p>
                      <p className="truncate text-xs text-slate-500">{user.email}</p>
                    </Link>
                    <button onClick={signOut} className="shrink-0 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                      Sign out
                    </button>
                  </div>
                )}
              </div>

              {store.phone && (
                <div className="mt-4 border-t border-slate-100 pt-4">
                  <a href={`tel:${store.phoneTel}`} className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-brand-muted hover:text-brand">
                    <PhoneIcon className="h-5 w-5 text-brand" />
                    {store.phone}
                  </a>
                </div>
              )}

              <div className="mt-4 flex items-center justify-center gap-4 border-t border-slate-100 pt-4">
                {store.facebookUrl && (
                  <a href={store.facebookUrl} target="_blank" rel="noopener noreferrer" aria-label="Facebook"
                     className="rounded-full p-2 text-slate-400 transition hover:bg-brand-muted hover:text-brand"><FacebookIcon className="h-5 w-5" /></a>
                )}
                {store.instagramUrl && (
                  <a href={store.instagramUrl} target="_blank" rel="noopener noreferrer" aria-label="Instagram"
                     className="rounded-full p-2 text-slate-400 transition hover:bg-brand-muted hover:text-brand"><InstagramIcon className="h-5 w-5" /></a>
                )}
                {whatsappUrl && (
                  <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"
                     className="rounded-full p-2 text-[#25D366] transition hover:bg-emerald-50 hover:text-[#20BD5A]"><WhatsAppIcon className="h-5 w-5" /></a>
                )}
              </div>
            </div>
          </div>
        )}
      </header>
    </>
  );
}
