/**
 * Configuration size-limit pour la surveillance des bundles
 * Documentation : https://github.com/ai/size-limit
 */

export default [
  {
    name: 'Main Bundle',
    path: '../dist/assets/index-*.js',
    limit: '120 KB',
    gzip: true,
    brotli: true,
    running: false,
    import: { repeatSize: true }, // Analyser les dépendances répétées
  },
  {
    name: 'CSS',
    path: '../dist/assets/index-*.css',
    limit: '10 KB',
    gzip: true,
    brotli: true,
  },
  {
    name: 'About Page',
    path: '../dist/assets/AboutView-*.js',
    limit: '5 KB',
    gzip: true,
    running: false,
  },
  {
    name: 'HTML',
    path: '../dist/index.html',
    limit: '2 KB',
    gzip: true,
  },
  {
    name: 'Total App Size',
    path: ['../dist/index.html', '../dist/assets/*.js', '../dist/assets/*.css'],
    limit: '200 KB',
    gzip: true,
  },
]
