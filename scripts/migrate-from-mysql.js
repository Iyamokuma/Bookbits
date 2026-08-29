/**
 * One-off data migration: copies the existing XAMPP/cPanel MySQL database into
 * Supabase Postgres.
 *
 * Run the schema first (npm run db:push), then:
 *
 *   MYSQL_HOST=localhost MYSQL_USER=root MYSQL_PASSWORD= MYSQL_DATABASE=bookbits \
 *     node scripts/migrate-from-mysql.js
 *
 * Idempotent: existing rows are skipped, so it is safe to re-run after fixing
 * a failure part-way through.
 */
import mysql from 'mysql2/promise';
import { createMigrationClient } from './migration-client.js';

// Bulk loading runs over the session pooler, not the app's transaction pooler.
const pool = createMigrationClient();
await pool.connect();

const source = await mysql.createConnection({
  host: process.env.MYSQL_HOST || 'localhost',
  user: process.env.MYSQL_USER || 'root',
  password: process.env.MYSQL_PASSWORD || '',
  database: process.env.MYSQL_DATABASE || 'bookbits',
});

const tinyint = (v) => v === 1 || v === true;
const nullIfEmpty = (v) => (v === '' || v === undefined ? null : v);

/** MySQL DATETIME columns come back as local-time Date objects or null. */
const ts = (v) => (v ? new Date(v) : null);

async function rowsOf(table) {
  try {
    const [rows] = await source.query(`SELECT * FROM \`${table}\``);
    return rows;
  } catch (err) {
    if (err.code === 'ER_NO_SUCH_TABLE') {
      console.log(`  (no ${table} table in the source database — skipping)`);
      return [];
    }
    throw err;
  }
}

/**
 * Clear the target tables so the schema's seed rows cannot collide with the
 * real data. Without this, the seeded categories occupy ids 1-6 and the
 * incoming categories are silently skipped, leaving books pointing at the
 * wrong category.
 */
async function truncateTargets() {
  console.log('Clearing target tables (including seeded categories)…\n');
  await pool.query(`
    TRUNCATE wishlist, cart_items, payments, order_items, orders,
             blogs, books, categories, users
    RESTART IDENTITY CASCADE
  `);
}

/**
 * Preserve the original primary keys so foreign keys line up. The identity
 * sequences are resynced at the end.
 */
async function copy(table, columns, mapRow) {
  const rows = await rowsOf(table);
  if (rows.length === 0) {
    console.log(`${table}: nothing to copy`);
    return;
  }

  const cols = ['id', ...columns];
  const placeholders = cols.map((_, i) => `$${i + 1}`).join(', ');
  const sql = `INSERT INTO ${table} (${cols.join(', ')}) OVERRIDING SYSTEM VALUE
               VALUES (${placeholders}) ON CONFLICT (id) DO NOTHING`;

  let copied = 0;
  for (const row of rows) {
    try {
      const res = await pool.query(sql, [row.id, ...mapRow(row)]);
      copied += res.rowCount;
    } catch (err) {
      console.error(`  ${table} id=${row.id} failed: ${err.message}`);
    }
  }
  console.log(`${table}: ${copied} of ${rows.length} row(s) copied`);
}

console.log('Migrating MySQL -> Supabase Postgres\n');

await truncateTargets();

await copy('users', ['name', 'email', 'password', 'role', 'email_verified_at', 'remember_token', 'created_at', 'updated_at'],
  (r) => [r.name, r.email, r.password, r.role || 'customer', ts(r.email_verified_at), nullIfEmpty(r.remember_token), ts(r.created_at), ts(r.updated_at)]);

await copy('categories', ['name', 'slug', 'description', 'icon', 'bg_color', 'sort_order', 'is_active', 'created_at', 'updated_at'],
  (r) => [r.name, r.slug, r.description, r.icon, r.bg_color, r.sort_order ?? 0, tinyint(r.is_active), ts(r.created_at), ts(r.updated_at)]);

await copy('books',
  ['category_id','title','author','isbn','description','price','sale_price','has_cover_options','paperback_price','hardcover_price','stock_qty','cover_image','pages','publisher','published_year','language','is_featured','is_deal','is_new_arrival','is_book_bundle','is_active','created_at','updated_at'],
  (r) => [r.category_id, r.title, r.author, nullIfEmpty(r.isbn), r.description, r.price, r.sale_price,
          tinyint(r.has_cover_options), r.paperback_price, r.hardcover_price, r.stock_qty ?? 0, r.cover_image,
          r.pages, r.publisher, r.published_year, r.language || 'English',
          tinyint(r.is_featured), tinyint(r.is_deal), tinyint(r.is_new_arrival), tinyint(r.is_book_bundle),
          tinyint(r.is_active), ts(r.created_at), ts(r.updated_at)]);

await copy('orders',
  ['user_id','status','subtotal','discount','shipping_fee','total','shipping_name','shipping_phone','shipping_address','shipping_city','shipping_state','shipping_zip','shipping_country','notes','tracking_number','payment_confirmed_at','created_at','updated_at'],
  (r) => [r.user_id, r.status || 'pending', r.subtotal ?? 0, r.discount ?? 0, r.shipping_fee ?? 0, r.total ?? 0,
          r.shipping_name, r.shipping_phone, r.shipping_address, r.shipping_city, r.shipping_state,
          r.shipping_zip, r.shipping_country, r.notes, nullIfEmpty(r.tracking_number),
          ts(r.payment_confirmed_at), ts(r.created_at), ts(r.updated_at)]);

// The MySQL order_items table has no created_at column, so date the line items
// from the order they belong to rather than losing the history to now().
const orderDates = new Map(
  (await rowsOf('orders')).map((o) => [o.id, ts(o.created_at) || new Date()])
);

await copy('order_items', ['order_id','book_id','title','author','cover_type','qty','unit_price','subtotal','created_at'],
  (r) => [r.order_id, r.book_id, r.title, r.author, nullIfEmpty(r.cover_type), r.qty, r.unit_price, r.subtotal,
          ts(r.created_at) || orderDates.get(r.order_id) || new Date()]);

await copy('payments', ['order_id','method','status','amount','currency','transaction_ref','gateway_response','paid_at','created_at','updated_at'],
  (r) => [r.order_id, r.method || 'card', r.status || 'pending', r.amount, r.currency || 'NGN',
          nullIfEmpty(r.transaction_ref), safeJson(r.gateway_response), ts(r.paid_at), ts(r.created_at), ts(r.updated_at)]);

await copy('blogs', ['title','slug','excerpt','body','cover_image','is_active','published_at','created_at','updated_at'],
  (r) => [r.title, r.slug, r.excerpt, r.body, r.cover_image, tinyint(r.is_active), ts(r.published_at), ts(r.created_at), ts(r.updated_at)]);

await copy('wishlist', ['user_id','book_id','created_at'],
  (r) => [r.user_id, r.book_id, ts(r.created_at)]);

// Identity columns don't advance when ids are supplied explicitly, so the next
// insert would collide with an existing row unless the sequences are moved on.
console.log('\nResyncing identity sequences…');
for (const table of ['users', 'categories', 'books', 'orders', 'order_items', 'payments', 'blogs', 'wishlist']) {
  await pool.query(
    `SELECT setval(pg_get_serial_sequence('${table}', 'id'), COALESCE((SELECT MAX(id) FROM ${table}), 1))`
  );
}

console.log('Done.');

await source.end();
await pool.end();

function safeJson(v) {
  if (v === null || v === undefined || v === '') return null;
  if (typeof v === 'object') return v;
  try {
    return JSON.parse(v);
  } catch {
    return { raw: String(v) };
  }
}
