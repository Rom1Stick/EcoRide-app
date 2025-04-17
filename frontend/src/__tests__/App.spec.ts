import { describe, it, expect } from 'jest'
import { shallowMount, RouterLinkStub } from '@vue/test-utils'
import App from '../App.vue'

describe('App.vue', () => {
  it('affiche le titre EcoRide', () => {
    const wrapper = shallowMount(App, {
      global: {
        stubs: {
          RouterView: true,
          RouterLink: RouterLinkStub,
        },
      },
    })

    expect(wrapper.text()).toContain('EcoRide')
    expect(wrapper.find('h1').exists()).toBe(true)
  })

  it('contient un RouterView', () => {
    const wrapper = shallowMount(App, {
      global: {
        stubs: {
          RouterView: true,
          RouterLink: RouterLinkStub,
        },
      },
    })

    expect(wrapper.findComponent({ name: 'RouterView' }).exists()).toBe(true)
  })
})
