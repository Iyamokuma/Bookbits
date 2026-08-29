import { Router } from 'express';
import { queryOne, query } from '../db.js';
import {
  fetchBooksForShop,
  fetchBookById,
  fetchRelatedBooks,
  fetchDealBooks,
  fetchNewArrivalBooks,
  fetchStationeryBooks,
  fetchCategoriesForNav,
  bookCoverUrl,
} from '../lib/books.js';
import { bookSupportsCoverOptions } from '../lib/cart.js';
import { asyncRoute, notFound } from '../lib/http.js';

const router = Router();

/**
 * Shape a book row for the client: derive the display price and cover URL here
 * so the pricing rules live in one place rather than in every component.
 */
export function presentBook(b) {
  const hasCovers = bookSupportsCoverOptions(b);
  return {
    id: b.id,
    title: b.title,
    author: b.author,
    slugCategory: b.cat_slug,
    categoryName: b.cat_name,
    categoryId: b.category_id,
    coverUrl: bookCoverUrl(b),
    hasCoverOptions: hasCovers,
    price: hasCovers ? Number(b.paperback_price) : Number(b.sale_price ?? b.price),
    wasPrice: !hasCovers && b.sale_price != null ? Number(b.price) : null,
    paperbackPrice: b.paperback_price != null ? Number(b.paperback_price) : null,
    hardcoverPrice: b.hardcover_price != null ? Number(b.hardcover_price) : null,
    stock: b.stock_qty,
    inStock: b.stock_qty > 0,
  };
}

const presentBookDetail = (b) => ({
  ...presentBook(b),
  description: b.description,
  isbn: b.isbn,
  pages: b.pages,
  publisher: b.publisher,
  publishedYear: b.published_year,
  language: b.language,
});

router.get(
  '/categories',
  asyncRoute(async (req, res) => {
    const rows = await fetchCategoriesForNav();
    res.json(
      rows.map((c) => ({
        slug: c.slug,
        name: c.name,
        description: c.description,
        bgColor: c.bg_color,
      }))
    );
  })
);

router.get(
  '/home',
  asyncRoute(async (req, res) => {
    const [newArrivals, deals, stationery, posts] = await Promise.all([
      fetchNewArrivalBooks(8),
      fetchDealBooks(8),
      fetchStationeryBooks(4),
      query(
        `SELECT id, title, slug, excerpt, cover_image, published_at FROM blogs
         WHERE is_active = TRUE AND published_at IS NOT NULL AND published_at <= now()
         ORDER BY published_at DESC LIMIT 3`
      ),
    ]);

    res.json({
      newArrivals: newArrivals.map(presentBook),
      deals: deals.map(presentBook),
      stationery: stationery.map(presentBook),
      posts,
    });
  })
);

router.get(
  '/books',
  asyncRoute(async (req, res) => {
    const catSlug = req.query.cat ? String(req.query.cat) : null;
    const search = req.query.q ? String(req.query.q).trim() : '';

    const [books, category] = await Promise.all([
      fetchBooksForShop({ catSlug, search }),
      catSlug
        ? queryOne('SELECT slug, name FROM categories WHERE slug = $1 LIMIT 1', [catSlug])
        : Promise.resolve(null),
    ]);

    res.json({ books: books.map(presentBook), category, search });
  })
);

router.get(
  '/books/:id',
  asyncRoute(async (req, res) => {
    const book = await fetchBookById(parseInt(req.params.id, 10));
    if (!book) throw notFound('That title is no longer available.');

    const related = await fetchRelatedBooks(book.category_id, book.id, 4);
    res.json({ book: presentBookDetail(book), related: related.map(presentBook) });
  })
);

router.get(
  '/stationery',
  asyncRoute(async (req, res) => {
    const search = String(req.query.q || '').trim().toLowerCase();
    let books = await fetchStationeryBooks(200);
    if (search) {
      books = books.filter(
        (b) =>
          b.title.toLowerCase().includes(search) || b.author.toLowerCase().includes(search)
      );
    }
    res.json({ books: books.map(presentBook), search });
  })
);

router.get(
  '/blog',
  asyncRoute(async (req, res) => {
    res.json(
      await query(
        `SELECT id, title, slug, excerpt, cover_image, published_at FROM blogs
         WHERE is_active = TRUE AND published_at IS NOT NULL AND published_at <= now()
         ORDER BY published_at DESC`
      )
    );
  })
);

router.get(
  '/blog/:slug',
  asyncRoute(async (req, res) => {
    const post = await queryOne(
      `SELECT * FROM blogs
       WHERE slug = $1 AND is_active = TRUE AND published_at IS NOT NULL AND published_at <= now()
       LIMIT 1`,
      [req.params.slug]
    );
    if (!post) throw notFound("That article doesn't exist or is no longer published.");
    res.json(post);
  })
);

export default router;
