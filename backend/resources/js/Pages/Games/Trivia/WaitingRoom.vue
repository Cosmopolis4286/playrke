<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import RoomHeader from '@js/Components/Games/Trivia/Room/RoomHeader.vue'
import PlayerList from '@js/Components/Games/Trivia/Room/PlayerList.vue'
import JoinForm from '@js/Components/Games/Trivia/Room/JoinForm.vue'
import { getEchoInstance } from '@js/plugins/echo'
import type { Room, Player } from '@js/types/trivia'

const page  = usePage()
const authName = computed<string | null>(() => (page.props as any).auth?.user?.name ?? null)

interface Props {
  room: Room
  initialPlayers: Player[]
  alreadyJoined: boolean
  currentUserId: string
  userWallet?: {
    balance: number
    symbol: string
    currencyId: string
  } | null
}

const props = defineProps<Props>()

const players    = ref<Player[]>(props.initialPlayers)
const hasJoined  = ref(props.alreadyJoined)
const isLoading  = ref(false)
const joinError  = ref<string | null>(null)
const connectionStatus = ref<'connected' | 'disconnected' | 'reconnecting'>('disconnected')

// ── Modal de confirmación ───────────────────────────────────────────────────
const showConfirmModal = ref(false)
const hasInsufficientFunds = computed(() => {
  if (!props.room.entryFee || props.room.entryFee <= 0) return false
  if (!props.userWallet) return true
  return props.userWallet.balance < props.room.entryFee
})

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

function openJoinModal() {
  if (props.room.entryFee && props.room.entryFee > 0) {
    showConfirmModal.value = true
  } else {
    // Sin costo, unirse directamente
    doJoin(authName.value || 'Jugador')
  }
}

function closeConfirmModal() {
  showConfirmModal.value = false
}

async function handleJoin(playerName: string) {
  // Para usuarios no autenticados o cuando hay costo, mostrar modal primero
  if (props.room.entryFee && props.room.entryFee > 0 && !showConfirmModal.value) {
    showConfirmModal.value = true
    return
  }
  await doJoin(playerName)
}

async function doJoin(playerName: string) {
  if (hasJoined.value) return
  isLoading.value = true
  joinError.value = null
  showConfirmModal.value = false

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
      // Si ya estaba unido, no hacer nada más que actualizar estado
      if (data.already_joined) {
        hasJoined.value = true
        // Asegurarnos de que estamos en la lista de jugadores
        const existingPlayer = players.value.find(p => p.id === props.currentUserId)
        if (!existingPlayer) {
          const player: Player = {
            id: props.currentUserId,
            name: playerName,
            roomCode: props.room.code,
            joinedAt: new Date().toISOString(),
          }
          players.value.push(player)
          currentPlayer.value = player
        } else {
          currentPlayer.value = existingPlayer
        }
        return
      }

      // Unión exitosa por primera vez
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
    } else if (res.status === 402) {
      joinError.value = data.message ?? 'Saldo insuficiente para unirte a esta sala.'
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
  // Ya no se une automáticamente - el usuario debe confirmar (especialmente si hay costo)
})
onUnmounted(() => { unsubscribeFromChannel() })

// ── Computed ──────────────────────────────────────────────────────────────────

const showJoinForm  = computed(() => !hasJoined.value && !props.alreadyJoined && !joinError.value && !authName.value)
const showAuthJoinButton = computed(() => !hasJoined.value && !props.alreadyJoined && !joinError.value && !!authName.value && !isLoading.value)
const showAuthJoinLoading = computed(() => !hasJoined.value && !props.alreadyJoined && !joinError.value && !!authName.value && isLoading.value)
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

          <!-- Botón para unirse (usuario autenticado aún no unido) -->
          <Transition name="slide-down">
            <div
              v-if="showAuthJoinButton"
              class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
            >
              <div class="h-1 bg-gradient-to-r from-indigo-400 to-purple-500" />
              <div class="p-6 space-y-4">
                <div class="text-center">
                  <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                    <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </div>
                  <h3 class="text-base font-bold text-gray-900 dark:text-white mt-3">¿Listo para jugar?</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Entrarás como <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ authName }}</span></p>
                </div>

                <!-- Info de costo si aplica -->
                <div v-if="room.entryFee && room.entryFee > 0" class="bg-violet-50 dark:bg-violet-900/20 rounded-xl p-3">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-violet-700 dark:text-violet-300">Costo de entrada:</span>
                    <span class="font-bold text-violet-800 dark:text-violet-200">{{ room.entryFee }} {{ room.entryCurrencySymbol || '' }}</span>
                  </div>
                  <div v-if="userWallet" class="flex items-center justify-between text-xs mt-1">
                    <span class="text-gray-500 dark:text-gray-400">Tu balance:</span>
                    <span :class="['font-medium', hasInsufficientFunds ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400']">
                      {{ userWallet.balance }} {{ userWallet.symbol }}
                    </span>
                  </div>
                </div>
                <div v-else class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 text-center">
                  <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">✅ Entrada gratuita</span>
                </div>

                <!-- Botón de unión -->
                <button
                  @click="openJoinModal"
                  :disabled="hasInsufficientFunds"
                  class="w-full py-3 px-4 rounded-xl font-semibold text-white transition-colors flex items-center justify-center gap-2"
                  :class="hasInsufficientFunds ? 'bg-gray-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'"
                >
                  <span v-if="hasInsufficientFunds">Saldo insuficiente</span>
                  <span v-else>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Unirme al juego
                  </span>
                </button>
              </div>
            </div>
          </Transition>

          <!-- Loading de unión (usuario autenticado) -->
          <Transition name="slide-down">
            <div
              v-if="showAuthJoinLoading"
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
                  <h3 class="text-base font-bold text-gray-900 dark:text-white">Procesando entrada...</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Descontando de tu wallet</p>
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

  <!-- Modal de confirmación de entrada -->
  <Transition name="fade">
    <div
      v-if="showConfirmModal"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="closeConfirmModal"
    >
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4">
          <h3 class="text-lg font-bold text-white text-center">Confirmar entrada</h3>
        </div>

        <div class="p-6 space-y-4">
          <!-- Detalles de la sala -->
          <div class="text-center space-y-1">
            <p class="text-sm text-gray-500 dark:text-gray-400">Sala</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ room.title }}</p>
            <code class="text-xs text-gray-400">{{ room.code }}</code>
          </div>

          <!-- Detalle del costo -->
          <div class="bg-violet-50 dark:bg-violet-900/20 rounded-xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-violet-700 dark:text-violet-300">Costo de entrada:</span>
              <span class="font-bold text-violet-800 dark:text-violet-200">{{ room.entryFee || 0 }} {{ room.entryCurrencySymbol || '' }}</span>
            </div>

            <div v-if="userWallet" class="border-t border-violet-200 dark:border-violet-700 pt-3 space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Tu balance:</span>
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ userWallet.balance }} {{ userWallet.symbol }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Balance después:</span>
                <span :class="['font-bold', (userWallet.balance - (room.entryFee || 0)) < 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400']">
                  {{ (userWallet.balance - (room.entryFee || 0)).toFixed(2) }} {{ userWallet.symbol }}
                </span>
              </div>
            </div>

            <div v-else class="border-t border-violet-200 dark:border-violet-700 pt-2">
              <p class="text-xs text-amber-600 dark:text-amber-400">⚠️ No se pudo cargar tu wallet</p>
            </div>
          </div>

          <!-- Alerta de saldo insuficiente -->
          <div v-if="hasInsufficientFunds" class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
            <p class="text-sm text-red-600 dark:text-red-400">❌ Saldo insuficiente</p>
            <p class="text-xs text-red-500 dark:text-red-400 mt-1">Necesitas recargar tu wallet para unirte</p>
          </div>

          <!-- Botones -->
          <div class="flex gap-3 pt-2">
            <button
              @click="closeConfirmModal"
              class="flex-1 py-2.5 px-4 rounded-xl font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="doJoin(authName || 'Jugador')"
              :disabled="hasInsufficientFunds || isLoading"
              class="flex-1 py-2.5 px-4 rounded-xl font-semibold text-white transition-colors flex items-center justify-center gap-2"
              :class="hasInsufficientFunds ? 'bg-gray-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'"
            >
              <span v-if="isLoading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                Procesando...
              </span>
              <span v-else>Confirmar y unirme</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-down-enter-active { transition: all 0.25s ease-out; }
.slide-down-leave-active { transition: all 0.15s ease-in; }
.slide-down-enter-from   { opacity: 0; transform: translateY(-8px); }
.slide-down-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
