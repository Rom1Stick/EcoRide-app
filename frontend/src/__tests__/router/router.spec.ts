import { describe, it, expect } from 'jest'
import router from '../../router'

describe('Router', () => {
  it('a une route pour la home page', () => {
    const homeRoute = router.getRoutes().find((route) => route.name === 'home')

    expect(homeRoute).toBeDefined()
    expect(homeRoute?.path).toBe('/')
  })

  it('a une route pour la page about', () => {
    const aboutRoute = router.getRoutes().find((route) => route.name === 'about')

    expect(aboutRoute).toBeDefined()
    expect(aboutRoute?.path).toBe('/about')
  })

  it('utilise le lazy loading pour la route about', () => {
    const aboutRoute = router.getRoutes().find((route) => route.name === 'about')

    // La propriété component doit être une fonction pour indiquer le lazy loading
    expect(typeof aboutRoute?.component).toBe('function')
  })

  it('a un mode history', () => {
    // @ts-ignore: router.options est accessible mais pas typé correctement
    expect(router.options.history.base).toBe(import.meta.env.BASE_URL)
  })
})
