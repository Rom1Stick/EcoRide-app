module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'jsdom',
  transform: {
    '^.+\\.vue$': [
      '@vue/vue3-jest',
      {
        tsConfig: './config/tsconfig.json',
      },
    ],
    '^.+\\.(ts|js)$': [
      'ts-jest',
      {
        tsconfig: './config/tsconfig.json',
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
      tsconfig: '<rootDir>/config/tsconfig.json',
    },
    'vue-jest': {
      tsConfig: '<rootDir>/config/tsconfig.json',
    },
  },
}
