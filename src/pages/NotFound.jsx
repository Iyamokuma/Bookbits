import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div className="mx-auto max-w-lg px-4 py-24 text-center">
      <p className="font-display text-6xl text-brand-200">404</p>
      <h1 className="mt-4 font-display text-2xl text-slate-900">We can't find that page</h1>
      <p className="mt-3 text-sm text-slate-600">
        The link may be out of date, or the page may have moved.
      </p>
      <Link
        to="/"
        className="mt-8 inline-block rounded-full bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
      >
        Back to the shop
      </Link>
    </div>
  );
}
