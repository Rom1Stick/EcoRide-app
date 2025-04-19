import { describe, it, expect } from '@jest/globals'
import { shallowMount } from '@vue/test-utils'
import HomeView from '../../pages/HomeView.vue'
import CounterComponent from '../../components/CounterComponent.vue'

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

  it('contient la section de démonstration des composants', () => {
    const wrapper = shallowMount(HomeView)

    expect(wrapper.find('.demo-section').exists()).toBe(true)
    expect(wrapper.find('.demo-section h2').text()).toBe('Démonstration des composants')
  })

  it('inclut le composant CounterComponent', () => {
    const wrapper = shallowMount(HomeView)

    // Vérifier que le composant Counter est présent avec les bonnes props
    const counter = wrapper.findComponent(CounterComponent)
    expect(counter.exists()).toBe(true)
    expect(counter.props('title')).toBe('Compteur de trajets')
    expect(counter.props('initialValue')).toBe(3)
  })
})
