// Importation plus robuste pour environnement Docker
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { defineConfig } = require('vite');
const vue = require('@vitejs/plugin-vue');
const path = require('path');
const compression = require('vite-plugin-compression');

// Obtenir le chemin absolu du répertoire racine
const rootPath = path.resolve(__dirname, '..');

export default defineConfig({
  plugins: [vue(), compression()],
  resolve: {
    alias: {
      '@': path.resolve(rootPath, 'frontend/src')
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@import "${rootPath}/frontend/src/assets/scss/utils/_variables.scss"; @import "${rootPath}/frontend/src/assets/scss/utils/_mixins.scss";`
      }
    }
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      '^/api/.*': {
        target: 'http://localhost:8080',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: path.resolve(rootPath, 'frontend/dist'),
    emptyOutDir: true,
    brotliSize: false,
    reportCompressedSize: false,
    sourcemap: false
  }
}); 