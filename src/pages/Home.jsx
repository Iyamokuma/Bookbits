import { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { useFetch } from '../lib/useFetch';
import { Spinner } from '../components/ui';
import { categoryIcon, categoryTile } from '../lib/categoryIcons';
import { ArrowRight, ChevronLeft, ChevronRight, ClockIcon } from '../components/icons';

const SLIDE_MS = 5000;

const HERO_SLIDES = [
  {
    eyebrow: 'Shop by category',
    heading: ['Find the Perfect Book', 'In Any of Our Categories'],
    sub: 'Fiction, faith, business, biography, children’s books, and more — browse what you’re looking for.',
    ctaLabel: 'Browse Categories',
    ctaTo: '/#categories',
    badge: 'SHOP',
    from: '#0f172a',
    to: '#1e3a5f',
    accent: '#f59522',
    image: '/img/hero/hero-1.png',
    imageAlt: 'Woman holding a stack of popular books',
  },
  {
    eyebrow: 'Books, Bits & Co',
    heading: ['Bibles, Journals,', 'Books & Stationery'],
    sub: 'The bookstore for Bibles, journals, books, and stationery — everything you need in one place.',
    ctaLabel: 'Visit the Shop',
    ctaTo: '/shop',
    badge: 'STORE',
    from: '#002a6e',
    to: '#003fa7',
    accent: '#f2b25c',
    image: '/img/hero/hero-2.png',
    imageAlt: 'Reader with a stack of new arrivals',
  },
  {
    eyebrow: 'The Open Page',
    heading: ['Every Story', 'Begins Here'],
    sub: 'Discover curated books, rare finds & timeless reads — delivered to your door.',
    ctaLabel: 'Browse the Collection',
    ctaTo: '/shop',
    badge: 'READ',
    from: '#0c1a2e',
    to: '#003fa7',
    accent: '#fbe8cc',
    image: '/img/hero/hero-3.png',
    imageAlt: 'Book club meeting with friends reading together',
  },
];

function Hero() {
  const [index, setIndex] = useState(0);
  const [paused, setPaused] = useState(false);
  const touchX = useRef(0);
  const total = HERO_SLIDES.length;

  const go = useCallback((next) => setIndex(((next % total) + total) % total), [total]);

  useEffect(() => {
    if (paused) return undefined;
    const t = setTimeout(() => go(index + 1), SLIDE_MS);
    return () => clearTimeout(t);
  }, [index, paused, go]);

  return (
    <section
      id="hero"
      aria-label="Featured promotions"
      className="relative min-h-[560px] overflow-hidden md:h-[480px] md:max-h-[520px]"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onTouchStart={(e) => { touchX.current = e.touches[0].clientX; }}
      onTouchEnd={(e) => {
        const dx = e.changedTouches[0].clientX - touchX.current;
        if (Math.abs(dx) > 40) go(dx < 0 ? index + 1 : index - 1);
      }}
    >
      <div
        className="flex h-full transition-transform duration-700 ease-in-out will-change-transform"
        style={{ width: `${total * 100}%`, transform: `translateX(-${index * (100 / total)}%)` }}
      >
        {HERO_SLIDES.map((slide, i) => (
          <div
            key={slide.badge}
            className="relative flex h-full shrink-0 items-center overflow-hidden"
            style={{ width: `${100 / total}%`, background: `linear-gradient(135deg, ${slide.from} 0%, ${slide.to} 100%)` }}
          >
            <div className="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full opacity-20 blur-3xl" style={{ background: slide.accent }} aria-hidden="true" />
            <div className="pointer-events-none absolute -bottom-16 left-1/3 h-48 w-48 rounded-full opacity-10 blur-2xl" style={{ background: slide.accent }} aria-hidden="true" />

            <div className="relative z-10 mx-auto flex w-full max-w-7xl items-center gap-8 px-5 sm:gap-12 sm:px-8 lg:px-10">
              <div className="flex-1 py-8">
                <div className="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-widest"
                     style={{ background: `${slide.accent}20`, color: slide.accent }}>
                  <span className="inline-block h-1.5 w-1.5 rounded-full" style={{ background: slide.accent }} />
                  {slide.eyebrow}
                </div>

                <h1 className="mt-3 font-serif text-3xl font-black leading-[1.1] text-white sm:text-4xl lg:text-[2.75rem]">
                  {slide.heading[0]}
                  <br />
                  <span className="relative whitespace-nowrap">
                    {slide.heading[1]}
                    <svg className="absolute -bottom-1 left-0 w-full" height="6" viewBox="0 0 200 6" preserveAspectRatio="none" aria-hidden="true">
                      <path d="M0 5 Q50 0 100 4 Q150 8 200 3" stroke={slide.accent} strokeWidth="2.5" fill="none" strokeLinecap="round" />
                    </svg>
                  </span>
                </h1>

                <p className="mt-3 max-w-xs text-sm leading-relaxed text-white/70 sm:max-w-sm sm:text-base">{slide.sub}</p>

                <div className="mt-6 flex flex-wrap items-center gap-3">
                  <Link
                    to={slide.ctaTo}
                    className="inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold shadow-lg transition hover:opacity-90"
                    style={{ background: slide.accent, color: '#0f172a' }}
                  >
                    {slide.ctaLabel}
                    <ArrowRight />
                  </Link>
                  <span className="rounded-full border border-white/25 px-3 py-1.5 text-xs font-semibold text-white/80">{slide.badge}</span>
                </div>

                <div className="relative mt-6 overflow-hidden rounded-2xl shadow-lg ring-1 ring-white/20 md:hidden">
                  <img src={slide.image} alt={slide.imageAlt} width="320" height="320"
                       loading={i === 0 ? 'eager' : 'lazy'}
                       className="aspect-square w-full max-w-xs object-cover" />
                </div>
              </div>

              <div className="relative hidden shrink-0 md:block" style={{ width: 380, height: 380 }}>
                <div className="absolute inset-0 overflow-hidden rounded-2xl shadow-[0_28px_55px_rgba(2,6,23,0.45)] ring-1 ring-white/20">
                  <img src={slide.image} alt={slide.imageAlt} width="380" height="380"
                       loading={i === 0 ? 'eager' : 'lazy'}
                       className="h-full w-full object-cover" />
                </div>
                <div className="absolute -bottom-3 left-1/2 h-10 w-56 -translate-x-1/2 rounded-full opacity-40 blur-xl" style={{ background: slide.accent }} aria-hidden="true" />
              </div>
            </div>
          </div>
        ))}
      </div>

      <button type="button" onClick={() => go(index - 1)} aria-label="Previous slide"
              className="absolute left-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white sm:left-5">
        <ChevronLeft />
      </button>
      <button type="button" onClick={() => go(index + 1)} aria-label="Next slide"
              className="absolute right-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white sm:right-5">
        <ChevronRight />
      </button>

      <div className="absolute bottom-5 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2" role="tablist" aria-label="Slide indicators">
        {HERO_SLIDES.map((slide, i) => (
          <button
            key={slide.badge}
            type="button"
            role="tab"
            aria-selected={i === index}
            aria-label={`Go to slide ${i + 1}`}
            onClick={() => go(i)}
            className="relative h-2 overflow-hidden rounded-full bg-white/30 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white"
            style={{ width: i === index ? 32 : 8 }}
          >
            <span
              key={`${i}-${index}-${paused}`}
              className="absolute inset-0 rounded-full bg-white"
              style={
                i === index
                  ? { transformOrigin: 'left', animation: `heroBar ${SLIDE_MS}ms linear forwards`, animationPlayState: paused ? 'paused' : 'running' }
                  : { transform: 'scaleX(0)' }
              }
            />
          </button>
        ))}
      </div>

      <div className="absolute right-4 top-4 z-20 rounded-full bg-black/30 px-3 py-1 text-xs font-bold text-white backdrop-blur-sm sm:right-6 sm:top-5" aria-live="polite">
        {index + 1} / {total}
      </div>
    </section>
  );
}

function CategoryStrip() {
  const { categories } = useApp();
  const track = useRef(null);

  const scroll = (dir) => {
    const el = track.current;
    if (!el) return;
    const card = el.querySelector('a');
    el.scrollBy({ left: dir * (card ? card.offsetWidth + 16 : 260), behavior: 'smooth' });
  };

  return (
    <section id="categories" className="scroll-mt-24 border-t border-slate-100 bg-gradient-to-b from-slate-50 to-white py-10 sm:py-12" aria-labelledby="categories-heading">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 id="categories-heading" className="font-serif text-2xl font-black text-slate-900 sm:text-3xl">Browse by category</h2>
            <p className="mt-1 text-sm text-slate-500">Find your next read across fiction, faith, business, and more.</p>
          </div>
          <div className="flex items-center gap-2 self-start sm:self-auto">
            <button type="button" onClick={() => scroll(-1)} aria-label="Scroll categories left"
                    className="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-brand/30 hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
              <ChevronLeft className="h-4 w-4" />
            </button>
            <button type="button" onClick={() => scroll(1)} aria-label="Scroll categories right"
                    className="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-brand/30 hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
              <ChevronRight className="h-4 w-4" />
            </button>
            <Link to="/shop" className="ml-1 text-sm font-semibold text-brand transition hover:text-brand-dark">See all</Link>
          </div>
        </div>

        {categories.length === 0 ? (
          <p className="rounded-xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-500">
            Add books to categories to show them here.
          </p>
        ) : (
          <div ref={track} className="no-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-2">
            {categories.map((cat) => {
              const tile = categoryTile(cat.bgColor);
              return (
                <Link
                  key={cat.slug}
                  to={`/shop?cat=${encodeURIComponent(cat.slug)}`}
                  className="group relative flex w-[220px] shrink-0 snap-start flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-brand/25 hover:shadow-md sm:w-[240px]"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1 ring-black/5 ${tile.tile}`}>
                      <svg className={`h-6 w-6 ${tile.icon}`} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" d={categoryIcon(cat.slug)} />
                      </svg>
                    </div>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 transition group-hover:bg-brand/10 group-hover:text-brand">Shop</span>
                  </div>
                  <h3 className="mt-4 font-serif text-lg font-bold leading-tight text-slate-900 transition group-hover:text-brand">{cat.name}</h3>
                  <p className="mt-2 line-clamp-2 flex-1 text-xs leading-relaxed text-slate-500">{cat.description}</p>
                  <span className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-brand">
                    Browse books
                    <ChevronRight className="h-3.5 w-3.5 transition group-hover:translate-x-0.5" />
                  </span>
                </Link>
              );
            })}
          </div>
        )}
      </div>
    </section>
  );
}

const pad = (n) => String(n).padStart(2, '0');

function DealCountdown() {
  const endsAt = useRef(Date.now() + (23 * 3600 + 59 * 60 + 59) * 1000);
  const [left, setLeft] = useState(0);

  useEffect(() => {
    const tick = () => setLeft(Math.max(0, Math.floor((endsAt.current - Date.now()) / 1000)));
    tick();
    const t = setInterval(tick, 1000);
    return () => clearInterval(t);
  }, []);

  const parts = [
    { label: 'HRS', value: Math.floor(left / 3600) },
    { label: 'MIN', value: Math.floor((left % 3600) / 60) },
    { label: 'SEC', value: left % 60 },
  ];

  return (
    <div className="inline-flex items-center gap-3 self-start rounded-2xl border border-brand-100 bg-white px-4 py-2.5 shadow-sm sm:self-auto">
      <ClockIcon className="h-4 w-4 shrink-0 text-brand" />
      <div className="flex gap-1 font-mono text-xs font-bold tabular-nums text-slate-800">
        {parts.map((p, i) => (
          <span key={p.label} className="flex items-center gap-1">
            {i > 0 && <span className="mt-0.5 text-slate-300">:</span>}
            <span className="flex flex-col items-center">
              <span className="rounded bg-brand px-1.5 py-0.5 text-white">{pad(p.value)}</span>
              <span className="mt-0.5 text-[9px] text-slate-400">{p.label}</span>
            </span>
          </span>
        ))}
      </div>
    </div>
  );
}

function CoverGrid({ books, badge, badgeClass, hoverClass }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:gap-5">
      {books.map((book) => (
        <Link key={book.id} to={`/book/${book.id}`} className="group block">
          <div className="relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-100 shadow-sm ring-1 ring-slate-900/5 transition group-hover:-translate-y-1 group-hover:shadow-md">
            <img src={book.coverUrl} alt={book.title} loading="lazy"
                 className="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
            <div className="absolute inset-y-0 left-0 w-1 bg-gradient-to-r from-black/20 to-transparent" aria-hidden="true" />
            <span className={`absolute left-2 top-2 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow ${badgeClass}`}>
              {badge}
            </span>
          </div>
          <div className="mt-2 px-0.5">
            <p className={`line-clamp-1 text-xs font-bold text-slate-800 transition sm:text-sm ${hoverClass}`}>{book.title}</p>
            <p className="mt-0.5 text-[11px] text-slate-500 sm:text-xs">{book.author}</p>
          </div>
        </Link>
      ))}
    </div>
  );
}

const EmptyNote = ({ children }) => (
  <p className="rounded-xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-600">{children}</p>
);

export default function Home() {
  const { data, loading } = useFetch('/home');

  const featured = (data?.featured || []).slice(0, 4);
  const deals = (data?.deals || []).slice(0, 4);
  const newArrivals = (data?.newArrivals || []).slice(0, 4);
  const stationery = (data?.stationery || []).slice(0, 4);

  return (
    <>
      <Hero />
      <CategoryStrip />

      {/* Featured — only shown once the admin has flagged something */}
      {featured.length > 0 && (
        <section className="border-t border-slate-100 bg-white py-12 sm:py-14" aria-labelledby="featured-heading">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-8">
              <h2 id="featured-heading" className="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">Featured</h2>
              <p className="mt-1 text-sm text-slate-500">Our booksellers' current picks.</p>
            </div>
            <CoverGrid books={featured} badge="Featured" badgeClass="bg-brand-700" hoverClass="group-hover:text-brand-700" />
          </div>
        </section>
      )}

      {/* Daily Deals */}
      <section className="border-t border-slate-100 bg-gradient-to-b from-[#eff5ff] to-white py-12 sm:py-14" aria-labelledby="deals-heading">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 id="deals-heading" className="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">Daily Deals</h2>
              <p className="mt-1 text-sm text-slate-500">Handpicked titles at irresistible prices.</p>
            </div>
            <DealCountdown />
          </div>

          {loading ? <Spinner /> : deals.length === 0 ? (
            <EmptyNote>Mark books as “Daily deal” in the admin dashboard to show them here.</EmptyNote>
          ) : (
            <CoverGrid books={deals} badge="Deal" badgeClass="bg-brand" hoverClass="group-hover:text-brand" />
          )}

          <div className="mt-8 text-center">
            <Link to="/shop" className="inline-flex items-center gap-2 rounded-full border border-brand/20 bg-white px-7 py-2.5 text-sm font-semibold text-brand shadow-sm transition hover:bg-brand hover:text-white">
              Browse All Books
              <ArrowRight />
            </Link>
          </div>
        </div>
      </section>

      {/* New Arrivals */}
      <section className="border-t border-slate-100 bg-white py-12 sm:py-14" aria-labelledby="new-arrivals-heading">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-8">
            <h2 id="new-arrivals-heading" className="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">New Arrivals</h2>
            <p className="mt-1 text-sm text-slate-500">Fresh titles recently added to Bookbits.</p>
          </div>

          {loading ? <Spinner /> : newArrivals.length === 0 ? (
            <EmptyNote>Mark books as “New arrival” in the admin dashboard to show them here.</EmptyNote>
          ) : (
            <CoverGrid books={newArrivals} badge="New" badgeClass="bg-emerald-600" hoverClass="group-hover:text-emerald-700" />
          )}
        </div>
      </section>

      {/* Stationery */}
      {stationery.length > 0 && (
        <section className="border-t border-slate-100 bg-gradient-to-b from-accent-50 to-white py-12 sm:py-14" aria-labelledby="stationery-heading">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h2 id="stationery-heading" className="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">Stationery</h2>
                <p className="mt-1 text-sm text-slate-500">Journals, pens, and writing essentials for every desk.</p>
              </div>
              <Link to="/stationery" className="text-sm font-semibold text-accent-600 transition hover:text-accent-700">View all stationery ›</Link>
            </div>
            <CoverGrid books={stationery} badge="Stationery" badgeClass="bg-accent-500" hoverClass="group-hover:text-accent-600" />
          </div>
        </section>
      )}

      {/* From the blog */}
      {data?.posts?.length > 0 && (
        <section className="border-t border-slate-100 bg-slate-50 py-12 sm:py-14" aria-labelledby="blog-heading">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h2 id="blog-heading" className="font-serif text-2xl font-bold text-slate-900 sm:text-3xl">From the blog</h2>
                <p className="mt-1 text-sm text-slate-500">Reading lists, author notes, and bookish thoughts.</p>
              </div>
              <Link to="/blog" className="text-sm font-semibold text-brand transition hover:text-brand-dark">Read all posts ›</Link>
            </div>

            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {data.posts.map((post) => (
                <Link key={post.id} to={`/blog/${post.slug}`}
                      className="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-brand/25 hover:shadow-md">
                  <h3 className="font-serif text-lg font-bold leading-tight text-slate-900 transition group-hover:text-brand">{post.title}</h3>
                  <p className="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-500">{post.excerpt}</p>
                  <span className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-brand">
                    Read more
                    <ChevronRight className="h-3.5 w-3.5 transition group-hover:translate-x-0.5" />
                  </span>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}
    </>
  );
}
