import pg from 'pg';
import { config } from './config.js';

// NUMERIC columns arrive as strings by default so large values keep precision.
// Every money column here fits comfortably in a JS double, and the templates
// and totals arithmetic all expect numbers.
pg.types.setTypeParser(pg.types.builtins.NUMERIC, (v) => (v === null ? null : parseFloat(v)));
pg.types.setTypeParser(pg.types.builtins.INT8, (v) => (v === null ? null : parseInt(v, 10)));

// Serverless runs many short-lived instances in parallel, so each one keeps a
// tiny pool; a long-running server keeps a normal one. Point DATABASE_URL at
// Supabase's transaction pooler (port 6543) when deploying to Vercel.
const isServerless = Boolean(process.env.VERCEL);

export const pool = new pg.Pool({
  connectionString: config.db.connectionString,
  ssl: config.db.ssl,
  max: isServerless ? 1 : 10,
  idleTimeoutMillis: isServerless ? 10_000 : 30_000,
  connectionTimeoutMillis: 10_000,
});

pool.on('error', (err) => {
  console.error('[db] idle client error:', err.message);
});

/** Run a query and return all rows. */
export async function query(text, params = []) {
  const res = await pool.query(text, params);
  return res.rows;
}

/** Run a query and return the first row, or null. */
export async function queryOne(text, params = []) {
  const rows = await query(text, params);
  return rows[0] ?? null;
}

/** Run a query and return the first column of the first row, or null. */
export async function queryValue(text, params = []) {
  const row = await queryOne(text, params);
  if (!row) return null;
  return Object.values(row)[0] ?? null;
}

/**
 * Run `fn` inside a transaction, committing on success and rolling back on
 * throw. The callback receives a dedicated client — use it for every statement
 * in the transaction, not the shared pool.
 */
export async function transaction(fn) {
  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    const result = await fn(client);
    await client.query('COMMIT');
    return result;
  } catch (err) {
    await client.query('ROLLBACK').catch(() => {});
    throw err;
  } finally {
    client.release();
  }
}
