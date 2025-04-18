describe('Tests de performance', () => {
  it("charge la page d'accueil rapidement", () => {
    cy.visit('/', {
      onBeforeLoad: (_win) => {
        _win.performance.mark('start-loading')
      },
      onLoad: (_win) => {
        _win.performance.mark('end-loading')
      },
    })

    cy.window().then((_win) => {
      _win.performance.measure('page-load', 'start-loading', 'end-loading')
      const measure = _win.performance.getEntriesByName('page-load')[0]
      expect(measure.duration).to.be.lessThan(1000) // La page doit charger en moins de 1s
    })

    // Vérifie la taille du DOM (indicateur d'écoconception)
    cy.document().then((doc) => {
      const domSize = new Blob([doc.documentElement.outerHTML]).size / 1024
      expect(domSize).to.be.lessThan(100) // Le DOM doit être < 100 KB
      cy.log(`Taille du DOM: ${domSize.toFixed(2)} KB`)
    })
  })

  it('charge les ressources efficacement', () => {
    cy.visit('/')

    // Vérifie le nombre de requêtes réseau (moins = mieux)
    cy.window().then((_win) => {
      const resources = _win.performance.getEntriesByType('resource')
      expect(resources.length).to.be.lessThan(15) // Max 15 requêtes

      // Vérifier la taille totale des ressources
      const totalSize = resources.reduce((sum, r) => sum + r.transferSize, 0) / 1024
      expect(totalSize).to.be.lessThan(200) // Total < 200 KB
      cy.log(`Taille totale des ressources: ${totalSize.toFixed(2)} KB`)
    })
  })

  it('affiche rapidement le contenu utile (FCP)', () => {
    cy.visit('/')

    // Vérifie le First Contentful Paint via Performance API
    cy.window().then((_win) => {
      const observer = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        const fcp = entries[0]
        expect(fcp.startTime).to.be.lessThan(1000) // FCP < 1s
      })
      observer.observe({ type: 'paint', buffered: true })
    })
  })

  it('charge rapidement les pages en navigation', () => {
    cy.visit('/')

    // Mesure du temps de navigation
    cy.window().then((_win) => {
      _win.performance.mark('start-navigation')
    })

    cy.get('a').contains('About').click()

    cy.window().then((_win) => {
      _win.performance.mark('end-navigation')
      _win.performance.measure('navigation-time', 'start-navigation', 'end-navigation')
      const navMeasure = _win.performance.getEntriesByName('navigation-time')[0]
      expect(navMeasure.duration).to.be.lessThan(300) // Navigation < 300ms
    })
  })
})
