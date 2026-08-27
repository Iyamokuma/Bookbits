import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import BookCard from '../components/BookCard';
import { Spinner } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { useApp } from '../context/AppContext';
import { formatDate } from '../lib/format';

const SLIDES = [
  {
    image: '/img/hero/hero-1.png',
    title: 'Find the perfect book',
    subtitle: 'In any of our categories',
    body: 'Fiction, self help, business, children, christian and more — carefully curated for every kind of reader.',
    cta: { label: 'Browse categories', to: '/shop' },
  },
  {
    image: '/img/hero/hero-2.png',
    title: 'The bookstore for bibles, journals,',
    subtitle: 'books and stationeries',
    body: 'Everything you need to read, write and reflect — all in one place.',
    cta: { label: 'Shop stationery', to: '/stationery' },
  },
  {
    image: '/img/hero/hero-3.png',
    title: 'Every story',
    subtitle: 'begins here',
    body: 'Discover curated books, rare finds and timeless reads — delivered to your door.',
    cta: { label: 'Browse the collection', to: '/shop' },
  },
];

function Hero() {
  const [index, setIndex] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => setIndex((i) => (i + 1) % SLIDES.length), 6000);
    return () => clearInterval(timer);
  }, []);

  return (
    <section className="relative h-[26rem] overflow-hidden bg-brand-900 sm:h-[32rem]">
      {SLIDES.map((slide, i) => (
        <div
          key={slide.image}
          className={`absolute inset-0 transition-opacity duration-1000 ${
            i === index ? 'opacity-100' : 'pointer-events-none opacity-0'
          }`}
        >
          <img src={slide.image} alt="" className="h-full w-full object-cover" />
          <div className="absolute inset-0 bg-gradient-to-r from-brand-900/90 via-brand-900/70 to-brand-900/20" />

          <div className="absolute inset-0">
            <div className="mx-auto flex h-full max-w-7xl items-center px-4">
              <div className="max-w-xl text-white">
                <h1 className="font-display text-3xl leading-tight sm:text-5xl">
                  {slide.title}
                  <span className="block text-accent-400">{slide.subtitle}</span>
                </h1>
                <p className="mt-4 text-sm text-slate-200 sm:text-base">{slide.body}</p>
                <Link
                  to={slide.cta.to}
                  className="mt-7 inline-block rounded-full bg-accent-500 px-7 py-3 text-sm font-semibold text-white transition hover:bg-accent-600"
                >
                  {slide.cta.label}
                </Link>
              </div>
            </div>
          </div>
        </div>
      ))}

      <div className="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2">
        {SLIDES.map((slide, i) => (
          <button
            key={slide.image}
            onClick={() => setIndex(i)}
            aria-label={`Slide ${i + 1}`}
            className={`h-2 rounded-full transition-all ${
              i === index ? 'w-8 bg-accent-400' : 'w-2 bg-white/50 hover:bg-white/80'
            }`}
          />
        ))}
      </div>
    </section>
  );
}

const TRUST = [
  { title: 'Nationwide delivery', body: 'Every state in Nigeria' },
  { title: 'Secure payment', body: 'Card, transfer & USSD' },
  { title: 'Protective packaging', body: 'At no extra charge' },
  { title: '7-day returns', body: 'On unread, undamaged books' },
];

function Shelf({ title, subtitle, books, viewAllTo }) {
  if (!books?.length) return null;
  return (
    <section className="mx-auto max-w-7xl px-4 py-12">
      <div className="mb-6 flex items-end justify-between gap-4">
        <div>
          <h2 className="font-display text-2xl text-slate-900 sm:text-3xl">{title}</h2>
          {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
        </div>
        {viewAllTo && (
          <Link to={viewAllTo} className="shrink-0 text-sm font-semibold text-brand-700 hover:underline">
            View all →
          </Link>
        )}
      </div>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {books.map((b) => <BookCard key={b.id} book={b} />)}
      </div>
    </section>
  );
}

export default function Home() {
  const { categories } = useApp();
  const { data, loading } = useFetch('/home');

  return (
    <>
      <Hero />

      <section className="border-b border-slate-200 bg-white">
        <div className="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 lg:grid-cols-4">
          {TRUST.map((t) => (
            <div key={t.title}>
              <p className="text-sm font-semibold text-slate-900">{t.title}</p>
              <p className="mt-0.5 text-xs text-slate-500">{t.body}</p>
            </div>
          ))}
        </div>
      </section>

      {categories.length > 0 && (
        <section className="mx-auto max-w-7xl px-4 py-12">
          <h2 className="mb-6 font-display text-2xl text-slate-900 sm:text-3xl">Browse by category</h2>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            {categories.map((c) => (
              <Link
                key={c.slug}
                to={`/shop?cat=${c.slug}`}
                className="group rounded-2xl border border-slate-200 bg-white p-5 text-center transition hover:border-brand-300 hover:shadow-md"
              >
                <div className="mx-auto grid h-12 w-12 place-items-center rounded-full bg-brand-50 text-brand-700 transition group-hover:bg-brand-700 group-hover:text-white">
                  <svg className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="1.6" viewBox="0 0 24 24">
                    <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z" />
                  </svg>
                </div>
                <p className="mt-3 text-sm font-semibold text-slate-900">{c.name}</p>
              </Link>
            ))}
          </div>
        </section>
      )}

      {loading ? (
        <Spinner label="Loading the shelves…" />
      ) : (
        <>
          <Shelf title="New arrivals" subtitle="Fresh on the shelves" books={data?.newArrivals} viewAllTo="/shop" />
          <Shelf title="On offer" subtitle="Great reads at a better price" books={data?.deals} viewAllTo="/shop" />
          <Shelf title="Stationery" subtitle="Journals, pens and supplies" books={data?.stationery} viewAllTo="/stationery" />

          {data?.posts?.length > 0 && (
            <section className="mx-auto max-w-7xl px-4 py-12">
              <div className="mb-6 flex items-end justify-between">
                <h2 className="font-display text-2xl text-slate-900 sm:text-3xl">From the blog</h2>
                <Link to="/blog" className="text-sm font-semibold text-brand-700 hover:underline">
                  All articles →
                </Link>
              </div>
              <div className="grid gap-5 sm:grid-cols-3">
                {data.posts.map((p) => (
                  <Link
                    key={p.id}
                    to={`/blog/${p.slug}`}
                    className="rounded-2xl border border-slate-200 bg-white p-6 transition hover:shadow-md"
                  >
                    <p className="text-xs text-slate-400">{formatDate(p.published_at)}</p>
                    <h3 className="mt-2 line-clamp-2 font-semibold text-slate-900">{p.title}</h3>
                    {p.excerpt && <p className="mt-2 line-clamp-3 text-sm text-slate-500">{p.excerpt}</p>}
                  </Link>
                ))}
              </div>
            </section>
          )}
        </>
      )}
    </>
  );
}
