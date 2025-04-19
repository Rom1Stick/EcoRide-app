import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../pages/HomeView.vue'

// Utiliser une valeur par défaut pour BASE_URL
const BASE_URL = '/'

const router = createRouter({
  history: createWebHistory(BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/about',
      name: 'about',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../pages/AboutView.vue'),
    },
  ],
})

export default router
