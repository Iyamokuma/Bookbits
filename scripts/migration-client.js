import 'dotenv/config';
import pg from 'pg';

/**
 * A connection suited to schema changes and bulk loads.
 *
 * The app runs against Supabase's transaction pooler (port 6543), which is
 * right for short web requests but does not support prepared statements or
 * long multi-statement scripts. Migrations therefore use the session pooler
 * (port 5432) on the same host, which behaves like a normal Postgres
 * connection.
 *
 * Set MIGRATION_DATABASE_URL to override this inference.
 */
export function migrationConnectionString() {
  const explicit = process.env.MIGRATION_DATABASE_URL;
  if (explicit) return explicit;

  const url = new URL(process.env.DATABASE_URL);
  if (url.port === '6543') url.port = '5432';
  return url.toString();
}

export function createMigrationClient() {
  return new pg.Client({
    connectionString: migrationConnectionString(),
    ssl: process.env.DATABASE_SSL === 'false' ? false : { rejectUnauthorized: false },
    connectionTimeoutMillis: 30_000,
    // Bulk loads and large DDL scripts can legitimately take a while.
    statement_timeout: 300_000,
  });
}
