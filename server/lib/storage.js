import path from 'node:path';
import fs from 'node:fs/promises';
import crypto from 'node:crypto';
import { config } from '../config.js';

const EXTENSIONS = {
  'image/jpeg': 'jpg',
  'image/png': 'png',
  'image/webp': 'webp',
  'image/gif': 'gif',
};

export const ALLOWED_IMAGE_TYPES = Object.keys(EXTENSIONS);

/** True when Supabase Storage is configured and should be used for uploads. */
export const usingObjectStorage = () =>
  Boolean(config.storage.url && config.storage.serviceKey);

const localDir = path.join(process.cwd(), 'public', 'uploads', 'covers');

const publicUrl = (name) =>
  `${config.storage.url}/storage/v1/object/public/${config.storage.bucket}/${name}`;

const authHeaders = () => ({
  Authorization: `Bearer ${config.storage.serviceKey}`,
  apikey: config.storage.serviceKey,
});

/**
 * Create the storage bucket if it does not exist. Safe to call repeatedly.
 * Covers are public so the browser can load them straight from the CDN.
 */
export async function ensureBucket() {
  if (!usingObjectStorage()) return { ok: false, reason: 'not configured' };

  const res = await fetch(`${config.storage.url}/storage/v1/bucket`, {
    method: 'POST',
    headers: { ...authHeaders(), 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id: config.storage.bucket,
      name: config.storage.bucket,
      public: true,
      file_size_limit: 5 * 1024 * 1024,
      allowed_mime_types: ALLOWED_IMAGE_TYPES,
    }),
  });

  if (res.ok) return { ok: true, created: true };

  const body = await res.text();
  // Supabase answers 409 (or "already exists") when the bucket is already there.
  if (res.status === 409 || /already exists/i.test(body)) {
    return { ok: true, created: false };
  }
  return { ok: false, reason: `${res.status} ${body}` };
}

/**
 * Persist an uploaded image and return the value to store in `cover_image`:
 * an absolute URL for object storage, or a `uploads/covers/...` relative path
 * for the local-disk fallback. Both forms are understood by bookCoverUrl().
 */
export async function saveImage(file) {
  if (!file) return null;

  const ext = EXTENSIONS[file.mimetype];
  if (!ext) throw new Error('Unsupported image type.');
  const name = `cover_${crypto.randomBytes(8).toString('hex')}.${ext}`;

  if (!usingObjectStorage()) {
    // Serverless filesystems are read-only and per-invocation, so a local write
    // would either throw something cryptic or vanish. Say what to fix instead.
    if (process.env.VERCEL) {
      throw new Error(
        'Image uploads need object storage in production. Set SUPABASE_SERVICE_KEY ' +
          '(the service_role key from Supabase → Settings → API) and redeploy.'
      );
    }
    await fs.mkdir(localDir, { recursive: true });
    await fs.writeFile(path.join(localDir, name), file.buffer);
    return `uploads/covers/${name}`;
  }

  const res = await fetch(
    `${config.storage.url}/storage/v1/object/${config.storage.bucket}/${name}`,
    {
      method: 'POST',
      headers: {
        ...authHeaders(),
        'Content-Type': file.mimetype,
        'Cache-Control': 'public, max-age=31536000, immutable',
      },
      body: file.buffer,
    }
  );

  if (!res.ok) {
    throw new Error(`Storage upload failed (${res.status}): ${await res.text()}`);
  }
  return publicUrl(name);
}

/**
 * Remove a previously stored image. Anything we did not upload — a hand-entered
 * URL, or a cover carried over from the old PHP site — is left alone.
 */
export async function deleteImage(stored) {
  const value = String(stored || '').trim();
  if (value === '') return;

  if (value.startsWith('uploads/covers/')) {
    await fs.unlink(path.join(process.cwd(), 'public', value)).catch(() => {});
    return;
  }

  const prefix = usingObjectStorage() ? publicUrl('') : null;
  if (!prefix || !value.startsWith(prefix)) return;

  const name = value.slice(prefix.length);
  await fetch(`${config.storage.url}/storage/v1/object/${config.storage.bucket}/${name}`, {
    method: 'DELETE',
    headers: authHeaders(),
  }).catch(() => {});
}
