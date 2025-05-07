module.exports = {
  ci: {
    collect: {
      startServerCommand: 'cd frontend && npm ci && npm run serve',
      startServerReadyPattern: 'http://localhost:3000',
      url: ['http://localhost:3000', 'http://localhost:3000/about'],
      numberOfRuns: 2,
      settings: {
        throttling: {
          // Simuler une connexion lente pour évaluer les performances dans des conditions défavorables
          cpuSlowdownMultiplier: 4,
          downloadThroughputKbps: 1500,
          uploadThroughputKbps: 750,
          rttMs: 40
        },
        formFactor: 'mobile',
        screenEmulation: {
          mobile: true,
          width: 375,
          height: 667,
          deviceScaleFactor: 2,
          disabled: false
        }
      }
    },
    assert: {
      assertions: {
        // Critères de performance
        'categories:performance': ['error', { minScore: 0.90 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
        'categories:best-practices': ['error', { minScore: 0.95 }],
        'categories:seo': ['error', { minScore: 0.90 }],

        // Critères d'éco-conception
        'uses-optimized-images': ['error', { maxLength: 0 }],
        'uses-webp-images': ['warn', { maxLength: 0 }],
        'uses-responsive-images': ['error', { maxLength: 0 }],
        'offscreen-images': ['error', { maxLength: 0 }],
        'total-byte-weight': ['error', { maxNumericValue: 1000000 }], // 1MB max
        'unused-javascript': ['error', { maxLength: 0 }],
        'uses-rel-preconnect': ['warn', { maxLength: 0 }],
        'efficient-animated-content': ['warn', { maxLength: 0 }],
        
        // Performance et ressources
        'first-contentful-paint': ['error', { maxNumericValue: 2000 }],
        'largest-contentful-paint': ['error', { maxNumericValue: 2500 }],
        'cumulative-layout-shift': ['error', { maxNumericValue: 0.1 }],
        'total-blocking-time': ['error', { maxNumericValue: 300 }],
        'server-response-time': ['error', { maxNumericValue: 600 }]
      }
    },
    upload: {
      target: 'filesystem'
    },
    outputPath: '.lighthouseci'
  }
};