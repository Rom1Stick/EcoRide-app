import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {},
  },
  test: {
    globals: true,
    environment: 'jsdom',
    deps: {
      inline: [/vue/, /@vue\/test-utils/],
    },
  },
})
