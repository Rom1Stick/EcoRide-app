#!/usr/bin/env node

/**
 * Script qui termine l'exécution de Cypress après un délai spécifié
 * Usage: node cypress-timeout.js [timeout_en_secondes]
 */

const timeoutSeconds = parseInt(process.argv[2] || '180', 10)

console.log(
  `⏰ Cypress sera interrompu après ${timeoutSeconds} secondes si les tests ne se terminent pas`
)

// Attendre le délai spécifié puis terminer le processus
setTimeout(() => {
  console.error(`❌ Timeout de ${timeoutSeconds} secondes atteint. Arrêt forcé des tests Cypress.`)
  process.exit(1) // Code d'erreur pour indiquer l'échec
}, timeoutSeconds * 1000)

// Cette commande est censée être exécutée avec les tests e2e qui la suivent
