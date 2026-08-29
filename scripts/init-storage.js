import { config } from '../server/config.js';
import { ensureBucket, usingObjectStorage } from '../server/lib/storage.js';

if (!usingObjectStorage()) {
  console.error(
    'Supabase Storage is not configured.\n' +
      'Set SUPABASE_URL and SUPABASE_SERVICE_KEY in .env, then run this again.'
  );
  process.exit(1);
}

const result = await ensureBucket();

if (!result.ok) {
  console.error(`Could not create the "${config.storage.bucket}" bucket: ${result.reason}`);
  process.exit(1);
}

console.log(
  result.created
    ? `Created the public "${config.storage.bucket}" bucket.`
    : `The "${config.storage.bucket}" bucket already exists.`
);
