<script setup>
import { RouterLink, RouterView, useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const cerrarSesion = () => {
  // 1. Borramos el ID del usuario del almacenamiento local
  localStorage.removeItem('user_id')
  
  // 2. Redirigimos al login (el guardia del router validará esto y lo dejará pasar)
  router.push('/login')
}
</script>

<template>
  <header class="navbar">
    <RouterLink to="/" class="logo">🎩 Le Magicien</RouterLink>
    
    <nav v-if="route.name !== 'login' && route.name !== 'registro'">
      <button @click="cerrarSesion" class="btn-logout">Cerrar Sesión</button>
    </nav>
  </header>

  <main class="contenedor-principal">
    <RouterView />
  </main>
</template>

<style>
/* Estilos globales básicos */
body {
  margin: 0;
  /* Aplicamos Roboto a todo el proyecto */
  font-family: 'Roboto', sans-serif;
  background-color: #f4f4f9;
  color: #333;
}

.navbar {
  background-color: #2c3e50;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between; /* Esto empuja el logo a la izq y el nav a la der */
  align-items: center;
}

.navbar .logo {
  color: white;
  font-size: 1.5rem;
  font-weight: bold;
  text-decoration: none; /* Asegura que el logo no tenga subrayado de link */
}

nav a {
  color: #42b983;
  text-decoration: none;
  margin-left: 1.5rem;
  font-weight: bold;
}

nav a.router-link-exact-active {
  color: white;
}

/* Estilos para el botón de cerrar sesión */
.btn-logout {
  background-color: transparent;
  color: #ff4757;
  border: 1px solid #ff4757;
  padding: 6px 12px;
  border-radius: 6px;
  font-family: 'Roboto', sans-serif;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-logout:hover {
  background-color: #ff4757;
  color: white;
}

.contenedor-principal {
  padding: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}
</style>