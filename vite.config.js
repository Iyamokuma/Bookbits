import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
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
});
