import { describe, it, expect, beforeEach } from '@jest/globals'
import { setActivePinia, createPinia } from 'pinia'
import { useCounterStore } from '../store/counter'

describe('Counter Store', () => {
  let counter: number

  beforeEach(() => {
    setActivePinia(createPinia())
    counter = 0
  })

  it('increments the counter', () => {
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

  it('decrements the counter', () => {
    counter--
    expect(counter).toBe(-1)
  })
})
