module.exports = {
  root: true,
  extends: ['plugin:vue/vue3-recommended', '@vue/eslint-config-typescript'],
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module'
  },
  rules: {
    'vue/script-setup-uses-vars': 'error',
    '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }]
  },
  env: {
    browser: true,
    node: true,
    jest: true
  }
}; 