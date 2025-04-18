import { describe, it, expect, beforeEach } from 'jest'
import { setActivePinia, createPinia } from 'pinia'
import { useCounterStore } from '../../store/counter'

describe('Counter Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('a une valeur initiale de 0', () => {
    const store = useCounterStore()
    expect(store.count).toBe(0)
  })

  it('incrémente le compteur', () => {
    const store = useCounterStore()
    expect(store.count).toBe(0)
    store.increment()
    expect(store.count).toBe(1)
  })

  it('incrémente plusieurs fois correctement', () => {
    const store = useCounterStore()
    store.increment()
    store.increment()
    store.increment()
    expect(store.count).toBe(3)
  })

  it('calcule correctement le double', () => {
    const store = useCounterStore()
    store.count = 2
    expect(store.doubleCount).toBe(4)

    store.count = 5
    expect(store.doubleCount).toBe(10)
  })

  it('expose les bonnes propriétés et méthodes', () => {
    const store = useCounterStore()
    expect(store).toHaveProperty('count')
    expect(store).toHaveProperty('doubleCount')
    expect(store).toHaveProperty('increment')
    expect(typeof store.increment).toBe('function')
  })
})
