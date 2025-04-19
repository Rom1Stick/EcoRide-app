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
  globals: {
    'ts-jest': {
      tsconfig: '<rootDir>/tsconfig.test.json',
    },
    'vue-jest': {
      tsConfig: '<rootDir>/tsconfig.test.json',
    },
  },
}
