import { useEffect, useState } from 'react';
import { Link, NavLink, useNavigate, useSearchParams, useLocation } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { api } from '../lib/api';
import { formatMoney } from '../lib/format';

const navLink = ({ isActive }) =>
  `hover:text-brand-700 ${isActive ? 'text-brand-700' : ''}`;

export default function Header() {
  const { user, cartCount, store, categories, setUser, setCartCount, notify } = useApp();
  const [menuOpen, setMenuOpen] = useState(false);
  const [params] = useSearchParams();
  const [term, setTerm] = useState(params.get('q') || '');
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => setMenuOpen(false), [location.pathname]);

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
      <div className="bg-brand-700 text-xs text-white sm:text-sm">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2">
          <p className="truncate">Free delivery on orders over {formatMoney(10000)}</p>
          {store.phone && (
            <a href={`tel:${store.phoneTel}`} className="hidden whitespace-nowrap hover:text-accent-400 sm:inline">
              {store.phone}
            </a>
          )}
        </div>
      </div>

      <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div className="mx-auto max-w-7xl px-4">
          <div className="flex h-16 items-center gap-4 sm:h-20">
            <Link to="/" className="shrink-0">
              <img src="/img/logo.png" alt={store.name} className="h-9 w-auto sm:h-11" />
            </Link>

            <form onSubmit={search} className="hidden max-w-xl flex-1 md:flex">
              <div className="relative w-full">
                <input
                  type="search"
                  value={term}
                  onChange={(e) => setTerm(e.target.value)}
                  placeholder="Search books, authors, ISBN…"
                  className="w-full rounded-full border border-slate-300 bg-slate-50 py-2.5 pl-11 pr-4 text-sm focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100"
                />
                <SearchIcon />
              </div>
            </form>

            <nav className="ml-auto hidden items-center gap-6 text-sm font-medium lg:flex">
              <NavLink to="/shop" className={navLink}>Shop</NavLink>
              <NavLink to="/stationery" className={navLink}>Stationery</NavLink>
              <NavLink to="/blog" className={navLink}>Blog</NavLink>
              <NavLink to="/contact" className={navLink}>Contact</NavLink>
            </nav>

            <div className="ml-auto flex items-center gap-1 sm:gap-2 lg:ml-0">
              {user ? (
                <Link to="/account" className="hidden items-center gap-2 rounded-full px-3 py-2 text-sm hover:bg-slate-100 sm:flex">
                  <UserIcon />
                  <span className="max-w-[8rem] truncate">{user.name.split(' ')[0]}</span>
                </Link>
              ) : (
                <Link to="/login" className="hidden rounded-full px-4 py-2 text-sm font-medium hover:bg-slate-100 sm:inline">
                  Sign in
                </Link>
              )}

              <Link to="/cart" className="relative rounded-full p-2.5 hover:bg-slate-100" aria-label="Cart">
                <CartIcon />
                {cartCount > 0 && (
                  <span className="absolute -right-0.5 -top-0.5 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-accent-500 px-1 text-[11px] font-bold text-white">
                    {cartCount}
                  </span>
                )}
              </Link>

              <button
                onClick={() => setMenuOpen((v) => !v)}
                className="rounded-full p-2.5 hover:bg-slate-100 lg:hidden"
                aria-label="Menu"
                aria-expanded={menuOpen}
              >
                <MenuIcon />
              </button>
            </div>
          </div>

          <div className="pb-3 md:hidden">
            <form onSubmit={search}>
              <div className="relative">
                <input
                  type="search"
                  value={term}
                  onChange={(e) => setTerm(e.target.value)}
                  placeholder="Search books…"
                  className="w-full rounded-full border border-slate-300 bg-slate-50 py-2.5 pl-11 pr-4 text-sm focus:border-brand-500 focus:bg-white focus:outline-none"
                />
                <SearchIcon />
              </div>
            </form>
          </div>
        </div>

        {menuOpen && (
          <div className="border-t border-slate-200 bg-white lg:hidden">
            <nav className="mx-auto grid max-w-7xl gap-1 px-4 py-3 text-sm font-medium">
              <Link to="/shop" className="rounded-lg px-3 py-2.5 hover:bg-slate-100">Shop</Link>
              <Link to="/stationery" className="rounded-lg px-3 py-2.5 hover:bg-slate-100">Stationery</Link>
              <Link to="/blog" className="rounded-lg px-3 py-2.5 hover:bg-slate-100">Blog</Link>
              <Link to="/contact" className="rounded-lg px-3 py-2.5 hover:bg-slate-100">Contact</Link>

              <div className="my-2 border-t border-slate-200" />
              <p className="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                Categories
              </p>
              {categories.map((c) => (
                <Link key={c.slug} to={`/shop?cat=${c.slug}`} className="rounded-lg px-3 py-2.5 hover:bg-slate-100">
                  {c.name}
                </Link>
              ))}

              <div className="my-2 border-t border-slate-200" />
              {user ? (
                <>
                  <Link to="/account" className="rounded-lg px-3 py-2.5 hover:bg-slate-100">My account</Link>
                  <button onClick={signOut} className="rounded-lg px-3 py-2.5 text-left hover:bg-slate-100">
                    Sign out
                  </button>
                </>
              ) : (
                <>
                  <Link to="/login" className="rounded-lg px-3 py-2.5 hover:bg-slate-100">Sign in</Link>
                  <Link to="/register" className="rounded-lg px-3 py-2.5 hover:bg-slate-100">Create account</Link>
                </>
              )}
            </nav>
          </div>
        )}

        <div className="hidden border-t border-slate-200 bg-white lg:block">
          <div className="mx-auto max-w-7xl px-4">
            <nav className="no-scrollbar flex items-center gap-6 overflow-x-auto py-2.5 text-sm text-slate-600">
              <Link to="/shop" className="whitespace-nowrap font-medium hover:text-brand-700">All books</Link>
              {categories.map((c) => (
                <Link key={c.slug} to={`/shop?cat=${c.slug}`} className="whitespace-nowrap hover:text-brand-700">
                  {c.name}
                </Link>
              ))}
            </nav>
          </div>
        </div>
      </header>
    </>
  );
}

const SearchIcon = () => (
  <svg className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
    <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
  </svg>
);

const UserIcon = () => (
  <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
    <circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" />
  </svg>
);

const CartIcon = () => (
  <svg className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
    <path d="M3 3h2l2.4 12.3a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 2-1.6L21 8H6" />
    <circle cx="10" cy="20" r="1.4" /><circle cx="17" cy="20" r="1.4" />
  </svg>
);

const MenuIcon = () => (
  <svg className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
    <path d="M4 7h16M4 12h16M4 17h16" />
  </svg>
);
