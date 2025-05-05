import { fileURLToPath, URL } from 'node:url'
import { resolve } from 'node:path'

import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

// Chemin vers la racine du projet frontend
const rootDir = resolve(__dirname, '..')

// https://vitest.dev/config/
export default defineConfig(({ mode }) => {
  const isTest = process.env.NODE_ENV === 'test' || mode === 'test'
  const isCI = process.env.CI === 'true'

  return {
    root: rootDir,
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('../src', import.meta.url)),
      },
    },
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
