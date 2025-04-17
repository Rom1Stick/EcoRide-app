module.exports = {
  ci: {
    collect: {
      startServerCommand: 'cd frontend && npm ci && npm run serve',
      startServerReadyPattern: 'http://localhost:3000',
      url: ['http://localhost:3000'],
      numberOfRuns: 1
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.85 }],
        'categories:accessibility': ['warn', { minScore: 0.90 }],
        'categories:best-practices': ['warn', { minScore: 0.90 }]
      }
    },
    upload: {
      target: 'filesystem'
    },
    outputPath: '.lighthouseci'
  }
}; 