import { Router } from 'express';
import multer from 'multer';

import { config } from '../config.js';
import { query, queryOne } from '../db.js';
import { bookCoverUrl } from '../lib/books.js';
import { ALLOWED_IMAGE_TYPES, saveImage, deleteImage } from '../lib/storage.js';
import {
  listOrders,
  getOrder,
  getOrderItems,
  updateOrderStatus,
  ORDER_STATUSES,
} from '../lib/orders.js';
import {
  sendShippedNotice,
  sendProcessingNotice,
  sendDeliveryNotice,
  sendOrderUpdateEmail,
} from '../lib/mailer.js';
import { asyncRoute, unauthorized, badRequest, notFound } from '../lib/http.js';
import { regenerateSession } from '../middleware.js';

const router = Router();

const PER_PAGE = 5;

// --------------------------------------------------------------------------
// Auth
// --------------------------------------------------------------------------

router.post(
  '/admin/login',
  asyncRoute(async (req, res) => {
    const email = String(req.body.email || '').trim().toLowerCase();
    const password = String(req.body.password || '');

    if (email !== config.admin.email.toLowerCase() || password !== config.admin.password) {
      throw unauthorized('Invalid email or password.');
    }

    await regenerateSession(req);
    req.session.isAdmin = true;
    res.json({ ok: true });
  })
);

router.post('/admin/logout', (req, res) => {
  req.session.isAdmin = false;
  res.json({ ok: true });
});

// Everything past this point requires an admin session.
router.use('/admin', (req, res, next) => {
  if (req.session.isAdmin !== true) return next(unauthorized('Admin sign-in required.'));
  next();
});

const paginate = (page, total) => ({
  page,
  perPage: PER_PAGE,
  total,
  pages: Math.max(1, Math.ceil(total / PER_PAGE)),
});

const pageParam = (req) => Math.max(1, parseInt(req.query.page, 10) || 1);

// --------------------------------------------------------------------------
// Dashboard
// --------------------------------------------------------------------------

router.get(
  '/admin/dashboard',
  asyncRoute(async (req, res) => {
    const [totals, monthly, counts, recentOrders, pipeline, topCategories, lowStock, salesByDay] =
      await Promise.all([
        queryOne(`
          SELECT COALESCE(SUM(total), 0) AS revenue, COUNT(*)::int AS orders
          FROM orders WHERE payment_confirmed_at IS NOT NULL
        `),
        queryOne(`
          SELECT
            COALESCE(SUM(total) FILTER (WHERE payment_confirmed_at >= date_trunc('month', now())), 0) AS this_month,
            COALESCE(SUM(total) FILTER (
              WHERE payment_confirmed_at >= date_trunc('month', now()) - interval '1 month'
                AND payment_confirmed_at <  date_trunc('month', now())
            ), 0) AS last_month
          FROM orders WHERE payment_confirmed_at IS NOT NULL
        `),
        queryOne(`
          SELECT
            (SELECT COUNT(*)::int FROM users WHERE role = 'customer') AS customers,
            (SELECT COUNT(*)::int FROM books WHERE is_active = TRUE)  AS books,
            (SELECT COUNT(*)::int FROM orders)                        AS all_orders,
            (SELECT COUNT(*)::int FROM orders WHERE status = 'processing') AS processing
        `),
        query(`
          SELECT o.id, o.total, o.status, o.created_at, u.name AS user_name
          FROM orders o INNER JOIN users u ON u.id = o.user_id
          ORDER BY o.created_at DESC LIMIT 6
        `),
        query('SELECT status, COUNT(*)::int AS n FROM orders GROUP BY status'),
        query(`
          SELECT c.name, COUNT(oi.id)::int AS sold
          FROM order_items oi
          INNER JOIN books b ON b.id = oi.book_id
          INNER JOIN categories c ON c.id = b.category_id
          GROUP BY c.name ORDER BY sold DESC LIMIT 5
        `),
        query(`
          SELECT id, title, stock_qty FROM books
          WHERE is_active = TRUE AND stock_qty <= 5 ORDER BY stock_qty ASC LIMIT 6
        `),
        query(`
          SELECT to_char(d.day, 'Dy') AS label, COALESCE(SUM(o.total), 0) AS revenue
          FROM generate_series(current_date - interval '6 days', current_date, interval '1 day') AS d(day)
          LEFT JOIN orders o ON o.payment_confirmed_at::date = d.day
          GROUP BY d.day ORDER BY d.day
        `),
      ]);

    const revenue = Number(totals.revenue);
    res.json({
      stats: {
        revenue,
        paidOrders: totals.orders,
        avgOrder: totals.orders > 0 ? revenue / totals.orders : 0,
        customers: counts.customers,
        books: counts.books,
        allOrders: counts.all_orders,
        processing: counts.processing,
        thisMonth: Number(monthly.this_month),
        lastMonth: Number(monthly.last_month),
      },
      recentOrders: recentOrders.map((o) => ({
        id: o.id,
        total: Number(o.total),
        status: o.status,
        createdAt: o.created_at,
        userName: o.user_name,
      })),
      pipeline: Object.fromEntries(pipeline.map((p) => [p.status, p.n])),
      topCategories,
      lowStock,
      salesByDay: salesByDay.map((d) => ({ label: d.label, revenue: Number(d.revenue) })),
    });
  })
);

// --------------------------------------------------------------------------
// Orders
// --------------------------------------------------------------------------

router.get(
  '/admin/orders',
  asyncRoute(async (req, res) => {
    const status = ORDER_STATUSES.includes(req.query.status) ? req.query.status : null;
    const result = await listOrders({ page: pageParam(req), perPage: PER_PAGE, status });

    res.json({
      orders: result.rows.map((o) => ({
        id: o.id,
        total: Number(o.total),
        status: o.status,
        createdAt: o.created_at,
        userName: o.user_name,
        userEmail: o.user_email,
        paymentStatus: o.payment_status,
        paymentMethod: o.payment_method,
      })),
      pagination: paginate(result.page, result.total),
      statuses: ORDER_STATUSES,
    });
  })
);

router.get(
  '/admin/orders/:id',
  asyncRoute(async (req, res) => {
    const order = await getOrder(parseInt(req.params.id, 10));
    if (!order) throw notFound('That order no longer exists.');

    const [items, customer, payment] = await Promise.all([
      getOrderItems(order.id),
      queryOne('SELECT id, name, email FROM users WHERE id = $1', [order.user_id]),
      queryOne('SELECT * FROM payments WHERE order_id = $1 ORDER BY id DESC LIMIT 1', [order.id]),
    ]);

    res.json({
      order: {
        id: order.id,
        status: order.status,
        subtotal: Number(order.subtotal),
        shippingFee: Number(order.shipping_fee),
        total: Number(order.total),
        trackingNumber: order.tracking_number,
        notes: order.notes,
        createdAt: order.created_at,
        paidAt: order.payment_confirmed_at,
        shipping: {
          name: order.shipping_name,
          phone: order.shipping_phone,
          address: order.shipping_address,
          city: order.shipping_city,
          state: order.shipping_state,
          zip: order.shipping_zip,
          country: order.shipping_country,
        },
      },
      items: items.map((i) => ({
        id: i.id,
        title: i.title,
        author: i.author,
        coverType: i.cover_type,
        qty: i.qty,
        unitPrice: Number(i.unit_price),
        subtotal: Number(i.subtotal),
      })),
      customer,
      payment: payment && {
        status: payment.status,
        method: payment.method,
        transactionRef: payment.transaction_ref,
      },
      statuses: ORDER_STATUSES,
    });
  })
);

/**
 * Change status, set a tracking number, and email the customer — the automatic
 * status notice, a message the admin typed, or both.
 */
router.post(
  '/admin/orders/:id/update',
  asyncRoute(async (req, res) => {
    const orderId = parseInt(req.params.id, 10);
    const order = await getOrder(orderId);
    if (!order) throw notFound('That order no longer exists.');

    const customer = await queryOne('SELECT id, name, email FROM users WHERE id = $1', [
      order.user_id,
    ]);

    const newStatus = ORDER_STATUSES.includes(req.body.status) ? req.body.status : order.status;
    const tracking = String(req.body.trackingNumber || '').trim();
    const customSubject = String(req.body.subject || '').trim();
    const customMessage = String(req.body.message || '').trim();

    // Defaults to the customer's account email; the override exists for when
    // they ask us to write to a different address.
    const recipient = String(req.body.recipient || '').trim() || customer.email;

    const statusChanged = newStatus !== order.status;
    const updated = await updateOrderStatus(orderId, newStatus, tracking);

    const notices = [];
    const outcomes = [];

    if (statusChanged) {
      if (newStatus === 'shipped') notices.push(sendShippedNotice(recipient, updated, customer.name));
      else if (newStatus === 'processing') notices.push(sendProcessingNotice(recipient, updated, customer.name));
      else if (newStatus === 'delivered') notices.push(sendDeliveryNotice(recipient, updated, customer.name));
      outcomes.push(`Order marked ${newStatus}.`);
    }

    if (customMessage) {
      notices.push(
        sendOrderUpdateEmail(
          recipient,
          customSubject || `Update on your order #${orderId}`,
          customMessage,
          updated
        )
      );
    }

    if (notices.length) {
      const results = await Promise.all(notices);
      const failed = results.find((r) => !r.ok);
      if (failed) {
        return res.status(502).json({
          message: `Order updated, but the email could not be sent. ${failed.error}`,
        });
      }
      return res.json({ message: `Email sent to ${recipient}. ${outcomes.join(' ')}`.trim() });
    }

    res.json({ message: outcomes.join(' ') || 'Order updated.' });
  })
);

// --------------------------------------------------------------------------
// Products
// --------------------------------------------------------------------------

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 5 * 1024 * 1024 },
  fileFilter: (req, file, cb) =>
    ALLOWED_IMAGE_TYPES.includes(file.mimetype)
      ? cb(null, true)
      : cb(badRequest('Cover must be a JPEG, PNG, WebP, or GIF image.')),
});

const saveCover = (file) => saveImage(file);
const deleteCover = (stored) => deleteImage(stored);

const bool = (v) => v === true || v === 'true' || v === 'on' || v === '1';
const numOrNull = (v) => {
  const s = String(v ?? '').trim();
  if (s === '') return null;
  const n = Number(s);
  return Number.isFinite(n) ? n : null;
};

const readProduct = (b) => ({
  category_id: parseInt(b.categoryId, 10),
  title: String(b.title || '').trim(),
  author: String(b.author || '').trim(),
  isbn: String(b.isbn || '').trim() || null,
  description: String(b.description || '').trim() || null,
  price: numOrNull(b.price) ?? 0,
  sale_price: numOrNull(b.salePrice),
  has_cover_options: bool(b.hasCoverOptions),
  paperback_price: numOrNull(b.paperbackPrice),
  hardcover_price: numOrNull(b.hardcoverPrice),
  stock_qty: parseInt(b.stockQty, 10) || 0,
  pages: numOrNull(b.pages),
  publisher: String(b.publisher || '').trim() || null,
  published_year: numOrNull(b.publishedYear),
  language: String(b.language || '').trim() || 'English',
  is_featured: bool(b.isFeatured),
  is_deal: bool(b.isDeal),
  is_new_arrival: bool(b.isNewArrival),
  is_book_bundle: bool(b.isStationery),
  is_active: bool(b.isActive),
});

const presentAdminBook = (b) => ({
  id: b.id,
  title: b.title,
  author: b.author,
  categoryId: b.category_id,
  categoryName: b.cat_name,
  coverUrl: bookCoverUrl(b),
  coverImage: b.cover_image,
  price: Number(b.price),
  salePrice: b.sale_price != null ? Number(b.sale_price) : null,
  hasCoverOptions: b.has_cover_options,
  paperbackPrice: b.paperback_price != null ? Number(b.paperback_price) : null,
  hardcoverPrice: b.hardcover_price != null ? Number(b.hardcover_price) : null,
  stockQty: b.stock_qty,
  isbn: b.isbn,
  description: b.description,
  pages: b.pages,
  publisher: b.publisher,
  publishedYear: b.published_year,
  language: b.language,
  isFeatured: b.is_featured,
  isDeal: b.is_deal,
  isNewArrival: b.is_new_arrival,
  isStationery: b.is_book_bundle,
  isActive: b.is_active,
});

router.get(
  '/admin/products',
  asyncRoute(async (req, res) => {
    const page = pageParam(req);
    const search = String(req.query.q || '').trim();

    const params = [];
    let where = '';
    if (search) {
      params.push(`%${search}%`);
      where = ' WHERE b.title ILIKE $1 OR b.author ILIKE $1';
    }

    const total = await queryOne(`SELECT COUNT(*)::int AS n FROM books b${where}`, params);
    params.push(PER_PAGE, (page - 1) * PER_PAGE);

    const books = await query(
      `SELECT b.*, c.name AS cat_name FROM books b
       INNER JOIN categories c ON c.id = b.category_id
       ${where} ORDER BY b.created_at DESC
       LIMIT $${params.length - 1} OFFSET $${params.length}`,
      params
    );

    res.json({ books: books.map(presentAdminBook), pagination: paginate(page, total.n), search });
  })
);

router.get(
  '/admin/products/:id',
  asyncRoute(async (req, res) => {
    const book = await queryOne(
      `SELECT b.*, c.name AS cat_name FROM books b
       INNER JOIN categories c ON c.id = b.category_id WHERE b.id = $1`,
      [parseInt(req.params.id, 10)]
    );
    if (!book) throw notFound('That product no longer exists.');
    res.json(presentAdminBook(book));
  })
);

router.post(
  '/admin/products',
  upload.single('cover'),
  asyncRoute(async (req, res) => {
    const data = readProduct(req.body);
    if (!data.title || !data.author || !data.category_id) {
      throw badRequest('Title, author and category are all required.');
    }

    const cover = (await saveCover(req.file)) || String(req.body.coverImage || '').trim() || null;
    const row = await queryOne(
      `INSERT INTO books (
         category_id, title, author, isbn, description, price, sale_price,
         has_cover_options, paperback_price, hardcover_price, stock_qty, cover_image,
         pages, publisher, published_year, language,
         is_featured, is_deal, is_new_arrival, is_book_bundle, is_active
       ) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$20,$21)
       RETURNING id`,
      [
        data.category_id, data.title, data.author, data.isbn, data.description,
        data.price, data.sale_price, data.has_cover_options, data.paperback_price,
        data.hardcover_price, data.stock_qty, cover, data.pages, data.publisher,
        data.published_year, data.language, data.is_featured, data.is_deal,
        data.is_new_arrival, data.is_book_bundle, data.is_active,
      ]
    );

    res.status(201).json({ id: row.id, message: `“${data.title}” has been added.` });
  })
);

router.post(
  '/admin/products/:id',
  upload.single('cover'),
  asyncRoute(async (req, res) => {
    const id = parseInt(req.params.id, 10);
    const existing = await queryOne('SELECT * FROM books WHERE id = $1', [id]);
    if (!existing) throw notFound('That product no longer exists.');

    const data = readProduct(req.body);
    let cover = existing.cover_image;
    if (req.file) {
      cover = await saveCover(req.file);
      await deleteCover(existing.cover_image);
    } else if (String(req.body.coverImage || '').trim()) {
      cover = String(req.body.coverImage).trim();
    }

    await query(
      `UPDATE books SET
         category_id=$1, title=$2, author=$3, isbn=$4, description=$5, price=$6, sale_price=$7,
         has_cover_options=$8, paperback_price=$9, hardcover_price=$10, stock_qty=$11, cover_image=$12,
         pages=$13, publisher=$14, published_year=$15, language=$16,
         is_featured=$17, is_deal=$18, is_new_arrival=$19, is_book_bundle=$20, is_active=$21
       WHERE id=$22`,
      [
        data.category_id, data.title, data.author, data.isbn, data.description,
        data.price, data.sale_price, data.has_cover_options, data.paperback_price,
        data.hardcover_price, data.stock_qty, cover, data.pages, data.publisher,
        data.published_year, data.language, data.is_featured, data.is_deal,
        data.is_new_arrival, data.is_book_bundle, data.is_active, id,
      ]
    );

    res.json({ message: `“${data.title}” has been updated.` });
  })
);

router.delete(
  '/admin/products/:id',
  asyncRoute(async (req, res) => {
    const id = parseInt(req.params.id, 10);
    const book = await queryOne('SELECT * FROM books WHERE id = $1', [id]);
    if (!book) throw notFound('That product no longer exists.');

    const ordered = await queryOne('SELECT COUNT(*)::int AS n FROM order_items WHERE book_id = $1', [id]);

    if (ordered.n > 0) {
      // Order history references this book, so retire it instead of deleting.
      await query('UPDATE books SET is_active = FALSE WHERE id = $1', [id]);
      return res.json({
        message: `“${book.title}” appears in past orders, so it has been hidden from the store instead of deleted.`,
      });
    }

    await query('DELETE FROM books WHERE id = $1', [id]);
    await deleteCover(book.cover_image);
    res.json({ message: `“${book.title}” has been deleted.` });
  })
);

// --------------------------------------------------------------------------
// Categories
// --------------------------------------------------------------------------

const slugify = (s) =>
  String(s).toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

router.get(
  '/admin/categories',
  asyncRoute(async (req, res) => {
    const page = pageParam(req);
    const total = await queryOne('SELECT COUNT(*)::int AS n FROM categories');
    const categories = await query(
      `SELECT c.*, COUNT(b.id)::int AS book_count
       FROM categories c LEFT JOIN books b ON b.category_id = c.id
       GROUP BY c.id ORDER BY c.sort_order, c.name
       LIMIT $1 OFFSET $2`,
      [PER_PAGE, (page - 1) * PER_PAGE]
    );

    res.json({
      categories: categories.map((c) => ({
        id: c.id,
        name: c.name,
        slug: c.slug,
        description: c.description,
        sortOrder: c.sort_order,
        isActive: c.is_active,
        bookCount: c.book_count,
      })),
      pagination: paginate(page, total.n),
    });
  })
);

/** Used by the product form's category dropdown. */
router.get(
  '/admin/categories/all',
  asyncRoute(async (req, res) => {
    res.json(await query('SELECT id, name FROM categories ORDER BY sort_order, name'));
  })
);

router.post(
  '/admin/categories',
  asyncRoute(async (req, res) => {
    const name = String(req.body.name || '').trim();
    const slug = slugify(req.body.slug || name);
    if (!name || !slug) throw badRequest('Enter a category name.');

    if (await queryOne('SELECT id FROM categories WHERE slug = $1', [slug])) {
      throw badRequest(`A category with the address “${slug}” already exists.`);
    }

    await query(
      'INSERT INTO categories (name, slug, description, sort_order) VALUES ($1,$2,$3,$4)',
      [name, slug, String(req.body.description || '').trim() || null, parseInt(req.body.sortOrder, 10) || 0]
    );

    res.status(201).json({ message: `Category “${name}” added.` });
  })
);

router.patch(
  '/admin/categories/:id',
  asyncRoute(async (req, res) => {
    const id = parseInt(req.params.id, 10);
    const existing = await queryOne('SELECT * FROM categories WHERE id = $1', [id]);
    if (!existing) throw notFound('That category no longer exists.');

    const name = String(req.body.name ?? existing.name).trim();
    const slug = slugify(req.body.slug || name);
    if (!name || !slug) throw badRequest('Enter a category name.');

    const clash = await queryOne('SELECT id FROM categories WHERE slug = $1 AND id <> $2', [slug, id]);
    if (clash) throw badRequest(`A category with the address “${slug}” already exists.`);

    // Hiding a category that still holds products would strand them, since the
    // storefront only lists books whose category is active.
    const isActive = req.body.isActive === undefined ? existing.is_active : Boolean(req.body.isActive);

    await query(
      `UPDATE categories
          SET name = $1, slug = $2, description = $3, sort_order = $4, is_active = $5
        WHERE id = $6`,
      [
        name,
        slug,
        String(req.body.description ?? existing.description ?? '').trim() || null,
        parseInt(req.body.sortOrder, 10) || 0,
        isActive,
        id,
      ]
    );

    res.json({ message: `Category “${name}” updated.` });
  })
);

router.delete(
  '/admin/categories/:id',
  asyncRoute(async (req, res) => {
    const category = await queryOne(
      `SELECT c.*, COUNT(b.id)::int AS book_count
       FROM categories c LEFT JOIN books b ON b.category_id = c.id
       WHERE c.id = $1 GROUP BY c.id`,
      [parseInt(req.params.id, 10)]
    );
    if (!category) throw notFound('That category no longer exists.');

    // Books have a RESTRICT foreign key onto categories, so deleting a
    // populated one would fail at the database level anyway.
    if (category.book_count > 0) {
      throw badRequest(
        `“${category.name}” still has ${category.book_count} product(s). Move or delete them first.`
      );
    }

    await query('DELETE FROM categories WHERE id = $1', [category.id]);
    res.json({ message: `Category “${category.name}” deleted.` });
  })
);

// --------------------------------------------------------------------------
// Blog
// --------------------------------------------------------------------------

router.get(
  '/admin/blogs',
  asyncRoute(async (req, res) => {
    const page = pageParam(req);
    const total = await queryOne('SELECT COUNT(*)::int AS n FROM blogs');
    const posts = await query(
      `SELECT id, title, slug, is_active, published_at, created_at
       FROM blogs ORDER BY created_at DESC LIMIT $1 OFFSET $2`,
      [PER_PAGE, (page - 1) * PER_PAGE]
    );
    res.json({ posts, pagination: paginate(page, total.n) });
  })
);

router.get(
  '/admin/blogs/:id',
  asyncRoute(async (req, res) => {
    const post = await queryOne('SELECT * FROM blogs WHERE id = $1', [parseInt(req.params.id, 10)]);
    if (!post) throw notFound('That post no longer exists.');
    res.json(post);
  })
);

const readPost = (b) => {
  const title = String(b.title || '').trim();
  return {
    title,
    slug: slugify(b.slug || title),
    excerpt: String(b.excerpt || '').trim() || null,
    body: String(b.body || '').trim(),
    coverImage: String(b.coverImage || '').trim() || null,
    isActive: bool(b.isActive),
    published: bool(b.published),
  };
};

router.post(
  '/admin/blogs',
  asyncRoute(async (req, res) => {
    const data = readPost(req.body);
    if (!data.title || !data.body) throw badRequest('A post needs both a title and a body.');

    const row = await queryOne(
      `INSERT INTO blogs (title, slug, excerpt, body, cover_image, is_active, published_at)
       VALUES ($1,$2,$3,$4,$5,$6,$7) RETURNING id`,
      [data.title, data.slug, data.excerpt, data.body, data.coverImage, data.isActive,
       data.published ? new Date() : null]
    );

    res.status(201).json({ id: row.id, message: `“${data.title}” has been created.` });
  })
);

router.post(
  '/admin/blogs/:id',
  asyncRoute(async (req, res) => {
    const id = parseInt(req.params.id, 10);
    const existing = await queryOne('SELECT * FROM blogs WHERE id = $1', [id]);
    if (!existing) throw notFound('That post no longer exists.');

    const data = readPost(req.body);
    // Keep the original publication date rather than bumping it on every edit.
    const publishedAt = data.published ? existing.published_at || new Date() : null;

    await query(
      `UPDATE blogs SET title=$1, slug=$2, excerpt=$3, body=$4, cover_image=$5,
                        is_active=$6, published_at=$7 WHERE id=$8`,
      [data.title, data.slug, data.excerpt, data.body, data.coverImage, data.isActive, publishedAt, id]
    );

    res.json({ message: `“${data.title}” has been updated.` });
  })
);

router.delete(
  '/admin/blogs/:id',
  asyncRoute(async (req, res) => {
    await query('DELETE FROM blogs WHERE id = $1', [parseInt(req.params.id, 10)]);
    res.json({ message: 'Post deleted.' });
  })
);

export default router;
