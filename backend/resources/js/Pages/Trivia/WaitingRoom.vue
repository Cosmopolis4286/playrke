<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import RoomHeader from '@js/Components/Trivia/Room/RoomHeader.vue'
import PlayerList from '@js/Components/Trivia/Room/PlayerList.vue'
import JoinForm from '@js/Components/Trivia/Room/JoinForm.vue'
import { getEchoInstance } from '@js/plugins/echo'
import type { Room, Player } from '@js/types/trivia'

const page  = usePage()
const authName = computed<string | null>(() => (page.props as any).auth?.user?.name ?? null)

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

const currentPlayer = ref<Player | null>(
  props.alreadyJoined
    ? (props.initialPlayers.find(p => p.id === props.currentUserId) ?? null)
    : null
)

// ── Echo / Reverb ─────────────────────────────────────────────────────────────

let channel: any = null
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
          joinedAt: new Date().toISOString(),
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

    const conn = echo.connector.pusher.connection
    conn.bind('connected',    () => { connectionStatus.value = 'connected' })
    conn.bind('disconnected', () => { connectionStatus.value = 'disconnected' })
    conn.bind('unavailable',  () => { connectionStatus.value = 'disconnected' })
    conn.bind('connecting',   () => { connectionStatus.value = 'reconnecting' })

    const state = conn.state
    if (state === 'connected') connectionStatus.value = 'connected'
    else if (['connecting', 'reconnecting'].includes(state)) connectionStatus.value = 'reconnecting'

  } catch (err) {
    console.warn('[WaitingRoom] Echo no disponible:', err)
    connectionStatus.value = 'disconnected'
  }
}

function unsubscribeFromChannel() {
  try { echo.leaveChannel(channelName) } catch {}
  channel = null
}

// ── Join ──────────────────────────────────────────────────────────────────────

async function handleJoin(playerName: string) {
  if (hasJoined.value) return
  isLoading.value = true
  joinError.value = null

  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res  = await fetch(`/trivia-game/room/${props.room.id}/join`, {
      method: 'POST',
      headers: {
        'Content-Type':     'application/json',
        'Accept':           'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN':     csrf,
      },
      body: JSON.stringify({ name: playerName }),
    })

    const data = await res.json().catch(() => ({}))

    if (res.ok) {
      const player: Player = {
        ...(data.player ?? { id: props.currentUserId, name: playerName, roomCode: props.room.code }),
        joinedAt: new Date().toISOString(),
      }
      currentPlayer.value = player
      hasJoined.value     = true
      if (!players.value.find(p => p.id === player.id)) {
        players.value.push(player)
      }
    } else if (res.status === 403) {
      joinError.value = 'No tienes permiso. Se requiere el rol de jugador.'
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
  if (authName.value && !props.alreadyJoined) {
    handleJoin(authName.value)
  }
})
onUnmounted(() => { unsubscribeFromChannel() })

// ── Computed ──────────────────────────────────────────────────────────────────

const showJoinForm  = computed(() => !hasJoined.value && !joinError.value && !authName.value)
const statusDot     = computed(() => ({
  connected:    'bg-emerald-400',
  reconnecting: 'bg-amber-400 animate-pulse',
  disconnected: 'bg-gray-400',
})[connectionStatus.value])
const statusLabel   = computed(() => ({
  connected:    'En tiempo real',
  reconnecting: 'Reconectando...',
  disconnected: 'Sin conexión',
})[connectionStatus.value])
</script>

<template>
  <Head :title="`Sala ${room.code} · Trivia`" />

  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950">

    <!-- Connection status pill (top-right) -->
    <div class="fixed top-4 right-4 z-50 pointer-events-none">
      <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-100 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-300">
        <span :class="['w-2 h-2 rounded-full', statusDot]" />
        {{ statusLabel }}
      </div>
    </div>

    <!-- Loading overlay -->
    <Transition name="fade">
      <div
        v-if="isLoading"
        class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm flex items-center justify-center"
      >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl px-8 py-6 flex items-center gap-4">
          <svg class="w-6 h-6 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Uniéndose a la sala...</span>
        </div>
      </div>
    </Transition>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

      <!-- Room header -->
      <RoomHeader
        :room="room"
        :player-count="players.length"
        :max-players="room.maxPlayers"
      />

      <!-- Main grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Player list (2/3) -->
        <div class="lg:col-span-2">
          <PlayerList
            :players="players"
            :host-id="room.hostId"
            :current-player-id="currentPlayer?.id ?? (hasJoined ? currentUserId : undefined)"
          />
        </div>

        <!-- Right panel (1/3) -->
        <div class="space-y-4">

          <!-- Error de acceso -->
          <Transition name="slide-down">
            <div
              v-if="joinError"
              class="rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 p-4"
            >
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                  <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-red-800 dark:text-red-300">Acceso denegado</p>
                  <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">{{ joinError }}</p>
                </div>
              </div>
            </div>
          </Transition>

          <!-- Formulario de unirse (solo jugadores externos) -->
          <Transition name="slide-down">
            <JoinForm
              v-if="showJoinForm"
              @join="handleJoin"
            />
          </Transition>

          <!-- Conectando automáticamente (usuario auth aún no unido) -->
          <Transition name="slide-down">
            <div
              v-if="authName && !hasJoined && !joinError"
              class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
            >
              <div class="h-1 bg-gradient-to-r from-indigo-400 to-purple-500" />
              <div class="p-6 text-center space-y-4">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                  <svg class="w-7 h-7 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-base font-bold text-gray-900 dark:text-white">Uniéndose a la sala...</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Entrando como <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ authName }}</span></p>
                </div>
              </div>
            </div>
          </Transition>

          <!-- Ya unido -->
          <Transition name="slide-down">
            <div
              v-if="hasJoined"
              class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
            >
              <!-- Top accent -->
              <div class="h-1 bg-gradient-to-r from-emerald-400 to-teal-500" />

              <div class="p-6 text-center space-y-4">
                <!-- Check icon -->
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                  <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                </div>

                <div>
                  <h3 class="text-base font-bold text-gray-900 dark:text-white">¡Ya estás dentro!</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Esperando que el anfitrión inicie el juego
                  </p>
                </div>

                <!-- Connection indicator -->
                <div class="flex items-center justify-center gap-2 text-xs">
                  <span :class="['w-2 h-2 rounded-full', statusDot]" />
                  <span class="text-gray-500 dark:text-gray-400">{{ statusLabel }}</span>
                </div>

                <!-- Player count pill -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ players.length }} jugador{{ players.length !== 1 ? 'es' : '' }} en sala
                </div>
              </div>
            </div>
          </Transition>

          <!-- Info box (siempre visible) -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 space-y-3">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cómo funciona</p>
            <ul class="space-y-2.5">
              <li class="flex items-start gap-2.5">
                <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">1</span>
                <span class="text-xs text-gray-600 dark:text-gray-300">Únete a la sala con tu nombre</span>
              </li>
              <li class="flex items-start gap-2.5">
                <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">2</span>
                <span class="text-xs text-gray-600 dark:text-gray-300">Espera a que el anfitrión inicie el juego</span>
              </li>
              <li class="flex items-start gap-2.5">
                <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">3</span>
                <span class="text-xs text-gray-600 dark:text-gray-300">Responde {{ room.questionCount }} preguntas en {{ room.timePerQuestion }}s cada una</span>
              </li>
            </ul>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-down-enter-active { transition: all 0.25s ease-out; }
.slide-down-leave-active { transition: all 0.15s ease-in; }
.slide-down-enter-from   { opacity: 0; transform: translateY(-8px); }
.slide-down-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
