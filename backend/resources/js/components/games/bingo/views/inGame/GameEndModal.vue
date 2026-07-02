<script setup lang="ts">
import type { GameEndData } from './types'

interface Props {
  show: boolean
  gameEndData: GameEndData | null
  isMyWin: boolean
  userId?: string | null
  currencySym: string
  totalChips: number
  drawnNumbers: number[]
  chipLetter: (n: number) => string
}

const props = withDefaults(defineProps<Props>(), {
  userId: null,
})

const emit = defineEmits<{ close: [] }>()

// Posiciones fijas de confetti (evita re-render random en cada update)
const confettiPieces = Array.from({ length: 40 }, (_, i) => ({
  left:     `${(i * 2.618033) % 100}%`,
  delay:    `${(i * 0.07) % 2}s`,
  duration: `${2.5 + (i % 5) * 0.3}s`,
  color:    ['#7b4fff','#2af598','#ffd700','#ff4466','#00c6ff','#ff8c00'][i % 6],
}))
</script>

<template>
  <Transition name="gm-fade">
    <div v-if="show" class="gm-overlay" @click.self="emit('close')">

      <template v-if="gameEndData?.hasWinner || isMyWin">
        <div
          v-for="(cf, i) in confettiPieces"
          :key="`cf-${i}`"
          class="gm-confetti"
          :style="{ left: cf.left, animationDelay: cf.delay, animationDuration: cf.duration, background: cf.color }"
        />
      </template>

      <div
        class="gm-modal"
        :class="{
          'gm-modal--win': gameEndData?.hasWinner || isMyWin,
          'gm-modal--no-winner': gameEndData && !gameEndData.hasWinner,
        }"
      >
        <button class="gm-close" @click="emit('close')"><i class="fas fa-times" /></button>

        <div class="gm-header">
          <div v-if="gameEndData?.hasWinner || isMyWin" class="gm-trophy">
            <div class="gm-trophy__ring" />
            <span class="gm-trophy__icon">🏆</span>
          </div>
          <div v-else class="gm-trophy gm-trophy--end">
            <span class="gm-trophy__icon">🎱</span>
          </div>

          <h2 class="gm-title">
            <template v-if="isMyWin">¡BINGO! ¡Ganaste!</template>
            <template v-else-if="gameEndData?.hasWinner">¡Tenemos Ganador!</template>
            <template v-else>Sorteo Finalizado</template>
          </h2>
          <p class="gm-subtitle">
            <template v-if="gameEndData">
              {{ gameEndData.totalDrawn }} fichas sorteadas de {{ totalChips }}
            </template>
          </p>

          <div v-if="gameEndData" class="gm-progress">
            <div class="gm-progress__bar" :style="{ width: `${(gameEndData.totalDrawn / totalChips) * 100}%` }" />
            <span class="gm-progress__label">{{ gameEndData.totalDrawn }}/{{ totalChips }}</span>
          </div>
        </div>

        <div v-if="gameEndData?.winners?.length" class="gm-winners">
          <div class="gm-winners__title">
            <i class="fas fa-star" /> {{ gameEndData.winners.length === 1 ? 'Ganador' : 'Ganadores' }}
          </div>
          <div
            v-for="(w, idx) in gameEndData.winners"
            :key="w.card_id"
            class="gm-winner-card"
            :class="{ 'gm-winner-card--me': String(w.user_id) === String(userId) }"
          >
            <div class="gm-winner-card__rank">#{{ idx + 1 }}</div>
            <div class="gm-winner-card__info">
              <div class="gm-winner-card__name">
                {{ w.user_name }}
                <span v-if="String(w.user_id) === String(userId)" class="gm-winner-card__you">TÚ</span>
              </div>
              <div class="gm-winner-card__serial">Cartón {{ w.card_serial }}</div>
            </div>
            <div class="gm-winner-card__prize">
              <span class="gm-winner-card__sym">{{ currencySym }}</span>
              {{ w.prize.toFixed(2) }}
            </div>
          </div>
        </div>

        <div v-else-if="gameEndData && !gameEndData.hasWinner" class="gm-no-winner">
          <i class="fas fa-info-circle" />
          Ningún cartón completó el bingo en este sorteo.
        </div>

        <div v-if="drawnNumbers.length > 0" class="gm-chips-summary">
          <div class="gm-chips-summary__label">Fichas sorteadas</div>
          <div class="gm-chips-summary__grid">
            <div
              v-for="n in drawnNumbers"
              :key="`gs-${n}`"
              class="ig-chip ig-chip--sm"
              :class="`ig-chip--${chipLetter(n)}`"
            >
              <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
              <span class="ig-chip__num">{{ n }}</span>
            </div>
          </div>
        </div>

        <div class="gm-actions">
          <button class="gm-btn gm-btn--primary" @click="emit('close')">
            <i class="fas fa-check" /> Cerrar
          </button>
        </div>
      </div>

    </div>
  </Transition>
</template>
