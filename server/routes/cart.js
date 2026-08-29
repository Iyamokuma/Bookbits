import { Router } from 'express';
import * as cart from '../lib/cart.js';
import { bookCoverUrl } from '../lib/books.js';
import { asyncRoute, badRequest } from '../lib/http.js';

const router = Router();

async function cartPayload(session) {
  const lines = await cart.linesWithBooks(session);
  return {
    lines: lines.map((l) => ({
      key: l.key,
      qty: l.qty,
      coverType: l.cover_type,
      unitPrice: cart.bookCoverPrice(l.book, l.cover_type),
      lineTotal: cart.bookCoverPrice(l.book, l.cover_type) * l.qty,
      book: {
        id: l.book.id,
        title: l.book.title,
        author: l.book.author,
        coverUrl: bookCoverUrl(l.book),
        stock: l.book.stock_qty,
      },
    })),
    subtotal: cart.subtotal(lines),
    count: cart.totalQty(session),
  };
}

router.get(
  '/cart',
  asyncRoute(async (req, res) => {
    res.json(await cartPayload(req.session));
  })
);

router.post(
  '/cart/items',
  asyncRoute(async (req, res) => {
    const bookId = parseInt(req.body.bookId, 10);
    if (!bookId) throw badRequest('Which book would you like to add?');

    const result = await cart.add(
      req.session,
      req.user,
      bookId,
      parseInt(req.body.qty, 10) || 1,
      req.body.coverType || null
    );
    if (!result.ok) throw badRequest(result.message);

    res.json({ message: result.message, ...(await cartPayload(req.session)) });
  })
);

router.patch(
  '/cart/items',
  asyncRoute(async (req, res) => {
    const { bookId, coverType } = cart.decodeKey(req.body.key);
    const result = await cart.setQty(
      req.session,
      req.user,
      bookId,
      parseInt(req.body.qty, 10) || 0,
      coverType
    );
    if (!result.ok) throw badRequest(result.message);

    res.json({ message: result.message, ...(await cartPayload(req.session)) });
  })
);

router.delete(
  '/cart/items',
  asyncRoute(async (req, res) => {
    const { bookId, coverType } = cart.decodeKey(req.body.key);
    await cart.remove(req.session, req.user, bookId, coverType);
    res.json({ message: 'Removed from your cart.', ...(await cartPayload(req.session)) });
  })
);

export default router;
