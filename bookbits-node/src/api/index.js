import { Router } from 'express';
import { config } from '../config.js';
import { legalPages } from '../content/legal.js';
import { queryOne } from '../db.js';
import { getOrder } from '../lib/orders.js';

import sessionRoutes from './session.js';
import catalogRoutes from './catalog.js';
import cartRoutes from './cart.js';
import authRoutes from './auth.js';
import orderRoutes from './orders.js';
import adminRoutes from './admin.js';
import { asyncRoute, notFound, ApiError } from './helpers.js';

const router = Router();

router.use(sessionRoutes);
router.use(catalogRoutes);
router.use(cartRoutes);
router.use(authRoutes);
router.use(orderRoutes);
router.use(adminRoutes);

router.get('/legal/:slug', (req, res, next) => {
  const page = legalPages[req.params.slug];
  if (!page) return next(notFound('No such page.'));
  res.json(page);
});

/** Data the Klump checkout widget needs to open. */
router.get(
  '/payment/klump/:orderId',
  asyncRoute(async (req, res) => {
    const order = await getOrder(parseInt(req.params.orderId, 10));
    if (!order || !config.klump.publicKey) {
      throw notFound('This payment method is temporarily unavailable.');
    }
    const user = await queryOne('SELECT name, email FROM users WHERE id = $1', [order.user_id]);
    res.json({
      publicKey: config.klump.publicKey,
      currency: config.store.currencyCode,
      order: { id: order.id, total: Number(order.total) },
      customer: user,
    });
  })
);

router.use((req, res) => {
  res.status(404).json({ error: 'No such endpoint.' });
});

/** JSON error handler — never let an HTML error page reach a fetch() caller. */
router.use((err, req, res, _next) => {
  if (err instanceof ApiError) {
    return res.status(err.status).json({ error: err.message, details: err.details });
  }
  if (err?.code === 'LIMIT_FILE_SIZE') {
    return res.status(413).json({ error: 'That file is too large. The limit is 5 MB.' });
  }

  console.error('[api]', err);
  res.status(500).json({
    error:
      config.env === 'development'
        ? err.message
        : 'Something went wrong on our side. Please try again.',
  });
});

export default router;
