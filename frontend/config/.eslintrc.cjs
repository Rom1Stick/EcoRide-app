/* eslint-env node */
const path = require('path')

// Configuration ESLint spécifique pour le frontend
// Étend la configuration commune dans le répertoire config à la racine
module.exports = {
  root: true,
  env: {
    browser: true,
    node: true,
    jest: true,
  },
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module',
  },
  extends: [
    path.resolve(__dirname, '../../config/eslint.config.js'),
    'plugin:vue/vue3-recommended',
    '@vue/eslint-config-typescript',
  ],
  ignorePatterns: ['**/dist/**', '**/dist', 'dist', 'dist/**', '**/node_modules/**'],
  rules: {
    'vue/script-setup-uses-vars': 'error',
    '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
  },
}
