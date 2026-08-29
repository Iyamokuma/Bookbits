import { useState } from 'react';
import { NavLink, Navigate, Outlet, useNavigate, useLocation } from 'react-router-dom';
import { useApp } from '../../context/AppContext';
import { api } from '../../lib/api';
import { Spinner } from '../../components/ui';

const NAV = [
  { to: '/admin', end: true, label: 'Dashboard', icon: 'M4 13h6V4H4v9zm0 7h6v-5H4v5zm10 0h6V11h-6v9zm0-16v5h6V4h-6z' },
  { to: '/admin/orders', label: 'Orders', icon: 'M6 2h9l5 5v15H6zM15 2v5h5' },
  { to: '/admin/products', label: 'Products', icon: 'M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z' },
  { to: '/admin/categories', label: 'Categories', icon: 'M4 6h16M4 12h16M4 18h16' },
  { to: '/admin/blogs', label: 'Blog', icon: 'M4 4h16v16H4zM8 9h8M8 13h8M8 17h4' },
];

export default function AdminLayout() {
  const { isAdmin, setIsAdmin, ready, notify } = useApp();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  if (!ready) return <Spinner />;
  if (!isAdmin) return <Navigate to="/admin/login" state={{ from: location.pathname }} replace />;

  const signOut = async () => {
    await api.post('/admin/logout');
    setIsAdmin(false);
    notify('Signed out of the dashboard.');
    navigate('/admin/login');
  };

  const linkClass = ({ isActive }) =>
    `flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
      isActive ? 'bg-white/15 text-white' : 'text-brand-100 hover:bg-white/10 hover:text-white'
    }`;

  return (
    <div className="min-h-screen bg-slate-100">
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-64 transform bg-brand-900 transition-transform lg:translate-x-0 ${
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex h-16 items-center gap-3 border-b border-white/10 px-5">
          <img src="/img/logo.png" alt="" className="h-8 w-auto brightness-0 invert" />
          <span className="text-sm font-semibold text-white">Dashboard</span>
        </div>

        <nav className="space-y-1 p-3">
          {NAV.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.end} className={linkClass} onClick={() => setSidebarOpen(false)}>
              <svg className="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24" style={{ width: 18, height: 18 }}>
                <path d={item.icon} />
              </svg>
              {item.label}
            </NavLink>
          ))}
        </nav>

        <div className="absolute inset-x-0 bottom-0 space-y-1 border-t border-white/10 p-3">
          <a href="/" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-brand-100 hover:bg-white/10 hover:text-white">
            View store
          </a>
          <button onClick={signOut} className="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-brand-100 hover:bg-white/10 hover:text-white">
            Sign out
          </button>
        </div>
      </aside>

      {sidebarOpen && (
        <div className="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" onClick={() => setSidebarOpen(false)} />
      )}

      <div className="lg:pl-64">
        <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:px-8">
          <button onClick={() => setSidebarOpen(true)} className="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
              <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
          </button>
          <p className="text-sm font-semibold text-slate-900">Books, Bits &amp; Co · Admin</p>
        </header>

        <main className="p-4 lg:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
