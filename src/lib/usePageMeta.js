import { useEffect } from 'react';

/**
 * Keep the document title and description in step with the page on screen.
 *
 * Link-preview scrapers get their tags from the server (server/lib/seo.js);
 * this covers everything that reads the live page instead — the browser tab,
 * bookmarks, the native share sheet, and Google, which does run JavaScript.
 */

const SITE_NAME = 'Books, Bits & Co';
const DEFAULT_TITLE = 'Bookbits — Your Next Great Read Awaits';
const DEFAULT_DESCRIPTION =
  'Curated books, bibles, journals and stationery delivered across Nigeria.';

function setTag(attr, key, content) {
  let el = document.head.querySelector(`meta[${attr}="${key}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute(attr, key);
    document.head.appendChild(el);
  }
  el.setAttribute('content', content);
}

function apply({ title, description, image }) {
  document.title = title;
  setTag('name', 'description', description);
  setTag('property', 'og:title', title);
  setTag('property', 'og:description', description);
  setTag('name', 'twitter:title', title);
  setTag('name', 'twitter:description', description);
  if (image) {
    setTag('property', 'og:image', image);
    setTag('name', 'twitter:image', image);
  }
}

const clip = (text, max = 160) => {
  const clean = String(text || '').replace(/\s+/g, ' ').trim();
  return clean.length > max ? `${clean.slice(0, max - 1).trimEnd()}…` : clean;
};

/**
 * @param {{ title?: string, description?: string, image?: string }} meta
 *   Pass `title` as null/undefined while data is loading to leave the tags alone.
 */
export function usePageMeta({ title, description, image } = {}) {
  useEffect(() => {
    if (!title) return undefined;
    const defaultImage = document.head
      .querySelector('meta[property="og:image"]')
      ?.getAttribute('content');

    apply({
      title: `${title} | ${SITE_NAME}`,
      description: clip(description) || DEFAULT_DESCRIPTION,
      image: image && /^https?:\/\//.test(image) ? image : null,
    });

    return () =>
      apply({ title: DEFAULT_TITLE, description: DEFAULT_DESCRIPTION, image: defaultImage });
  }, [title, description, image]);
}
