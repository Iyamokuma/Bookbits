import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Alert, Field, inputClass } from '../../components/ui';
import { api } from '../../lib/api';
import { useApp } from '../../context/AppContext';

export default function AdminLogin() {
  const { setIsAdmin, loadSession } = useApp();
  const navigate = useNavigate();
  const location = useLocation();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      await api.post('/admin/login', { email, password });
      // The session id changed, so pick up the new CSRF token before any write.
      await loadSession();
      setIsAdmin(true);
      navigate(location.state?.from || '/admin', { replace: true });
    } catch (err) {
      setError(err.message);
      setBusy(false);
    }
  };

  return (
    <div className="grid min-h-screen place-items-center bg-brand-900 px-4">
      <div className="w-full max-w-sm rounded-2xl bg-white p-8">
        <img src="/img/logo.png" alt="" className="mx-auto h-10 w-auto" />
        <h1 className="mt-6 text-center font-display text-xl text-slate-900">Dashboard sign in</h1>
        <p className="mt-1 text-center text-sm text-slate-500">Staff access only.</p>

        <form onSubmit={submit} className="mt-7 space-y-4">
          {error && <Alert>{error}</Alert>}

          <Field label="Email address">
            <input type="email" required autoComplete="username" className={inputClass} value={email} onChange={(e) => setEmail(e.target.value)} />
          </Field>

          <Field label="Password">
            <input type="password" required autoComplete="current-password" className={inputClass} value={password} onChange={(e) => setPassword(e.target.value)} />
          </Field>

          <button
            type="submit"
            disabled={busy}
            className="h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
          >
            {busy ? 'Signing in…' : 'Sign in'}
          </button>
        </form>
      </div>
    </div>
  );
}
