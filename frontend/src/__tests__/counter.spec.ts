import { describe, it, expect, beforeEach } from 'jest'
import { setActivePinia, createPinia } from 'pinia'
import { useCounterStore } from '../store/counter'

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

  it('returns doubled count', () => {
    const store = useCounterStore()
    store.count = 2
    expect(store.doubleCount).toBe(4)
  })
})
