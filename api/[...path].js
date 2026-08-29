import { createApp } from '../server/app.js';

/**
 * Vercel serverless entry point for the whole API.
 *
 * This is a catch-all filesystem route, which is deliberate: Vercel matches
 * `/api/anything/deep` here with `req.url` still intact, so Express can do its
 * own routing. A `vercel.json` rewrite pointing at a fixed destination would
 * replace the path and break that.
 *
 * The built React app is served from the CDN and never reaches this function.
 * The app is constructed once per warm instance so the Postgres pool and the
 * session store are reused across invocations.
 */
export default createApp();
