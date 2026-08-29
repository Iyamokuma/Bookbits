import { useEffect, useRef, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { api } from '../lib/api';
import { Alert } from './ui';

const SCRIPT_SRC = 'https://accounts.google.com/gsi/client';

/** Load Google Identity Services once, and reuse it on later mounts. */
function loadGoogleScript() {
  if (window.google?.accounts?.id) return Promise.resolve();

  const existing = document.querySelector(`script[src="${SCRIPT_SRC}"]`);
  if (existing) {
    return new Promise((resolve, reject) => {
      existing.addEventListener('load', resolve);
      existing.addEventListener('error', () => reject(new Error('Google script failed to load')));
    });
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = SCRIPT_SRC;
    script.async = true;
    script.defer = true;
    script.onload = resolve;
    script.onerror = () => reject(new Error('Google script failed to load'));
    document.head.appendChild(script);
  });
}

/**
 * Renders Google's own sign-in button and exchanges the returned ID token for
 * a Bookbits session. Renders nothing when GOOGLE_CLIENT_ID is not configured.
 */
export default function GoogleSignIn({ text = 'continue_with' }) {
  const { googleClientId, setUser, setCartCount, notify } = useApp();
  const navigate = useNavigate();
  const location = useLocation();
  const target = useRef(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!googleClientId || !target.current) return undefined;

    let cancelled = false;

    const handleCredential = async ({ credential }) => {
      setError(null);
      try {
        const res = await api.post('/auth/google', { credential });
        setUser(res.user);
        setCartCount(res.cartCount);
        notify(
          res.created
            ? `Welcome to Books, Bits & Co, ${res.user.name.split(' ')[0]}.`
            : `Welcome back, ${res.user.name.split(' ')[0]}.`
        );
        navigate(location.state?.from || '/account', { replace: true });
      } catch (err) {
        setError(err.message);
      }
    };

    loadGoogleScript()
      .then(() => {
        if (cancelled || !target.current) return;
        window.google.accounts.id.initialize({
          client_id: googleClientId,
          callback: handleCredential,
        });
        window.google.accounts.id.renderButton(target.current, {
          theme: 'outline',
          size: 'large',
          shape: 'pill',
          text,
          width: 320,
        });
      })
      .catch(() => {
        if (!cancelled) setError('Could not reach Google. Please use your email and password.');
      });

    return () => {
      cancelled = true;
    };
  }, [googleClientId, text, setUser, setCartCount, notify, navigate, location.state]);

  if (!googleClientId) return null;

  return (
    <div className="space-y-3">
      {error && <Alert>{error}</Alert>}
      <div ref={target} className="flex justify-center" />
      <div className="flex items-center gap-3">
        <span className="h-px flex-1 bg-slate-200" />
        <span className="text-xs font-medium uppercase tracking-wide text-slate-400">or</span>
        <span className="h-px flex-1 bg-slate-200" />
      </div>
    </div>
  );
}
