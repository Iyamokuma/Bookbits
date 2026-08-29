import { query, queryOne, transaction } from '../db.js';
import { round2 } from './money.js';

export const COVER_PAPERBACK = 'paperback';
export const COVER_HARDCOVER = 'hardcover';

// --------------------------------------------------------------------------
// Cart keys — a book with cover options needs one line per cover type, so the
// key is "<bookId>:<coverType>" and falls back to plain "<bookId>" otherwise.
// --------------------------------------------------------------------------

export function encodeKey(bookId, coverType) {
  return coverType === COVER_PAPERBACK || coverType === COVER_HARDCOVER
    ? `${bookId}:${coverType}`
    : String(bookId);
}

export function decodeKey(key) {
  const [rawId, rawCover] = String(key).split(':', 2);
  const bookId = parseInt(rawId, 10) || 0;
  const coverType =
    rawCover === COVER_PAPERBACK || rawCover === COVER_HARDCOVER ? rawCover : null;
  return { bookId, coverType };
}

// --------------------------------------------------------------------------
// Cover-type pricing
// --------------------------------------------------------------------------

export function bookSupportsCoverOptions(book) {
  if (book?.has_cover_options) return true;
  const p = Number(book?.paperback_price || 0);
  const h = Number(book?.hardcover_price || 0);
  return p > 0 && h > 0 && Math.abs(p - h) > 0.0001;
}

export function validateCoverType(book, coverType) {
  if (!bookSupportsCoverOptions(book)) return !coverType;
  return coverType === COVER_PAPERBACK || coverType === COVER_HARDCOVER;
}

export function bookCoverPrice(book, coverType) {
  if (bookSupportsCoverOptions(book)) {
    return Number(
      coverType === COVER_HARDCOVER ? book.hardcover_price : book.paperback_price
    );
  }
  return Number(book.sale_price ?? book.price);
}

// --------------------------------------------------------------------------
// Session-backed cart
// --------------------------------------------------------------------------

/** @returns {Record<string, number>} cart key -> qty */
export function getRaw(session) {
  const raw = session.cart && typeof session.cart === 'object' ? session.cart : {};
  const out = {};
  for (const [key, qty] of Object.entries(raw)) {
    const n = Math.max(0, parseInt(qty, 10) || 0);
    if (key && n > 0) out[key] = n;
  }
  return out;
}

export function setRaw(session, items) {
  session.cart = items;
}

export function totalQty(session) {
  return Object.values(getRaw(session)).reduce((a, b) => a + b, 0);
}

// --------------------------------------------------------------------------
// Persistence for logged-in users
// --------------------------------------------------------------------------

export async function loadFromDatabase(session, userId) {
  const rows = await query('SELECT book_id, cover_type, qty FROM cart_items WHERE user_id = $1', [
    userId,
  ]);
  const map = {};
  for (const r of rows) map[encodeKey(r.book_id, r.cover_type)] = r.qty;
  setRaw(session, map);
}

export async function syncToDatabase(session, userId) {
  const items = getRaw(session);
  await transaction(async (client) => {
    await client.query('DELETE FROM cart_items WHERE user_id = $1', [userId]);
    for (const [key, qty] of Object.entries(items)) {
      const { bookId, coverType } = decodeKey(key);
      if (bookId < 1) continue;
      await client.query(
        `INSERT INTO cart_items (user_id, session_id, book_id, cover_type, qty)
         VALUES ($1, $2, $3, $4, $5)`,
        [userId, `u${userId}`, bookId, coverType, qty]
      );
    }
  });
}

/** Sum a guest cart into the user's saved cart at login, then persist. */
export async function mergeGuestIntoUser(session, userId) {
  const guest = getRaw(session);
  await loadFromDatabase(session, userId);
  const merged = getRaw(session);
  for (const [key, qty] of Object.entries(guest)) {
    merged[key] = (merged[key] || 0) + qty;
  }
  setRaw(session, merged);
  await syncToDatabase(session, userId);
}

export async function clearForUser(session, userId) {
  await query('DELETE FROM cart_items WHERE user_id = $1', [userId]);
  setRaw(session, {});
}

export async function bootstrap(session, user) {
  if (!session.cart) session.cart = {};
  if (user && totalQty(session) === 0) {
    await loadFromDatabase(session, user.id);
  }
}

// --------------------------------------------------------------------------
// Mutations
// --------------------------------------------------------------------------

async function fetchBookCartData(bookId) {
  return queryOne(
    `SELECT id, title, is_active, stock_qty, price, sale_price,
            has_cover_options, paperback_price, hardcover_price
     FROM books WHERE id = $1 AND is_active = TRUE LIMIT 1`,
    [bookId]
  );
}

async function persist(session, user) {
  if (user) await syncToDatabase(session, user.id);
}

export async function add(session, user, bookId, qty, coverType = null) {
  if (qty < 1) qty = 1;
  const book = await fetchBookCartData(bookId);
  if (!book) return { ok: false, message: 'This book is not available.' };

  coverType = coverType?.trim() || null;
  if (!validateCoverType(book, coverType)) {
    return { ok: false, message: 'Please select a valid cover type.' };
  }
  if (bookSupportsCoverOptions(book)) {
    if (Number(book.paperback_price) <= 0 || Number(book.hardcover_price) <= 0) {
      return { ok: false, message: 'Cover prices are not configured for this book.' };
    }
  }

  const cart = getRaw(session);
  const key = encodeKey(bookId, coverType);
  const current = cart[key] || 0;
  if (current + qty > book.stock_qty) {
    return { ok: false, message: 'Not enough copies in stock.' };
  }

  cart[key] = current + qty;
  setRaw(session, cart);
  await persist(session, user);
  return { ok: true, message: 'Added to cart' };
}

export async function setQty(session, user, bookId, qty, coverType = null) {
  if (qty < 1) return remove(session, user, bookId, coverType);

  const book = await fetchBookCartData(bookId);
  if (!book) return { ok: false, message: 'This book is not available.' };

  coverType = coverType?.trim() || null;
  if (!validateCoverType(book, coverType)) {
    return { ok: false, message: 'Please select a valid cover type.' };
  }

  const cart = getRaw(session);
  cart[encodeKey(bookId, coverType)] = Math.min(qty, book.stock_qty);
  setRaw(session, cart);
  await persist(session, user);
  return { ok: true, message: 'Cart updated' };
}

export async function remove(session, user, bookId, coverType = null) {
  const cart = getRaw(session);
  delete cart[encodeKey(bookId, coverType)];
  setRaw(session, cart);
  await persist(session, user);
  return { ok: true, message: 'Removed' };
}

// --------------------------------------------------------------------------
// Reading the cart with joined book data
// --------------------------------------------------------------------------

export async function linesWithBooks(session) {
  const cart = getRaw(session);
  const keys = Object.keys(cart);
  if (keys.length === 0) return [];

  const ids = [...new Set(keys.map((k) => decodeKey(k).bookId).filter((id) => id > 0))];
  if (ids.length === 0) return [];

  const rows = await query(
    `SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
     FROM books b
     INNER JOIN categories c ON c.id = b.category_id
     WHERE b.id = ANY($1::int[]) AND b.is_active = TRUE`,
    [ids]
  );
  const booksById = new Map(rows.map((r) => [r.id, r]));

  const lines = [];
  for (const [key, qty] of Object.entries(cart)) {
    const { bookId, coverType } = decodeKey(key);
    const book = booksById.get(bookId);
    if (!book || !validateCoverType(book, coverType)) continue;
    lines.push({ key, book, cover_type: coverType, qty });
  }
  return lines;
}

export function subtotal(lines) {
  return round2(
    lines.reduce((sum, l) => sum + bookCoverPrice(l.book, l.cover_type) * l.qty, 0)
  );
}
