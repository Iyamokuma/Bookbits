import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import AuthCard from './AuthCard';
import { Spinner, Alert, Field, inputClass } from '../../components/ui';
import { api, ApiError } from '../../lib/api';
import { useFetch } from '../../lib/useFetch';
import { useApp } from '../../context/AppContext';

export default function ResetPassword() {
  const [params] = useSearchParams();
  const token = params.get('token') || '';
  const { notify } = useApp();
  const navigate = useNavigate();

  const { loading, error: tokenError } = useFetch(`/auth/reset-password/${token}`);

  const [form, setForm] = useState({ password: '', passwordConfirm: '' });
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
      const res = await api.post('/auth/reset-password', { token, ...form });
      notify(res.message);
      navigate('/login', { replace: true });
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.details);
      else setError(err.message);
      setBusy(false);
    }
  };

  if (loading) return <Spinner label="Checking your link…" />;

  if (tokenError) {
    return (
      <AuthCard title="That link has expired">
        <Alert>{tokenError.message}</Alert>
        <p className="mt-4 text-sm leading-relaxed text-slate-600">
          Password reset links are valid for one hour. Request a new one to continue.
        </p>
        <Link
          to="/forgot-password"
          className="mt-6 grid h-11 w-full place-items-center rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800"
        >
          Request a new link
        </Link>
      </AuthCard>
    );
  }

  return (
    <AuthCard title="Choose a new password" subtitle="Make it at least 8 characters long.">
      <form onSubmit={submit} className="space-y-4">
        {error && <Alert>{error}</Alert>}

        <Field label="New password" error={errors.password}>
          <input type="password" required autoComplete="new-password" className={inputClass} value={form.password} onChange={set('password')} />
        </Field>

        <Field label="Confirm new password" error={errors.passwordConfirm}>
          <input type="password" required autoComplete="new-password" className={inputClass} value={form.passwordConfirm} onChange={set('passwordConfirm')} />
        </Field>

        <button
          type="submit"
          disabled={busy}
          className="h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
        >
          {busy ? 'Saving…' : 'Save new password'}
        </button>
      </form>
    </AuthCard>
  );
}
