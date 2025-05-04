import { fileURLToPath, URL } from 'node:url'
import { resolve } from 'node:path'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
// import Inspect from 'vite-plugin-inspect'

// Chemin vers la racine du projet frontend
const rootDir = resolve(__dirname, '..')

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const isTest = process.env.NODE_ENV === 'test' || mode === 'test'
  const isCI = process.env.CI === 'true'

  return {
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
      emptyOutDir: true,
      // Mode minification réduite en environnement de test pour faciliter le débogage
      minify: isTest ? false : 'esbuild',
      // Réduire le parallélisme en CI pour éviter les problèmes de mémoire
      ...(isCI ? { assetsInlineLimit: 4096, cssCodeSplit: false } : {}),
    },
    server: {
      // Permettre l'accès depuis n'importe quelle adresse IP
      host: true,
      // Port par défaut qui correspond à notre configuration
      port: 5173,
      // Forcer le lancement même si le port est occupé
      strictPort: true,
    },
    preview: {
      // Permettre l'accès depuis n'importe quelle adresse IP
      host: true,
      // Port par défaut pour le mode preview
      port: 3000,
      // Forcer le lancement même si le port est occupé
      strictPort: true,
      // Ajouter cette option pour éviter les problèmes CI en environnement GitHub Actions
      cors: true,
      // Assurer que le serveur est accessible en environnement CI
      hmr: isCI ? false : true,
    },
    // Configuration des tests unitaires avec Vitest
    test: {
      globals: true,
      environment: 'jsdom',
      transformMode: {
        web: [/\.[jt]sx?$/],
        ssr: [/\.vue$/],
      },
      coverage: {
        provider: 'c8',
        reporter: ['text', 'lcov'],
      },
      deps: {
        inline: [/vue/, /@vue\/test-utils/],
      },
    },
  }
})
