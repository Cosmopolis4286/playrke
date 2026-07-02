<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

interface Props {
  prizePoolType: string
  currencySym: string
  prizeFormatted: string
  prizeFlash: boolean
  // countdown
  hasTime: boolean
  cd: { time: { days: number; hours: number; minutes: number; seconds: number } } | null
  isFinished: boolean
  // stats
  livePlayers: number
  totalWinners: number
  playersFlash: boolean
  cardCount: number
  rounds?: number
  round?: number
  liveDrawnLength: number
  totalChips: number
  bingoPattern?: string
  // sound
  muted: boolean
  // dev
  isLocal: boolean
  devResetting: boolean
}

const props = withDefaults(defineProps<Props>(), {
  rounds: 0,
  round: 1,
  bingoPattern: '',
})

const emit = defineEmits<{
  toggleMute: []
  openPlayers: []
  devReset: []
}>()

function pad(n: number) { return String(n).padStart(2, '0') }
</script>

<template>
  <div class="ig-panel">
    <div class="ig-panel__topline" />

    <!-- Fila 1: Premio | Countdown | Acciones -->
    <div class="ig-panel__row ig-panel__row--top">

      <div class="ig-prize" :class="{ 'ig-prize--flash': prizeFlash }">
        <span class="ig-prize__label">
          <i class="fas fa-trophy ig-prize__icon" /> {{ prizePoolType }}
        </span>
        <div class="ig-prize__amount">
          <span class="ig-prize__sym">{{ currencySym }}</span>
          <span class="ig-prize__num">{{ prizeFormatted }}</span>
        </div>
      </div>

      <div class="ig-panel__divider" />

      <div class="ig-countdown" v-if="hasTime && cd">
        <span class="ig-countdown__label"><i class="fas fa-clock" /> Inicia en</span>
        <div class="ig-timer">
          <template v-if="cd.time.days > 0">
            <div class="timer__unit"><span class="timer__num">{{ cd.time.days }}</span><span class="timer__lbl">D</span></div>
            <span class="timer__sep">:</span>
          </template>
          <template v-if="cd.time.hours > 0">
            <div class="timer__unit"><span class="timer__num">{{ cd.time.hours }}</span><span class="timer__lbl">H</span></div>
            <span class="timer__sep">:</span>
          </template>
          <div class="timer__unit"><span class="timer__num">{{ pad(cd.time.minutes) }}</span><span class="timer__lbl">M</span></div>
          <span class="timer__sep">:</span>
          <div class="timer__unit"><span class="timer__num">{{ pad(cd.time.seconds) }}</span><span class="timer__lbl">S</span></div>
        </div>
      </div>
      <div v-else-if="isFinished" class="ig-live-badge ig-live-badge--finished">
        <i class="fas fa-flag-checkered" /> FINALIZADA
      </div>
      <div v-else class="ig-live-badge">
        <span class="ig-live-badge__dot" /> EN VIVO
      </div>

      <div style="flex:1" />

      <div class="ig-panel__actions">
        <Link v-if="isFinished" href="/bingo/game-rooms" class="ig-action-btn ig-action-btn--rooms">
          <i class="fas fa-door-open" /> Volver a salas
        </Link>
        <button
          class="ig-action-btn ig-action-btn--mute"
          :class="{ 'ig-action-btn--muted': muted }"
          @click="emit('toggleMute')"
          :title="muted ? 'Activar sonido' : 'Silenciar'"
        >
          <i :class="muted ? 'fas fa-volume-mute' : 'fas fa-volume-up'" />
        </button>
      </div>
    </div>

    <div class="ig-panel__hline" />

    <!-- Fila 2: Stats -->
    <div class="ig-panel__row ig-panel__row--stats">

      <div
        class="ig-stat ig-stat--clickable"
        :class="{ 'ig-stat--flash': playersFlash }"
        @click="emit('openPlayers')"
        title="Ver lista de jugadores / ganadores"
      >
        <div class="ig-stat__icons">
          <i class="fas fa-users" />
          <i v-if="totalWinners > 0" class="fas fa-trophy ig-stat__trophy-overlay" />
        </div>
        <div class="ig-stat__body">
          <span class="ig-stat__val">
            {{ livePlayers }}
            <small v-if="totalWinners > 0" class="ig-stat__winners-badge">· {{ totalWinners }} <i class="fas fa-trophy" /></small>
          </span>
          <span class="ig-stat__lbl">jugadores</span>
        </div>
      </div>

      <div class="ig-stat__sep" />

      <div class="ig-stat">
        <i class="fas fa-ticket-alt" />
        <div class="ig-stat__body">
          <span class="ig-stat__val">{{ cardCount }}</span>
          <span class="ig-stat__lbl">cartone{{ cardCount === 1 ? '' : 's' }}</span>
        </div>
      </div>

      <template v-if="rounds">
        <div class="ig-stat__sep" />
        <div class="ig-stat">
          <i class="fas fa-sync-alt" />
          <div class="ig-stat__body">
            <span class="ig-stat__val">{{ round }}<small>/{{ rounds }}</small></span>
            <span class="ig-stat__lbl">ronda</span>
          </div>
        </div>
      </template>

      <template v-if="liveDrawnLength > 0">
        <div class="ig-stat__sep" />
        <div class="ig-stat">
          <i class="fas fa-random" />
          <div class="ig-stat__body">
            <span class="ig-stat__val">{{ liveDrawnLength }}<small>/{{ totalChips }}</small></span>
            <span class="ig-stat__lbl">sorteados</span>
          </div>
          <div class="ig-stat__bar">
            <div class="ig-stat__bar-fill" :style="{ width: `${(liveDrawnLength / totalChips) * 100}%` }" />
          </div>
        </div>
      </template>

      <template v-if="bingoPattern">
        <div class="ig-stat__sep" />
        <div class="ig-stat">
          <i class="fas fa-th" />
          <div class="ig-stat__body">
            <span class="ig-stat__val ig-stat__val--sm">{{ bingoPattern }}</span>
            <span class="ig-stat__lbl">patrón</span>
          </div>
        </div>
      </template>

    </div>

    <!-- DEV ribbon -->
    <div v-if="isLocal" class="ig-dev-ribbon">
      <div class="ig-dev-ribbon__label">
        <i class="fas fa-code" /> DEV
      </div>
      <button
        class="ig-dev-ribbon__btn"
        :class="{ 'ig-dev-ribbon__btn--busy': devResetting }"
        :disabled="devResetting"
        @click="emit('devReset')"
      >
        <span v-if="devResetting" class="ig-dev-ribbon__spinner" />
        <i v-else class="fas fa-play" />
        {{ devResetting ? 'Iniciando…' : 'Iniciar sorteo de prueba' }}
      </button>
    </div>
  </div>
</template>
