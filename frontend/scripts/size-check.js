/**
 * Script pour vérifier la taille des bundles
 * Usage: node scripts/size-check.js
 */

import fs from 'fs'
import path from 'path'
import zlib from 'zlib'
import { fileURLToPath } from 'url'

// Récupérer le dossier courant
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const distDir = path.join(__dirname, '../dist/assets')

// Limites de taille en KB
const LIMITS = {
  js: 120,
  css: 10,
  aboutPage: 5,
  total: 200,
}

// Fonction pour obtenir la taille d'un fichier en KB
function getFileSize(filePath) {
  const stats = fs.statSync(filePath)
  return (stats.size / 1024).toFixed(2)
}

// Fonction pour obtenir la taille gzippée d'un fichier en KB
function getGzippedSize(filePath) {
  const fileContent = fs.readFileSync(filePath)
  const gzippedContent = zlib.gzipSync(fileContent)
  return (gzippedContent.length / 1024).toFixed(2)
}

// Fonction pour vérifier un fichier ou un pattern
function checkSize(pattern, limit, name) {
  console.log(`\n📦 Vérification de ${name}:`)

  const files = fs.readdirSync(distDir).filter((file) => {
    if (pattern === '*') return true
    return new RegExp(pattern).test(file)
  })

  files.forEach((file) => {
    const filePath = path.join(distDir, file)
    const size = getFileSize(filePath)
    const gzipSize = getGzippedSize(filePath)

    console.log(`   ${file}:`)
    console.log(`     - Taille: ${size} KB / Limite: ${limit} KB ${size > limit ? '❌' : '✅'}`)
    console.log(`     - Gzip:   ${gzipSize} KB ${gzipSize > limit * 0.4 ? '⚠️' : '✅'}`)
  })
}

// Vérifier les fichiers individuels
checkSize('index-.*\\.js$', LIMITS.js, 'Bundle JS principal')
checkSize('index-.*\\.css$', LIMITS.css, 'Bundle CSS')
checkSize('AboutView-.*\\.js$', LIMITS.aboutPage, 'Page About (lazy-loaded)')

// Vérifier la taille totale
console.log('\n📊 Taille totale:')
let totalSize = 0
let totalGzipSize = 0

fs.readdirSync(distDir).forEach((file) => {
  const filePath = path.join(distDir, file)
  totalSize += parseFloat(getFileSize(filePath))
  totalGzipSize += parseFloat(getGzippedSize(filePath))
})

// Ajouter index.html
const indexHtmlPath = path.join(__dirname, '../dist/index.html')
totalSize += parseFloat(getFileSize(indexHtmlPath))
totalGzipSize += parseFloat(getGzippedSize(indexHtmlPath))

console.log(
  `   - Taille totale:     ${totalSize.toFixed(2)} KB / Limite: ${LIMITS.total} KB ${totalSize > LIMITS.total ? '❌' : '✅'}`
)
console.log(
  `   - Taille totale gzip: ${totalGzipSize.toFixed(2)} KB ${totalGzipSize > LIMITS.total * 0.4 ? '⚠️' : '✅'}`
)

// Résumé
console.log('\n🔍 Résumé:')
console.log(`   - JS principal: ${totalSize <= LIMITS.js ? '✅' : '❌'}`)
console.log(`   - CSS: ${totalSize <= LIMITS.css ? '✅' : '❌'}`)
console.log(`   - About Page: ${totalSize <= LIMITS.aboutPage ? '✅' : '❌'}`)
console.log(`   - Taille totale: ${totalSize <= LIMITS.total ? '✅' : '❌'}`)
console.log('\n💡 Recommandations:')
console.log('   - Utiliser la compression Brotli en production')
console.log('   - Optimiser les images avec WebP')
console.log('   - Mettre en place un système de cache efficace')
