<script setup lang="ts">
interface Props {
  drawnNumbers: number[]
  totalChips: number
  chipLetter: (n: number) => string
}

defineProps<Props>()
</script>

<template>
  <Transition name="ig-fade">
    <div v-if="drawnNumbers.length > 0" class="ig-drawn-board">

      <!-- Fila superior: última bola + recientes + info -->
      <div class="ig-db-top">

        <!-- Última bola -->
        <div class="ig-db-last">
          <div class="ig-db-last__ring" />
          <div class="ig-chip ig-chip--lg" :class="`ig-chip--${chipLetter(drawnNumbers[drawnNumbers.length-1])}`">
            <span class="ig-chip__letter">{{ chipLetter(drawnNumbers[drawnNumbers.length-1]) }}</span>
            <span class="ig-chip__num">{{ drawnNumbers[drawnNumbers.length-1] }}</span>
          </div>
        </div>

        <!-- Recientes -->
        <div class="ig-db-recent">
          <span class="ig-db-recent__label">RECIENTES</span>
          <div class="ig-db-recent__row">
            <div
              v-for="n in [...drawnNumbers].slice(-7).reverse().slice(1)"
              :key="`r-${n}`"
              class="ig-chip ig-chip--md"
              :class="`ig-chip--${chipLetter(n)}`"
            >
              <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
              <span class="ig-chip__num">{{ n }}</span>
            </div>
          </div>
        </div>

        <!-- Info central -->
        <div class="ig-db-info">
          <span class="ig-db-info__label"><i class="fas fa-sort-amount-up-alt" /> ÚLTIMO NÚMERO</span>
          <span class="ig-db-info__num">{{ drawnNumbers[drawnNumbers.length - 1] }}</span>
          <div class="ig-db-info__progress">
            <div class="ig-db-info__bar" :style="{ width: `${(drawnNumbers.length / totalChips) * 100}%` }" />
            <span class="ig-db-info__pct">{{ drawnNumbers.length }}<em>/{{ totalChips }}</em></span>
          </div>
        </div>

      </div>

      <div class="ig-db-sep" />

      <!-- Todas las bolas sorteadas en orden cronológico -->
      <div class="ig-db-chips">
        <div
          v-for="n in [...drawnNumbers].reverse()"
          :key="n"
          class="ig-chip ig-chip--sm"
          :class="[`ig-chip--${chipLetter(n)}`, { 'ig-chip--latest': n === drawnNumbers[drawnNumbers.length-1] }]"
        >
          <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
          <span class="ig-chip__num">{{ n }}</span>
        </div>
      </div>

    </div>
  </Transition>
</template>
