import { describe, it, expect } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AboutView from '../../pages/AboutView.vue'

describe('AboutView.vue', () => {
  it('renders properly', () => {
    const wrapper = shallowMount(AboutView)
    expect(wrapper.exists()).toBe(true)
  })

  it('displays the main title', () => {
    const wrapper = shallowMount(AboutView)
    expect(wrapper.find('h1').text()).toBe("À propos d'EcoRide")
  })

  it('contains information about the app', () => {
    const wrapper = shallowMount(AboutView)
    const content = wrapper.text()
    expect(content).toContain('EcoRide')
    expect(content).toContain('application')
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
