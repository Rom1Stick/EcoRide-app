module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'jsdom',
  transform: {
    '^.+\\.vue$': [
      '@vue/vue3-jest',
      {
        tsConfig: '<rootDir>/tsconfig.test.json',
      },
    ],
    '^.+\\.(ts|js)$': [
      'ts-jest',
      {
        tsconfig: '<rootDir>/tsconfig.test.json',
      },
    ],
  },
  transformIgnorePatterns: ['node_modules/(?!(vue|@vue/test-utils|@vue/vue3-jest)/)'],
  moduleFileExtensions: ['ts', 'js', 'json', 'vue'],
  collectCoverage: true,
  collectCoverageFrom: ['src/**/*.{ts,vue}', '!src/main.ts'],
  coverageReporters: ['text', 'lcov'],
  testMatch: ['**/src/__tests__/**/*.spec.[jt]s?(x)', '**/src/**/?(*.)+(spec|test).[jt]s?(x)'],
  rootDir: '../',
  verbose: true,
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },
  setupFiles: ['<rootDir>/config/jest.setup.js'],
}
