<script setup lang="ts">
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import type { Ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BingoPurchaseModal from '@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue'
import BingoRulesModal   from '@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue'
import LobbyHero          from '@js/Components/Games/Bingo/Room/Lobby/LobbyHero.vue'
import LobbyCardsProgress from '@js/Components/Games/Bingo/Room/Lobby/LobbyCardsProgress.vue'
import LobbyStatsPanel    from '@js/Components/Games/Bingo/Room/Lobby/LobbyStatsPanel.vue'
import LobbyPlayersModal  from '@js/Components/Games/Bingo/Room/Lobby/LobbyPlayersModal.vue'
import LobbyToasts        from '@js/Components/Games/Bingo/Room/Lobby/LobbyToasts.vue'
import LobbyRedirectBanner from '@js/Components/Games/Bingo/Room/Lobby/LobbyRedirectBanner.vue'
import { useGameRoomPurchaseModal } from '@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal'
import { useLobbyEcho }      from '@js/composables/games/bingo/room/lobby/useLobbyEcho'
import { useLobbyCountdown } from '@js/composables/games/bingo/room/lobby/useLobbyCountdown'
import { useLobbyToasts }    from '@js/composables/games/bingo/room/lobby/useLobbyToasts'
import { useLobbyPlayers }   from '@js/composables/games/bingo/room/lobby/useLobbyPlayers'
import { useLobbyRedirect }  from '@js/composables/games/bingo/room/lobby/useLobbyRedirect'
import type { LobbyRoom } from '@js/Components/Games/Bingo/Room/Lobby/types'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

interface Props {
  room: LobbyRoom
  roomRouteKey?: string
  userCards?: number
  exchangeRate?: number
  bingoRules?: string | null
  imageBingoRules?: string | null
}

const props = defineProps<Props>()
const page  = usePage()

// ── Live state ──
const live = reactive({
  prizePool:      props.room?.prize_pool    ?? 0,
  totalPlayers:   props.room?.total_players ?? 0,
  totalCards:     props.room?.total_cards   ?? 0,
  userCards:      props.userCards           ?? 0,
  statusCode:     props.room?.status_code   ?? '',
  cardPrice:      props.room?.card_price    ?? props.room?.card_price_usd ?? 0,
  currencySymbol: props.room?.currency_symbol ?? 'USD',
})

// ── Flash ──
const prizeFlash   = ref(false)
const playersFlash = ref(false)
const flashTimers = new Set<ReturnType<typeof setTimeout>>()
function flash(target: Ref<boolean>) {
  target.value = true
  const t = setTimeout(() => {
    target.value = false
    flashTimers.delete(t)
  }, 1800)
  flashTimers.add(t)
}
onUnmounted(() => { flashTimers.forEach(clearTimeout) })

// ── Composables ──
const { saleToasts, pushSaleToast } = useLobbyToasts()
const { countdown, isLive, isUrgent } = useLobbyCountdown(() => props.room?.unix_date)

const userId = computed(() => page.props.auth?.user?.id ?? null)
const { wsStatus } = useLobbyEcho(props.room.id, () => userId.value, live, {
  onPrizeChanged:   () => flash(prizeFlash),
  onPlayersChanged: () => flash(playersFlash),
  onCardsChanged:   (total) => pushSaleToast(total),
  onCardsUpdated:   (price, symbol) => {
    if (price  !== null) live.cardPrice      = price
    if (symbol !== null) live.currencySymbol = symbol
  },
})

const players = useLobbyPlayers(props.roomRouteKey, () => live.totalPlayers)
const { redirecting, redirectSecs } = useLobbyRedirect(props.roomRouteKey, () => live.statusCode)

// ── Modals ──
const showRulesModal = ref(false)
const modal = useGameRoomPurchaseModal()

// Cierra el modal de compra si el sorteo arranca mientras está abierto
watch(() => live.statusCode, (code) => {
  if (code?.toUpperCase() === 'IN_PROGRESS' && modal.isOpen.value) modal.close()
})

// ── Computed ──
const rate = computed(() => props.exchangeRate ?? 1)

const roomRouteKey = computed(() => props.roomRouteKey?.trim() ?? '')

const canNavigateGame = computed(() => Boolean(props.roomRouteKey?.trim()))

const canPurchaseCards = computed(() =>
  props.room != null &&
  live.statusCode?.toUpperCase() === 'ACTIVE' &&
  !props.room.close_room
)

const minCardsToStart = computed(() => props.room?.min_cards_to_start ?? null)

const showCardsProgress = computed(() =>
  minCardsToStart.value != null && minCardsToStart.value > 0
)

const cardsProgressPct = computed(() => {
  if (!minCardsToStart.value) return 0
  return Math.min(100, Math.round((live.totalCards / minCardsToStart.value) * 100))
})

const cardsProgressReady = computed(() =>
  minCardsToStart.value != null && live.totalCards >= minCardsToStart.value
)

// ── Purchase ──
function buildRoomSnapshot(): BingoGameRoom {
  return {
    id:              props.room.id,
    encrypted:       props.roomRouteKey ?? undefined,
    uuid_code:       props.room.uuid_code ?? null,
    prize_pool:      live.prizePool,
    total_players:   live.totalPlayers,
    creator_user_id: '',
    room_type:       'PUBLIC',
    game_mode:       props.room.game_mode_description ?? null,
    status_code:     live.statusCode,
    currency_symbol: live.currencySymbol,
    card_price:      live.cardPrice,
    players_room:    [{ ctns: live.userCards }],
    config: props.room.unix_date ? {
      room_id:         props.room.id,
      card_price_usd:  props.room.card_price_usd ?? 0,
      prize_pool_type: 1,
      unix_date:       props.room.unix_date,
      date:            props.room.date ?? '',
      time:            props.room.time ?? '',
      game_variant:    props.room.game_variant ?? null,
    } : null,
  }
}

function openPurchase() {
  if (!canPurchaseCards.value) return
  modal.open(buildRoomSnapshot())
}

function onPurchaseSuccess(_roomId: string, result: { prizePool: number; ctns: number }) {
  live.prizePool  = result.prizePool
  live.userCards += result.ctns
  flash(prizeFlash)
}
</script>

<template>
  <div class="lby-page">

    <!-- FAB: reglamento — Teleport para salir de isolation:isolate y superar z-index del sidebar -->
    <Teleport to="body">
      <button
        type="button"
        class="lby-rules-fab"
        title="Ver reglamento del Bingo"
        aria-label="Ver reglamento del Bingo"
        @click="showRulesModal = true"
      >
        <i class="fas fa-book-open" />
      </button>
    </Teleport>

    <!-- Loading -->
    <div v-if="!room" class="lby-loading">
      <div class="lby-spinner" />
    </div>

    <template v-else>
      <div class="lby-shell">
        <LobbyHero
          :room="room"
          :live="live"
          :ws-status="wsStatus"
          :countdown="countdown"
          :is-live="isLive"
          :is-urgent="isUrgent"
          :prize-flash="prizeFlash"
          :rate="rate"
        />

        <LobbyCardsProgress
          v-if="showCardsProgress"
          :total-cards="live.totalCards"
          :min-cards-to-start="minCardsToStart"
          :progress-pct="cardsProgressPct"
          :ready="cardsProgressReady"
        />

        <LobbyStatsPanel
          :room="room"
          :live="live"
          :room-route-key="roomRouteKey"
          :can-navigate-game="canNavigateGame"
          :can-purchase-cards="canPurchaseCards"
          :players-flash="playersFlash"
          :min-cards-to-start="minCardsToStart"
          :cards-progress-ready="cardsProgressReady"
          @open-players="players.open()"
          @open-purchase="openPurchase"
        />
      </div>
    </template>

    <LobbyToasts :toasts="saleToasts" />

    <LobbyPlayersModal
      :show="players.showModal.value"
      :total-players="live.totalPlayers"
      :players="players.playersList.value"
      :loading="players.loading.value"
      :fetch-error="players.fetchError.value"
      :current-user-id="userId"
      @close="players.close()"
    />

    <LobbyRedirectBanner :show="redirecting" :secs="redirectSecs" />

    <BingoRulesModal
      v-model="showRulesModal"
      :rules="props.bingoRules"
      :image="props.imageBingoRules"
    />

    <BingoPurchaseModal
      v-if="modal.isOpen.value"
      :is-open="modal.isOpen.value"
      :step="modal.step.value"
      :selected-room="modal.selectedRoom.value"
      :countries="modal.countries.value"
      :loading-countries="modal.loadingCountries.value"
      :selected-country="modal.selectedCountry.value"
      :card-price="modal.cardPrice.value"
      :currency-symbol="modal.currencySymbol.value"
      :loading-price="modal.loadingPrice.value"
      :num-ctns="modal.numCtns.value"
      :total-amount="modal.totalAmount.value"
      :can-advance="modal.canAdvance.value"
      :purchasing="modal.purchasing.value"
      :exchange-rate="rate"
      :is-player="modal.isPlayer.value"
      :can-customize="modal.canCustomize.value"
      :customize-card="modal.customizeCard.value"
      :custom-card-numbers="modal.customCardNumbers.value"
      :confirm-incomplete-custom-card="modal.confirmIncompleteCustomCard"
      @close="modal.close()"
      @select-country="modal.selectCountry($event)"
      @next="modal.nextStep()"
      @prev="modal.prevStep()"
      @increment="modal.increment()"
      @decrement="modal.decrement()"
      @set-ctns="modal.setNumCtns($event)"
      @toggle-customize="modal.toggleCustomize()"
      @update:custom-card-numbers="modal.customCardNumbers.value = $event"
      @purchase="modal.purchase(result => onPurchaseSuccess(modal.selectedRoom.value!.id, result))"
    />

  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════
   PÁGINA
═══════════════════════════════════════════════════════ */
.lby-page {
  flex: 1; width: 100%; box-sizing: border-box;
  min-height: calc(100vh - 56px - 44px);
  margin: -0.5rem -0.5rem 0; padding: 18px 20px 36px;
  display: flex; flex-direction: column;
  position: relative; isolation: isolate;
  background: linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}

/* estrellas finas */
.lby-page::before {
  content: ""; position: absolute; inset: 0; z-index: -2; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  3%  4%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(1px 1px at  9% 14%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 16%  2%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 22% 28%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 29%  9%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 36% 18%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 42%  5%, rgba(255,255,255,0.88) 0%, transparent 100%),
    radial-gradient(1px 1px at 50% 22%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  8%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 63% 32%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1px 1px at 70%  3%, rgba(255,255,255,0.86) 0%, transparent 100%),
    radial-gradient(1px 1px at 77% 18%, rgba(255,255,255,0.68) 0%, transparent 100%),
    radial-gradient(1px 1px at 84%  7%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 91% 25%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 97% 12%, rgba(255,255,255,0.84) 0%, transparent 100%),
    radial-gradient(1px 1px at  5% 42%, rgba(255,255,255,0.62) 0%, transparent 100%),
    radial-gradient(1px 1px at 21% 67%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at 35% 72%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 51% 80%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 67% 62%, rgba(255,255,255,0.56) 0%, transparent 100%),
    radial-gradient(1px 1px at 81% 50%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 94% 85%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 11% 16%, rgba(190,210,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 26%  6%, rgba(210,185,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 46% 12%, rgba(180,225,255,0.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 66% 19%, rgba(200,255,220,0.82) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 85% 11%, rgba(255,240,180,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 38% 60%, rgba(190,210,255,0.78) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 72% 52%, rgba(255,200,200,0.72) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 55% 76%, rgba(200,225,255,0.86) 0%, transparent 100%);
  animation: lbyTwinkleA 7s ease-in-out infinite alternate;
}

/* estrellas grandes */
.lby-page::after {
  content: ""; position: absolute; inset: 0; z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at  8% 12%, rgba(255,255,255,0.96) 0%, transparent 100%),
    radial-gradient(2px 2px at 24% 35%, rgba(200,215,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 40%  6%, rgba(255,255,255,0.84) 0%, transparent 100%),
    radial-gradient(2px 2px at 58% 22%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 74% 15%, rgba(180,200,255,0.86) 0%, transparent 100%),
    radial-gradient(2px 2px at 90% 38%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at 15% 65%, rgba(200,255,240,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 50% 55%, rgba(220,200,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 80% 72%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 35% 82%, rgba(200,220,255,0.80) 0%, transparent 100%),
    radial-gradient(2px 2px at 68% 88%, rgba(255,250,200,0.84) 0%, transparent 100%),
    radial-gradient(3px 3px at 19% 20%, rgba(255,255,255,1.00) 0%, rgba(210,230,255,0.20) 55%, transparent 100%),
    radial-gradient(3px 3px at 44% 14%, rgba(220,200,255,0.96) 0%, rgba(185,165,255,0.18) 55%, transparent 100%),
    radial-gradient(3px 3px at 69% 42%, rgba(255,255,255,0.94) 0%, rgba(225,245,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 88% 60%, rgba(200,240,255,0.98) 0%, rgba(160,220,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 30% 78%, rgba(255,240,160,0.90) 0%, rgba(255,220,100,0.12) 55%, transparent 100%),
    radial-gradient(4px 4px at 55% 30%, rgba(255,255,255,1.00) 0%, rgba(205,225,255,0.28) 50%, transparent 100%),
    radial-gradient(4px 4px at 12% 45%, rgba(255,248,200,0.95) 0%, rgba(255,240,160,0.18) 50%, transparent 100%);
  animation: lbyTwinkleB 9s ease-in-out infinite alternate-reverse;
}

@keyframes lbyTwinkleA { 0%{opacity:.55} 40%{opacity:.95} 70%{opacity:.70} 100%{opacity:.85} }
@keyframes lbyTwinkleB { 0%{opacity:.70} 35%{opacity:.50} 65%{opacity:1.00} 100%{opacity:.65} }

/* loading */
.lby-loading { flex:1; display:flex; align-items:center; justify-content:center; min-height:60vh; }
.lby-spinner {
  width:32px; height:32px;
  border:2px solid rgba(100,140,255,.15); border-top-color:#2af598;
  border-radius:50%; animation:lbySpin 1s linear infinite;
}
@keyframes lbySpin { to { transform: rotate(360deg); } }

/* shell */
.lby-shell {
  position: relative; z-index: 1;
  width: 100%; max-width: 860px; margin: 0 auto; flex: 1;
  display: flex; flex-direction: column; justify-content: center; gap: 10px;
  animation: lbyFadeUp .45s cubic-bezier(.22,1,.36,1) both;
}
@keyframes lbyFadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

/* FAB */
.lby-rules-fab {
  position: fixed; right: 20px; bottom: 69px; z-index: 200;
  width: 42px; height: 42px; border-radius: 50%;
  border: 1px solid rgba(42,245,152,.28);
  background: rgba(4,8,20,.88); color: #2af598; font-size: 14px;
  cursor: pointer; backdrop-filter: blur(14px);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 22px rgba(0,0,0,.55);
  transition: transform .2s, box-shadow .2s, border-color .2s, background .2s;
  animation: lbyFabPulse 3.5s ease-in-out infinite;
}
.lby-rules-fab:hover {
  transform: translateY(-2px) scale(1.08); background: rgba(4,18,10,.95);
  border-color: rgba(42,245,152,.6);
  box-shadow: 0 6px 26px rgba(0,0,0,.6), 0 0 20px rgba(42,245,152,.25);
  animation: none;
}
.lby-rules-fab:active { transform: scale(.94); }
@keyframes lbyFabPulse {
  0%,100% { box-shadow: 0 4px 22px rgba(0,0,0,.55), 0 0 0 0 rgba(42,245,152,.3); }
  50%     { box-shadow: 0 4px 22px rgba(0,0,0,.55), 0 0 0 7px rgba(42,245,152,.0); }
}

@media (max-width: 480px) {
  .lby-page { padding: 10px 10px 28px; }
  .lby-rules-fab { right: 14px; bottom: 18px; width: 38px; height: 38px; font-size: 13px; }
}
</style>
