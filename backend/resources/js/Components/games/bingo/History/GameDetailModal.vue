<script setup lang="ts">
import Modal from '@js/Components/Common/Modal/Modal.vue'
import GameDetailBanner from './GameDetailBanner.vue'
import GameResultSummary from './GameResultSummary.vue'
import GameInfoGrid from './GameInfoGrid.vue'
import GamePrizesList from './GamePrizesList.vue'
import GameRoundsGrid from './GameRoundsGrid.vue'
import GameCardsCarousel from './GameCardsCarousel.vue'
import type { GameHistoryDetail, GameHistoryPlayerCard } from '@/js/types/games/bingo/history/my-games'

defineProps<{
  modelValue: boolean
  loading: boolean
  detail: GameHistoryDetail | null
  activeCardTab: 'all' | 'winners'
  displayCards: GameHistoryPlayerCard[]
  cardIndex: number
  activeCard: GameHistoryPlayerCard | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:activeCardTab': [tab: 'all' | 'winners']
  'update:cardIndex': [index: number]
  prev: []
  next: []
}>()
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Detalle de la partida"
    size="xl"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="text-center py-5 text-muted">
      <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
      <div>Cargando detalle…</div>
    </div>

    <template v-else-if="detail">
      <GameDetailBanner :detail="detail" />
      <GameResultSummary :detail="detail" />
      <GameInfoGrid :detail="detail" />
      <GamePrizesList :detail="detail" />
      <GameRoundsGrid :detail="detail" />
      <GameCardsCarousel
        :detail="detail"
        :active-card-tab="activeCardTab"
        :display-cards="displayCards"
        :card-index="cardIndex"
        :active-card="activeCard"
        @update:active-card-tab="emit('update:activeCardTab', $event)"
        @update:card-index="emit('update:cardIndex', $event)"
        @prev="emit('prev')"
        @next="emit('next')"
      />
    </template>
  </Modal>
</template>
