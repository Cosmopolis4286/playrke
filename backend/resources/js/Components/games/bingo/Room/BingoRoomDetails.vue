<script setup lang="ts">
import { computed, ref } from 'vue'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { useBingoRoomFinancials } from '@js/composables/games/bingo/room/useBingoRoomFinancials'
import { useWinnerCardViewer } from '@js/composables/games/bingo/room/useWinnerCardViewer'

import RoomDetailsHeader        from './Details/RoomDetailsHeader.vue'
import RoomGeneralConfigSection from './Details/RoomGeneralConfigSection.vue'
import RoomOverviewSection      from './Details/RoomOverviewSection.vue'
import RoomTimeline             from './Details/RoomTimeline.vue'
import RoomFinancialSection     from './Details/RoomFinancialSection.vue'
import RoomPercentagesSection   from './Details/RoomPercentagesSection.vue'
import RoomRoundsSection        from './Details/RoomRoundsSection.vue'
import RoomSerialsSection       from './Details/RoomSerialsSection.vue'
import RoomWinnersList          from './Details/RoomWinnersList.vue'
import WinnerCardViewerModal    from './Details/WinnerCardViewerModal.vue'

interface Props {
  room: BingoRoom
  roomTypeOptions: { value: string; label: string }[]
  connectionTypeOptions: { value: string; label: string }[]
  gameModeOptions?: { value: string; label: string }[]
  prizePoolTypeOptions: { value: string; code: string; label: string }[]
  jackpotTypeOptions?: { value: string; code: string; label: string }[]
  allowedCurrenciesOptions: { value: string; label: string }[]
}

const props = defineProps<Props>()

const room = computed(() => props.room)
const allowedCurrenciesOptions = computed(() => props.allowedCurrenciesOptions)
const prizePoolTypeOptions = computed(() => props.prizePoolTypeOptions)

const {
  startModeLabel, scheduleInfo, cardPricesList, metricsByCurrencyList,
  currencyIso, isAccumulative,
  roundsConfig, roundPrizeLabel, totalPct,
} = useBingoRoomFinancials(room, allowedCurrenciesOptions, prizePoolTypeOptions)

const {
  loadingCard, cardViewerOpen, cardViewerError, cardViewerData, cardViewerWinner,
  cardViewerCols, cardViewerRows,
  isFreeViewerCell, viewerNum, openCardViewer, closeCardViewer, winnerUsername,
} = useWinnerCardViewer(room)

/** Pestañas: el modal mostraba todo en una sola columna con scroll largo. */
type Tab = 'summary' | 'finance' | 'prizes' | 'history'
const tab = ref<Tab>('summary')
const tabs = computed(() => [
  { key: 'summary' as Tab, label: 'Resumen', icon: 'fa-gauge', count: null },
  { key: 'finance' as Tab, label: 'Finanzas', icon: 'fa-coins', count: null },
  { key: 'prizes' as Tab, label: 'Premios', icon: 'fa-trophy', count: room.value.winners?.length || null },
  { key: 'history' as Tab, label: 'Historial', icon: 'fa-clock-rotate-left', count: room.value.timeline?.length || null },
])
</script>

<template>
  <div class="room-details">

    <RoomDetailsHeader :room="room" :schedule-info="scheduleInfo" />

    <nav class="rd-tabs" role="tablist">
      <button
        v-for="t in tabs"
        :key="t.key"
        type="button"
        role="tab"
        class="rd-tab"
        :class="{ 'is-active': tab === t.key }"
        :aria-selected="tab === t.key"
        @click="tab = t.key"
      >
        <i class="fas" :class="t.icon"></i>{{ t.label }}<span v-if="t.count" class="rd-tab__count">{{ t.count }}</span>
      </button>
    </nav>

    <div class="rd-body">
      <template v-if="tab === 'summary'">
        <RoomOverviewSection :room="room" :metrics-by-currency-list="metricsByCurrencyList" />
        <RoomGeneralConfigSection
          :room="room"
          :room-type-options="roomTypeOptions"
          :connection-type-options="connectionTypeOptions"
          :game-mode-options="gameModeOptions"
          :prize-pool-type-options="prizePoolTypeOptions"
          :start-mode-label="startModeLabel"
          :schedule-info="scheduleInfo"
        />
      </template>

      <template v-else-if="tab === 'finance'">
        <RoomFinancialSection
          :room="room"
          :is-accumulative="isAccumulative"
          :card-prices-list="cardPricesList"
          :metrics-by-currency-list="metricsByCurrencyList"
        />
        <RoomPercentagesSection :room="room" :total-pct="totalPct" :jackpot-type-options="jackpotTypeOptions ?? []" />
      </template>

      <template v-else-if="tab === 'prizes'">
        <RoomRoundsSection
          :room="room"
          :rounds-config="roundsConfig"
          :round-prize-label="roundPrizeLabel"
        />
        <RoomSerialsSection :room="room" />
        <RoomWinnersList
          :winners="room.winners ?? []"
          :loading-card="loadingCard"
          :currency-iso="currencyIso"
          :winner-username="winnerUsername"
          @view-card="openCardViewer"
        />
      </template>

      <RoomTimeline v-else :entries="room.timeline ?? []" />
    </div>

    <WinnerCardViewerModal
      :open="cardViewerOpen"
      :winner="cardViewerWinner"
      :data="cardViewerData"
      :error="cardViewerError"
      :cols="cardViewerCols"
      :rows="cardViewerRows"
      :currency-iso="currencyIso"
      :winner-username="winnerUsername"
      :is-free-cell="isFreeViewerCell"
      :cell-num="viewerNum"
      @close="closeCardViewer"
      @retry="openCardViewer"
    />

  </div>
</template>

<style scoped>
.room-details { display: flex; flex-direction: column; }

.rd-tabs {
  display: flex;
  gap: 0.25rem;
  border-bottom: 1px solid var(--ds-border);
  margin-bottom: 0.75rem;
  overflow-x: auto;
}
.rd-tab {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.4rem 0.7rem;
  border: none; border-bottom: 2px solid transparent; background: none;
  font-size: 0.8rem; font-weight: 600; color: var(--ds-text-muted);
  white-space: nowrap; cursor: pointer;
}
.rd-tab i { font-size: 0.72rem; }
.rd-tab:hover { color: var(--ds-text); }
.rd-tab.is-active { color: #2563eb; border-bottom-color: #2563eb; }
.rd-tab__count { font-size: 0.65rem; padding: 0 0.35rem; border-radius: 999px; background: var(--ds-surface-2); color: var(--ds-text-2); }

.rd-body {
  max-height: 58vh;
  overflow-y: auto;
  padding-right: 0.35rem;
}
.rd-body::-webkit-scrollbar { width: 5px; }
.rd-body::-webkit-scrollbar-thumb { background: var(--ds-border); border-radius: 3px; }

/* Compacta las secciones reutilizadas (detail-item / section-title) */
.rd-body :deep(.details-section) { margin-bottom: 0.85rem; padding-bottom: 0.5rem; }
.rd-body :deep(.section-title) { font-size: 0.7rem; margin-bottom: 0.45rem; padding-bottom: 0.25rem; border-bottom-width: 1px; }
.rd-body :deep(.details-grid) { grid-template-columns: repeat(auto-fill, minmax(165px, 1fr)); gap: 0.4rem; }
.rd-body :deep(.detail-item) { padding: 0.35rem 0.5rem; }
.rd-body :deep(.detail-label) { font-size: 0.62rem; margin-bottom: 0.1rem; }
.rd-body :deep(.detail-value) { font-size: 0.82rem; }
</style>
