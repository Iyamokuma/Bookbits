import fs from 'node:fs/promises';
import path from 'node:path';
import { createMigrationClient, migrationConnectionString } from './migration-client.js';

const schemaPath = path.join(process.cwd(), 'db', 'schema.sql');
const sql = await fs.readFile(schemaPath, 'utf8');

const target = migrationConnectionString().replace(/:[^:@/]+@/, ':***@');
console.log(`Applying db/schema.sql via ${target}`);

const client = createMigrationClient();

try {
  await client.connect();
  await client.query(sql);
  console.log('Schema applied successfully.');

  const { rows } = await client.query(`
    SELECT table_name FROM information_schema.tables
    WHERE table_schema = 'public' ORDER BY table_name
  `);
  console.log(`\n${rows.length} tables in public:`);
  for (const r of rows) console.log('  -', r.table_name);
} catch (err) {
  console.error('Failed to apply schema:', err.message);
  process.exitCode = 1;
} finally {
  await client.end().catch(() => {});
}
