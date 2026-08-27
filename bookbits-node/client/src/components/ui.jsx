import { Link } from 'react-router-dom';

export function Spinner({ label = 'Loading…' }) {
  return (
    <div className="grid place-items-center py-24 text-slate-400">
      <div className="h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-brand-700" />
      <p className="mt-4 text-sm">{label}</p>
    </div>
  );
}

export function EmptyState({ title, message, actionLabel, actionTo }) {
  return (
    <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
      <p className="font-semibold text-slate-900">{title}</p>
      {message && <p className="mx-auto mt-2 max-w-sm text-sm text-slate-500">{message}</p>}
      {actionTo && (
        <Link
          to={actionTo}
          className="mt-6 inline-block rounded-full bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
        >
          {actionLabel}
        </Link>
      )}
    </div>
  );
}

export function Alert({ tone = 'error', children }) {
  const styles = {
    error: 'border-rose-200 bg-rose-50 text-rose-800',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    info: 'border-sky-200 bg-sky-50 text-sky-800',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
  };
  return (
    <div className={`rounded-xl border px-4 py-3 text-sm ${styles[tone]}`}>{children}</div>
  );
}

const STATUS_STYLES = {
  pending: 'bg-amber-100 text-amber-800',
  processing: 'bg-sky-100 text-sky-800',
  shipped: 'bg-indigo-100 text-indigo-800',
  delivered: 'bg-emerald-100 text-emerald-800',
  cancelled: 'bg-slate-200 text-slate-700',
  refunded: 'bg-rose-100 text-rose-800',
  completed: 'bg-emerald-100 text-emerald-800',
  failed: 'bg-rose-100 text-rose-800',
};

export function StatusBadge({ status }) {
  if (!status) return <span className="text-xs text-slate-400">—</span>;
  return (
    <span
      className={`inline-block rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize ${
        STATUS_STYLES[status] || 'bg-slate-100 text-slate-700'
      }`}
    >
      {status}
    </span>
  );
}

export function Field({ label, error, hint, required, children }) {
  return (
    <div>
      <label className="block text-xs font-medium text-slate-600">
        {label} {required && <span className="text-rose-500">*</span>}
      </label>
      {children}
      {error ? (
        <p className="mt-1 text-[11px] text-rose-600">{error}</p>
      ) : hint ? (
        <p className="mt-1 text-[11px] text-slate-400">{hint}</p>
      ) : null}
    </div>
  );
}

export const inputClass =
  'mt-1.5 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100';

export const textareaClass =
  'mt-1.5 w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100';

export function Pagination({ pagination, onPage }) {
  const { page, pages, total, perPage } = pagination;
  if (!total) return null;

  const from = (page - 1) * perPage + 1;
  const to = Math.min(page * perPage, total);
  const btn =
    'rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50';
  const disabled = 'rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-300';

  return (
    <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
      <p className="text-xs text-slate-500">
        Showing <span className="font-semibold text-slate-700">{from}–{to}</span> of{' '}
        <span className="font-semibold text-slate-700">{total}</span>
      </p>

      {pages > 1 && (
        <div className="flex items-center gap-2">
          {page > 1 ? (
            <button className={btn} onClick={() => onPage(page - 1)}>Previous</button>
          ) : (
            <span className={disabled}>Previous</span>
          )}
          <span className="px-1 text-xs text-slate-500">Page {page} of {pages}</span>
          {page < pages ? (
            <button
              className="rounded-lg bg-brand-700 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-800"
              onClick={() => onPage(page + 1)}
            >
              Next
            </button>
          ) : (
            <span className={disabled}>Next</span>
          )}
        </div>
      )}
    </div>
  );
}
