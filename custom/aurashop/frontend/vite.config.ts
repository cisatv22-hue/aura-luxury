import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

const dolibarr = process.env.DOLIBARR_URL ?? 'http://localhost:8080'

// Build output goes to public/AuraShop/assets; public/AuraShop/index.php reads the manifest to load it.
export default defineConfig({
  plugins: [vue()],
  base: './',
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  build: {
    outDir: fileURLToPath(new URL('../../../public/AuraShop/assets', import.meta.url)),
    emptyOutDir: true,
    assetsDir: '',
    manifest: true,
    rollupOptions: { input: 'src/main.ts' },
  },
  server: {
    port: 5173,
    proxy: {
      '/custom/aurashop/api': { target: dolibarr, changeOrigin: true },
    },
  },
  test: {
    environment: 'node',
    include: ['src/**/*.test.ts'],
  },
})
