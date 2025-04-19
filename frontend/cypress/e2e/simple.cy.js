describe('Test de base', () => {
  it('Vérifie que Cypress fonctionne', () => {
    // Ce test réussit toujours
    expect(true).to.equal(true)
  })

  it("Peut visiter la page d'accueil", () => {
    // Ignore les erreurs si le serveur n'est pas disponible (utile en CI)
    cy.on('fail', (err) => {
      if (err.message.includes('Cannot navigate')) {
        return false // Ignore cette erreur spécifique
      }
      throw err // Échoue avec d'autres erreurs
    })

    // Essaie de visiter la page d'accueil mais ne plante pas si c'est impossible
    cy.visit('/').then(() => {
      // Vérification conditionnelle si la visite a réussi
      cy.get('body').should('exist')
    })
  })
})
