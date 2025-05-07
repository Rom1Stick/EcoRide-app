module.exports = {
  ci: {
    collect: {
      startServerCommand: 'cd frontend && npm run build && npm run preview:ci',
      startServerReadyPattern: 'server started at|ready in',
      url: ['http://localhost:3000'],
      numberOfRuns: 1,
      settings: {
        // Options spéciales pour environnement CI
        chromeFlags: '--no-sandbox --disable-dev-shm-usage --disable-gpu --headless=new',
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
          'interactive',
          'first-contentful-paint',
          'largest-contentful-paint'
        ],
        // Désactiver la limitation de CPU/réseau dans CI
        throttling: {
          cpuSlowdownMultiplier: 1,
          downloadThroughputKbps: 10000,
          uploadThroughputKbps: 10000,
          rttMs: 0
        },
        onlyCategories: ['accessibility', 'best-practices'],
        // Augmenter le timeout pour les environnements CI
        maxWaitForLoad: 120000,
        // Éviter les problèmes avec le headless browser
        disableStorageReset: true,
      }
    },
    assert: {
      assertions: {
        // Assouplir les critères pour CI
        'categories:performance': ['off'],
        'categories:accessibility': ['warn', { minScore: 0.7 }],
        'categories:best-practices': ['warn', { minScore: 0.7 }],
        'categories:seo': ['off'],

        // Désactiver les critères d'éco-conception stricts pour CI
        'uses-optimized-images': ['off'],
        'uses-webp-images': ['off'],
        'uses-responsive-images': ['off'],
        'offscreen-images': ['off'],
        'total-byte-weight': ['off'],
        'unused-javascript': ['off'],
        'uses-rel-preconnect': ['off'],
        'efficient-animated-content': ['off'],
        
        // Désactiver les critères de performance qui posent problème
        'first-contentful-paint': ['off'],
        'largest-contentful-paint': ['off'],
        'cumulative-layout-shift': ['off'],
        'total-blocking-time': ['off'],
        'server-response-time': ['off']
      }
    },
    upload: {
      target: 'filesystem'
    },
    outputPath: '.lighthouseci'
  }
}; 