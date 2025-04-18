describe('Navigation Tests', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('should navigate to the about page', () => {
    cy.contains('About').click()
    cy.url().should('include', '/about')
    cy.contains('This is an about page').should('exist')
  })

  it('should navigate back to home page', () => {
    cy.contains('About').click()
    cy.contains('Home').click()
    cy.url().should('not.include', '/about')
    cy.contains('HomeView').should('exist')
  })
})
