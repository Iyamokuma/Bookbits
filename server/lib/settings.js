import dns from 'node:dns/promises';
import { config } from '../config.js';
import { query, queryOne } from '../db.js';
import { badRequest } from './http.js';
import { round2 } from './money.js';

/**
 * Store settings the admin edits from the dashboard, kept in `site_settings`.
 *
 * Every reader has a built-in default, so a fresh database (or one where
 * `npm run db:push` has not been run yet) behaves exactly like the hard-coded
 * values these settings replaced.
 */

export const DEFAULT_SHIPPING_FEE = 1500;

// Matches the menu that was hard-coded in the header before it became editable.
export const DEFAULT_NAV = [
  { label: 'Home', url: '/', newTab: false },
  { label: 'Books', url: '/shop', newTab: false },
  { label: 'Stationery', url: '/stationery', newTab: false },
  { label: 'Blog', url: '/blog', newTab: false },
  { label: 'Shop', url: '/shop', newTab: false },
];

const KEYS = {
  nav: 'nav_menu',
  shippingFee: 'shipping_fee',
  notificationEmails: 'notification_emails',
};

const MAX_NAV_ITEMS = 12;
const MAX_NOTIFICATION_EMAILS = 5;
const UNDEFINED_TABLE = '42P01';

async function getSetting(key, fallback) {
  try {
    const row = await queryOne('SELECT value FROM site_settings WHERE key = $1', [key]);
    return row ? row.value : fallback;
  } catch (err) {
    // The table arrives with `npm run db:push`; until then, use the defaults
    // rather than taking the storefront down.
    if (err.code === UNDEFINED_TABLE) return fallback;
    throw err;
  }
}

async function setSetting(key, value) {
  // pg would send a JS array as a Postgres array literal, so serialise first.
  await query(
    `INSERT INTO site_settings (key, value) VALUES ($1, $2::jsonb)
     ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()`,
    [key, JSON.stringify(value)]
  );
}

// --------------------------------------------------------------------------
// Navigation menu
// --------------------------------------------------------------------------

/** Internal paths, web links, and mailto:/tel: are allowed; javascript: is not. */
const isAllowedUrl = (url) =>
  (/^\/(?!\/)\S*$/.test(url) || /^(https?:\/\/|mailto:|tel:)\S+$/i.test(url)) && url.length <= 300;

export async function getNav() {
  const items = await getSetting(KEYS.nav, DEFAULT_NAV);
  return Array.isArray(items) ? items : DEFAULT_NAV;
}

/** Validate a menu sent from the dashboard. Throws a 400 naming the bad row. */
export function cleanNav(input) {
  if (!Array.isArray(input)) throw badRequest('The menu must be a list of links.');
  if (input.length > MAX_NAV_ITEMS) {
    throw badRequest(`The menu can hold at most ${MAX_NAV_ITEMS} links.`);
  }

  return input.map((raw, i) => {
    const label = String(raw?.label ?? '').trim();
    const url = String(raw?.url ?? '').trim();
    const row = `Link ${i + 1}`;

    if (!label) throw badRequest(`${row} needs a label.`);
    if (label.length > 40) throw badRequest(`${row}: keep the label under 40 characters.`);
    if (!url) throw badRequest(`${row} (“${label}”) needs somewhere to link to.`);
    if (!isAllowedUrl(url)) {
      throw badRequest(
        `${row} (“${label}”): links must start with / for a page on this site, or https:// for another website.`
      );
    }
    return { label, url, newTab: Boolean(raw?.newTab) };
  });
}

export const saveNav = (items) => setSetting(KEYS.nav, items);

// --------------------------------------------------------------------------
// Delivery fee
// --------------------------------------------------------------------------

export async function getShippingFee() {
  const fee = Number(await getSetting(KEYS.shippingFee, DEFAULT_SHIPPING_FEE));
  return Number.isFinite(fee) && fee >= 0 ? round2(fee) : DEFAULT_SHIPPING_FEE;
}

export function cleanShippingFee(input) {
  const s = String(input ?? '').replace(/[,\s]/g, '');
  const fee = Number(s);
  if (s === '' || !Number.isFinite(fee) || fee < 0) {
    throw badRequest('Enter a delivery fee of 0 or more. Use 0 for free delivery.');
  }
  if (fee > 1_000_000) throw badRequest('That delivery fee looks too high. Please check it.');
  return round2(fee);
}

export const saveShippingFee = (fee) => setSetting(KEYS.shippingFee, fee);

// --------------------------------------------------------------------------
// Where the shop's own notifications go (new orders, contact-form messages)
// --------------------------------------------------------------------------

const EMAIL_RE = /^[^\s@,;]+@[^\s@,;]+\.[^\s@,;]{2,}$/;

export async function getNotificationEmails() {
  const saved = await getSetting(KEYS.notificationEmails, null);
  if (Array.isArray(saved) && saved.length) return saved;
  return config.store.email ? [config.store.email] : [];
}

/** Accepts a list, or text separated by commas, semicolons, or new lines. */
export function cleanNotificationEmails(input) {
  const parts = (Array.isArray(input) ? input : String(input ?? '').split(/[\s,;]+/))
    .map((e) => String(e).trim().toLowerCase())
    .filter(Boolean);

  const invalid = parts.filter((e) => !EMAIL_RE.test(e));
  if (invalid.length) throw badRequest(`“${invalid[0]}” is not a valid email address.`);

  const unique = [...new Set(parts)];
  if (!unique.length) throw badRequest('Add at least one email address for order notifications.');
  if (unique.length > MAX_NOTIFICATION_EMAILS) {
    throw badRequest(`Add at most ${MAX_NOTIFICATION_EMAILS} notification addresses.`);
  }
  return unique;
}

export const saveNotificationEmails = (emails) => setSetting(KEYS.notificationEmails, emails);

const NO_SUCH_RECORD = ['ENODATA', 'ENOTFOUND'];

/**
 * True unless DNS says definitively that nothing receives mail for the domain.
 * Mail servers fall back to the A record when there is no MX, so both have to
 * be missing. Lookup failures of any other kind give the benefit of the doubt.
 */
async function canReceiveMail(domain) {
  const has = (lookup) =>
    lookup(domain).then(
      (records) => records.length > 0,
      (err) => !NO_SUCH_RECORD.includes(err.code)
    );
  // In parallel: a single lookup can take a couple of seconds.
  const [mx, a] = await Promise.all([has(dns.resolveMx), has(dns.resolve4)]);
  return mx || a;
}

/** Domains among `emails` that cannot receive mail, so the dashboard can warn. */
export async function undeliverableDomains(emails) {
  const domains = [...new Set(emails.map((e) => e.split('@')[1]).filter(Boolean))];
  const checks = await Promise.all(
    domains.map(async (domain) => {
      const timeout = new Promise((resolve) => setTimeout(resolve, 6000, true));
      const ok = await Promise.race([canReceiveMail(domain), timeout]);
      return ok ? null : domain;
    })
  );
  return checks.filter(Boolean);
}
