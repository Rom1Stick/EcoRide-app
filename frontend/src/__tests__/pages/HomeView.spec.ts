import { describe, it, expect } from 'jest'
import { shallowMount } from '@vue/test-utils'
import HomeView from '../../pages/HomeView.vue'

describe('HomeView.vue', () => {
  it('affiche le titre principal', () => {
    const wrapper = shallowMount(HomeView)

    expect(wrapper.find('h1').text()).toBe('Accueil EcoRide')
  })

  it('affiche le message de bienvenue', () => {
    const wrapper = shallowMount(HomeView)

    expect(wrapper.find('p').text()).toContain("Bienvenue sur l'application EcoRide")
  })

  it('a la balise main comme conteneur principal', () => {
    const wrapper = shallowMount(HomeView)

    expect(wrapper.find('main').exists()).toBe(true)
  })
})
