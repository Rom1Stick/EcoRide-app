import { describe, it, expect } from 'jest'
import { shallowMount } from '@vue/test-utils'
import AboutView from '../../pages/AboutView.vue'

describe('AboutView.vue', () => {
  it('affiche le titre À propos', () => {
    const wrapper = shallowMount(AboutView)

    expect(wrapper.find('h1').text()).toBe("À propos d'EcoRide")
  })

  it("affiche la description de l'application", () => {
    const wrapper = shallowMount(AboutView)

    expect(wrapper.find('p').text()).toContain('application éco-conçue')
    expect(wrapper.find('p').text()).toContain('empreinte carbone')
  })

  it('a la balise main comme conteneur principal', () => {
    const wrapper = shallowMount(AboutView)

    expect(wrapper.find('main').exists()).toBe(true)
  })
})
