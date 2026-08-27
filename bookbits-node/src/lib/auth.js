import bcrypt from 'bcryptjs';
import crypto from 'node:crypto';
import { query, queryOne } from '../db.js';

export const hashPassword = (plain) => bcrypt.hash(plain, 12);

/**
 * PHP's password_hash() emits the `$2y$` bcrypt identifier, which bcryptjs does
 * not recognise even though the digest is byte-identical to `$2a$`. Rewriting
 * the prefix lets accounts created by the old PHP app log in unchanged.
 */
export async function verifyPassword(plain, hash) {
  if (!hash) return false;
  const normalised = hash.startsWith('$2y$') ? '$2a$' + hash.slice(4) : hash;
  try {
    return await bcrypt.compare(plain, normalised);
  } catch {
    return false;
  }
}

export const randomToken = (bytes = 32) => crypto.randomBytes(bytes).toString('hex');

export async function findUserByEmail(email) {
  return queryOne('SELECT * FROM users WHERE lower(email) = lower($1) LIMIT 1', [email]);
}

export async function findUserById(id) {
  return queryOne('SELECT * FROM users WHERE id = $1 LIMIT 1', [id]);
}

export async function createUser({ name, email, password, role = 'customer', verifyToken = null }) {
  const hash = await hashPassword(password);
  return queryOne(
    `INSERT INTO users (name, email, password, role, remember_token)
     VALUES ($1, $2, $3, $4, $5)
     RETURNING *`,
    [name, email, hash, role, verifyToken]
  );
}

export async function markEmailVerified(userId) {
  await query('UPDATE users SET email_verified_at = now(), remember_token = NULL WHERE id = $1', [
    userId,
  ]);
}

/** Store a single-use reset token, replacing any previous one for the address. */
export async function createPasswordReset(email, ttlMinutes = 60) {
  const token = randomToken(32);
  await query('DELETE FROM password_resets WHERE email = $1', [email]);
  await query(
    `INSERT INTO password_resets (email, token, expires_at)
     VALUES ($1, $2, now() + ($3 || ' minutes')::interval)`,
    [email, token, String(ttlMinutes)]
  );
  return token;
}

export async function consumePasswordReset(token) {
  const row = await queryOne(
    'SELECT * FROM password_resets WHERE token = $1 AND expires_at > now() LIMIT 1',
    [token]
  );
  return row;
}

export async function deletePasswordReset(token) {
  await query('DELETE FROM password_resets WHERE token = $1', [token]);
}
