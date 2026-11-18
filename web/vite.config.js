import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// The Laravel API runs on :8010 (php artisan serve --port=8010). Proxying /api keeps everything same-origin,
// so Laravel's session cookie + CSRF work without CORS or tokens.
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': { target: 'http://127.0.0.1:8010', changeOrigin: false },
      '/storage': { target: 'http://127.0.0.1:8010', changeOrigin: false },
    },
  },
})
