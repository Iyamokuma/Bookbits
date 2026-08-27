import { useEffect } from 'react';
import { useApp } from '../context/AppContext';

export default function Toast() {
  const { toast, dismissToast } = useApp();

  useEffect(() => {
    if (!toast) return undefined;
    const timer = setTimeout(dismissToast, 4000);
    return () => clearTimeout(timer);
  }, [toast, dismissToast]);

  if (!toast) return null;

  const tones = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    error: 'border-rose-200 bg-rose-50 text-rose-800',
    info: 'border-sky-200 bg-sky-50 text-sky-800',
  };

  return (
    <div
      role="status"
      className="fixed bottom-5 left-1/2 z-[60] w-[min(24rem,calc(100vw-2rem))] -translate-x-1/2"
    >
      <div className={`flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg ${tones[toast.tone] || tones.info}`}>
        <span className="flex-1">{toast.message}</span>
        <button onClick={dismissToast} aria-label="Dismiss" className="shrink-0 opacity-60 hover:opacity-100">
          <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  );
}
