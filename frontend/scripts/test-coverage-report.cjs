/**
 * Script pour générer et analyser un rapport de couverture de tests
 * Usage: node scripts/test-coverage-report.cjs
 */
/* eslint-env es6 */

const fs = require('fs')
const path = require('path')
const { execSync } = require('child_process')

// Dans un fichier .cjs, __dirname est déjà défini
const coverageDir = path.join(__dirname, '..', 'coverage')

// Seuils de couverture minimaux
const THRESHOLDS = {
  statements: 80,
  branches: 70,
  functions: 80,
  lines: 80,
}

console.log('📊 Génération du rapport de couverture des tests...')

try {
  // Créer le dossier coverage s'il n'existe pas
  if (!fs.existsSync(coverageDir)) {
    fs.mkdirSync(coverageDir, { recursive: true })
  }

  // Exécuter les tests avec couverture
  execSync('npm test', { stdio: 'inherit' })

  // Vérifier si le rapport de couverture existe
  const summaryPath = path.join(coverageDir, 'coverage-summary.json')

  if (!fs.existsSync(summaryPath)) {
    console.log(
      "ℹ️ Aucun fichier de rapport de couverture trouvé. Probablement aucun test n'est présent."
    )
    console.log(
      '✅ Tout est en ordre si le projet est en cours de développement et que les tests seront ajoutés ultérieurement.'
    )
    process.exit(0)
  }

  // Analyser le rapport
  const summary = JSON.parse(fs.readFileSync(summaryPath, 'utf8'))
  const total = summary.total

  console.log('\n🔍 Analyse de la couverture des tests:')
  console.log(
    `   - Lignes: ${total.lines.pct.toFixed(2)}% / Seuil: ${THRESHOLDS.lines}% ${total.lines.pct >= THRESHOLDS.lines ? '✅' : '❌'}`
  )
  console.log(
    `   - Fonctions: ${total.functions.pct.toFixed(2)}% / Seuil: ${THRESHOLDS.functions}% ${total.functions.pct >= THRESHOLDS.functions ? '✅' : '❌'}`
  )
  console.log(
    `   - Branches: ${total.branches.pct.toFixed(2)}% / Seuil: ${THRESHOLDS.branches}% ${total.branches.pct >= THRESHOLDS.branches ? '✅' : '❌'}`
  )
  console.log(
    `   - Statements: ${total.statements.pct.toFixed(2)}% / Seuil: ${THRESHOLDS.statements}% ${total.statements.pct >= THRESHOLDS.statements ? '✅' : '❌'}`
  )

  // Analyser les fichiers individuels
  console.log('\n📄 Fichiers avec une couverture insuffisante:')
  let hasLowCoverage = false

  for (const [file, stats] of Object.entries(summary)) {
    if (file === 'total') continue

    const isLowCoverage =
      stats.lines.pct < THRESHOLDS.lines ||
      stats.functions.pct < THRESHOLDS.functions ||
      stats.branches.pct < THRESHOLDS.branches ||
      stats.statements.pct < THRESHOLDS.statements

    if (isLowCoverage) {
      hasLowCoverage = true
      console.log(`   - ${file}`)
      console.log(
        `     Lines: ${stats.lines.pct.toFixed(2)}%, Functions: ${stats.functions.pct.toFixed(2)}%, Branches: ${stats.branches.pct.toFixed(2)}%`
      )
    }
  }

  if (!hasLowCoverage) {
    console.log('   ✅ Tous les fichiers ont une couverture suffisante!')
  }

  // Vérifier si tous les seuils sont respectés
  const isThresholdsMet =
    total.lines.pct >= THRESHOLDS.lines &&
    total.functions.pct >= THRESHOLDS.functions &&
    total.branches.pct >= THRESHOLDS.branches &&
    total.statements.pct >= THRESHOLDS.statements

  console.log('\n📈 Résultat global:')
  if (isThresholdsMet) {
    console.log('   ✅ La couverture des tests est conforme aux exigences minimales!')
  } else {
    console.log(
      '   ❌ La couverture des tests est insuffisante. Des améliorations sont nécessaires.'
    )
    console.log('   💡 Suggestions:')
    console.log('      - Ajouter des tests pour les fichiers avec une couverture insuffisante')
    console.log("      - Tester les cas limites et les chemins d'erreur")
    console.log('      - Utiliser des mocks pour les dépendances externes')
  }
} catch (error) {
  console.error('❌ Erreur lors de la génération du rapport:', error)
  process.exit(1)
}
