<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const password = ref('')

const realizarLogin = async () => {
  try {
    const response = await fetch('/backend/api/usuarios/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value, password: password.value })
    })
    
    // 1. Leemos la respuesta como texto crudo para ver qué dice el backend
    const textoRespuesta = await response.text()
    console.log("Respuesta del servidor:", textoRespuesta)
    
    if (response.ok) {
      // 2. Si todo salió bien, convertimos ese texto a JSON
      const data = JSON.parse(textoRespuesta)
      localStorage.setItem('user_id', data.usuario.id)
      router.push('/')
    } else {
      alert('Error del servidor: ' + textoRespuesta)
    }
  } catch (error) {
    // 3. Si hay un bloqueo de CORS o de red, lo atrapamos acá
    console.error("Error de conexión:", error)
    alert("Falló la conexión. Revisá la consola (F12).")
  }
}
</script>

<template>
  <div class="login-wrapper">
    <div class="login-card">
      <h1>🎩 Le Magicien</h1>
      <p>Ingresá a tu panel de gestión</p>
      
      <div class="input-group">
        <input v-model="email" type="email" placeholder="Correo electrónico" />
      </div>
      <div class="input-group">
        <input v-model="password" type="password" placeholder="Contraseña" />
      </div>
      
      <button @click="realizarLogin" class="btn-login">Iniciar Sesión</button>
      <div class="links-extra" style="margin-top: 1.5rem; font-size: 0.9rem;">
  <RouterLink to="/registro" style="color: #42b983; text-decoration: none;">¿No tenés cuenta? Registrate</RouterLink>
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
p { color: #888; margin-bottom: 2rem; }

.input-group { margin-bottom: 1rem; }

input {
  width: 100%;
  padding: 0.8rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-sizing: border-box; /* Importante para que el padding no rompa el ancho */
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

.btn-login:hover { background-color: #3aa876; }
</style>