<template>
  <div class="counter">
    <h2>{{ title }}</h2>
    <div class="counter-display">
      {{ count }}
    </div>
    <div class="counter-actions">
      <button 
        class="counter-button decrement" 
        aria-label="Décrémenter" 
        @click="decrement"
      >
        -
      </button>
      <button 
        class="counter-button increment" 
        aria-label="Incrémenter" 
        @click="increment"
      >
        +
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, defineProps } from 'vue'

interface CounterProps {
  title?: string
  initialValue?: number
}

const props = defineProps<CounterProps>()

const count = ref(props.initialValue || 0)

function increment() {
  count.value++
}

function decrement() {
  count.value--
}
</script>

<style scoped>
.counter {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  margin: 1rem 0;
  border-radius: 0.5rem;
  background-color: #f5f5f5;
}

.counter-display {
  font-size: 2rem;
  font-weight: bold;
  margin: 1rem 0;
}

.counter-actions {
  display: flex;
  gap: 1rem;
}

.counter-button {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 2.5rem;
  height: 2.5rem;
  font-size: 1.25rem;
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  transition: transform 0.1s;
}

.counter-button:active {
  transform: scale(0.95);
}

.increment {
  background-color: #4caf50;
}

.decrement {
  background-color: #f44336;
}
</style>
