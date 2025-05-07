module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-empty': [0, 'never'],
    'subject-empty': [0, 'never'],
    'type-case': [0, 'always'],
    'subject-case': [0, 'always']
  }
}; 