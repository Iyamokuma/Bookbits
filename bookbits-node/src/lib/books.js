import { query, queryOne } from '../db.js';

const BOOK_SELECT = `
  SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
  FROM books b
  INNER JOIN categories c ON c.id = b.category_id
`;

const FALLBACK_COVER =
  'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=400&q=80';

/** Resolve a stored cover_image value to a browser-usable URL. */
export function bookCoverUrl(row) {
  const c = String(row?.cover_image || '').trim();
  if (c === '') return FALLBACK_COVER;
  if (/^https?:\/\//i.test(c)) return c;
  if (c.startsWith('uploads/')) return '/' + c.replace(/^\/+/, '');
  return '/img/covers/' + c.replace(/^\/+/, '');
}

export async function fetchBooksForShop({ catSlug = null, search = '', limit = 120 } = {}) {
  const params = [];
  let sql = BOOK_SELECT + ' WHERE b.is_active = TRUE';

  if (catSlug) {
    params.push(catSlug);
    sql += ` AND c.slug = $${params.length}`;
  }
  if (search) {
    params.push(`%${search}%`);
    // ILIKE is Postgres' case-insensitive LIKE, matching MySQL's default
    // case-insensitive collation behaviour in the original queries.
    sql += ` AND (b.title ILIKE $${params.length} OR b.author ILIKE $${params.length} OR COALESCE(b.isbn, '') ILIKE $${params.length})`;
  }

  params.push(limit);
  sql += ` ORDER BY b.created_at DESC LIMIT $${params.length}`;

  return query(sql, params);
}

export async function fetchBookById(id) {
  return queryOne(BOOK_SELECT + ' WHERE b.id = $1 AND b.is_active = TRUE LIMIT 1', [id]);
}

export async function fetchRelatedBooks(categoryId, excludeId, limit = 4) {
  return query(
    BOOK_SELECT +
      ' WHERE b.category_id = $1 AND b.id <> $2 AND b.is_active = TRUE ORDER BY b.created_at DESC LIMIT $3',
    [categoryId, excludeId, limit]
  );
}

const flagQuery = (column) => (limit = 8) =>
  query(
    BOOK_SELECT +
      ` WHERE b.is_active = TRUE AND b.${column} = TRUE ORDER BY b.updated_at DESC LIMIT $1`,
    [limit]
  );

export const fetchFeaturedBooks = flagQuery('is_featured');
export const fetchDealBooks = flagQuery('is_deal');
export const fetchNewArrivalBooks = flagQuery('is_new_arrival');

/** Stationery is stored as is_book_bundle for backwards compatibility. */
export const fetchStationeryBooks = (limit = 200) => flagQuery('is_book_bundle')(limit);

export async function fetchCategoriesForNav() {
  return query('SELECT slug, name FROM categories WHERE is_active = TRUE ORDER BY sort_order, name');
}

export async function fetchAllCategories() {
  return query(`
    SELECT c.*, COUNT(b.id)::int AS book_count
    FROM categories c
    LEFT JOIN books b ON b.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order, c.name
  `);
}
