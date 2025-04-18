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
  extends: ['plugin:vue/vue3-recommended', '@vue/eslint-config-typescript'],
  ignorePatterns: ['**/dist/**', '**/dist', 'dist', 'dist/**', '**/node_modules/**'],
  rules: {
    'vue/script-setup-uses-vars': 'error',
    '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
  },
}
