import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    // Express serves this directory in production.
    outDir: 'dist',
    emptyOutDir: true,
  },
  server: {
    port: 5173,
    // Same-origin in dev too, so the session cookie behaves exactly as it will
    // in production and there is no CORS configuration to keep in sync.
    proxy: {
      '/api': 'http://localhost:3000',
      '/payment': 'http://localhost:3000',
      '/uploads': 'http://localhost:3000',
      '/img': 'http://localhost:3000',
    },
  },
});
