import { createApp } from '../server/app.js';

/**
 * Vercel serverless entry point for the whole API.
 *
 * Every `/api/*` request is rewritten here by vercel.json. Vercel keeps the
 * original URL on `req`, so Express still sees `/api/orders/12/pay` and does
 * its own routing.
 *
 * Do not rename this back to a `[...path].js` catch-all: bracket catch-alls are
 * a Next.js feature, and on other frameworks they match only a single path
 * segment, so anything nested 404s before it reaches this function.
 *
 * The built React app is served from the CDN and never reaches here. The app is
 * constructed once per warm instance so the Postgres pool and the session store
 * are reused across invocations.
 */
export default createApp();
