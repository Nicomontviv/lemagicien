<script setup>
import { ref, onMounted } from 'vue'

const eventos = ref([])
const error = ref(null)
const cargando = ref(true)

const mostrarFormulario = ref(false)
const guardando = ref(false)
const usuarioId = localStorage.getItem('user_id')

const showsDisponibles = ref([])
const ingresosMes = ref(null)

const ESTADOS = ['Pendiente', 'Confirmado', 'Realizado', 'Cancelado']

const eventoVacio = () => ({
  usuario_id: usuarioId,
  show_id: '',
  cliente: '',
  fecha: '',
  hora: '',
  direccion: '',
  monto: '',
  observaciones: ''
})

const nuevoEvento = ref(eventoVacio())

// --- CARGA DE DATOS ---
const cargarEventos = async () => {
  cargando.value = true
  try {
    const respuesta = await fetch('/backend/api/eventos/agenda.php')
    if (!respuesta.ok) throw new Error('Error al cargar la agenda')
    const data = await respuesta.json()
    // Si no hay eventos, el backend devuelve un objeto (no un array): nos cubrimos
    eventos.value = Array.isArray(data) ? data : []
  } catch (err) {
    error.value = err.message
  } finally {
    cargando.value = false
  }
}

const cargarShowsDisponibles = async () => {
  if (!usuarioId) return
  try {
    const respuesta = await fetch(`/backend/api/shows/buscar.php?usuario_id=${usuarioId}`)
    if (respuesta.ok) showsDisponibles.value = await respuesta.json()
  } catch (err) {
    console.error(err)
  }
}

const cargarIngresosMes = async () => {
  try {
    const respuesta = await fetch('/backend/api/eventos/ingresos.php')
    if (respuesta.ok) ingresosMes.value = await respuesta.json()
  } catch (err) {
    console.error(err)
  }
}

// --- CREAR EVENTO ---
const guardarEvento = async () => {
  const e = nuevoEvento.value
  if (!e.show_id || !e.cliente || !e.fecha || !e.hora || e.monto === '') {
    alert('Completá show, cliente, fecha, hora y monto.')
    return
  }
  guardando.value = true
  try {
    const respuesta = await fetch('/backend/api/eventos/crear.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(e)
    })
    const data = await respuesta.json()
    if (respuesta.ok) {
      nuevoEvento.value = eventoVacio()
      mostrarFormulario.value = false
      await cargarEventos()
    } else {
      alert('Atención: ' + data.mensaje)
    }
  } catch (err) {
    alert('Falló la conexión.')
  } finally {
    guardando.value = false
  }
}

// --- CAMBIAR ESTADO ---
const cambiarEstado = async (evento, nuevoEstado) => {
  if (evento.estado === nuevoEstado) return
  const estadoAnterior = evento.estado
  evento.estado = nuevoEstado // actualización optimista
  try {
    const respuesta = await fetch('/backend/api/eventos/cambiar_estado.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: evento.id, estado: nuevoEstado })
    })
    const data = await respuesta.json()
    if (!respuesta.ok) {
      evento.estado = estadoAnterior
      alert('Atención: ' + data.mensaje)
    } else if (nuevoEstado === 'Realizado' || estadoAnterior === 'Realizado') {
      await cargarIngresosMes() // puede haber cambiado el total del mes
    }
  } catch (err) {
    evento.estado = estadoAnterior
    alert('Error de conexión.')
  }
}

const formatearFecha = (fecha) => {
  const [anio, mes, dia] = fecha.split('-')
  return `${dia}/${mes}/${anio}`
}

const formatearHora = (hora) => hora?.slice(0, 5)

const claseEstado = (estado) => `estado estado-${estado.toLowerCase()}`

onMounted(() => {
  cargarEventos()
  cargarShowsDisponibles()
  cargarIngresosMes()
})
</script>

<template>
  <div class="vista-eventos">
    <div class="header-acciones">
      <h1>📅 Mi Agenda</h1>
      <button class="btn-toggle" @click="mostrarFormulario = !mostrarFormulario">
        {{ mostrarFormulario ? 'Cancelar' : '+ Nuevo Evento' }}
      </button>
    </div>

    <div v-if="ingresosMes" class="tarjeta-ingresos">
      <span>💰 Ingresos de este mes:</span>
      <strong>${{ Number(ingresosMes.total).toLocaleString('es-AR', { minimumFractionDigits: 2 }) }}</strong>
      <span class="detalle-ingresos" v-if="ingresosMes.eventos_realizados">
        ({{ ingresosMes.eventos_realizados }} evento{{ ingresosMes.eventos_realizados === 1 ? '' : 's' }} realizado{{ ingresosMes.eventos_realizados === 1 ? '' : 's' }})
      </span>
    </div>

    <div v-if="mostrarFormulario" class="formulario-crear">
      <h3>Registrar nueva contratación</h3>
      <div class="inputs-grid">
        <select v-model="nuevoEvento.show_id">
          <option value="" disabled>Seleccioná un show</option>
          <option v-for="show in showsDisponibles" :key="show.id" :value="show.id">{{ show.nombre }}</option>
        </select>
        <input v-model="nuevoEvento.cliente" type="text" placeholder="Nombre del cliente" />
      </div>
      <div class="inputs-grid">
        <input v-model="nuevoEvento.fecha" type="date" />
        <input v-model="nuevoEvento.hora" type="time" />
        <input v-model="nuevoEvento.monto" type="number" min="0" step="0.01" placeholder="Monto acordado" />
      </div>
      <input v-model="nuevoEvento.direccion" type="text" placeholder="Dirección (opcional)" class="input-full" />
      <textarea v-model="nuevoEvento.observaciones" placeholder="Observaciones (opcional)..."></textarea>
      <p v-if="showsDisponibles.length === 0" class="aviso">
        Todavía no tenés shows creados. Armá uno primero en la sección Shows.
      </p>
      <button class="btn-guardar" @click="guardarEvento" :disabled="guardando || showsDisponibles.length === 0">
        {{ guardando ? 'Guardando...' : 'Registrar Evento' }}
      </button>
    </div>

    <div v-if="cargando">Cargando agenda...</div>
    <div v-else-if="error" class="error-msg">{{ error }}</div>
    <div v-else-if="eventos.length === 0">No hay eventos cargados en la agenda.</div>
    <div v-else class="lista-eventos">
      <div v-for="evento in eventos" :key="evento.id" class="fila-evento">
        <div class="info-principal">
          <span class="fecha">{{ formatearFecha(evento.fecha) }} · {{ formatearHora(evento.hora) }}</span>
          <span class="cliente">{{ evento.cliente }}</span>
          <span class="show">{{ evento.show_nombre }}</span>
        </div>
        <select :value="evento.estado" @change="cambiarEstado(evento, $event.target.value)" :class="claseEstado(evento.estado)">
          <option v-for="estado in ESTADOS" :key="estado" :value="estado">{{ estado }}</option>
        </select>
      </div>
    </div>
  </div>
</template>

<style scoped>
.vista-eventos { max-width: 1000px; margin: 0 auto; padding: 1rem; }
.header-acciones { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #eee; padding-bottom: 1rem; }
h1 { margin: 0; color: #2c3e50; }

.btn-toggle { background-color: #3498db; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: bold; }
.btn-toggle:hover { background-color: #2980b9; }

.tarjeta-ingresos { display: flex; align-items: center; gap: 0.5rem; background: #eafaf1; border: 1px solid #b7e4c7; color: #1e7a46; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 1.05rem; flex-wrap: wrap; }
.tarjeta-ingresos strong { font-size: 1.2rem; }
.detalle-ingresos { color: #4c8b6a; font-size: 0.9rem; }

.formulario-crear { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #ddd; }
.inputs-grid { display: flex; gap: 1rem; margin-bottom: 1rem; }
input, select, textarea { width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
.input-full { margin-bottom: 1rem; }
textarea { min-height: 80px; resize: vertical; margin-bottom: 1rem; }
.aviso { color: #c0392b; font-size: 0.9rem; margin: 0 0 1rem; }

.btn-guardar { background-color: #42b983; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: bold; }
.btn-guardar:disabled { background-color: #a0d9c0; cursor: not-allowed; }

.lista-eventos { display: flex; flex-direction: column; gap: 0.8rem; }
.fila-evento { display: flex; justify-content: space-between; align-items: center; background: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border: 1px solid #eee; flex-wrap: wrap; gap: 1rem; }
.info-principal { display: flex; flex-direction: column; gap: 0.2rem; }
.fecha { font-weight: bold; color: #2c3e50; }
.cliente { color: #333; }
.show { color: #777; font-size: 0.9rem; font-style: italic; }

select.estado { font-weight: bold; border-width: 2px; cursor: pointer; width: auto; }
.estado-pendiente { border-color: #f39c12; color: #b9770e; }
.estado-confirmado { border-color: #3498db; color: #2471a3; }
.estado-realizado { border-color: #42b983; color: #1e7a46; }
.estado-cancelado { border-color: #e74c3c; color: #b03a2e; }

.error-msg { color: #e74c3c; }
</style>