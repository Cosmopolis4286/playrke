<script setup lang="ts">
import { computed } from 'vue'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { useBingoRoomFinancials } from '@js/composables/games/bingo/room/useBingoRoomFinancials'
import { useWinnerCardViewer } from '@js/composables/games/bingo/room/useWinnerCardViewer'

import RoomDetailsHeader        from './Details/RoomDetailsHeader.vue'
import RoomGeneralConfigSection from './Details/RoomGeneralConfigSection.vue'
import RoomParticipationSection from './Details/RoomParticipationSection.vue'
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
  prizePoolTypeOptions: { value: string; code: string; label: string }[]
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
</script>

<template>
  <div class="room-details">

    <RoomDetailsHeader :room="room" />

    <RoomGeneralConfigSection
      :room="room"
      :room-type-options="roomTypeOptions"
      :connection-type-options="connectionTypeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
      :start-mode-label="startModeLabel"
      :schedule-info="scheduleInfo"
    />

    <RoomParticipationSection :room="room" />

    <RoomFinancialSection
      :room="room"
      :is-accumulative="isAccumulative"
      :card-prices-list="cardPricesList"
      :metrics-by-currency-list="metricsByCurrencyList"
    />

    <RoomPercentagesSection :room="room" :total-pct="totalPct" />

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
.room-details {
  max-height: 65vh;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.room-details::-webkit-scrollbar { width: 5px; }
.room-details::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
.room-details::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
.room-details::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
</style>
