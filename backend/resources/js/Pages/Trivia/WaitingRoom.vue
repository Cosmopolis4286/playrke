<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import RoomHeader from '@js/Components/Trivia/Room/RoomHeader.vue'
import PlayerList from '@js/Components/Trivia/Room/PlayerList.vue'
import JoinForm from '@js/Components/Trivia/Room/JoinForm.vue'
import ConnectionStatus from '@js/Components/Trivia/Shared/ConnectionStatus.vue'
import LoadingOverlay from '@js/Components/Trivia/Shared/LoadingOverlay.vue'
import type { Room, Player } from '@js/types/trivia'

interface Props {
  room: Room
  initialPlayers: Player[]
}

const props = defineProps<Props>()

const players    = ref<Player[]>(props.initialPlayers)
const hasJoined  = ref(false)
const currentPlayer = ref<Player | null>(null)
const isLoading  = ref(false)
const joinError  = ref<string | null>(null)
const connectionStatus = ref<'connected' | 'disconnected' | 'reconnecting'>('disconnected')

// ── WebSocket (best-effort — no disponible en local sin Redis/Reverb) ─────────

let ws: WebSocket | null = null
let pollInterval: ReturnType<typeof setInterval> | null = null

function connectWebSocket() {
  const wsBase = import.meta.env.VITE_WEBSOCKET_URL
  if (!wsBase) {
    // Sin WebSocket configurado → usar polling como fallback
    startPolling()
    return
  }

  try {
    const wsUrl = `${wsBase}/trivia/room/${props.room.code}`
    ws = new WebSocket(wsUrl)

    ws.onopen = () => {
      connectionStatus.value = 'connected'
      stopPolling()
    }

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data)
      handleWebSocketMessage(data)
    }

    ws.onclose = () => {
      connectionStatus.value = 'reconnecting'
      // Reintentar después de 5s, con fallback a polling
      setTimeout(() => {
        if (!ws || ws.readyState === WebSocket.CLOSED) {
          startPolling()
        }
      }, 5000)
    }

    ws.onerror = () => {
      connectionStatus.value = 'reconnecting'
    }
  } catch {
    startPolling()
  }
}

function handleWebSocketMessage(data: any) {
  switch (data.type) {
    case 'player.joined':
      if (!players.value.find(p => p.id === data.player?.id)) {
        players.value.push(data.player)
      }
      break
    case 'player.left':
      players.value = players.value.filter(p => p.id !== data.playerId)
      break
    case 'game.started':
      window.location.href = `/trivia/game/${props.room.code}`
      break
  }
}

// ── Polling fallback ──────────────────────────────────────────────────────────

function startPolling() {
  if (pollInterval) return
  connectionStatus.value = 'connected' // DB está disponible
  pollInterval = setInterval(fetchRoomState, 5000)
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

async function fetchRoomState() {
  try {
    const res = await fetch(`/trivia/room/${props.room.code}/state`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    if (!res.ok) return
    const data = await res.json()
    if (data.players) players.value = data.players
    if (data.status === 'playing') {
      window.location.href = `/trivia/game/${props.room.code}`
    }
  } catch {
    // silencioso
  }
}

// ── Join ──────────────────────────────────────────────────────────────────────

async function handleJoin(playerName: string) {
  isLoading.value = true
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(`/trivia-game/room/${props.room.id}/join`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ name: playerName }),
    })

    const data = await res.json().catch(() => ({}))

    if (res.ok) {
      currentPlayer.value = data.player ?? { id: 'me', name: playerName, roomCode: props.room.code }
      hasJoined.value = true
      if (currentPlayer.value && !players.value.find(p => p.id === currentPlayer.value!.id)) {
        players.value.push(currentPlayer.value)
      }
    } else if (res.status === 403) {
      joinError.value = 'No tienes permiso para unirte a salas de trivia. Se requiere el rol de jugador.'
    } else if (res.status === 422) {
      joinError.value = data.message ?? 'La sala no está disponible.'
    } else {
      joinError.value = data.message ?? 'Error al unirse a la sala.'
    }
  } catch {
    joinError.value = 'Error de conexión. Intenta de nuevo.'
  } finally {
    isLoading.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  connectWebSocket()
})

onUnmounted(() => {
  ws?.close()
  stopPolling()
})
</script>

<template>
  <Head :title="`Sala ${room.code} - Trivia`" />

  <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <ConnectionStatus :status="connectionStatus" />

    <LoadingOverlay v-if="isLoading" message="Uniéndose a la sala..." />

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <RoomHeader
        :room="room"
        :player-count="players.length"
        :max-players="room.maxPlayers"
      />

      <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
          <PlayerList
            :players="players"
            :host-id="room.hostId"
            :current-player-id="currentPlayer?.id"
          />
        </div>

        <div class="lg:col-span-1">
          <!-- Error de acceso / join -->
          <div
            v-if="joinError"
            class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 p-4"
          >
            <div class="flex items-start gap-3">
              <span class="text-red-500 text-lg flex-shrink-0">⛔</span>
              <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">Acceso denegado</p>
                <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">{{ joinError }}</p>
              </div>
            </div>
          </div>

          <JoinForm
            v-if="!hasJoined && !joinError"
            @join="handleJoin"
          />

          <div
            v-else
            class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center"
          >
            <div class="text-green-500 mb-2">
              <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">¡Te has unido!</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Esperando que el anfitrión inicie el juego...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
