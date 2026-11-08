<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import RoomHeader from '@js/Components/games/Trivia/Room/RoomHeader.vue'
import PlayerList from '@js/Components/games/Trivia/Room/PlayerList.vue'
import TeamSelector from '@js/Components/games/Trivia/Room/TeamSelector.vue'
import JoinPanel from '@js/Components/games/Trivia/Room/JoinPanel.vue'
import JoinConfirmModal from '@js/Components/games/Trivia/Room/JoinConfirmModal.vue'
import JoinedStatus from '@js/Components/games/Trivia/Room/JoinedStatus.vue'
import RoomInfoBox from '@js/Components/games/Trivia/Room/RoomInfoBox.vue'
import ConnectionStatusPill from '@js/Components/games/Trivia/Room/ConnectionStatusPill.vue'
import { useTriviaRoomEcho } from '@js/composables/games/trivia/room/useTriviaRoomEcho'
import { useTriviaRoomJoin } from '@js/composables/games/trivia/room/useTriviaRoomJoin'
import { useTriviaRoomStatusPoll } from '@js/composables/games/trivia/room/useTriviaRoomStatusPoll'
import type { Room, Player, Team } from '@js/types/trivia'

const page = usePage()

// La ruta siempre requiere auth — authName resuelve nombre del usuario autenticado.
const authName = computed<string>(() => {
  const auth = (page.props as any).auth?.user
  if (!auth) return 'Jugador'
  const full = `${auth.first_name ?? ''} ${auth.last_name ?? ''}`.trim()
  return full || auth.name || auth.username || 'Jugador'
})

interface Props {
  room: Room
  teams?: Team[]
  gameMode?: 'free_for_all' | 'teams' | 'duel'
  initialPlayers: Player[]
  alreadyJoined: boolean
  currentUserId: string
  userWallet?: { balance: number; symbol: string; currencyId: string; found: boolean } | null
}

const props = defineProps<Props>()

const players        = ref<Player[]>(props.initialPlayers)
const selectedTeamId = ref<string | null>(null)

const requiresTeamSelection = computed(() =>
  props.gameMode === 'teams' || props.gameMode === 'duel'
)

const currentPlayer = ref<Player | null>(
  props.alreadyJoined
    ? (props.initialPlayers.find(p => p.id === props.currentUserId) ?? null)
    : null
)

const {
  hasJoined, isLoading, joinError, showConfirmModal,
  feeWithoutCurrency, hasInsufficientFunds, walletNotFound,
  openJoinModal, closeConfirmModal, confirmAndJoin
} = useTriviaRoomJoin({
  room: props.room,
  currentUserId: props.currentUserId,
  userWallet: props.userWallet,
  alreadyJoined: props.alreadyJoined,
  authName,
  requiresTeamSelection,
  selectedTeamId: computed(() => selectedTeamId.value),
  onJoined: (player) => {
    if (!players.value.find(p => p.id === player.id)) {
      players.value.push(player)
    }
    currentPlayer.value = player
  }
})

const { connectionStatus, subscribe, unsubscribe } = useTriviaRoomEcho({
  roomId: props.room.id,
  roomCode: props.room.code,
  onPlayerJoined: (player) => {
    if (!players.value.find(p => p.id === player.id)) {
      players.value.push(player)
    }
  },
  onPlayerLeft: (playerId) => {
    players.value = players.value.filter(p => p.id !== playerId)
  },
  onGameStarted: (gameId) => {
    // Redirige a la página de juego usando el gameId recibido
    window.location.href = `/trivia/play/${gameId}`
  }
})

// ── Respaldo por polling (evento perdido o llegada tarde) ─────────────────────
const roomCancelled = ref(false)
const { scheduledAt: polledScheduledAt } = useTriviaRoomStatusPoll({
  roomCode: props.room.code,
  enabled: computed(() => hasJoined.value || props.alreadyJoined),
  connectionStatus,
  onPlaying: (gameId) => { window.location.href = `/trivia/play/${gameId}` },
  onCancelled: () => { roomCancelled.value = true }
})

// ── Hora de inicio + cuenta regresiva ─────────────────────────────────────────
const now = ref(Date.now())
let clock: ReturnType<typeof setInterval> | null = null
const scheduledAt = computed(() => polledScheduledAt.value ?? props.room.scheduledAt ?? null)
const startLabel = computed(() => {
  if (!scheduledAt.value) return null
  return new Date(scheduledAt.value).toLocaleString('es-VE', {
    weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
  })
})
const countdown = computed(() => {
  if (!scheduledAt.value) return null
  const diff = Math.round((new Date(scheduledAt.value).getTime() - now.value) / 1000)
  if (diff <= 0) return 'Iniciando…'
  const h = Math.floor(diff / 3600)
  const m = Math.floor((diff % 3600) / 60)
  const sec = diff % 60
  return h > 0 ? `${h} h ${m} min` : `${m}:${String(sec).padStart(2, '0')}`
})

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
  subscribe()
  clock = setInterval(() => { now.value = Date.now() }, 1000)
})
onUnmounted(() => {
  unsubscribe()
  if (clock) clearInterval(clock)
})

// ── Computed helpers ──────────────────────────────────────────────────────────
const notJoined           = computed(() => !hasJoined.value && !props.alreadyJoined && !joinError.value)
const showAuthJoinButton  = computed(() => notJoined.value && !isLoading.value)
const showAuthJoinLoading = computed(() => notJoined.value && isLoading.value)
const statusDot = computed(() => ({
  connected: 'bg-emerald-400',
  reconnecting: 'bg-amber-400 animate-pulse',
  disconnected: 'bg-gray-400'
})[connectionStatus.value])
const statusLabel = computed(() => ({
  connected: 'En tiempo real',
  reconnecting: 'Reconectando...',
  disconnected: 'Sin conexión'
})[connectionStatus.value])
</script>

<template>
  <Head :title="`Sala ${room.code} · Trivia`" />

  <div class="min-h-screen wr-page-bg">
    <ConnectionStatusPill :status-dot="statusDot" :status-label="statusLabel" />

    <!-- Loading overlay -->
    <Transition name="fade">
      <div
        v-if="isLoading"
        class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm flex items-center justify-center"
      >
        <div class="wr-card rounded-2xl shadow-xl px-8 py-6 flex items-center gap-4">
          <svg class="w-6 h-6 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
          <span class="text-sm font-medium wr-text-secondary">Uniéndose a la sala...</span>
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
            :teams="teams"
            :max-players="room.maxPlayers"
          />
        </div>

        <!-- Right panel (1/3) -->
        <div class="space-y-4">
          <!-- Sala cancelada (detectada por polling) -->
          <div
            v-if="roomCancelled"
            class="rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 p-4"
          >
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
              La sala se canceló
            </p>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
              Si pagaste la entrada, se reembolsa a tu billetera. <a href="/trivia/lobby" class="underline">Volver al lobby</a>
            </p>
          </div>

          <!-- Hora de inicio -->
          <div v-if="startLabel && !roomCancelled" class="wr-card rounded-2xl shadow-sm border p-4">
            <p class="text-xs wr-text-muted">
              La partida empieza
            </p>
            <p class="text-base font-bold wr-text capitalize">
              {{ startLabel }}
            </p>
            <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-1 tabular-nums">
              {{ countdown }}
            </p>
          </div>

          <!-- Error de acceso -->
          <Transition name="slide-down">
            <div
              v-if="joinError"
              class="rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 p-4"
            >
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                  <svg
                    class="w-4 h-4 text-red-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-red-800 dark:text-red-300">
                    Acceso denegado
                  </p>
                  <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">
                    {{ joinError }}
                  </p>
                </div>
              </div>
            </div>
          </Transition>

          <!-- Selección de Equipos -->
          <Transition name="slide-down">
            <TeamSelector
              v-if="requiresTeamSelection && !hasJoined"
              :teams="teams"
              :game-mode="gameMode"
              :selected-team-id="selectedTeamId"
              @update:selected-team-id="selectedTeamId = $event"
            />
          </Transition>

          <!-- Botón para unirse (usuario autenticado aún no unido) -->
          <Transition name="slide-down">
            <JoinPanel
              v-if="showAuthJoinButton"
              :room="room"
              :auth-name="authName"
              :user-wallet="userWallet"
              :fee-without-currency="feeWithoutCurrency"
              :has-insufficient-funds="hasInsufficientFunds"
              :wallet-not-found="walletNotFound"
              @join="openJoinModal"
            />
          </Transition>

          <!-- Loading de unión (usuario autenticado) -->
          <Transition name="slide-down">
            <div
              v-if="showAuthJoinLoading"
              class="wr-card rounded-2xl shadow-sm border overflow-hidden"
            >
              <div class="h-1 bg-gradient-to-r from-indigo-400 to-purple-500" />
              <div class="p-6 text-center space-y-4">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                  <svg class="w-7 h-7 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-base font-bold wr-text">
                    Procesando entrada...
                  </h3>
                  <p class="text-xs wr-text-muted mt-1">
                    Descontando de tu wallet
                  </p>
                </div>
              </div>
            </div>
          </Transition>

          <!-- Ya unido -->
          <Transition name="slide-down">
            <JoinedStatus
              v-if="hasJoined"
              :status-dot="statusDot"
              :status-label="statusLabel"
              :player-count="players.length"
            />
          </Transition>

          <!-- Info box (siempre visible) -->
          <RoomInfoBox :question-count="room.questionCount" :time-per-question="room.timePerQuestion" />
        </div>
      </div>
    </div>
  </div>

  <JoinConfirmModal
    :show="showConfirmModal"
    :room="room"
    :user-wallet="userWallet"
    :has-insufficient-funds="hasInsufficientFunds"
    :is-loading="isLoading"
    @close="closeConfirmModal"
    @confirm="confirmAndJoin"
  />
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-down-enter-active { transition: all 0.25s ease-out; }
.slide-down-leave-active { transition: all 0.15s ease-in; }
.slide-down-enter-from   { opacity: 0; transform: translateY(-8px); }
.slide-down-leave-to     { opacity: 0; transform: translateY(-4px); }

.wr-page-bg { background: var(--ds-bg); }
.wr-card { background: var(--ds-surface); border-color: var(--ds-border); }
.wr-text { color: var(--ds-text); }
.wr-text-secondary { color: var(--ds-text-2); }
.wr-text-muted { color: var(--ds-text-muted); }
</style>
