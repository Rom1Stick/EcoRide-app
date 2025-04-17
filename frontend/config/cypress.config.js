import { defineConfig } from 'cypress';

export default defineConfig({
  e2e: {
    // Emplacement des specs E2E
    specPattern: 'cypress/e2e/**/*.cy.{js,ts}',
    supportFile: false,
    baseUrl: 'http://localhost:3001',
    setupNodeEvents(on, config) {
      // On détecte dynamiquement le port disponible
      if (process.env.PORT) {
        config.baseUrl = `http://localhost:${process.env.PORT}`;
      }
      return config;
    }
  }
}); 