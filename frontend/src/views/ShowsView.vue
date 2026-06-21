<script setup>
import { ref, onMounted, computed } from 'vue'

const shows = ref([])
const error = ref(null)
const cargando = ref(true)

const mostrarFormulario = ref(false)
const guardando = ref(false)
const usuarioId = localStorage.getItem('user_id') 

const nuevoShow = ref({ usuario_id: usuarioId, nombre: '', descripcion: '' })

// --- VARIABLES PARA EL MODAL DEL SETLIST ---
const modalSetlist = ref(false)
const showActivo = ref(null)
const trucosDelShow = ref([])
const trucosCatalogo = ref([])

// Trucos que NO están en el show actual (para la columna de "Agregar")
const trucosDisponibles = computed(() => {
  return trucosCatalogo.value.filter(trucoCat => 
    !trucosDelShow.value.some(trucoShow => trucoShow.id === trucoCat.id)
  )
})

// --- FUNCIONES DE SHOWS ---
const cargarShows = async () => {
  if (!usuarioId) return
  cargando.value = true
  try {
    const respuesta = await fetch(`/backend/api/shows/buscar.php?usuario_id=${usuarioId}`)
    if (!respuesta.ok) throw new Error('Error al cargar')
    shows.value = await respuesta.json()
  } catch (err) {
    error.value = err.message
  } finally {
    cargando.value = false
  }
}

const guardarShow = async () => {
  if (!nuevoShow.value.nombre) return alert("Nombre obligatorio.")
  guardando.value = true
  try {
    const respuesta = await fetch('/backend/api/shows/crear.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(nuevoShow.value)
    })
    if (respuesta.ok) {
      nuevoShow.value = { usuario_id: usuarioId, nombre: '', descripcion: '' }
      mostrarFormulario.value = false
      await cargarShows()
    }
  } catch (err) {
    alert("Falló la conexión.")
  } finally {
    guardando.value = false
  }
}

// --- FUNCIONES DEL SETLIST ---
const abrirSetlist = async (show) => {
  showActivo.value = show
  modalSetlist.value = true
  await cargarDetalleShow(show.id)
  await cargarCatalogoTrucos()
}

const cargarDetalleShow = async (showId) => {
  try {
    const respuesta = await fetch(`/backend/api/shows/detalle.php?id=${showId}`)
    if (respuesta.ok) {
      const data = await respuesta.json()
      trucosDelShow.value = data.trucos || []
    }
  } catch (err) { console.error(err) }
}

const cargarCatalogoTrucos = async () => {
  try {
    const respuesta = await fetch(`/backend/api/trucos/buscar.php`)
    if (respuesta.ok) {
      trucosCatalogo.value = await respuesta.json()
    }
  } catch (err) { console.error(err) }
}

const modificarSetlist = async (trucoId, accion) => {
  try {
    const respuesta = await fetch('/backend/api/shows/modificar_trucos.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        show_id: showActivo.value.id,
        truco_id: trucoId,
        accion: accion
      })
    })
    
    const data = await respuesta.json()
    if (respuesta.ok) {
      // Recargamos la rutina para ver los cambios instantáneamente
      await cargarDetalleShow(showActivo.value.id)
    } else {
      alert("Atención: " + data.mensaje)
    }
  } catch (err) {
    alert("Error de conexión.")
  }
}

onMounted(() => {
  cargarShows()
})

const eliminarShow = async (id) => {
  if (!confirm("¿Estás seguro de que querés eliminar este formato de show? Esta acción no se puede deshacer.")) {
    return
  }
  
  try {
    const respuesta = await fetch('/backend/api/shows/eliminar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
    
    if (respuesta.ok) {
      // Recargamos la lista para que desaparezca el eliminado
      await cargarShows()
    } else {
      const errorData = await respuesta.json()
      alert("Error al eliminar: " + errorData.mensaje)
    }
  } catch (err) {
    alert("Error de conexión al intentar eliminar el show.")
  }
}
</script>

<template>
  <div class="vista-shows">
    <div class="header-acciones">
      <h1>🎭 Mis Espectáculos</h1>
      <button class="btn-toggle" @click="mostrarFormulario = !mostrarFormulario">
        {{ mostrarFormulario ? 'Cancelar' : '+ Nuevo Espectáculo' }}
      </button>
    </div>

    <div v-if="mostrarFormulario" class="formulario-crear">
      <h3>Configurar nuevo formato de Show</h3>
      <div class="inputs-grid">
        <input v-model="nuevoShow.nombre" type="text" placeholder="Nombre (Ej: Show de Magia de Salón)" />
      </div>
      <textarea v-model="nuevoShow.descripcion" placeholder="Descripción general o requerimientos..."></textarea>
      <button class="btn-guardar" @click="guardarShow" :disabled="guardando">
        {{ guardando ? 'Guardando...' : 'Guardar Formato' }}
      </button>
    </div>
    
    <div v-if="cargando">Cargando espectáculos...</div>
    <div v-else-if="error" class="error-msg">{{ error }}</div>
    <div v-else-if="shows.length === 0">No tenés formatos de shows configurados.</div>
    <div v-else class="grilla">
      <div v-for="show in shows" :key="show.id" class="tarjeta">
        <h3>{{ show.nombre }}</h3>
        <p v-if="show.descripcion">{{ show.descripcion }}</p>
        <p v-else class="sin-desc">Sin descripción detallada.</p>
        <button class="btn-trucos" @click="abrirSetlist(show)">🪄 Configurar Setlist</button>
        <button class="btn-eliminar" @click="eliminarShow(show.id)">🗑️ Eliminar</button>
      </div>
    </div>

    <div v-if="modalSetlist" class="modal-overlay">
      <div class="modal-content setlist-modal">
        <div class="modal-header">
          <h2>🪄 Setlist: {{ showActivo.nombre }}</h2>
          <button class="btn-cerrar" @click="modalSetlist = false">✖</button>
        </div>

        <div class="columnas-setlist">
          <div class="columna rutina">
            <h3>En la Rutina</h3>
            <div v-if="trucosDelShow.length === 0" class="empty-state">Rutina vacía. Agregá ilusiones.</div>
            <div v-for="truco in trucosDelShow" :key="truco.id" class="item-truco">
              <span>{{ truco.nombre }}</span>
              <button class="btn-quitar" @click="modificarSetlist(truco.id, 'quitar')">Quitar</button>
            </div>
          </div>

          <div class="columna catalogo">
            <h3>Catálogo Disponible</h3>
            <div v-if="trucosDisponibles.length === 0" class="empty-state">Todo tu catálogo está en este show.</div>
            <div v-for="truco in trucosDisponibles" :key="truco.id" class="item-truco disponible">
              <span>{{ truco.nombre }}</span>
              <button class="btn-agregar" @click="modificarSetlist(truco.id, 'agregar')">Agregar</button>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
.vista-shows { max-width: 1000px; margin: 0 auto; padding: 1rem; }
.header-acciones { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem; }
h1 { margin: 0; color: #2c3e50; }

.btn-toggle { background-color: #9b59b6; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: bold; }
.btn-toggle:hover { background-color: #8e44ad; }

.formulario-crear { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #ddd; }
.inputs-grid { display: flex; gap: 1rem; margin-bottom: 1rem; }
input, textarea { width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit;}
textarea { min-height: 80px; resize: vertical; margin-bottom: 1rem; }

.btn-guardar { background-color: #42b983; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: bold; }

.grilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
.tarjeta { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #eee; display: flex; flex-direction: column;}
.tarjeta h3 { margin-top: 0; color: #2c3e50; margin-bottom: 0.5rem;}
.tarjeta p { color: #555; flex-grow: 1;}
.sin-desc { font-style: italic; color: #aaa !important; }

.btn-trucos { margin-top: 1rem; background-color: #f1f2f6; border: 1px solid #dfe4ea; padding: 0.5rem; border-radius: 4px; cursor: pointer; color: #2f3542; font-weight: bold; transition: background 0.2s; }
.btn-trucos:hover { background-color: #dfe4ea; }

/* MODAL ESTILOS */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.setlist-modal { background: white; padding: 0; border-radius: 10px; width: 95%; max-width: 800px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: #2c3e50; color: white; }
.modal-header h2 { margin: 0; font-size: 1.3rem; }
.btn-cerrar { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; }

.columnas-setlist { display: grid; grid-template-columns: 1fr 1fr; overflow-y: auto; }
.columna { padding: 1.5rem; height: 400px; overflow-y: auto; }
.rutina { background: #f8f9fa; border-right: 1px solid #eee; }
.columna h3 { margin-top: 0; color: #333; margin-bottom: 1rem; font-size: 1.1rem; border-bottom: 2px solid #ddd; padding-bottom: 0.5rem;}

.item-truco { display: flex; justify-content: space-between; align-items: center; padding: 0.8rem; background: white; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.1s;}
.item-truco:hover { transform: translateX(2px); border-color: #ccc; }
.item-truco span { font-weight: bold; color: #2c3e50; }

.btn-agregar { background: #3498db; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; }
.btn-agregar:hover { background: #2980b9; }
.btn-quitar { background: #e74c3c; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; }
.btn-quitar:hover { background: #c0392b; }

.empty-state { color: #888; font-style: italic; text-align: center; margin-top: 2rem; }
.acciones-tarjeta {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.btn-eliminar {
  background-color: #ffeaa7;
  border: 1px solid #fdcb6e;
  padding: 0.5rem;
  border-radius: 4px;
  cursor: pointer;
  color: #d63031;
  font-weight: bold;
  transition: background 0.2s;
  flex-grow: 1; /* Para que ocupe el espacio sobrante */
}

.btn-eliminar:hover {
  background-color: #fdcb6e;
  color: white;
}

.btn-trucos {
  margin-top: 0; /* Le sacamos el margin-top porque ahora lo maneja .acciones-tarjeta */
  flex-grow: 2; /* Para que el botón principal sea más ancho */
}
</style>