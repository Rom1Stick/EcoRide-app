import { fileURLToPath, URL } from 'node:url'
import { resolve } from 'node:path'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
// import Inspect from 'vite-plugin-inspect'

// Chemin vers la racine du projet frontend
const rootDir = resolve(__dirname, '..');

// https://vite.dev/config/
export default defineConfig({
  root: rootDir,
  plugins: [
    vue(),
    // Inspect(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('../src', import.meta.url)),
    },
  },
  build: {
    outDir: resolve(rootDir, 'dist'),
    emptyOutDir: true
  }
})
