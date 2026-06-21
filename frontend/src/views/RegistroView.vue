<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'

const router = useRouter()
const nombre = ref('')
const apellido = ref('')
const email = ref('')
const password = ref('')
const mensaje = ref('')
const cargando = ref(false)

const realizarRegistro = async () => {
  if (!nombre.value || !apellido.value || !email.value || !password.value) {
    alert("Por favor, completá todos los campos.")
    return
  }

  cargando.value = true
  mensaje.value = ''

  try {
    const response = await fetch('/backend/api/usuarios/registro.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        nombre: nombre.value, 
        apellido: apellido.value, 
        email: email.value, 
        password: password.value 
      })
    })
    
    const textoRespuesta = await response.text()
    const data = JSON.parse(textoRespuesta)
    
    if (response.ok) {
      mensaje.value = data.mensaje
      // Limpiamos el formulario
      nombre.value = ''
      apellido.value = ''
      email.value = ''
      password.value = ''
    } else {
      alert('Error: ' + data.mensaje)
    }
  } catch (error) {
    console.error("Error de conexión:", error)
    alert("Falló la conexión con el servidor.")
  } finally {
    cargando.value = false
  }
}
</script>

<template>
  <div class="login-wrapper">
    <div class="login-card">
      <h1>🎩 Crear Cuenta</h1>
      <p>Unite a Le Magicien</p>
      
      <div v-if="mensaje" class="mensaje-exito">
        {{ mensaje }}
      </div>

      <div class="input-group">
        <input v-model="nombre" type="text" placeholder="Nombre" />
      </div>
      <div class="input-group">
        <input v-model="apellido" type="text" placeholder="Apellido" />
      </div>
      <div class="input-group">
        <input v-model="email" type="email" placeholder="Correo electrónico" />
      </div>
      <div class="input-group">
        <input v-model="password" type="password" placeholder="Contraseña" />
      </div>
      
      <button @click="realizarRegistro" class="btn-login" :disabled="cargando">
        {{ cargando ? 'Enviando...' : 'Registrarse' }}
      </button>

      <div class="links-extra">
        <RouterLink to="/login">¿Ya tenés cuenta? Iniciar sesión</RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 80vh;
}

.login-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  width: 100%;
  max-width: 350px;
  text-align: center;
}

h1 { color: #2c3e50; margin-bottom: 0.5rem; }
p { color: #888; margin-bottom: 1.5rem; }

.input-group { margin-bottom: 1rem; }

input {
  width: 100%;
  padding: 0.8rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-sizing: border-box;
  font-family: 'Roboto', sans-serif;
}

.btn-login {
  width: 100%;
  padding: 0.8rem;
  background-color: #42b983;
  border: none;
  border-radius: 6px;
  color: white;
  font-weight: bold;
  cursor: pointer;
  margin-top: 1rem;
  transition: background 0.3s;
}

.btn-login:hover:not(:disabled) { background-color: #3aa876; }
.btn-login:disabled { background-color: #a0d8bf; cursor: not-allowed; }

.mensaje-exito {
  background-color: #e8f5e9;
  color: #2e7d32;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
  border: 1px solid #c8e6c9;
}

.links-extra {
  margin-top: 1.5rem;
  font-size: 0.9rem;
}

.links-extra a {
  color: #42b983;
  text-decoration: none;
}
</style>