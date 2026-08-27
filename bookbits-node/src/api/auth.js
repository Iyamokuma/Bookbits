import { Router } from 'express';
import { query, queryOne } from '../db.js';
import {
  findUserByEmail,
  createUser,
  verifyPassword,
  hashPassword,
  randomToken,
  markEmailVerified,
  createPasswordReset,
  consumePasswordReset,
  deletePasswordReset,
} from '../lib/auth.js';
import * as cart from '../lib/cart.js';
import { sendVerificationEmail, sendPasswordResetEmail } from '../lib/mailer.js';
import { asyncRoute, unprocessable, unauthorized, badRequest, publicUser } from './helpers.js';

const router = Router();

const isEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(v || '').trim());

/** Rebuild the session under a new id, then re-apply the values we want kept. */
function regenerate(req) {
  return new Promise((resolve, reject) => {
    const cartBefore = req.session.cart;
    req.session.regenerate((err) => {
      if (err) return reject(err);
      req.session.cart = cartBefore;
      resolve();
    });
  });
}

router.post(
  '/auth/register',
  asyncRoute(async (req, res) => {
    const name = String(req.body.name || '').trim();
    const email = String(req.body.email || '').trim().toLowerCase();
    const password = String(req.body.password || '');
    const confirm = String(req.body.passwordConfirm || '');

    const errors = {};
    if (name.length < 2) errors.name = 'Enter your full name.';
    if (!isEmail(email)) errors.email = 'Enter a valid email address.';
    if (password.length < 8) errors.password = 'Your password must be at least 8 characters.';
    if (password !== confirm) errors.passwordConfirm = 'The two passwords do not match.';
    if (!errors.email && (await findUserByEmail(email))) {
      errors.email = 'An account with that email already exists. Try signing in instead.';
    }
    if (Object.keys(errors).length) throw unprocessable('Please check the form.', errors);

    const token = randomToken(32);
    const user = await createUser({ name, email, password, verifyToken: token });
    const mail = await sendVerificationEmail(user, token);

    res.status(201).json({
      user: publicUser(user),
      emailSent: mail.ok,
      message: mail.ok
        ? 'Account created. Check your inbox for a link to verify your email address.'
        : 'Account created, but we could not send the verification email. You can still sign in.',
    });
  })
);

router.post(
  '/auth/verify-email',
  asyncRoute(async (req, res) => {
    const token = String(req.body.token || '');
    const user = token
      ? await queryOne('SELECT * FROM users WHERE remember_token = $1 LIMIT 1', [token])
      : null;
    if (!user) {
      throw badRequest('This verification link is invalid or has already been used.');
    }

    await markEmailVerified(user.id);
    await regenerate(req);
    req.session.userId = user.id;
    await cart.mergeGuestIntoUser(req.session, user.id);

    res.json({ user: publicUser(user), message: 'Your email is verified. Welcome!' });
  })
);

router.post(
  '/auth/login',
  asyncRoute(async (req, res) => {
    const email = String(req.body.email || '').trim().toLowerCase();
    const password = String(req.body.password || '');

    const user = await findUserByEmail(email);
    if (!user || !(await verifyPassword(password, user.password))) {
      throw unauthorized('Invalid email or password.');
    }

    // Regenerating defends against session fixation across the privilege change.
    await regenerate(req);
    req.session.userId = user.id;
    try {
      await cart.mergeGuestIntoUser(req.session, user.id);
    } catch (err) {
      console.error('[login] cart merge failed:', err);
    }

    res.json({ user: publicUser(user), cartCount: cart.totalQty(req.session) });
  })
);

router.post('/auth/logout', (req, res) => {
  req.session.destroy(() => res.json({ ok: true }));
});

router.post(
  '/auth/forgot-password',
  asyncRoute(async (req, res) => {
    const email = String(req.body.email || '').trim().toLowerCase();
    if (!isEmail(email)) {
      throw unprocessable('Please check the form.', { email: 'Enter a valid email address.' });
    }

    const user = await findUserByEmail(email);
    if (user) {
      const token = await createPasswordReset(email);
      await sendPasswordResetEmail(email, user.name, token);
    }

    // Identical response either way, so this can't be used to discover which
    // addresses have accounts.
    res.json({
      message:
        'If an account exists for that address, we have sent a password reset link. Please check your inbox.',
    });
  })
);

router.get(
  '/auth/reset-password/:token',
  asyncRoute(async (req, res) => {
    const reset = await consumePasswordReset(req.params.token);
    if (!reset) throw badRequest('This password reset link is invalid or has expired.');
    res.json({ valid: true });
  })
);

router.post(
  '/auth/reset-password',
  asyncRoute(async (req, res) => {
    const token = String(req.body.token || '');
    const password = String(req.body.password || '');
    const confirm = String(req.body.passwordConfirm || '');

    const reset = token ? await consumePasswordReset(token) : null;
    if (!reset) throw badRequest('This password reset link is invalid or has expired.');

    const errors = {};
    if (password.length < 8) errors.password = 'Your password must be at least 8 characters.';
    if (password !== confirm) errors.passwordConfirm = 'The two passwords do not match.';
    if (Object.keys(errors).length) throw unprocessable('Please check the form.', errors);

    await query('UPDATE users SET password = $1 WHERE lower(email) = lower($2)', [
      await hashPassword(password),
      reset.email,
    ]);
    await deletePasswordReset(token);

    res.json({ message: 'Your password has been reset. You can now sign in.' });
  })
);

export default router;
