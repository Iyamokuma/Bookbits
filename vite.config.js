import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Absolute origin for the link-preview tags in index.html. Preview scrapers
 * need full URLs for og:image, so this is baked in at build time: BASE_URL
 * (the deployed URL, also used for payment callbacks) when it is a real
 * address, else Vercel's production domain. Empty locally, which leaves the
 * URLs relative — harmless, since nothing scrapes localhost.
 */
function siteUrl(env) {
  const base = String(env.BASE_URL || '').replace(/\/+$/, '');
  if (base && !/\/\/(localhost|127\.0\.0\.1)(:|$)/.test(base)) return base;
  if (env.VERCEL_PROJECT_PRODUCTION_URL) return `https://${env.VERCEL_PROJECT_PRODUCTION_URL}`;
  return '';
}

export default defineConfig(({ mode }) => {
  // Real environment variables (Vercel's) win over the local .env file.
  const env = { ...loadEnv(mode, process.cwd(), ''), ...process.env };
  const origin = siteUrl(env);

  return {
    plugins: [
      react(),
      {
        name: 'bookbits-site-url',
        transformIndexHtml: (html) => html.replaceAll('__SITE_URL__', origin),
      },
    ],
    build: {
      outDir: 'dist',
      emptyOutDir: true,
    },
    server: {
      port: 5173,
      // Same-origin in dev too, so the session cookie behaves exactly as it will
      // in production and there is no CORS configuration to keep in sync.
      // Static files under public/ are served by Vite directly.
      proxy: {
        '/api': 'http://localhost:3000',
      },
    },
  };
});
