<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="dashboard">
    <div class="cabecera-bienvenida">
      <h1>Bienvenido, Nicolás</h1>
      <p>¿Qué te gustaría gestionar hoy?</p>
    </div>

    <div class="grilla-opciones">
      <RouterLink to="/eventos" class="tarjeta-opcion eventos">
        <div class="icono">📅</div>
        <h2>Eventos</h2>
        <p>Revisá tu agenda y contrataciones</p>
      </RouterLink>

      <RouterLink to="/shows" class="tarjeta-opcion shows">
        <div class="icono">🎭</div>
        <h2>Shows</h2>
        <p>Armá tus rutinas y espectáculos</p>
      </RouterLink>

      <RouterLink to="/trucos" class="tarjeta-opcion trucos">
        <div class="icono">🃏</div>
        <h2>Trucos</h2>
        <p>Administrá tu catálogo de ilusiones</p>
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.dashboard {
  text-align: center;
  padding: 2rem 0;
}

.cabecera-bienvenida h1 {
  font-size: 2.5rem;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.cabecera-bienvenida p {
  font-size: 1.2rem;
  color: #666;
  margin-bottom: 3rem;
}

.grilla-opciones {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  padding: 0 1rem;
}

.tarjeta-opcion {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: white;
  padding: 2.5rem 1.5rem;
  border-radius: 12px;
  text-decoration: none;
  color: #333;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.tarjeta-opcion:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  border-color: #42b983;
}

.icono {
  font-size: 3.5rem;
  margin-bottom: 1rem;
}

.tarjeta-opcion h2 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.tarjeta-opcion p {
  margin: 0;
  color: #777;
  font-size: 0.95rem;
}
</style>