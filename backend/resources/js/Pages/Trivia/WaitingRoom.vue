<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import RoomHeader from '@js/Components/Trivia/Room/RoomHeader.vue'
import PlayerList from '@js/Components/Trivia/Room/PlayerList.vue'
import JoinForm from '@js/Components/Trivia/Room/JoinForm.vue'
import ConnectionStatus from '@js/Components/Trivia/Shared/ConnectionStatus.vue'
import LoadingOverlay from '@js/Components/Trivia/Shared/LoadingOverlay.vue'
import { getEchoInstance } from '@js/plugins/echo'
import type { Room, Player } from '@js/types/trivia'

interface Props {
  room: Room
  initialPlayers: Player[]
  alreadyJoined: boolean
  currentUserId: string
}

const props = defineProps<Props>()

const players    = ref<Player[]>(props.initialPlayers)
const hasJoined  = ref(props.alreadyJoined)
const isLoading  = ref(false)
const joinError  = ref<string | null>(null)
const connectionStatus = ref<'connected' | 'disconnected' | 'reconnecting'>('disconnected')

// ── Jugador actual (si ya estaba inscrito, buscarlo en la lista inicial) ──────
const currentPlayer = ref<Player | null>(
  props.alreadyJoined
    ? (props.initialPlayers.find(p => p.id === props.currentUserId) ?? null)
    : null
)

// ── Echo / Reverb ─────────────────────────────────────────────────────────────
let channel: ReturnType<typeof echo.channel> | null = null
const echo = getEchoInstance()
const channelName = `trivia.room.${props.room.id}`

function subscribeToChannel() {
  try {
    channel = echo.channel(channelName)

    channel
      .listen('.trivia.player.joined', (data: any) => {
        const incoming: Player = {
          id:       data.player?.player_id ?? data.player?.id,
          name:     data.player?.nickname  ?? data.player?.name ?? 'Jugador',
          roomCode: props.room.code,
        }
        if (!players.value.find(p => p.id === incoming.id)) {
          players.value.push(incoming)
        }
      })
      .listen('.trivia.player.left', (data: any) => {
        players.value = players.value.filter(p => p.id !== data.player_id)
      })
      .listen('.trivia.game.started', () => {
        window.location.href = `/trivia/game/${props.room.code}`
      })

    // Estado de conexión via Pusher connector
    const conn = echo.connector.pusher.connection
    conn.bind('connected',     () => { connectionStatus.value = 'connected' })
    conn.bind('disconnected',  () => { connectionStatus.value = 'disconnected' })
    conn.bind('unavailable',   () => { connectionStatus.value = 'disconnected' })
    conn.bind('connecting',    () => { connectionStatus.value = 'reconnecting' })

    // Estado inicial
    const state = conn.state
    if (state === 'connected') connectionStatus.value = 'connected'
    else if (state === 'connecting' || state === 'reconnecting') connectionStatus.value = 'reconnecting'

  } catch (err) {
    console.warn('[WaitingRoom] Echo no disponible:', err)
    connectionStatus.value = 'disconnected'
  }
}

function unsubscribeFromChannel() {
  try {
    if (channel) {
      echo.leaveChannel(channelName)
      channel = null
    }
  } catch {}
}

// ── Join ──────────────────────────────────────────────────────────────────────

async function handleJoin(playerName: string) {
  if (hasJoined.value) return
  isLoading.value = true
  joinError.value = null

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(`/trivia-game/room/${props.room.id}/join`, {
      method: 'POST',
      headers: {
        'Content-Type':      'application/json',
        'Accept':            'application/json',
        'X-Requested-With':  'XMLHttpRequest',
        'X-CSRF-TOKEN':      csrfToken,
      },
      body: JSON.stringify({ name: playerName }),
    })

    const data = await res.json().catch(() => ({}))

    if (res.ok) {
      const player: Player = data.player ?? { id: props.currentUserId, name: playerName, roomCode: props.room.code }
      currentPlayer.value = player
      hasJoined.value     = true
      // Añadir optimistamente (el WS también lo mandará, se desduplicará)
      if (!players.value.find(p => p.id === player.id)) {
        players.value.push(player)
      }
    } else if (res.status === 403) {
      joinError.value = 'No tienes permiso para unirte. Se requiere el rol de jugador.'
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
  subscribeToChannel()
})

onUnmounted(() => {
  unsubscribeFromChannel()
})

// ── Computed ──────────────────────────────────────────────────────────────────

const showJoinForm = computed(() => !hasJoined.value && !joinError.value)
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
        <!-- Lista de jugadores -->
        <div class="lg:col-span-2">
          <PlayerList
            :players="players"
            :host-id="room.hostId"
            :current-player-id="currentPlayer?.id ?? currentUserId"
          />
        </div>

        <!-- Panel derecho -->
        <div class="lg:col-span-1 space-y-4">

          <!-- Error de acceso -->
          <div
            v-if="joinError"
            class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 p-4"
          >
            <div class="flex items-start gap-3">
              <span class="text-red-500 text-lg flex-shrink-0">⛔</span>
              <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">Acceso denegado</p>
                <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">{{ joinError }}</p>
              </div>
            </div>
          </div>

          <!-- Formulario de unirse -->
          <JoinForm
            v-if="showJoinForm"
            @join="handleJoin"
          />

          <!-- Ya unido -->
          <div
            v-else-if="hasJoined"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center"
          >
            <div class="text-green-500 mb-3">
              <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">¡Ya estás dentro!</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Esperando que el anfitrión inicie el juego...
            </p>
            <div class="mt-4 flex items-center justify-center gap-2 text-xs text-gray-400 dark:text-gray-500">
              <span
                :class="[
                  'w-2 h-2 rounded-full',
                  connectionStatus === 'connected'     ? 'bg-green-400'           :
                  connectionStatus === 'reconnecting'  ? 'bg-yellow-400 animate-pulse' :
                                                         'bg-gray-400'
                ]"
              />
              <span>
                {{ connectionStatus === 'connected' ? 'Conectado en tiempo real' :
                   connectionStatus === 'reconnecting' ? 'Reconectando...' : 'Sin conexión' }}
              </span>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>
