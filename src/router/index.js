import { createRouter, createWebHashHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import DashBoard from '@/views/DashBoard.vue'
import KategorienVue from '@/views/KategorienVue.vue'
import AnalysenVue from '@/views/AnalysenVue.vue'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: DashBoard
  },
  {
    path: '/kategorien',
    name: 'kategorien',
    component: KategorienVue
  },
  {
    path: '/analysen',
    name: 'analysen',
    component: AnalysenVue
  },
  {
    path: '/about',
    name: 'about',
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: () => import(/* webpackChunkName: "about" */ '../views/AboutView.vue')
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
