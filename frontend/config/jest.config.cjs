module.exports = {
  preset: 'ts-jest/presets/js-with-ts-esm',
  testEnvironment: 'jsdom',
  transform: {
    '^.+\\.vue$': [
      '@vue/vue3-jest',
      {
        tsConfig: '<rootDir>/tsconfig.test.json',
      },
    ],
    '^.+\\.(ts|js|tsx|jsx)$': [
      'ts-jest',
      {
        tsconfig: '<rootDir>/tsconfig.test.json',
        useESM: true,
        isolatedModules: true,
      },
    ],
  },
  transformIgnorePatterns: ['node_modules/(?!(vue|@vue/test-utils|@vue/vue3-jest)/)'],
  moduleFileExtensions: ['ts', 'tsx', 'js', 'jsx', 'json', 'vue'],
  collectCoverage: true,
  collectCoverageFrom: ['src/**/*.{ts,vue}', '!src/main.ts'],
  coverageReporters: ['text', 'lcov'],
  testMatch: ['**/src/__tests__/**/*.spec.[jt]s?(x)', '**/src/**/?(*.)+(spec|test).[jt]s?(x)'],
  rootDir: '../',
  verbose: true,
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    '\\.(jpg|jpeg|png|gif|webp|svg)$': '<rootDir>/config/__mocks__/fileMock.js',
  },
  setupFilesAfterEnv: ['<rootDir>/config/jest.setup.js'],
  extensionsToTreatAsEsm: ['.ts', '.tsx', '.vue'],
  globals: {
    'ts-jest': {
      useESM: true,
      isolatedModules: true,
      diagnostics: {
        warnOnly: true,
      },
    },
    'vue-jest': {
      babelConfig: false,
      experimentalCSSCompile: true,
    },
  },
  testPathIgnorePatterns: ['/node_modules/'],
  bail: 0,
}
