import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  // './' makes every link in the built files relative, so the app works from
  // whatever folder it is uploaded to on Mercury. Only change this if the
  // group decides on a fixed Mercury path.
  base: './',
  server: {
    // Only for "npm run dev": Vite can't run PHP, so api/ and uploads/ requests
    // are passed to PHP's own server (start it with "npm run api"). Not used on Mercury.
    proxy: {
      '/api': 'http://localhost:8000',
      '/uploads': 'http://localhost:8000'
    }
  }
})
