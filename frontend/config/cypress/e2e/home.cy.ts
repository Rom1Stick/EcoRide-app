describe("Page d'accueil", () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('affiche le titre EcoRide', () => {
    cy.get('h1').contains('EcoRide').should('be.visible')
  })

  it('affiche le message de bienvenue', () => {
    cy.contains("Bienvenue sur l'application EcoRide").should('be.visible')
  })

  it('a un lien vers la page about', () => {
    cy.get('nav a').contains('About').should('have.attr', 'href').and('include', '/about')
  })

  it('a le bon titre de page', () => {
    cy.title().should('include', 'EcoRide')
  })

  it('charge rapidement (performance)', () => {
    // Test de performance : la page doit se charger en moins de 1s
    cy.window().then((win) => {
      const perfEntries = win.performance.getEntriesByType('navigation')
      expect(perfEntries[0].duration).to.be.lessThan(1000)
    })
  })
})
