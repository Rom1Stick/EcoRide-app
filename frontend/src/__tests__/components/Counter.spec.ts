import { mount } from '@vue/test-utils'
import { describe, it, expect } from '@jest/globals'
import CounterComponent from '../../components/CounterComponent.vue'

describe('CounterComponent', () => {
  it('renders with default props', () => {
    const wrapper = mount(CounterComponent)
    expect(wrapper.find('.counter').exists()).toBe(true)
    expect(wrapper.find('.counter-display').text()).toBe('0')
  })

  it('renders with custom initial value', () => {
    const wrapper = mount(CounterComponent, {
      props: {
        initialValue: 5,
      },
    })
    expect(wrapper.find('.counter-display').text()).toBe('5')
  })

  it('renders with custom title', () => {
    const title = 'Test Counter'
    const wrapper = mount(CounterComponent, {
      props: { title },
    })
    expect(wrapper.find('h2').text()).toBe(title)
  })

  it('increments the counter when increment button is clicked', async () => {
    const wrapper = mount(CounterComponent)

    // Initial value should be 0
    expect(wrapper.find('.counter-display').text()).toBe('0')

    // Click increment button
    await wrapper.find('.increment').trigger('click')

    // Value should be incremented to 1
    expect(wrapper.find('.counter-display').text()).toBe('1')
  })

  it('decrements the counter when decrement button is clicked', async () => {
    const wrapper = mount(CounterComponent, {
      props: {
        initialValue: 5,
      },
    })

    // Initial value should be 5
    expect(wrapper.find('.counter-display').text()).toBe('5')

    // Click decrement button
    await wrapper.find('.decrement').trigger('click')

    // Value should be decremented to 4
    expect(wrapper.find('.counter-display').text()).toBe('4')
  })
})
