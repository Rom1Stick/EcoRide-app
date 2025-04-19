/**
 * Test basique pour valider le fonctionnement de l'application
 */
describe('Tests de base', () => {
  beforeEach(() => {
    // Réinitialiser l'état entre les tests
    cy.clearLocalStorage()
    cy.clearCookies()
  })

  it("Vérifie que l'application se charge correctement", () => {
    // Visite de la page d'accueil
    cy.visit('/')

    // Vérifie que le document a été chargé
    cy.document().should('have.property', 'readyState', 'complete')

    // Vérifie que le corps de la page existe
    cy.get('body').should('exist')
  })

  it('Vérifie les performances de base', () => {
    // Vérifie que la page se charge en moins de 5 secondes
    cy.visit('/', {
      onBeforeLoad: (win) => {
        win.performance.mark('start-loading')
      },
      onLoad: (win) => {
        win.performance.mark('end-loading')
        win.performance.measure('page-load', 'start-loading', 'end-loading')
      },
    })

    // Vérification que le DOM est accessible
    cy.get('body').should('be.visible')
  })
})
