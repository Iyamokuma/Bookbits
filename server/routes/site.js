import { Router } from 'express';
import multer from 'multer';

import { config } from '../config.js';
import { query, queryOne } from '../db.js';
import { mediaUrl } from '../lib/books.js';
import { ALLOWED_IMAGE_TYPES, saveImage } from '../lib/storage.js';
import { sanitizeRichText } from '../lib/sanitize.js';
import { sendTestEmail } from '../lib/mailer.js';
import {
  DEFAULT_NAV,
  getNav,
  cleanNav,
  saveNav,
  getShippingFee,
  cleanShippingFee,
  saveShippingFee,
  getNotificationEmails,
  cleanNotificationEmails,
  saveNotificationEmails,
  undeliverableDomains,
} from '../lib/settings.js';
import { asyncRoute, badRequest, notFound } from '../lib/http.js';
import { requireAdmin } from '../middleware.js';

/**
 * Site content and settings the admin manages: custom pages, the navigation
 * menu, the delivery fee, and where the shop's notifications are emailed.
 */
const router = Router();

const PER_PAGE = 5;
const MAX_NAV_ITEMS = 12;

const slugify = (s) =>
  String(s).toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 200);

const pagePath = (slug) => `/pages/${slug}`;

// --------------------------------------------------------------------------
// Public
// --------------------------------------------------------------------------

router.get(
  '/pages/:slug',
  asyncRoute(async (req, res) => {
    const page = await queryOne('SELECT * FROM pages WHERE slug = $1 AND is_published = TRUE', [
      req.params.slug,
    ]);
    if (!page) throw notFound("That page doesn't exist or is no longer published.");
    res.json({
      title: page.title,
      slug: page.slug,
      metaDescription: page.meta_description,
      body: page.body,
      coverUrl: mediaUrl(page.cover_image),
      updatedAt: page.updated_at,
    });
  })
);

// Everything below requires an admin session.
router.use('/admin', requireAdmin);

// --------------------------------------------------------------------------
// Image uploads for page content
// --------------------------------------------------------------------------

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 5 * 1024 * 1024 },
  fileFilter: (req, file, cb) =>
    ALLOWED_IMAGE_TYPES.includes(file.mimetype)
      ? cb(null, true)
      : cb(badRequest('Images must be JPEG, PNG, WebP, or GIF.')),
});

router.post(
  '/admin/uploads',
  upload.single('image'),
  asyncRoute(async (req, res) => {
    if (!req.file) throw badRequest('Choose an image to upload.');
    const stored = await saveImage(req.file, { prefix: 'page' });
    res.status(201).json({ url: mediaUrl(stored) });
  })
);

// --------------------------------------------------------------------------
// Pages
// --------------------------------------------------------------------------

const presentPage = (p, nav) => ({
  id: p.id,
  title: p.title,
  slug: p.slug,
  metaDescription: p.meta_description || '',
  body: p.body,
  coverImage: p.cover_image || '',
  coverUrl: mediaUrl(p.cover_image),
  isPublished: p.is_published,
  inNav: nav.some((item) => item.url === pagePath(p.slug)),
  createdAt: p.created_at,
  updatedAt: p.updated_at,
});

async function readPage(body) {
  const title = String(body.title || '').trim();
  const slug = slugify(body.slug || title);
  const cover = String(body.coverImage || '').trim();

  if (!title) throw badRequest('Give the page a title.');
  if (title.length > 200) throw badRequest('Keep the title under 200 characters.');
  if (!slug) throw badRequest('The page address needs at least one letter or number.');
  if (cover && !/^(https?:\/\/|\/)\S+$/i.test(cover)) {
    throw badRequest('The banner image must be an uploaded image or a full https:// link.');
  }

  const html = await sanitizeRichText(body.body);
  if (html.length > 500_000) throw badRequest('This page is too long to save. Try splitting it in two.');

  return {
    title,
    slug,
    metaDescription: String(body.metaDescription || '').trim().slice(0, 300) || null,
    body: html,
    coverImage: cover.slice(0, 500) || null,
    isPublished: body.isPublished !== false && body.isPublished !== 'false',
    showInNav: body.showInNav === true || body.showInNav === 'true',
  };
}

/**
 * Work out the menu after a page is saved: follow a changed address, then add
 * or remove the page's own link to match the "show in navbar" box.
 */
function navAfterSave(nav, { oldSlug, newSlug, title, showInNav }) {
  let next = nav;
  if (oldSlug && oldSlug !== newSlug) {
    next = next.map((item) => (item.url === pagePath(oldSlug) ? { ...item, url: pagePath(newSlug) } : item));
  }

  const linked = next.some((item) => item.url === pagePath(newSlug));
  if (showInNav && !linked) {
    if (next.length >= MAX_NAV_ITEMS) {
      throw badRequest(
        `The navigation bar already has ${MAX_NAV_ITEMS} links. Remove one under Navigation first, or untick “Show in navigation bar”.`
      );
    }
    next = [...next, { label: title.slice(0, 40), url: pagePath(newSlug), newTab: false }];
  } else if (!showInNav && linked) {
    next = next.filter((item) => item.url !== pagePath(newSlug));
  }
  return next;
}

router.get(
  '/admin/pages',
  asyncRoute(async (req, res) => {
    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const [total, rows, nav] = await Promise.all([
      queryOne('SELECT COUNT(*)::int AS n FROM pages'),
      query('SELECT * FROM pages ORDER BY updated_at DESC LIMIT $1 OFFSET $2', [
        PER_PAGE,
        (page - 1) * PER_PAGE,
      ]),
      getNav(),
    ]);

    res.json({
      pages: rows.map((p) => {
        const { body, ...rest } = presentPage(p, nav);
        return rest;
      }),
      pagination: {
        page,
        perPage: PER_PAGE,
        total: total.n,
        pages: Math.max(1, Math.ceil(total.n / PER_PAGE)),
      },
    });
  })
);

router.get(
  '/admin/pages/:id',
  asyncRoute(async (req, res) => {
    const [page, nav] = await Promise.all([
      queryOne('SELECT * FROM pages WHERE id = $1', [parseInt(req.params.id, 10)]),
      getNav(),
    ]);
    if (!page) throw notFound('That page no longer exists.');
    res.json(presentPage(page, nav));
  })
);

router.post(
  '/admin/pages',
  asyncRoute(async (req, res) => {
    const data = await readPage(req.body);
    if (await queryOne('SELECT id FROM pages WHERE slug = $1', [data.slug])) {
      throw badRequest(`Another page already uses the address /pages/${data.slug}.`);
    }

    const nav = await getNav();
    const nextNav = navAfterSave(nav, { newSlug: data.slug, title: data.title, showInNav: data.showInNav });

    const row = await queryOne(
      `INSERT INTO pages (title, slug, meta_description, body, cover_image, is_published)
       VALUES ($1,$2,$3,$4,$5,$6) RETURNING id`,
      [data.title, data.slug, data.metaDescription, data.body, data.coverImage, data.isPublished]
    );
    if (nextNav !== nav) await saveNav(nextNav);

    res.status(201).json({ id: row.id, nav: nextNav, message: `“${data.title}” has been created.` });
  })
);

router.post(
  '/admin/pages/:id',
  asyncRoute(async (req, res) => {
    const id = parseInt(req.params.id, 10);
    const existing = await queryOne('SELECT * FROM pages WHERE id = $1', [id]);
    if (!existing) throw notFound('That page no longer exists.');

    const data = await readPage(req.body);
    if (await queryOne('SELECT id FROM pages WHERE slug = $1 AND id <> $2', [data.slug, id])) {
      throw badRequest(`Another page already uses the address /pages/${data.slug}.`);
    }

    const nav = await getNav();
    const nextNav = navAfterSave(nav, {
      oldSlug: existing.slug,
      newSlug: data.slug,
      title: data.title,
      showInNav: data.showInNav,
    });

    await query(
      `UPDATE pages SET title=$1, slug=$2, meta_description=$3, body=$4, cover_image=$5, is_published=$6
       WHERE id=$7`,
      [data.title, data.slug, data.metaDescription, data.body, data.coverImage, data.isPublished, id]
    );
    if (nextNav !== nav) await saveNav(nextNav);

    res.json({ nav: nextNav, message: `“${data.title}” has been updated.` });
  })
);

router.delete(
  '/admin/pages/:id',
  asyncRoute(async (req, res) => {
    const page = await queryOne('DELETE FROM pages WHERE id = $1 RETURNING title, slug', [
      parseInt(req.params.id, 10),
    ]);
    if (!page) throw notFound('That page no longer exists.');

    // A menu link to a deleted page would only lead to a 404.
    const nav = await getNav();
    const nextNav = nav.filter((item) => item.url !== pagePath(page.slug));
    if (nextNav.length !== nav.length) await saveNav(nextNav);

    res.json({ nav: nextNav, message: `“${page.title}” has been deleted.` });
  })
);

// --------------------------------------------------------------------------
// Navigation menu
// --------------------------------------------------------------------------

router.get(
  '/admin/navigation',
  asyncRoute(async (req, res) => {
    const [items, pages, categories] = await Promise.all([
      getNav(),
      query('SELECT title, slug, is_published FROM pages ORDER BY title'),
      query('SELECT name, slug FROM categories WHERE is_active = TRUE ORDER BY sort_order, name'),
    ]);
    res.json({
      items,
      defaults: DEFAULT_NAV,
      maxItems: MAX_NAV_ITEMS,
      pages: pages.map((p) => ({ title: p.title, url: pagePath(p.slug), isPublished: p.is_published })),
      categories: categories.map((c) => ({ name: c.name, url: `/shop?cat=${encodeURIComponent(c.slug)}` })),
    });
  })
);

router.post(
  '/admin/navigation',
  asyncRoute(async (req, res) => {
    const items = cleanNav(req.body.items);
    if (!items.length) throw badRequest('Keep at least one link in the navigation bar.');
    await saveNav(items);
    res.json({ items, message: 'Navigation bar saved.' });
  })
);

// --------------------------------------------------------------------------
// Store settings
// --------------------------------------------------------------------------

async function settingsPayload() {
  const [shippingFee, notificationEmails] = await Promise.all([
    getShippingFee(),
    getNotificationEmails(),
  ]);
  return {
    shippingFee,
    notificationEmails,
    undeliverable: await undeliverableDomains(notificationEmails),
    mailConfigured: Boolean(config.mail.resendApiKey),
    fromEmail: config.mail.fromEmail,
  };
}

router.get(
  '/admin/settings',
  asyncRoute(async (req, res) => {
    res.json(await settingsPayload());
  })
);

router.post(
  '/admin/settings',
  asyncRoute(async (req, res) => {
    // Validate everything before writing anything, so a bad email address
    // doesn't leave a half-saved form behind.
    const fee =
      req.body.shippingFee !== undefined ? cleanShippingFee(req.body.shippingFee) : undefined;
    const emails =
      req.body.notificationEmails !== undefined
        ? cleanNotificationEmails(req.body.notificationEmails)
        : undefined;

    await Promise.all([
      fee !== undefined && saveShippingFee(fee),
      emails !== undefined && saveNotificationEmails(emails),
    ]);
    res.json({ ...(await settingsPayload()), message: 'Settings saved.' });
  })
);

/** Prove the notification inboxes actually receive mail. */
router.post(
  '/admin/settings/test-email',
  asyncRoute(async (req, res) => {
    const to = await getNotificationEmails();
    if (!to.length) throw badRequest('Add a notification email address first.');

    const result = await sendTestEmail(to);
    if (!result.ok) {
      return res.status(502).json({ error: `The test email could not be sent. ${result.error}` });
    }
    res.json({
      message: `Test email sent to ${to.join(', ')}. If it isn't in the inbox within a few minutes, check spam.`,
    });
  })
);

export default router;
