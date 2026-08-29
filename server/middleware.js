import crypto from 'node:crypto';
import { findUserById } from './lib/auth.js';
import * as cart from './lib/cart.js';

/** Load the signed-in user and hydrate their saved cart. */
export async function loadUser(req, res, next) {
  try {
    req.user = req.session.userId ? await findUserById(req.session.userId) : null;
    if (req.session.userId && !req.user) {
      delete req.session.userId; // account deleted underneath the session
    }
    await cart.bootstrap(req.session, req.user);
    next();
  } catch (err) {
    next(err);
  }
}

/**
 * Rotate the session id on a privilege change, which is what defeats session
 * fixation, while carrying over the state that has to outlive it.
 *
 * The CSRF token must survive: the SPA fetches it once from GET /api/session
 * at boot and reuses it for the rest of the visit. Minting a fresh one here
 * would leave the browser holding a stale token, so every write after signing
 * in would be rejected as expired. Keeping it is safe because a token is
 * useless without the session cookie, and that cookie has just changed.
 */
export function regenerateSession(req) {
  return new Promise((resolve, reject) => {
    const { cart, csrfToken } = req.session;
    req.session.regenerate((err) => {
      if (err) return reject(err);
      req.session.cart = cart;
      req.session.csrfToken = csrfToken;
      resolve();
    });
  });
}

/**
 * Issue a CSRF token and reject state-changing requests that don't echo it.
 * The SPA reads the token from GET /api/session and sends it as a header.
 *
 * Gateway webhooks authenticate by HMAC signature instead and are mounted
 * before this runs.
 */
export function csrf(req, res, next) {
  if (!req.session.csrfToken) {
    req.session.csrfToken = crypto.randomBytes(24).toString('hex');
  }

  if (['GET', 'HEAD', 'OPTIONS'].includes(req.method)) return next();

  const supplied = req.get('x-csrf-token') || req.body?._csrf;
  if (!supplied || supplied !== req.session.csrfToken) {
    return res.status(419).json({
      error: 'Your session expired. Please refresh the page and try again.',
      code: 'CSRF_INVALID',
    });
  }
  next();
}
