import { describe, it, expect, beforeEach } from '@jest/globals'
import { setActivePinia, createPinia } from 'pinia'
import { useCounterStore } from '../../store/counter'

describe('Counter Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('increments count', () => {
    const store = useCounterStore()
    expect(store.count).toBe(0)

    store.increment()
    expect(store.count).toBe(1)
  })

  it('decrements count', () => {
    const store = useCounterStore()
    expect(store.count).toBe(0)

    store.decrement()
    expect(store.count).toBe(-1)
  })

  it('resets count', () => {
    const store = useCounterStore()
    store.count = 5

    store.reset()
    expect(store.count).toBe(0)
  })

  it('returns double count', () => {
    const store = useCounterStore()
    store.count = 2

    expect(store.doubleCount).toBe(4)
  })

  it('expose les bonnes propriétés et méthodes', () => {
    const store = useCounterStore()
    expect(store).toHaveProperty('count')
    expect(store).toHaveProperty('doubleCount')
    expect(store).toHaveProperty('increment')
    expect(typeof store.increment).toBe('function')
  })
})
