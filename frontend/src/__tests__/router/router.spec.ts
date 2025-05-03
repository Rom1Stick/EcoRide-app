import { describe, it, expect } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from '../../router'

describe('Router Configuration', () => {
  const router = createRouter({
    history: createWebHistory('/'),
    routes,
  })

  it('has a home route', () => {
    const homeRoute = router.hasRoute('home')
    expect(homeRoute).toBe(true)
  })

  it('has an about route', () => {
    const aboutRoute = router.hasRoute('about')
    expect(aboutRoute).toBe(true)
  })

  it('home route renders the HomeView component', () => {
    const homeRoute = router.getRoutes().find((route) => route.name === 'home')
    expect(homeRoute).toBeDefined()
    expect(homeRoute?.path).toBe('/')
    expect(typeof homeRoute?.components?.default).toBe('function')
  })

  it('about route renders the AboutView component', () => {
    const aboutRoute = router.getRoutes().find((route) => route.name === 'about')
    expect(aboutRoute).toBeDefined()
    expect(aboutRoute?.path).toBe('/about')
    expect(typeof aboutRoute?.components?.default).toBe('function')
  })
})
