import { useState } from 'react';
import { Link } from 'react-router-dom';
import AuthCard from './AuthCard';
import { Alert, Field, inputClass } from '../../components/ui';
import { api, ApiError } from '../../lib/api';

export default function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [errors, setErrors] = useState({});
  const [error, setError] = useState(null);
  const [sent, setSent] = useState(null);
  const [busy, setBusy] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    setError(null);
    try {
      const res = await api.post('/auth/forgot-password', { email });
      setSent(res.message);
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.details);
      else setError(err.message);
    } finally {
      setBusy(false);
    }
  };

  if (sent) {
    return (
      <AuthCard title="Check your inbox">
        <Alert tone="success">{sent}</Alert>
        <p className="mt-4 text-sm leading-relaxed text-slate-600">
          The link is valid for one hour. If it doesn't arrive shortly, check your spam folder.
        </p>
        <Link
          to="/login"
          className="mt-6 grid h-11 w-full place-items-center rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800"
        >
          Back to sign in
        </Link>
      </AuthCard>
    );
  }

  return (
    <AuthCard
      title="Reset your password"
      subtitle="Enter your email and we'll send you a link to set a new one."
      footer={
        <Link to="/login" className="font-semibold text-brand-700 hover:underline">
          Back to sign in
        </Link>
      }
    >
      <form onSubmit={submit} className="space-y-4">
        {error && <Alert>{error}</Alert>}

        <Field label="Email address" error={errors.email}>
          <input
            type="email"
            required
            autoComplete="email"
            className={inputClass}
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
        </Field>

        <button
          type="submit"
          disabled={busy}
          className="h-11 w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-60"
        >
          {busy ? 'Sending…' : 'Send reset link'}
        </button>
      </form>
    </AuthCard>
  );
}
