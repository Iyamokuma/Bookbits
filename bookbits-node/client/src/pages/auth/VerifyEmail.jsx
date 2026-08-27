import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import AuthCard from './AuthCard';
import { Spinner, Alert } from '../../components/ui';
import { api } from '../../lib/api';
import { useApp } from '../../context/AppContext';

export default function VerifyEmail() {
  const [params] = useSearchParams();
  const { loadSession, notify } = useApp();
  const navigate = useNavigate();

  const [state, setState] = useState({ status: 'working' });
  // StrictMode double-invokes effects in development; the token is single-use,
  // so a second attempt would always report failure.
  const attempted = useRef(false);

  useEffect(() => {
    if (attempted.current) return;
    attempted.current = true;

    const token = params.get('token');
    if (!token) {
      setState({ status: 'error', message: 'This verification link is incomplete.' });
      return;
    }

    api
      .post('/auth/verify-email', { token })
      .then(async (res) => {
        await loadSession().catch(() => {});
        setState({ status: 'ok', message: res.message });
        notify('Your email is verified.');
        setTimeout(() => navigate('/account', { replace: true }), 2000);
      })
      .catch((err) => setState({ status: 'error', message: err.message }));
  }, [params, loadSession, navigate, notify]);

  if (state.status === 'working') {
    return <Spinner label="Verifying your email…" />;
  }

  return (
    <AuthCard title={state.status === 'ok' ? 'Email verified' : 'We could not verify that link'}>
      {state.status === 'ok' ? (
        <>
          <Alert tone="success">{state.message}</Alert>
          <p className="mt-4 text-sm text-slate-600">Taking you to your account…</p>
        </>
      ) : (
        <>
          <Alert>{state.message}</Alert>
          <p className="mt-4 text-sm leading-relaxed text-slate-600">
            The link may have already been used or expired. Try signing in — if your email still
            needs verifying, you can request a new link from your account page.
          </p>
          <Link
            to="/login"
            className="mt-6 grid h-11 w-full place-items-center rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800"
          >
            Go to sign in
          </Link>
        </>
      )}
    </AuthCard>
  );
}
