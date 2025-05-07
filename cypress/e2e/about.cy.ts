describe('Page À propos', () => {
  beforeEach(() => {
    cy.visit('/about')
  })

  it('affiche le titre À propos', () => {
    cy.get('h1').contains('À propos').should('be.visible')
  })

  it("affiche la description de l'application", () => {
    cy.contains('application éco-conçue').should('be.visible')
    cy.contains('empreinte carbone').should('be.visible')
  })

  it("a un lien vers la page d'accueil", () => {
    cy.get('nav a').contains('Home').should('have.attr', 'href').and('include', '/')
  })

  it('charge le JS en lazy-loading', () => {
    // Vérifie que le chunk JS de la page about a été chargé
    cy.window().then((win) => {
      const resources = win.performance.getEntriesByType('resource')
      const aboutJsLoaded = resources.some(
        (resource) => resource.name.includes('AboutView') && resource.name.endsWith('.js')
      )
      expect(aboutJsLoaded).to.be.true
    })
  })

  it("navigue correctement vers la page d'accueil", () => {
    cy.get('nav a').contains('Home').click()
    cy.url().should('not.include', '/about')
    cy.contains("Bienvenue sur l'application EcoRide").should('be.visible')
  })
})
