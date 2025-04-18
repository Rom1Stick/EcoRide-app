<script setup lang="ts">
// Page d'accueil
import CounterComponent from '../components/CounterComponent.vue'
</script>

<template>
  <main>
    <h1>Accueil EcoRide</h1>
    <p>Bienvenue sur l'application EcoRide, la solution de mobilité éco-responsable.</p>

    <section class="demo-section">
      <h2>Démonstration des composants</h2>
      <CounterComponent title="Compteur de trajets" :initial-value="3" />
    </section>
  </main>
</template>

<style scoped>
.demo-section {
  margin-top: 2rem;
  padding: 1.5rem;
  border-radius: 0.5rem;
  background-color: #eef7ee;
}
</style>
