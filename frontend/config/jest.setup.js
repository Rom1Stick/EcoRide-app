// Définir Vue globalement avant d'importer @vue/test-utils
const Vue = require('vue')
global.Vue = Vue
// Assurer la compatibilité avec jsdom
if (typeof window !== 'undefined') {
  window.Vue = Vue
}

// Configurer @vue/test-utils
const { config } = require('@vue/test-utils')
