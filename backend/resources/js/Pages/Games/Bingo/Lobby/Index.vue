<script setup lang="ts">
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import type { Ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import backendClient from '@/js/plugins/axios'
import BingoPurchaseModalWrapper from '@js/Components/games/bingo/Card/PurchaseModal/BingoPurchaseModalWrapper.vue'
import BingoRulesModal   from '@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue'
import LobbyHero          from '@js/Components/games/bingo/Room/Lobby/LobbyHero.vue'
import LobbyCardsProgress from '@js/Components/games/bingo/Room/Lobby/LobbyCardsProgress.vue'
import LobbyStatsPanel    from '@js/Components/games/bingo/Room/Lobby/LobbyStatsPanel.vue'
import LobbyPlayersModal  from '@js/Components/games/bingo/Room/Lobby/LobbyPlayersModal.vue'
import LobbyMyCardsModal  from '@js/Components/games/bingo/Room/Lobby/LobbyMyCardsModal.vue'
import LobbyToasts        from '@js/Components/games/bingo/Room/Lobby/LobbyToasts.vue'
import LobbyRedirectBanner from '@js/Components/games/bingo/Room/Lobby/LobbyRedirectBanner.vue'
import LobbyAccessModal   from '@js/Components/games/bingo/Room/Lobby/LobbyAccessModal.vue'
import { useGameRoomPurchaseModal } from '@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal'
import { useLobbyEcho }          from '@js/composables/games/bingo/room/lobby/useLobbyEcho'
import { useLobbyCountdown }     from '@js/composables/games/bingo/room/lobby/useLobbyCountdown'
import { useLobbyToasts }        from '@js/composables/games/bingo/room/lobby/useLobbyToasts'
import { useLobbyPlayers }       from '@js/composables/games/bingo/room/lobby/useLobbyPlayers'
import { useLobbyRedirect }      from '@js/composables/games/bingo/room/lobby/useLobbyRedirect'
import { useLobbyShare }         from '@js/composables/games/bingo/room/lobby/useLobbyShare'
import { useLobbyMyCards }       from '@js/composables/games/bingo/room/lobby/useLobbyMyCards'
import { useLobbyNotification }  from '@js/composables/games/bingo/room/lobby/useLobbyNotification'
import { useLobbyLastSale }      from '@js/composables/games/bingo/room/lobby/useLobbyLastSale'
import type { LobbyRoom } from '@js/Components/games/bingo/Room/Lobby/types'
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

const liveFullHousePrize = ref<number | null>(props.room?.full_house_prize ?? null)
const liveSerialPrize    = ref<number | null>(props.room?.serial_prize    ?? null)

// Sala privada: el primer render solo trae el payload mínimo (is_private/
// is_access_verified) — cuando router.reload() trae los datos reales tras
// verificar, hay que resincronizar el estado `live` que se inicializó vacío.
watch(() => props.room?.is_access_verified, (verified, wasVerified) => {
  if (verified && !wasVerified) {
    live.prizePool      = props.room?.prize_pool    ?? 0
    live.totalPlayers    = props.room?.total_players ?? 0
    live.totalCards       = props.room?.total_cards   ?? 0
    live.statusCode      = props.room?.status_code   ?? ''
    live.cardPrice        = props.room?.card_price    ?? props.room?.card_price_usd ?? 0
    live.currencySymbol  = props.room?.currency_symbol ?? 'USD'
    liveFullHousePrize.value = props.room?.full_house_prize ?? null
    liveSerialPrize.value    = props.room?.serial_prize    ?? null
  }
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

// ── Computed (deben ir antes de los composables que los usen) ──
const rate         = computed(() => props.exchangeRate ?? 1)
const roomRouteKey = computed(() => props.roomRouteKey?.trim() ?? '')

// ── Composables ──
const { saleToasts, pushSaleToast }    = useLobbyToasts()
const { countdown, isLive, isUrgent }  = useLobbyCountdown(() => props.room?.unix_date)
const { recordSale, lastSaleLabel }    = useLobbyLastSale()
const shareName = computed(() =>
  props.room?.game_mode_description
    ? `${props.room.game_mode_description} · ${props.room.uuid_code ?? ''}`
    : (props.room?.uuid_code ?? 'Bingo')
)
const { share, copied }                = useLobbyShare(shareName.value)
const notification                     = useLobbyNotification(
  props.room?.uuid_code ?? 'Bingo',
  () => `/bingo/${roomRouteKey.value}/game`,
)

const userId = computed(() => (page.props.auth as any)?.user?.id ?? null)
const { wsStatus } = useLobbyEcho(props.room.id, () => userId.value, live, {
  onPrizeChanged:   () => flash(prizeFlash),
  onPlayersChanged: (prev, next) => { if (next > prev) flash(playersFlash) },
  onCardsChanged:   (total) => { pushSaleToast(total); recordSale() },
  onCardsUpdated:   (price, symbol) => {
    if (price  !== null) live.cardPrice      = price
    if (symbol !== null) live.currencySymbol = symbol
  },
  onGameStarted: () => notification.notify(),
  onSubPrizesChanged: (fhp, sp) => {
    if (fhp !== undefined) liveFullHousePrize.value = fhp
    if (sp  !== undefined) liveSerialPrize.value    = sp
  },
})

const players = useLobbyPlayers(props.roomRouteKey, () => live.totalPlayers)
const { redirecting, redirectSecs, cancel: cancelRedirect } = useLobbyRedirect(props.roomRouteKey, () => live.statusCode)

// ── Modals ──
const showRulesModal = ref(false)
const modal      = useGameRoomPurchaseModal()
const myCards    = useLobbyMyCards(props.roomRouteKey)

// ── Acceso a sala privada ──
// Si la sala es privada y no fue verificada aún en esta sesión (is_access_verified
// resuelto server-side), se bloquea el lobby con un modal de código/contraseña
// antes de mostrar cualquier dato de la sala. La verificación real ocurre en el
// backend (POST verify-access); esto es solo la puerta de entrada en la UI —
// la defensa real está en buyFromGameRooms/playerBuyFromGameRooms (403 si no
// verificada), así que aunque se salte esta pantalla no se puede comprar.
// computed (no ref) porque router.reload() actualiza props.room in-place tras
// verificar — un ref inicializado una sola vez no reaccionaría a ese cambio.
const accessVerified   = computed(() => props.room?.is_private ? (props.room?.is_access_verified ?? false) : true)
const showAccessModal  = computed(() => !accessVerified.value)
const verifyingAccess  = ref(false)
const accessError      = ref<string | null>(null)

async function handleVerifyAccess(accessCode: string, password: string) {
  verifyingAccess.value = true
  accessError.value = null
  try {
    const { data } = await backendClient.post<{ verified: boolean }>(
      `/bingo-rooms/${props.room.id}/verify-access`,
      { access_code: accessCode, password }
    )
    if (data.verified) {
      // El backend recién ahora entrega los datos reales de la sala (premio,
      // jugadores, precio, etc.) — hasta este punto solo viajó el payload
      // mínimo (is_private/is_access_verified), así que se recarga la página
      // completa vía Inertia para obtenerlos con la sesión ya verificada.
      router.reload()
    } else {
      accessError.value = 'Código o contraseña incorrectos.'
    }
  } catch {
    accessError.value = 'No se pudo verificar el acceso. Intenta de nuevo.'
  } finally {
    verifyingAccess.value = false
  }
}

function handleLeaveAccessModal() {
  window.location.href = '/bingo/game-rooms'
}

// Cierra el modal de compra si el sorteo arranca mientras está abierto
watch(() => live.statusCode, (code) => {
  if (code?.toUpperCase() === 'IN_PROGRESS' && modal.isOpen.value) modal.close()
})

const canNavigateGame = computed(() => roomRouteKey.value.length > 0)

// Cupo de sala (max_players): BingoRoomCapacityGuard::assertHasCapacity() solo
// se invoca para jugadores NUEVOS (ver BingoCardGenerationService, condicionado
// a $isNewRoomUser) — un jugador que ya tiene cartones en la sala puede seguir
// comprando aunque el cupo de jugadores esté lleno. Replicar la misma distinción
// aquí evita bloquear al usuario equivocado en la UI.
const roomIsFull = computed(() =>
  props.room?.max_players != null &&
  live.userCards === 0 &&
  live.totalPlayers >= props.room.max_players
)

const canPurchaseCards = computed(() =>
  props.room != null &&
  live.statusCode?.toUpperCase() === 'ACTIVE' &&
  !props.room.close_room &&
  !roomIsFull.value
)

const minCardsToStart = computed(() => props.room?.min_cards_to_start ?? null)
const maxCardsToStart = computed(() => props.room?.max_cards_to_start ?? null)

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
    players_room:    [{ card_count: live.userCards }],
    config: props.room.unix_date ? {
      room_id:         props.room.id,
      card_price_usd:  props.room.card_price_usd ?? 0,
      // TODO: LobbyRoom no expone el código del tipo de bote — el backend del lobby
      // debe agregarlo (ver fix aplicado en BingoGameRoomsDTO::toView para game-rooms).
      prize_pool_type: null,
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

function onPurchaseSuccess(_roomId: string, result: { prizePool: number; cardCount: number }) {
  live.prizePool  = result.prizePool
  live.userCards += result.cardCount
  flash(prizeFlash)
}
</script>

<template>
  <div class="lby-page">

    <!-- FAB: reglamento — anclado a .lby-page (no Teleport/fixed): con
         Teleport+fixed quedaba posicionado respecto al viewport completo
         en vez del área de contenido con scroll propio, igual que el bug
         corregido en GameRooms.vue (podía solaparse con el footer, cuya
         altura es variable en móvil). -->
    <button
      type="button"
      class="lby-rules-fab"
      title="Ver reglamento del Bingo"
      aria-label="Ver reglamento del Bingo"
      @click="showRulesModal = true"
    >
      <i class="fas fa-book-open" />
    </button>

    <!-- Loading -->
    <div v-if="!room" class="lby-loading">
      <div class="lby-spinner" />
    </div>

    <!-- Sala privada sin verificar: no se muestra ningún dato hasta ingresar código/contraseña -->
    <div v-else-if="showAccessModal" class="lby-loading">
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
          :share-copied="copied"
          :full-house-prize="liveFullHousePrize"
          :serial-prize="liveSerialPrize"
          @share="share()"
        />

        <LobbyCardsProgress
          v-if="showCardsProgress"
          :total-cards="live.totalCards"
          :min-cards-to-start="minCardsToStart"
          :max-cards-to-start="maxCardsToStart"
          :progress-pct="cardsProgressPct"
          :ready="cardsProgressReady"
        />

        <LobbyStatsPanel
          :room="room"
          :live="live"
          :room-route-key="roomRouteKey"
          :can-navigate-game="canNavigateGame"
          :can-purchase-cards="canPurchaseCards"
          :room-is-full="roomIsFull"
          :redirecting="redirecting"
          :players-flash="playersFlash"
          :min-cards-to-start="minCardsToStart"
          :cards-progress-ready="cardsProgressReady"
          :last-sale-label="lastSaleLabel"
          :notification-permission="notification.permission.value"
          @open-players="players.open()"
          @open-purchase="openPurchase"
          @open-my-cards="myCards.open()"
          @request-notification="notification.requestPermission()"
        />
      </div>
    </template>

    <LobbyToasts :toasts="saleToasts" />

    <LobbyAccessModal
      :show="showAccessModal"
      :loading="verifyingAccess"
      :error="accessError"
      @verify="handleVerifyAccess"
      @leave="handleLeaveAccessModal"
    />

    <LobbyPlayersModal
      :show="players.showModal.value"
      :total-players="live.totalPlayers"
      :players="players.playersList.value"
      :loading="players.loading.value"
      :fetch-error="players.fetchError.value"
      :current-user-id="userId"
      @close="players.close()"
      @retry="players.open()"
    />

    <LobbyMyCardsModal
      :show="myCards.showModal.value"
      :cards="myCards.cards.value"
      :loading="myCards.loading.value"
      :fetch-error="myCards.fetchError.value"
      @close="myCards.close()"
      @retry="myCards.refresh()"
    />

    <LobbyRedirectBanner :show="redirecting" :secs="redirectSecs" @cancel="cancelRedirect" />

    <BingoRulesModal
      v-model="showRulesModal"
      :rules="props.bingoRules"
      :image="props.imageBingoRules"
    />

    <BingoPurchaseModalWrapper
      :modal="modal"
      :exchange-rate="rate"
      @success="onPurchaseSuccess"
    />

  </div>
</template>

<style>
/* .mt-2 y #app son ancestros fuera de este componente (vienen de
   auth-layout.blade.php), scoped no los alcanza. Son `display: block` con
   `height: auto`, así que no propagan la altura real de #content-scrollable
   (flex-grow-1) hacia .lby-page — sin esto, min-height:100% no tiene una
   base % válida (ver mismo fix en GameRooms.vue). */
#app:has(> .lby-page),
#slot-wrapper:has(> #app > .lby-page) {
  flex: 1 1 0%;
  min-height: 0;
}
</style>

<style scoped>
/* ═══════════════════════════════════════════════════════
   PÁGINA
═══════════════════════════════════════════════════════ */
.lby-page {
  flex: 1; width: 100%; box-sizing: border-box;
  /* El footer tiene altura variable en móvil (min-height: 0 !important en
     footer.blade.php bajo 576px — el texto puede envolver en 2 líneas y
     superar los 44px "de referencia"). calc(100vh - navbar - footer) asumía
     un footer fijo y se quedaba corto, generando overflow-y en el
     contenedor scrollable padre (#content-scrollable, que ya es
     flex-grow-1 dentro de .wrapper vh-100 y mide el espacio real
     disponible — ver mismo fix en GameRooms.vue). */
  min-height: 100%;
  margin: -0.5rem -0.5rem 0; padding: 18px 20px 36px;
  display: flex; flex-direction: column;
  position: relative; isolation: isolate;
  overflow-x: hidden;
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
  position: absolute; right: 20px; bottom: 24px; z-index: 50;
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
