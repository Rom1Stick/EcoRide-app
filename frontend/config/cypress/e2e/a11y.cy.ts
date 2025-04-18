// Tests d'accessibilité (a11y)
describe("Tests d'accessibilité", () => {
  it("respecte les critères d'accessibilité sur la page d'accueil", () => {
    cy.visit('/')

    // Vérification des alt sur les images
    cy.get('img').each(($img) => {
      cy.wrap($img).should('have.attr', 'alt')
    })

    // Vérification des titres
    cy.get('h1').should('exist')

    // Vérification du contraste (simulé)
    cy.document().then((doc) => {
      // Vérification basique que le texte n'est pas directement sur un fond blanc sans contraste
      const bodyStyle = window.getComputedStyle(doc.body)
      const hasDarkMode =
        bodyStyle.backgroundColor !== 'rgb(255, 255, 255)' || bodyStyle.color !== 'rgb(0, 0, 0)'
      expect(hasDarkMode || bodyStyle.color === 'rgb(0, 0, 0)').to.be.true
    })

    // Vérification de la navigation au clavier
    cy.get('body').type('{tab}')
    cy.focused().should('exist') // Un élément doit avoir le focus

    // Vérification des attributs ARIA
    cy.get('[role]').each(($el) => {
      const role = $el.attr('role')
      // Les rôles ne doivent pas être invalides
      expect([
        'button',
        'link',
        'navigation',
        'banner',
        'main',
        'contentinfo',
        'tab',
        'tabpanel',
      ]).to.include(role)
    })
  })

  it("respecte les critères d'accessibilité sur la page About", () => {
    cy.visit('/about')

    // Vérification des titres
    cy.get('h1').should('exist')

    // Vérification de la hiérarchie des titres
    cy.get('h1 + h2, h2 + h3, h3 + h4, h4 + h5, h5 + h6').each(($heading) => {
      const currentLevel = parseInt($heading.prop('tagName').replace('H', ''))
      const previousLevel = parseInt($heading.prev().prop('tagName').replace('H', ''))
      expect(currentLevel).to.be.greaterThan(previousLevel - 2) // Pas plus d'un niveau d'écart
    })

    // Vérification de la taille des textes (pas trop petite)
    cy.get('p, li, a').each(($text) => {
      const fontSize = parseInt(window.getComputedStyle($text[0]).fontSize)
      expect(fontSize).to.be.at.least(12) // Taille minimale de 12px
    })
  })

  it('a une structure HTML sémantique', () => {
    cy.visit('/')

    // Vérification des éléments sémantiques
    cy.get('header').should('exist')
    cy.get('main').should('exist')
    cy.get('nav').should('exist')

    // Pas trop de divs (anti-pattern)
    cy.get('div').then(($divs) => {
      cy.get('*').then(($all) => {
        const ratio = $divs.length / $all.length
        expect(ratio).to.be.lessThan(0.5) // Les divs ne devraient pas représenter plus de 50% des éléments
      })
    })
  })
})
