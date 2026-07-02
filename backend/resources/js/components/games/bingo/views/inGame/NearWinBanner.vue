<script setup lang="ts">
import type { PlayerProgress } from './types'

interface Props {
  players: PlayerProgress[]
  remainingChips: number
}

defineProps<Props>()
</script>

<template>
  <Transition name="ig-fade">
    <div v-if="players.length > 0" class="ig-near-win">
      <div class="ig-near-win__header">
        <i class="fas fa-fire ig-near-win__fire" />
        <span class="ig-near-win__chips-badge">{{ remainingChips }}</span>
        <span class="ig-near-win__title">ficha{{ remainingChips === 1 ? '' : 's' }} restantes</span>
      </div>
      <div class="ig-near-win__row">
        <div
          v-for="(p, idx) in players"
          :key="p.user_id"
          class="ig-near-win__player"
          :class="{
            'ig-near-win__player--hot':  p.missing <= 2,
            'ig-near-win__player--warn': p.missing <= 5 && p.missing > 2,
          }"
        >
          <span class="ig-near-win__rank">#{{ idx + 1 }}</span>
          <span class="ig-near-win__name">{{ p.user_name }}</span>
          <span class="ig-near-win__miss-badge" :class="{ 'ig-near-win__miss-badge--hot': p.missing <= 2 }">
            {{ p.missing === 0 ? '¡BINGO!' : p.missing }}
          </span>
        </div>
      </div>
    </div>
  </Transition>
</template>
