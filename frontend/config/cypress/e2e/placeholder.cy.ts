// Test Cypress simple pour vérifier que tout fonctionne

describe('Tests E2E de base', () => {
  it('La page se charge correctement', () => {
    cy.visit('/')
    cy.get('html').should('exist')
  })
})
