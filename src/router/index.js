import { createRouter, createWebHashHistory } from 'vue-router'
import DashBoard from '@/views/DashBoard.vue'
import KategorienVue from '@/views/KategorienVue.vue'
import AnalysenVue from '@/views/AnalysenVue.vue'
import LoginView from '@/views/LoginView.vue'

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
    path: '/login',
    name: 'login',
    component: LoginView
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
