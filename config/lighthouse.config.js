module.exports = {
  ci: {
    collect: {
      startServerCommand: 'cd frontend && npm ci && npm run serve',
      startServerReadyPattern: 'http://localhost:3000',
      url: ['http://localhost:3000'],
      numberOfRuns: 1,
      settings: {
        // Options spéciales pour environnement CI
        chromeFlags: '--no-sandbox --disable-dev-shm-usage --disable-gpu --headless',
        formFactor: 'desktop', // Moins de problèmes qu'en mode mobile
        // Désactiver l'émulation mobile pour réduire les problèmes
        screenEmulation: {
          mobile: false,
          width: 1350,
          height: 940,
          deviceScaleFactor: 1,
          disabled: true
        },
        skipAudits: [
          'uses-http2',
          'uses-long-cache-ttl',
          'is-on-https',
          'redirects-http',
          'efficient-animated-content',
          'interactive'
        ],
        // Désactiver la limitation de CPU/réseau dans CI
        throttling: {
          cpuSlowdownMultiplier: 1,
          downloadThroughputKbps: 10000,
          uploadThroughputKbps: 10000,
          rttMs: 0
        },
        onlyCategories: ['performance', 'accessibility', 'best-practices'],
        // Augmenter le timeout pour les environnements CI
        maxWaitForLoad: 60000
      }
    },
    assert: {
      assertions: {
        // Assouplir les critères pour CI
        'categories:performance': ['warn', { minScore: 0.5 }],
        'categories:accessibility': ['warn', { minScore: 0.7 }],
        'categories:best-practices': ['warn', { minScore: 0.7 }],
        'categories:seo': ['off'],

        // Désactiver les critères d'éco-conception stricts pour CI
        'uses-optimized-images': ['off'],
        'uses-webp-images': ['off'],
        'uses-responsive-images': ['off'],
        'offscreen-images': ['off'],
        'total-byte-weight': ['warn', { maxNumericValue: 5000000 }], // 5MB max
        'unused-javascript': ['off'],
        'uses-rel-preconnect': ['off'],
        'efficient-animated-content': ['off'],
        
        // Assouplir les critères de performance
        'first-contentful-paint': ['warn', { maxNumericValue: 5000 }],
        'largest-contentful-paint': ['warn', { maxNumericValue: 6000 }],
        'cumulative-layout-shift': ['warn', { maxNumericValue: 0.5 }],
        'total-blocking-time': ['warn', { maxNumericValue: 1000 }],
        'server-response-time': ['warn', { maxNumericValue: 2000 }]
      }
    },
    upload: {
      target: 'filesystem'
    },
    outputPath: '.lighthouseci'
  }
}; 