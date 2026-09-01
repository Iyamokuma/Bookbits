import { Router } from 'express';
import { query } from '../db.js';
import {
  sendContactMessage,
  sendContactAcknowledgement,
  sendNewsletterWelcome,
} from '../lib/mailer.js';
import { asyncRoute, unprocessable, badRequest } from '../lib/http.js';

const router = Router();

const isEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(v || '').trim());

router.post(
  '/contact',
  asyncRoute(async (req, res) => {
    const name = String(req.body.name || '').trim();
    const email = String(req.body.email || '').trim().toLowerCase();
    const phone = String(req.body.phone || '').trim();
    const subject = String(req.body.subject || '').trim();
    const message = String(req.body.message || '').trim();

    const errors = {};
    if (name.length < 2) errors.name = 'Please tell us your name.';
    if (!isEmail(email)) errors.email = 'Enter a valid email address so we can reply.';
    if (subject.length < 3) errors.subject = 'Add a short subject.';
    if (message.length < 10) errors.message = 'Please add a little more detail.';
    if (Object.keys(errors).length) throw unprocessable('Please check the form.', errors);

    const sent = await sendContactMessage({ name, email, phone, subject, message });
    if (!sent.ok) {
      throw badRequest(
        'We could not send your message just now. Please WhatsApp us instead and we will help right away.'
      );
    }

    // A failed acknowledgement should not make the customer think their
    // message was lost, since it already reached the shop.
    sendContactAcknowledgement({ name, email, subject }).catch(() => {});

    res.status(201).json({
      message: "Thanks — your message is on its way. We'll reply within one business day.",
    });
  })
);

router.post(
  '/newsletter',
  asyncRoute(async (req, res) => {
    const email = String(req.body.email || '').trim().toLowerCase();
    if (!isEmail(email)) {
      throw unprocessable('Please check the form.', { email: 'Enter a valid email address.' });
    }

    // Re-subscribing is not an error, and revives a previous unsubscribe.
    const [row] = await query(
      `INSERT INTO newsletter_subscribers (email) VALUES ($1)
       ON CONFLICT (email) DO UPDATE SET unsubscribed_at = NULL
       RETURNING (xmax = 0) AS is_new`,
      [email]
    );

    if (row?.is_new) sendNewsletterWelcome(email).catch(() => {});

    res.status(201).json({ message: "You're subscribed. Watch your inbox for new arrivals." });
  })
);

export default router;
