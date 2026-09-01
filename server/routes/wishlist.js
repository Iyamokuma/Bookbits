import { Router } from 'express';
import { query, queryOne } from '../db.js';
import { presentBook } from './catalog.js';
import { asyncRoute, unauthorized, notFound, badRequest } from '../lib/http.js';

const router = Router();

const requireUser = (req) => {
  if (!req.user) throw unauthorized('Please sign in to use your wishlist.');
  return req.user;
};

router.get(
  '/wishlist',
  asyncRoute(async (req, res) => {
    const user = requireUser(req);
    const rows = await query(
      `SELECT b.*, c.slug AS cat_slug, c.name AS cat_name, w.created_at AS saved_at
         FROM wishlist w
         INNER JOIN books b ON b.id = w.book_id
         INNER JOIN categories c ON c.id = b.category_id
        WHERE w.user_id = $1 AND b.is_active = TRUE
        ORDER BY w.created_at DESC`,
      [user.id]
    );
    res.json(rows.map((r) => ({ ...presentBook(r), savedAt: r.saved_at })));
  })
);

/** Ids only, so the storefront can render the saved state without a round trip per card. */
router.get(
  '/wishlist/ids',
  asyncRoute(async (req, res) => {
    if (!req.user) return res.json([]);
    const rows = await query('SELECT book_id FROM wishlist WHERE user_id = $1', [req.user.id]);
    res.json(rows.map((r) => r.book_id));
  })
);

router.post(
  '/wishlist',
  asyncRoute(async (req, res) => {
    const user = requireUser(req);
    const bookId = parseInt(req.body.bookId, 10);
    if (!bookId) throw badRequest('Which book would you like to save?');

    const book = await queryOne('SELECT id, title FROM books WHERE id = $1 AND is_active = TRUE', [bookId]);
    if (!book) throw notFound('That book is no longer available.');

    // Saving twice is not an error; the unique constraint makes this idempotent.
    await query(
      `INSERT INTO wishlist (user_id, book_id) VALUES ($1, $2)
       ON CONFLICT (user_id, book_id) DO NOTHING`,
      [user.id, bookId]
    );

    res.status(201).json({ message: `“${book.title}” saved to your wishlist.`, bookId });
  })
);

router.delete(
  '/wishlist/:bookId',
  asyncRoute(async (req, res) => {
    const user = requireUser(req);
    const bookId = parseInt(req.params.bookId, 10);
    await query('DELETE FROM wishlist WHERE user_id = $1 AND book_id = $2', [user.id, bookId]);
    res.json({ message: 'Removed from your wishlist.', bookId });
  })
);

export default router;
