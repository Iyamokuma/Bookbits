import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import express from 'express';
import session from 'express-session';
import connectPgSimple from 'connect-pg-simple';

import { config } from './config.js';
import { pool } from './db.js';
import { loadUser, csrf } from './middleware.js';

import apiRoutes from './routes/index.js';
import paymentRoutes from './routes/payments.js';
import webhookRoutes from './routes/webhooks.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.join(__dirname, '..');
const clientDist = path.join(rootDir, 'dist');

export function createApp() {
  const app = express();
  app.set('trust proxy', 1);
  app.disable('x-powered-by');

  // Webhooks validate an HMAC over the exact bytes the gateway sent, so they
  // are mounted ahead of the body parsers, the session, and the CSRF guard.
  app.use('/api/payment/webhook', express.raw({ type: '*/*' }), webhookRoutes);

  app.use(express.json());
  app.use(express.urlencoded({ extended: true }));

  const PgStore = connectPgSimple(session);
  app.use(
    session({
      store: new PgStore({ pool, tableName: 'user_sessions', createTableIfMissing: true }),
      name: 'bookbits.sid',
      secret: config.session.secret,
      resave: false,
      saveUninitialized: false,
      cookie: {
        httpOnly: true,
        sameSite: 'lax',
        secure: config.env === 'production',
        maxAge: config.session.maxAgeMs,
      },
    })
  );

  app.use(loadUser);
  app.use(csrf);

  // Everything the server owns lives under /api, which keeps the Vercel
  // routing rules to a single rewrite and leaves every other path to React.
  // The payment router is mounted first because the /api router ends in a
  // catch-all 404 that would otherwise swallow /api/payment/*.
  app.use('/api/payment', paymentRoutes);
  app.use('/api', apiRoutes);

  // ---- Single-page app ----------------------------------------------------
  // Only used when running the whole app from one Node process (`npm start`).
  // In development Vite serves the SPA on :5173 and proxies /api here; on
  // Vercel the built assets are served from the CDN and never reach this code.
  if (fs.existsSync(clientDist)) {
    app.use(express.static(clientDist, { maxAge: '7d', index: false }));
    app.get('*', (req, res) => res.sendFile(path.join(clientDist, 'index.html')));
  } else {
    app.get('*', (req, res) =>
      res
        .status(503)
        .type('text/plain')
        .send(
          'The front end has not been built yet.\n\n' +
            'Development:  npm run dev     (then open http://localhost:5173)\n' +
            'Production:   npm run build\n'
        )
    );
  }

  // Last-resort handler for anything outside /api that threw.
  app.use((err, req, res, _next) => {
    console.error('[app]', err);
    res.status(500).type('text/plain').send('Something went wrong.');
  });

  return app;
}
