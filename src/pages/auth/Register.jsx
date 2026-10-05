import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import AuthCard from './AuthCard';
import { Alert, Field, Spinner, inputClass } from '../../components/ui';
import GoogleSignIn from '../../components/GoogleSignIn';
import { api, ApiError } from '../../lib/api';
import { useApp } from '../../context/AppContext';

export default function Register() {
  const { notify, setUser, setCartCount, ready } = useApp();
  const navigate = useNavigate();
  const location = useLocation();
  const returnTo = location.state?.from || '/checkout';

  const [form, setForm] = useState({ name: '', email: '', password: '', passwordConfirm: '' });
  const [errors, setErrors] = useState({});
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }));

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    setError(null);
    try {
      const res = await api.post('/auth/register', form);
      setUser(res.user);
      setCartCount(res.cartCount ?? 0);
      notify(res.message);
      navigate(returnTo, { replace: true });
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.details);
      else setError(err.message);
      setBusy(false);
    }
  };

  if (!ready) return <Spinner label="Loading…" />;

  return (
    <AuthCard
      title="Create your account"
      subtitle="Order faster and keep track of every delivery."
      footer={
        <>
          Already have an account?{' '}
          <Link
            to="/login"
            state={location.state}
            className="font-semibold text-brand-700 hover:underline"
          >
            Sign in
          </Link>
        </>
      }
    >
      <GoogleSignIn text="signup_with" />

      <form onSubmit={submit} className="mt-4 space-y-4">
        {error && <Alert>{error}</Alert>}

        <Field label="Full name" error={errors.name}>
          <input required autoComplete="name" className={inputClass} value={form.name} onChange={set('name')} />
        </Field>

        <Field label="Email address" error={errors.email}>
          <input type="email" required autoComplete="email" className={inputClass} value={form.email} onChange={set('email')} />
        </Field>

        <Field label="Password" error={errors.password} hint="At least 8 characters.">
          <input type="password" required autoComplete="new-password" className={inputClass} value={form.password} onChange={set('password')} />
        </Field>

        <Field label="Confirm password" error={errors.passwordConfirm}>
          <input type="password" required autoComplete="new-password" className={inputClass} value={form.passwordConfirm} onChange={set('passwordConfirm')} />
        </Field>

        <button
          type="submit"
          disabled={busy}
          className="h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
        >
          {busy ? 'Creating your account…' : 'Create account & continue'}
        </button>

        <p className="text-center text-[11px] leading-relaxed text-slate-400">
          By creating an account you agree to our{' '}
          <Link to="/terms" className="underline hover:text-slate-600">Terms of Use</Link> and{' '}
          <Link to="/privacy" className="underline hover:text-slate-600">Privacy Policy</Link>.
        </p>
      </form>
    </AuthCard>
  );
}
