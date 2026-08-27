import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import express from 'express';
import session from 'express-session';
import connectPgSimple from 'connect-pg-simple';

import { config } from './config.js';
import { pool } from './db.js';
import { loadUser, csrf } from './middleware.js';

import apiRoutes from './api/index.js';
import paymentRoutes from './routes/payments.js';
import webhookRoutes from './routes/webhooks.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.join(__dirname, '..');
const clientDist = path.join(rootDir, 'client', 'dist');

export function createApp() {
  const app = express();
  app.set('trust proxy', 1);
  app.disable('x-powered-by');

  // Webhooks validate an HMAC over the exact bytes the gateway sent, so they
  // are mounted ahead of the body parsers, the session, and the CSRF guard.
  app.use('/payment/webhook', express.raw({ type: '*/*' }), webhookRoutes);

  app.use(express.json());
  app.use(express.urlencoded({ extended: true }));

  // Uploaded covers and brand images, served from the API origin.
  app.use('/uploads', express.static(path.join(rootDir, 'public', 'uploads'), { maxAge: '7d' }));
  app.use('/img', express.static(path.join(rootDir, 'public', 'img'), { maxAge: '7d' }));

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

  app.use('/api', apiRoutes);
  app.use('/payment', paymentRoutes);

  // ---- Single-page app ----------------------------------------------------
  // In development the SPA is served by Vite on :5173, which proxies here.
  if (fs.existsSync(clientDist)) {
    app.use(express.static(clientDist, { maxAge: '7d', index: false }));
    // Client-side routing: any non-API path returns the shell and React
    // resolves the route.
    app.get('*', (req, res) => res.sendFile(path.join(clientDist, 'index.html')));
  } else {
    app.get('*', (req, res) =>
      res
        .status(503)
        .type('text/plain')
        .send(
          'The front end has not been built yet.\n\n' +
            'Development:  cd client && npm run dev   (then open http://localhost:5173)\n' +
            'Production:   cd client && npm run build\n'
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
