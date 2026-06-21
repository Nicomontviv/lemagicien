import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import EventosView from '../views/EventosView.vue'
import ShowsView from '../views/ShowsView.vue'
import TrucosView from '../views/TrucosView.vue'
import LoginView from '../views/LoginView.vue'
import RegistroView from '../views/RegistroView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/eventos', name: 'eventos', component: EventosView },
    { path: '/shows', name: 'shows', component: ShowsView },
    { path: '/trucos', name: 'trucos', component: TrucosView },
    { path: '/login', name: 'login', component: LoginView },
    { path: '/registro', name: 'registro', component: RegistroView }
  ]
})

// Guardia de seguridad (Vue Router 4 - Sin next())
router.beforeEach((to, from) => {
  const estaLogueado = localStorage.getItem('user_id')
  
  // Si NO está logueado y la ruta NO es 'login' NI 'registro', lo pateamos al login
  if (!estaLogueado && to.name !== 'login' && to.name !== 'registro') {
    return { name: 'login' }
  }
  
  // Si ya está logueado y quiere ir al login o registro por accidente, lo mandamos al panel
  if (estaLogueado && (to.name === 'login' || to.name === 'registro')) {
    return { name: 'home' }
  }
  
  // Si todo está bien, lo dejamos pasar
  return true
})

export default router