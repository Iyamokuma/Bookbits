import { Link } from 'react-router-dom';

export default function AuthCard({ title, subtitle, children, footer }) {
  return (
    <div className="mx-auto max-w-md px-4 py-16">
      <div className="rounded-2xl border border-slate-200 bg-white p-8">
        <Link to="/" className="mb-6 block">
          <img src="/img/logo.png" alt="" className="h-10 w-auto" />
        </Link>

        <h1 className="font-display text-2xl text-slate-900">{title}</h1>
        {subtitle && <p className="mt-1.5 text-sm text-slate-500">{subtitle}</p>}

        <div className="mt-7">{children}</div>
      </div>

      {footer && <p className="mt-6 text-center text-sm text-slate-600">{footer}</p>}
    </div>
  );
}
