<script setup>
import { ref, onMounted } from 'vue'

const trucos = ref([])
const error = ref(null)
const cargando = ref(true)

// Variables para CREAR
const mostrarFormulario = ref(false)
const guardando = ref(false)
const nuevoTruco = ref({ nombre: '', descripcion: '', categoria: '', duracion_minutos: '' })

// Variables para EDITAR
const trucoEditando = ref(null) // Si tiene datos, se muestra el modal de edición
const actualizando = ref(false)

// --- FUNCIONES DE LECTURA ---
const cargarTrucos = async () => {
  cargando.value = true
  try {
    const respuesta = await fetch('/backend/api/trucos/buscar.php')
    if (!respuesta.ok) throw new Error('Error al cargar trucos')
    trucos.value = await respuesta.json()
  } catch (err) {
    error.value = err.message
  } finally {
    cargando.value = false
  }
}

// --- FUNCIONES DE CREACIÓN ---
const guardarTruco = async () => {
  if (!nuevoTruco.value.nombre) {
    alert("El nombre de la ilusión es obligatorio.")
    return
  }
  guardando.value = true
  try {
    const respuesta = await fetch('/backend/api/trucos/crear.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(nuevoTruco.value)
    })
    const data = await respuesta.json()
    if (respuesta.ok) {
      nuevoTruco.value = { nombre: '', descripcion: '', categoria: '', duracion_minutos: '' }
      mostrarFormulario.value = false
      await cargarTrucos()
    } else {
      alert("Error: " + data.mensaje)
    }
  } catch (err) {
    alert("Falló la conexión al guardar el truco.")
  } finally {
    guardando.value = false
  }
}

// --- FUNCIONES DE EDICIÓN ---
const abrirEdicion = (truco) => {
  // Clonamos el objeto para no editar la tarjeta en tiempo real antes de guardar
  trucoEditando.value = { ...truco }
}

const guardarEdicion = async () => {
  if (!trucoEditando.value.nombre) {
    alert("El nombre no puede estar vacío.")
    return
  }
  actualizando.value = true
  try {
    const respuesta = await fetch('/backend/api/trucos/editar.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(trucoEditando.value)
    })
    const data = await respuesta.json()
    if (respuesta.ok) {
      trucoEditando.value = null // Cierra el modal
      await cargarTrucos() // Recarga la grilla
    } else {
      alert("Error: " + data.mensaje)
    }
  } catch (err) {
    alert("Falló la conexión al actualizar.")
  } finally {
    actualizando.value = false
  }
}

// --- FUNCIONES DE ELIMINACIÓN ---
const eliminarTruco = async (id) => {
  if (!confirm("¿Estás seguro de que querés eliminar esta ilusión de tu repertorio?")) {
    return
  }
  
  try {
    const respuesta = await fetch('/backend/api/trucos/eliminar.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
    const data = await respuesta.json()
    if (respuesta.ok) {
      await cargarTrucos()
    } else {
      alert("Error: " + data.mensaje)
    }
  } catch (err) {
    alert("Falló la conexión al intentar eliminar.")
  }
}

onMounted(() => {
  cargarTrucos()
})
</script>

<template>
  <div class="vista-trucos">
    <div class="header-acciones">
      <h1>Catálogo de Ilusiones</h1>
      <button class="btn-toggle" @click="mostrarFormulario = !mostrarFormulario">
        {{ mostrarFormulario ? 'Cancelar' : '+ Nueva Ilusión' }}
      </button>
    </div>

    <div v-if="mostrarFormulario" class="formulario-crear">
      <h3>Agregar al Repertorio</h3>
      <div class="inputs-grid">
        <input v-model="nuevoTruco.nombre" type="text" placeholder="Nombre de la ilusión *" />
        <input v-model="nuevoTruco.categoria" type="text" placeholder="Categoría (Ej: Cartomagia)" />
        <input v-model="nuevoTruco.duracion_minutos" type="number" placeholder="Duración (minutos)" />
      </div>
      <textarea v-model="nuevoTruco.descripcion" placeholder="Descripción del efecto..."></textarea>
      <button class="btn-guardar" @click="guardarTruco" :disabled="guardando">
        {{ guardando ? 'Guardando...' : 'Guardar' }}
      </button>
    </div>
    
    <div v-if="cargando">Cargando ilusiones...</div>
    <div v-else-if="error" class="error-msg">{{ error }}</div>
    <div v-else-if="trucos.length === 0">No hay ilusiones registradas aún.</div>
    
    <div v-else class="grilla">
      <div v-for="truco in trucos" :key="truco.id" class="tarjeta">
        <div class="tarjeta-header">
          <h3>{{ truco.nombre }}</h3>
          <div class="acciones">
            <button class="btn-icon" @click="abrirEdicion(truco)" title="Editar">✏️</button>
            <button class="btn-icon delete" @click="eliminarTruco(truco.id)" title="Eliminar">🗑️</button>
          </div>
        </div>
        <span class="categoria">{{ truco.categoria || 'Sin categoría' }}</span>
        <span class="duracion" v-if="truco.duracion_minutos"> ⏱ {{ truco.duracion_minutos }} min</span>
        <p>{{ truco.descripcion }}</p>
      </div>
    </div>

    <div v-if="trucoEditando" class="modal-overlay">
      <div class="modal-content">
        <h3>Editar Ilusión</h3>
        <div class="inputs-grid">
          <input v-model="trucoEditando.nombre" type="text" placeholder="Nombre de la ilusión *" />
          <input v-model="trucoEditando.categoria" type="text" placeholder="Categoría" />
          <input v-model="trucoEditando.duracion_minutos" type="number" placeholder="Duración (minutos)" />
        </div>
        <textarea v-model="trucoEditando.descripcion" placeholder="Descripción..."></textarea>
        <div class="modal-acciones">
          <button class="btn-cancelar" @click="trucoEditando = null">Cancelar</button>
          <button class="btn-guardar" @click="guardarEdicion" :disabled="actualizando">
            {{ actualizando ? 'Actualizando...' : 'Actualizar' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.vista-trucos { max-width: 1000px; margin: 0 auto; padding: 1rem; position: relative; }
.header-acciones { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem; }
h1 { margin: 0; color: #2c3e50; }

.btn-toggle { background-color: #3498db; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: bold; }
.btn-toggle:hover { background-color: #2980b9; }

/* Formularios */
.formulario-crear { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #ddd; }
.inputs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
input, textarea { width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit;}
textarea { min-height: 80px; resize: vertical; margin-bottom: 1rem; }

.btn-guardar { background-color: #42b983; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: bold; }
.btn-guardar:disabled { background-color: #9bdcbd; }
.btn-cancelar { background-color: #95a5a6; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: bold; margin-right: 1rem;}

/* Grilla y Tarjetas */
.grilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
.tarjeta { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #eee; display: flex; flex-direction: column; }
.tarjeta-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; }
.tarjeta h3 { margin: 0; color: #2c3e50; font-size: 1.2rem; flex: 1;}

.acciones { display: flex; gap: 0.5rem; }
.btn-icon { background: none; border: none; cursor: pointer; font-size: 1.2rem; padding: 0.2rem; border-radius: 4px; transition: background 0.2s;}
.btn-icon:hover { background: #f1f2f6; }
.btn-icon.delete:hover { background: #ffeaa7; }

.categoria { display: inline-block; background: #e8f5e9; color: #2e7d32; font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 12px; font-weight: bold; margin-right: 0.5rem; margin-bottom: 0.5rem; width: fit-content;}
.duracion { font-size: 0.8rem; color: #666; }
.error-msg { color: #e74c3c; font-weight: bold; }

/* Modal Edición */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 2rem; border-radius: 10px; width: 90%; max-width: 600px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.modal-content h3 { margin-top: 0; color: #2c3e50; margin-bottom: 1.5rem; }
.modal-acciones { display: flex; justify-content: flex-end; margin-top: 1rem; }
</style>