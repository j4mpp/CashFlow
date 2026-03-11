import { createRouter, createWebHashHistory } from 'vue-router'
import DashBoard from '@/views/DashBoard.vue'
import KategorienVue from '@/views/KategorienVue.vue'
import AnalysenVue from '@/views/AnalysenVue.vue'
import LoginView from '@/views/LoginView.vue'
import AktivitaetenView from '@/views/AktivitaetenView.vue'

const routes = [
  { path: '/', name: 'dashboard', component: DashBoard },
  { path: '/kategorien', name: 'kategorien', component: KategorienVue },
  { path: '/analysen', name: 'analysen', component: AnalysenVue },
  { path: '/activity', name: 'activity', component: AktivitaetenView },
  { path: '/login', name: 'login', component: LoginView },
  { path: '/about', name: 'about', component: () => import('../views/AboutView.vue') }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const userid = localStorage.getItem("userid")
  if (!userid && to.name !== "login") {
    next({ name: "login" })
  } else {
    next()
  }
})

export default router