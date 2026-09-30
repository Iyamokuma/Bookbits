import { query, queryOne } from '../db.js';
import { STORE_BOOKS_PER_PAGE } from './storefront.js';

const BOOK_SELECT = `
  SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
  FROM books b
  INNER JOIN categories c ON c.id = b.category_id
`;

const FALLBACK_COVER =
  'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=400&q=80';

/**
 * Resolve any stored upload reference (blog covers and the like) to a URL the
 * browser can load. Relative paths are made absolute so they don't resolve
 * against the current route.
 */
export function mediaUrl(value) {
  const v = String(value || '').trim();
  if (v === '') return null;
  if (/^https?:\/\//i.test(v)) return v;
  return '/' + v.replace(/^\/+/, '');
}

/** Resolve a stored cover_image value to a browser-usable URL. */
export function bookCoverUrl(row) {
  const c = String(row?.cover_image || '').trim();
  if (c === '') return FALLBACK_COVER;
  if (/^https?:\/\//i.test(c)) return c;
  if (c.startsWith('uploads/')) return '/' + c.replace(/^\/+/, '');
  return '/img/covers/' + c.replace(/^\/+/, '');
}

function shopFilters(catSlug, search, params) {
  let where = ' WHERE b.is_active = TRUE AND c.is_active = TRUE';
  if (catSlug) {
    params.push(catSlug);
    where += ` AND c.slug = $${params.length}`;
  }
  if (search) {
    params.push(`%${search}%`);
    where += ` AND (b.title ILIKE $${params.length} OR b.author ILIKE $${params.length} OR COALESCE(b.isbn, '') ILIKE $${params.length})`;
  }
  return where;
}

export async function countBooksForShop({ catSlug = null, search = '' } = {}) {
  const params = [];
  const where = shopFilters(catSlug, search, params);
  const row = await queryOne(
    `SELECT COUNT(*)::int AS n FROM books b INNER JOIN categories c ON c.id = b.category_id${where}`,
    params
  );
  return row?.n ?? 0;
}

export async function fetchBooksForShop({
  catSlug = null,
  search = '',
  page = 1,
  perPage = STORE_BOOKS_PER_PAGE,
} = {}) {
  const params = [];
  const where = shopFilters(catSlug, search, params);
  const offset = (Math.max(1, page) - 1) * perPage;
  params.push(perPage, offset);

  return query(
    BOOK_SELECT +
      `${where} ORDER BY b.created_at DESC LIMIT $${params.length - 1} OFFSET $${params.length}`,
    params
  );
}

export async function countStationeryBooks(search = '') {
  const params = [];
  let where = ' WHERE b.is_active = TRUE AND c.is_active = TRUE AND b.is_book_bundle = TRUE';
  if (search) {
    params.push(`%${search}%`);
    where += ` AND (b.title ILIKE $${params.length} OR b.author ILIKE $${params.length})`;
  }
  const row = await queryOne(
    `SELECT COUNT(*)::int AS n FROM books b INNER JOIN categories c ON c.id = b.category_id${where}`,
    params
  );
  return row?.n ?? 0;
}

export async function fetchStationeryBooksPage({ search = '', page = 1, perPage = STORE_BOOKS_PER_PAGE } = {}) {
  const params = [];
  let where = ' WHERE b.is_active = TRUE AND c.is_active = TRUE AND b.is_book_bundle = TRUE';
  if (search) {
    params.push(`%${search}%`);
    where += ` AND (b.title ILIKE $${params.length} OR b.author ILIKE $${params.length})`;
  }
  const offset = (Math.max(1, page) - 1) * perPage;
  params.push(perPage, offset);
  return query(
    BOOK_SELECT + `${where} ORDER BY b.updated_at DESC LIMIT $${params.length - 1} OFFSET $${params.length}`,
    params
  );
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
  return query(`
    SELECT slug, name, description, bg_color
    FROM categories WHERE is_active = TRUE
    ORDER BY sort_order, name
  `);
}

/** Up to `limitPerCategory` random in-stock listings per active category (homepage). */
export async function fetchRandomBooksGroupedByCategory(limitPerCategory = 8) {
  const rows = await query(
    `
    WITH picked AS (
      SELECT
        b.*,
        c.slug AS cat_slug,
        c.name AS cat_name,
        c.sort_order AS cat_sort,
        ROW_NUMBER() OVER (PARTITION BY b.category_id ORDER BY RANDOM()) AS rn
      FROM books b
      INNER JOIN categories c ON c.id = b.category_id
      WHERE b.is_active = TRUE AND c.is_active = TRUE
    )
    SELECT * FROM picked
    WHERE rn <= $1
    ORDER BY cat_sort, cat_name, rn
    `,
    [limitPerCategory]
  );

  const bySlug = new Map();
  for (const row of rows) {
    const slug = row.cat_slug;
    if (!bySlug.has(slug)) {
      bySlug.set(slug, {
        slug,
        name: row.cat_name,
        books: [],
      });
    }
    bySlug.get(slug).books.push(row);
  }
  return [...bySlug.values()];
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
