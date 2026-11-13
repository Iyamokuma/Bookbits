import { Router } from 'express';
import { config } from '../config.js';
import { queryOne } from '../db.js';
import { fetchBookById, bookCoverUrl, mediaUrl } from '../lib/books.js';
import { formatMoney } from '../lib/money.js';
import { htmlToText } from '../lib/sanitize.js';
import {
  loadShell,
  requestOrigin,
  absolute,
  shareImageUrl,
  summarize,
  renderHead,
  injectHead,
  standalonePage,
} from '../lib/seo.js';
import { presentBook } from './catalog.js';

/**
 * Server-rendered <head> for the URLs people share: products, blog posts, and
 * admin-created pages. Everything below <head> is still the React app.
 *
 * On Vercel, vercel.json sends only link-preview bots and crawlers here, so
 * shoppers keep getting the SPA straight from the CDN. Under `npm start` every
 * request for these paths comes through, which is why the response is always
 * the full app shell whenever it can be found.
 *
 * Mounted ahead of the session middleware: a preview fetch should not mint a
 * session row or a cookie.
 */
const router = Router();

const TAGLINE = 'Your Next Great Read Awaits';
const DEFAULT_DESCRIPTION =
  'Curated books, bibles, journals and stationery delivered across Nigeria.';

const fullTitle = (title) => `${title} | ${config.store.name}`;

function defaultMeta(origin) {
  return {
    title: `${config.store.name} — ${TAGLINE}`,
    description: DEFAULT_DESCRIPTION,
    url: `${origin}/`,
    image: absolute(origin, '/img/logo.png'),
    card: 'summary',
  };
}

async function bookMeta(req, origin) {
  const id = parseInt(req.params.id, 10);
  if (!id) return null;
  const row = await fetchBookById(id);
  if (!row) return null;

  const book = presentBook(row);
  const price = formatMoney(book.price);
  const stock = book.inStock ? 'In stock' : 'Out of stock';
  const about = row.description
    ? summarize(row.description, 120)
    : `${row.title} by ${row.author}. Delivered across Nigeria.`;
  const url = `${origin}/book/${row.id}`;
  const image = await shareImageUrl(absolute(origin, bookCoverUrl(row)));
  const isbnDigits = String(row.isbn || '').replace(/\D/g, '');

  return {
    title: fullTitle(`${row.title} by ${row.author}`),
    shareTitle: `${row.title} — ${price}`,
    description: `${price} · ${stock}. ${about}`,
    url,
    image,
    imageAlt: `Cover of ${row.title}`,
    type: 'product',
    // Book covers are portrait; the large card would crop them badly.
    card: 'summary',
    properties: [
      ['product:price:amount', book.price.toFixed(2)],
      ['product:price:currency', config.store.currencyCode],
      ['product:availability', book.inStock ? 'in stock' : 'out of stock'],
    ],
    jsonLd: {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: row.title,
      description: summarize(row.description || `${row.title} by ${row.author}`, 500),
      image: image ? [image] : undefined,
      sku: String(row.id),
      // Only a 13-digit ISBN is a valid GTIN-13.
      ...(isbnDigits.length === 13 ? { gtin13: isbnDigits } : {}),
      brand: { '@type': 'Brand', name: row.author },
      category: row.cat_name,
      offers: {
        '@type': 'Offer',
        url,
        price: book.price.toFixed(2),
        priceCurrency: config.store.currencyCode,
        availability: book.inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        itemCondition: 'https://schema.org/NewCondition',
      },
    },
  };
}

async function blogMeta(req, origin) {
  const post = await queryOne(
    `SELECT * FROM blogs
     WHERE slug = $1 AND is_active = TRUE AND published_at IS NOT NULL AND published_at <= now()
     LIMIT 1`,
    [req.params.slug]
  );
  if (!post) return null;

  const cover = mediaUrl(post.cover_image);
  const image = cover ? await shareImageUrl(absolute(origin, cover)) : absolute(origin, '/img/logo.png');
  return {
    title: fullTitle(post.title),
    shareTitle: post.title,
    description: summarize(post.excerpt || post.body),
    url: `${origin}/blog/${post.slug}`,
    image,
    type: 'article',
    card: cover ? 'summary_large_image' : 'summary',
    properties: post.published_at
      ? [['article:published_time', new Date(post.published_at).toISOString()]]
      : [],
  };
}

async function pageMeta(req, origin) {
  let page;
  try {
    page = await queryOne('SELECT * FROM pages WHERE slug = $1 AND is_published = TRUE LIMIT 1', [
      req.params.slug,
    ]);
  } catch (err) {
    if (err.code === '42P01') return null; // pages table not created yet
    throw err;
  }
  if (!page) return null;

  const cover = mediaUrl(page.cover_image);
  const image = cover ? await shareImageUrl(absolute(origin, cover)) : absolute(origin, '/img/logo.png');
  return {
    title: fullTitle(page.title),
    shareTitle: page.title,
    description: summarize(page.meta_description || (await htmlToText(page.body)) || DEFAULT_DESCRIPTION),
    url: `${origin}/pages/${page.slug}`,
    image,
    card: cover ? 'summary_large_image' : 'summary',
  };
}

const render = (resolve) => async (req, res) => {
  const origin = requestOrigin(req);
  let meta = null;
  let failed = false;
  try {
    meta = await resolve(req, origin);
  } catch (err) {
    failed = true;
    console.error('[share]', req.path, err);
  }

  const status = meta || failed ? 200 : 404;
  const head = renderHead(meta || defaultMeta(origin));
  const shell = await loadShell(requestOrigin(req));

  res
    .status(status)
    .type('html')
    // Client-side caching only. No s-maxage: every one of these URLs is
    // rewritten to the same function, and an edge cache keyed on that would
    // hand one product's preview to all of them. Scrapers cache on their side
    // anyway, so the function only sees a trickle of requests.
    .set('Cache-Control', failed ? 'no-store' : status === 404 ? 'public, max-age=60' : 'public, max-age=300')
    .send(shell ? injectHead(shell, head) : standalonePage(meta || defaultMeta(origin), head));
};

router.get('/book/:id', render(bookMeta));
router.get('/blog/:slug', render(blogMeta));
router.get('/pages/:slug', render(pageMeta));

export default router;
