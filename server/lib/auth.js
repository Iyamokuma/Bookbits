import bcrypt from 'bcryptjs';
import crypto from 'node:crypto';
import { OAuth2Client } from 'google-auth-library';
import { query, queryOne } from '../db.js';
import { config } from '../config.js';

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

// --------------------------------------------------------------------------
// Google sign-in
// --------------------------------------------------------------------------

export const googleEnabled = () => config.google.enabled && Boolean(config.google.clientId);

const googleClient = new OAuth2Client(config.google.clientId);

/**
 * Validate an ID token issued by Google Identity Services.
 *
 * The library checks the signature against Google's published keys, the
 * issuer, the expiry, and that the audience is our own client id — without
 * that last check any Google token from any app would be accepted here.
 *
 * Returns the useful claims, or null if the token is not trustworthy.
 */
export async function verifyGoogleToken(credential) {
  if (!credential || !googleEnabled()) return null;

  try {
    const ticket = await googleClient.verifyIdToken({
      idToken: credential,
      audience: config.google.clientId,
    });
    const payload = ticket.getPayload();
    if (!payload?.sub || !payload.email) return null;

    return {
      googleId: payload.sub,
      email: String(payload.email).toLowerCase(),
      emailVerified: payload.email_verified === true,
      name: payload.name || payload.email.split('@')[0],
      avatarUrl: payload.picture || null,
    };
  } catch (err) {
    console.error('[auth] Google token rejected:', err.message);
    return null;
  }
}

export async function findUserByGoogleId(googleId) {
  return queryOne('SELECT * FROM users WHERE google_id = $1 LIMIT 1', [googleId]);
}

/** Attach a Google identity to an account that already exists. */
export async function linkGoogleAccount(userId, { googleId, avatarUrl }) {
  return queryOne(
    `UPDATE users
        SET google_id         = $1,
            avatar_url        = COALESCE($2, avatar_url),
            email_verified_at = COALESCE(email_verified_at, now())
      WHERE id = $3
      RETURNING *`,
    [googleId, avatarUrl, userId]
  );
}

/**
 * Create an account from a Google profile. There is no password: such accounts
 * sign in through Google, or through a password reset if they later set one.
 */
export async function createGoogleUser({ name, email, googleId, avatarUrl }) {
  return queryOne(
    `INSERT INTO users (name, email, password, role, google_id, avatar_url, email_verified_at)
     VALUES ($1, $2, NULL, 'customer', $3, $4, now())
     RETURNING *`,
    [name, email, googleId, avatarUrl]
  );
}
