<script setup lang="ts">
import type { MyGamesPageProps } from '@/js/types/games/bingo/history/my-games'
import { useMyGamesHistory } from '@js/composables/games/bingo/history/useMyGamesHistory'
import { useMyGamesDetail } from '@js/composables/games/bingo/history/useMyGamesDetail'
import MyGamesStats from '@js/Components/Games/Bingo/History/MyGamesStats.vue'
import MyGamesHistoryTable from '@js/Components/Games/Bingo/History/MyGamesHistoryTable.vue'
import GameDetailModal from '@js/Components/Games/Bingo/History/GameDetailModal.vue'

const props = defineProps<MyGamesPageProps>()

const {
  history, meta, loading,
  columns, tableFilters,
  fetchHistory, onFilter,
  totalGames, wonOnPage, totalCards,
} = useMyGamesHistory(props.history, props.pagination)

const {
  showDetail, detailLoading, detail, activeCardTab,
  displayCards, cardIndex, activeCard,
  prevCard, nextCard, openDetail,
} = useMyGamesDetail()
</script>

<template>
  <div class="mg-page p-3">

    <!-- Cabecera -->
    <div class="d-flex align-items-center gap-2 mb-3">
      <a href="/bingo/game-rooms" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Salas
      </a>
      <div>
        <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>{{ title }}</h5>
        <small class="text-muted">Historial completo de tus partidas de Bingo</small>
      </div>
    </div>

    <MyGamesStats :total-games="totalGames" :won-on-page="wonOnPage" :total-cards="totalCards" />

    <MyGamesHistoryTable
      :history="history"
      :meta="meta"
      :loading="loading"
      :columns="columns"
      :table-filters="tableFilters"
      :total-games="totalGames"
      @filter="onFilter"
      @page-change="fetchHistory"
      @view-detail="openDetail"
    />

    <GameDetailModal
      v-model="showDetail"
      :loading="detailLoading"
      :detail="detail"
      :active-card-tab="activeCardTab"
      :display-cards="displayCards"
      :card-index="cardIndex"
      :active-card="activeCard"
      @update:active-card-tab="activeCardTab = $event"
      @update:card-index="cardIndex = $event"
      @prev="prevCard"
      @next="nextCard"
    />

  </div>
</template>

<style scoped>
.mg-page {
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Botones secundarios (volver): mismo acento #667eea del resto del
 * design system en vez de los grises fijos de Bootstrap. ── */
.mg-page :deep(.btn-outline-secondary) {
  border-color: var(--ds-border-2);
  color: var(--ds-text-2);
}
.mg-page :deep(.btn-outline-secondary:hover:not(:disabled)) {
  background: rgba(102,126,234,.12);
  border-color: #667eea;
  color: #667eea;
}
</style>
