import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { Spinner } from './ui';

/** Gate for customer pages. Admin has its own gate in AdminLayout. */
export default function RequireAuth() {
  const { user, ready } = useApp();
  const location = useLocation();

  if (!ready) return <Spinner />;
  if (!user) return <Navigate to="/login" state={{ from: location.pathname }} replace />;

  return <Outlet />;
}
