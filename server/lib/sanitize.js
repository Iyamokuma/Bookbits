// Loaded on first use rather than at startup. Only page saving and page
// previews need it, so if the library ever fails to load in production it can
// break those two features, never the whole API. (2.18 did exactly that: its
// ESM-only parser can't be require()d by Vercel's function loader. Pinned to
// 2.17.0 in package.json for that reason.)
let sanitizeHtml;
const load = async () => {
  sanitizeHtml ??= (await import('sanitize-html')).default;
  return sanitizeHtml;
};

/**
 * Clean HTML from the page editor before it is stored. The storefront renders
 * page bodies as raw HTML, so this allowlist is the only thing standing between
 * a pasted snippet and script running on every visitor's browser.
 *
 * Formatting the editor can produce is kept; everything else — scripts, event
 * handlers, inline styles, iframes, javascript: links — is dropped.
 */
const OPTIONS = {
  allowedTags: [
    'p', 'br', 'div', 'h2', 'h3', 'h4',
    'strong', 'b', 'em', 'i', 'u', 's',
    'blockquote', 'ul', 'ol', 'li', 'hr',
    'a', 'img', 'figure', 'figcaption',
  ],
  allowedAttributes: {
    a: ['href', 'target', 'rel'],
    img: ['src', 'alt', 'width', 'height'],
  },
  allowedSchemes: ['http', 'https', 'mailto', 'tel'],
  allowedSchemesByTag: { img: ['http', 'https'] },
  allowProtocolRelative: false,
  transformTags: {
    // The page title is the only h1.
    h1: 'h2',
    a: (tagName, attribs) => {
      const out = { href: attribs.href };
      if (attribs.target === '_blank') {
        out.target = '_blank';
        out.rel = 'noopener noreferrer';
      }
      return { tagName: 'a', attribs: out };
    },
  },
  exclusiveFilter: (frame) =>
    // Links that lost their href to the scheme filter are just noise.
    (frame.tag === 'a' && !frame.attribs.href && !frame.text.trim()) ||
    // Empty paragraphs: left behind by browsers' list editing, and by blank
    // lines, which the stylesheet's paragraph spacing already provides.
    (frame.tag === 'p' && !frame.text.trim() && frame.mediaChildren.length === 0),
};

export const sanitizeRichText = async (html) => (await load())(String(html ?? ''), OPTIONS).trim();

/** Plain text from stored HTML, for meta descriptions and previews. */
export const htmlToText = async (html) =>
  (await load())(
    // Block boundaries become spaces, or "<p>One</p><p>Two</p>" reads "OneTwo".
    String(html ?? '').replace(/<(br|\/p|\/div|\/h\d|\/li|\/blockquote)\b[^>]*>/gi, ' $&'),
    { allowedTags: [], allowedAttributes: {} }
  )
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/\s+/g, ' ')
    .trim();
