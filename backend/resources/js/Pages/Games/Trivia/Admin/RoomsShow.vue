<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  room: Object,
  roomState: Object,
  players: Array,
  playerCount: Number,
})

// Auto-refresh cada 5 segundos para ver jugadores en tiempo real
let refreshInterval = null

onMounted(() => {
  refreshInterval = setInterval(() => {
    router.reload({ only: ['players', 'playerCount', 'roomState'] })
  }, 5000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})

// Estado colores
const getStatusColor = (status) => {
  const colors = {
    waiting: 'bg-yellow-100 text-yellow-800',
    playing: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

// Confirmar cerrar sala
const confirmClose = () => {
  if (confirm('¿Estás seguro de cerrar esta sala? Los jugadores serán desconectados.')) {
    router.delete(getRoute('roomsDestroy', { id: props.room.id }))
  }
}

// Formatear tiempo
const formatTime = (timestamp) => {
  if (!timestamp) return '-'
  return new Date(timestamp * 1000).toLocaleTimeString('es-ES')
}
</script>

<template>
  <Head :title="`Sala ${room.code} - Trivia`" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-start justify-between mb-6">
        <div>
          <Link
            :href="getRoute('roomsIndex')"
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-2 inline-flex items-center"
          >
            ← Volver al listado
          </Link>
          <div class="flex items-center gap-3 mt-2">
            <h1 class="text-2xl font-bold rs-text">
              {{ room.title }}
            </h1>
            <code class="rs-code px-2 py-1 rounded text-sm font-mono">
              {{ room.code }}
            </code>
            <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(room.status)]">
              {{ room.status }}
            </span>
          </div>
          <p class="text-sm rs-text-muted mt-1">
            {{ room.description || 'Sin descripción' }}
          </p>
        </div>

        <div class="flex gap-2">
          <Link
            v-if="room.status === 'waiting'"
            :href="getRoute('roomsEdit', { id: room.id })"
            class="px-4 py-2 bg-yellow-600 text-white rounded-md text-sm font-medium hover:bg-yellow-700"
          >
            Editar
          </Link>
          <button
            v-if="room.status !== 'closed'"
            @click="confirmClose"
            class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700"
          >
            Cerrar Sala
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rs-card p-4 rounded-lg shadow-sm">
          <p class="text-sm rs-text-muted">Jugadores</p>
          <p class="text-2xl font-bold rs-text">
            {{ playerCount }} / {{ room.max_players }}
          </p>
        </div>
        <div class="rs-card p-4 rounded-lg shadow-sm">
          <p class="text-sm rs-text-muted">Preguntas</p>
          <p class="text-2xl font-bold rs-text">
            {{ room.question_count }}
          </p>
        </div>
        <div class="rs-card p-4 rounded-lg shadow-sm">
          <p class="text-sm rs-text-muted">Tiempo/Pregunta</p>
          <p class="text-2xl font-bold rs-text">
            {{ room.time_per_question }}s
          </p>
        </div>
        <div class="rs-card p-4 rounded-lg shadow-sm">
          <p class="text-sm rs-text-muted">Creada</p>
          <p class="text-lg font-bold rs-text">
            {{ new Date(room.created_at).toLocaleDateString() }}
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Jugadores -->
        <div class="lg:col-span-2">
          <div class="rs-card shadow-sm rounded-lg overflow-hidden">
            <div class="rs-header-border px-6 py-4 border-b flex items-center justify-between">
              <h2 class="text-lg font-medium rs-text">
                👥 Jugadores Conectados ({{ playerCount }})
              </h2>
              <span class="text-xs rs-text-muted">Auto-refresh: 5s</span>
            </div>

            <div class="rs-divide max-h-96 overflow-y-auto">
              <div
                v-for="player in players"
                :key="player.player_id"
                class="rs-row-hover px-6 py-4 flex items-center justify-between"
              >
                <div class="flex items-center">
                  <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                    {{ player.nickname?.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium rs-text">
                      {{ player.nickname }}
                    </p>
                    <p class="text-xs rs-text-muted">
                      ID: {{ player.player_id.substring(0, 8) }}...
                    </p>
                  </div>
                </div>
                <div class="text-right">
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs',
                      player.status === 'waiting'
                        ? 'bg-yellow-100 text-yellow-800'
                        : 'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ player.status }}
                  </span>
                  <p class="text-xs rs-text-muted mt-1">
                    Unido: {{ formatTime(player.joined_at) }}
                  </p>
                </div>
              </div>

              <div v-if="players.length === 0" class="px-6 py-12 text-center rs-text-muted">
                <p class="text-4xl mb-2">👻</p>
                <p>No hay jugadores conectados</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Info y Settings -->
        <div>
          <div class="rs-card shadow-sm rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium rs-text mb-4">
              ⚙️ Configuración
            </h3>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="rs-text-muted">Pública:</span>
                <span class="font-medium">{{ room.settings?.public_room ? 'Sí' : 'No' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="rs-text-muted">Permitir tarde:</span>
                <span class="font-medium">{{ room.settings?.allow_late_join ? 'Sí' : 'No' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="rs-text-muted">Leaderboard:</span>
                <span class="font-medium">{{ room.settings?.show_leaderboard ? 'Sí' : 'No' }}</span>
              </div>
            </div>
          </div>

          <div class="rs-card shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium rs-text mb-4">
              📝 Datos Técnicos
            </h3>
            <div class="space-y-2 text-xs rs-text-muted font-mono">
              <p>Room ID: {{ room.id }}</p>
              <p>Host ID: {{ room.host_id }}</p>
              <p>Status: {{ room.status }}</p>
              <p>Connection: pgsql_trivia</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rs-text { color: var(--ds-text); }
.rs-text-muted { color: var(--ds-text-muted); }
.rs-card { background: var(--ds-surface); }
.rs-code { background: var(--ds-surface-2); }
.rs-header-border { border-color: var(--ds-border); }
.rs-divide > :not([hidden]) ~ :not([hidden]) { border-top: 1px solid var(--ds-border); }
.rs-row-hover:hover { background: var(--ds-surface-2); }
</style>
