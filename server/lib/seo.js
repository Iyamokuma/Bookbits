import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { config } from '../config.js';

/**
 * Link previews for a single-page app.
 *
 * WhatsApp, Facebook, X, Telegram and friends never run JavaScript: they read
 * the <head> of the first HTML response and stop. The SPA shell has only the
 * site-wide defaults, so every shared product looked the same — or, for chat
 * apps, showed nothing at all. These helpers put page-specific tags into that
 * first response.
 */

const SEO_START = '<!-- seo:start -->';
const SEO_END = '<!-- seo:end -->';

const esc = (s) =>
  String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

// --------------------------------------------------------------------------
// The built SPA shell (dist/index.html)
// --------------------------------------------------------------------------

// The first path is what the Vercel bundler traces (vercel.json also lists the
// file explicitly); the second covers `npm start` from the project root.
const SHELL_FILES = [
  fileURLToPath(new URL('../../dist/index.html', import.meta.url)),
  path.join(process.cwd(), 'dist', 'index.html'),
];

const SHELL_TTL_MS = 10 * 60 * 1000;
let shellCache = null;

const looksLikeShell = (html) =>
  typeof html === 'string' && html.includes('id="root"') && html.includes('<script');

/**
 * The built index.html, so a person who lands here still gets the full app.
 * Read from disk when it was bundled with the function, otherwise fetched from
 * the CDN, which serves it as a static file. Null when neither works.
 */
export async function loadShell(origin) {
  if (shellCache && Date.now() - shellCache.at < SHELL_TTL_MS) return shellCache.html;

  let html = null;
  for (const file of SHELL_FILES) {
    try {
      const text = await fs.readFile(file, 'utf8');
      if (looksLikeShell(text)) {
        html = text;
        break;
      }
    } catch {
      // try the next location
    }
  }

  if (!html && origin) {
    try {
      const res = await fetch(`${origin}/index.html`, { signal: AbortSignal.timeout(3000) });
      const text = res.ok ? await res.text() : '';
      if (looksLikeShell(text)) html = text;
    } catch {
      // fall through to the standalone page
    }
  }

  if (html) shellCache = { html, at: Date.now() };
  return html;
}

// --------------------------------------------------------------------------
// URLs
// --------------------------------------------------------------------------

/**
 * Scheme and host the request arrived on. Used for preview URLs, payment
 * return links and reaching this deployment's own CDN. It's preferred over
 * BASE_URL because it's the domain the visitor is actually on: a stale
 * BASE_URL once pointed every return link at a deleted deployment.
 */
export const requestOrigin = (req) => `${req.protocol}://${req.get('host')}`;

export function absolute(origin, url) {
  const u = String(url || '').trim();
  if (!u) return null;
  if (/^https?:\/\//i.test(u)) return u;
  return `${origin}/${u.replace(/^\/+/, '')}`;
}

const SUPABASE_PUBLIC = '/storage/v1/object/public/';
const SUPABASE_RENDER = '/storage/v1/render/image/public/';

// WhatsApp silently drops preview images much above this, and a sixth of the
// catalogue's covers are 1–2.5 MB.
const MAX_SHARE_IMAGE_BYTES = 300 * 1024;

/**
 * A preview-sized version of an image. Covers in Supabase Storage that are too
 * heavy are served through Supabase's resizer (600px wide is ~100 KB); small
 * ones and anything hosted elsewhere are used as they are.
 */
export async function shareImageUrl(url) {
  if (!url || !url.includes(SUPABASE_PUBLIC)) return url;
  try {
    const res = await fetch(url, { method: 'HEAD', signal: AbortSignal.timeout(1500) });
    const bytes = Number(res.headers.get('content-length'));
    if (res.ok && bytes > 0 && bytes <= MAX_SHARE_IMAGE_BYTES) return url;
  } catch {
    // Unknown size: resizing is the safe choice.
  }
  return url.replace(SUPABASE_PUBLIC, SUPABASE_RENDER) + (url.includes('?') ? '&' : '?') + 'width=600&quality=75';
}

// --------------------------------------------------------------------------
// Tags
// --------------------------------------------------------------------------

/** Trim to a search-snippet length on a word boundary. */
export function summarize(text, max = 160) {
  const clean = String(text || '').replace(/\s+/g, ' ').trim();
  if (clean.length <= max) return clean;
  const cut = clean.slice(0, max - 1);
  const lastSpace = cut.lastIndexOf(' ');
  return (lastSpace > max * 0.6 ? cut.slice(0, lastSpace) : cut).replace(/[\s,.;:–-]+$/, '') + '…';
}

/**
 * <head> markup for one page.
 *
 * @param {object} m
 * @param {string} m.title        Full document title.
 * @param {string} [m.shareTitle] Title for the preview card, if it should differ.
 * @param {string} m.description
 * @param {string} [m.url]        Absolute canonical URL.
 * @param {string} [m.image]      Absolute image URL.
 * @param {string} [m.imageAlt]
 * @param {string} [m.type]       og:type — website, article, product.
 * @param {'summary'|'summary_large_image'} [m.card]
 * @param {Array<[string, string]>} [m.properties] Extra og/product properties.
 * @param {object} [m.jsonLd]     Structured data for search engines.
 */
export function renderHead(m) {
  const site = config.store.name;
  const shareTitle = m.shareTitle || m.title;
  const tags = [
    `<title>${esc(m.title)}</title>`,
    `<meta name="description" content="${esc(m.description)}" />`,
    m.url && `<link rel="canonical" href="${esc(m.url)}" />`,
    `<meta property="og:site_name" content="${esc(site)}" />`,
    `<meta property="og:type" content="${esc(m.type || 'website')}" />`,
    `<meta property="og:title" content="${esc(shareTitle)}" />`,
    `<meta property="og:description" content="${esc(m.description)}" />`,
    m.url && `<meta property="og:url" content="${esc(m.url)}" />`,
    m.image && `<meta property="og:image" content="${esc(m.image)}" />`,
    m.image?.startsWith('https:') && `<meta property="og:image:secure_url" content="${esc(m.image)}" />`,
    m.image && `<meta property="og:image:alt" content="${esc(m.imageAlt || shareTitle)}" />`,
    ...(m.properties || []).map(
      ([property, content]) => `<meta property="${esc(property)}" content="${esc(content)}" />`
    ),
    `<meta name="twitter:card" content="${m.card || (m.image ? 'summary_large_image' : 'summary')}" />`,
    `<meta name="twitter:title" content="${esc(shareTitle)}" />`,
    `<meta name="twitter:description" content="${esc(m.description)}" />`,
    m.image && `<meta name="twitter:image" content="${esc(m.image)}" />`,
    m.jsonLd &&
      // "</script>" inside a string would end the tag early.
      `<script type="application/ld+json">${JSON.stringify(m.jsonLd).replace(/</g, '\\u003c')}</script>`,
  ];
  return tags.filter(Boolean).join('\n    ');
}

/** Swap the default block in the shell for page-specific tags. */
export function injectHead(shell, head) {
  const start = shell.indexOf(SEO_START);
  const end = shell.indexOf(SEO_END);
  if (start !== -1 && end > start) {
    return shell.slice(0, start) + head + shell.slice(end + SEO_END.length);
  }
  // Markers missing (an older build): drop the defaults that would conflict.
  return shell
    .replace(/<title>[\s\S]*?<\/title>/i, '')
    .replace(/<meta\s+name="description"[^>]*>/i, '')
    .replace('</head>', `    ${head}\n  </head>`);
}

/**
 * Last resort when the shell can't be found: enough for a link preview, plus a
 * loader that swaps in the real app for the rare person who ends up here.
 */
export function standalonePage(m, head) {
  return `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    ${head}
  </head>
  <body>
    <main>
      <h1>${esc(m.shareTitle || m.title)}</h1>
      <p>${esc(m.description)}</p>
      ${m.image ? `<img src="${esc(m.image)}" alt="${esc(m.imageAlt || '')}" width="300" />` : ''}
    </main>
    <script>
      fetch('/index.html').then(function (r) { return r.text(); }).then(function (html) {
        document.open(); document.write(html); document.close();
      }).catch(function () {});
    </script>
  </body>
</html>`;
}
