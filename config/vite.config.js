// Fichier de compatibilité - Redirection vers le fichier de configuration dans le dossier frontend
import { resolve } from 'path';
import { defineConfig } from 'vite';

// Importer la configuration Vite du frontend
const frontendViteConfig = require(resolve(__dirname, '../frontend/config/vite.config.js'));

// Exporter la configuration du frontend avec quelques modifications possibles
export default defineConfig({
  ...frontendViteConfig.default,
  // Vous pouvez ajouter ou modifier des options spécifiques ici si nécessaire
}); 