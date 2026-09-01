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
  googleEnabled,
  verifyGoogleToken,
  findUserByGoogleId,
  linkGoogleAccount,
  createGoogleUser,
} from '../lib/auth.js';
import * as cart from '../lib/cart.js';
import { regenerateSession as regenerate } from '../middleware.js';
import { sendVerificationEmail, sendPasswordResetEmail } from '../lib/mailer.js';
import { asyncRoute, unprocessable, unauthorized, badRequest, publicUser } from '../lib/http.js';

const router = Router();

const isEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(v || '').trim());

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

/**
 * Sign in or sign up with Google.
 *
 * The browser sends the ID token from Google Identity Services; everything
 * that matters is decided here from the verified claims, never from anything
 * the client asserts about itself.
 */
router.post(
  '/auth/google',
  asyncRoute(async (req, res) => {
    if (!googleEnabled()) {
      throw badRequest('Google sign-in is not available right now.');
    }

    const profile = await verifyGoogleToken(String(req.body.credential || ''));
    if (!profile) throw unauthorized('We could not verify that Google account.');

    let user = await findUserByGoogleId(profile.googleId);
    let created = false;

    if (!user) {
      const existing = await findUserByEmail(profile.email);

      if (existing) {
        // Adopting an existing password account on the strength of a Google
        // token is only safe once Google says it owns the address; otherwise
        // an unverified Google account could claim someone else's email.
        if (!profile.emailVerified) {
          throw unauthorized(
            'Your Google account email is not verified, so we cannot link it to your existing account. ' +
              'Please sign in with your password instead.'
          );
        }
        user = await linkGoogleAccount(existing.id, profile);
      } else {
        if (!profile.emailVerified) {
          throw unauthorized('Please verify your email address with Google first.');
        }
        user = await createGoogleUser(profile);
        created = true;
      }
    }

    await regenerate(req);
    req.session.userId = user.id;
    try {
      await cart.mergeGuestIntoUser(req.session, user.id);
    } catch (err) {
      console.error('[auth/google] cart merge failed:', err);
    }

    res.json({
      user: publicUser(user),
      cartCount: cart.totalQty(req.session),
      created,
    });
  })
);

/** Send the verification link again, for signup emails that never arrived. */
router.post(
  '/auth/resend-verification',
  asyncRoute(async (req, res) => {
    if (!req.user) throw unauthorized('Please sign in to resend the verification email.');
    if (req.user.email_verified_at) {
      return res.json({ message: 'Your email address is already verified.' });
    }

    const token = randomToken(32);
    await query('UPDATE users SET remember_token = $1 WHERE id = $2', [token, req.user.id]);
    const mail = await sendVerificationEmail(req.user, token);

    if (!mail.ok) {
      throw badRequest('We could not send the email just now. Please try again in a few minutes.');
    }
    res.json({ message: `Verification link sent to ${req.user.email}.` });
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
