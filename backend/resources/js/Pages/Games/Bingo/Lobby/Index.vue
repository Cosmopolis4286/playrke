<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, reactive } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { BingoService } from "@js/Services/bingoService";
import BingoPurchaseModal from "@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue";
import { useGameRoomPurchaseModal } from "@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal";
import { useLobbyEcho } from "@js/composables/games/bingo/room/lobby/useLobbyEcho";
import type { BingoGameRoom } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

// ── Props ──
interface LobbyRoom {
  id: string;
  uuid_code?: string;
  status_code?: string;
  prize_pool: number;
  full_house_prize?: number;
  serial_prize?: number;
  total_players: number;
  total_cards: number;
  close_room?: boolean;
  currency_symbol?: string;
  card_price?: number;
  card_price_usd?: number;
  room_type_description?: string;
  game_mode_description?: string;
  unix_date?: number | null;
  date?: string;
  time?: string;
  time_chip?: number;
  rounds?: number;
  in_live?: boolean;
  min_cards_to_start?: number | null;
  game_variant?: string | null;
}

interface Props {
  room: LobbyRoom;
  roomRouteKey?: string;
  userCards?: number;
  exchangeRate?: number;
}

const props = defineProps<Props>();
const page  = usePage();

// ── Live state (mutable, parcheado por WebSocket) ──
const live = reactive({
  prizePool:      props.room?.prize_pool    ?? 0,
  totalPlayers:   props.room?.total_players ?? 0,
  totalCards:     props.room?.total_cards   ?? 0,
  userCards:      props.userCards           ?? 0,
  statusCode:     props.room?.status_code   ?? '',
  cardPrice:      props.room?.card_price    ?? props.room?.card_price_usd ?? 0,
  currencySymbol: props.room?.currency_symbol ?? 'USD',
});

// ── Flash animations ──
const prizeFlash   = ref(false);
const playersFlash = ref(false);

function flash(target: ReturnType<typeof ref<boolean>>) {
  target.value = true;
  setTimeout(() => { target.value = false; }, 1800);
}

// ── Countdown ──
const countdown = ref('');
const isLive    = ref(false);
const isUrgent  = ref(false);
let countdownTimer: ReturnType<typeof setInterval> | null = null;

function startCountdown() {
  if (!props.room?.unix_date) return;
  updateCountdown();
  countdownTimer = setInterval(updateCountdown, 1000);
}

function updateCountdown() {
  const unixDate = props.room?.unix_date;
  if (!unixDate) { countdown.value = ''; return; }
  const diff = unixDate - Math.floor(Date.now() / 1000);
  if (diff <= 0) {
    isLive.value = true;
    isUrgent.value = false;
    countdown.value = 'EN VIVO';
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
    return;
  }
  isLive.value   = false;
  isUrgent.value = diff < 300;
  const h = Math.floor(diff / 3600);
  const m = Math.floor((diff % 3600) / 60);
  const s = diff % 60;
  countdown.value = h > 0
    ? `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
    : `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

// ── WebSocket (composable limpio) ──
const userId = computed(() => page.props.auth?.user?.id ?? null);

const { wsStatus } = useLobbyEcho(
  props.room.id,
  userId.value,
  live,
  {
    onPrizeChanged:   () => flash(prizeFlash),
    onPlayersChanged: () => flash(playersFlash),
    onCardsUpdated: (cardPrice, currencySymbol) => {
      if (cardPrice      !== null) live.cardPrice      = cardPrice;
      if (currencySymbol !== null) live.currencySymbol = currencySymbol;
    },
  },
);

// ── Purchase modal ──
const modal = useGameRoomPurchaseModal();

const bingoRoomImg = '/img/games/bingo/estelar_fd.png';

// Adaptador LobbyRoom → BingoGameRoom para el modal (reactivo a live)
const roomAsGameRoom = computed((): BingoGameRoom => ({
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
}));

// ── Computed ──
const roomRouteKey = computed(() =>
  props.roomRouteKey?.trim() || props.room?.id?.toString() || ''
);

const canNavigateGame = computed(() => Boolean(props.roomRouteKey?.trim()));

const roomStatusText = computed(() =>
  BingoService.getRoomStatusText(live.statusCode || (props.room?.status_code ?? ''))
);

const isRoomLoading = computed(() => !props.room);

const canPurchaseCards = computed(() =>
  props.room != null &&
  live.statusCode?.toUpperCase() === 'ACTIVE' &&
  !props.room.close_room
);

const statusClass = computed(() => {
  const c = (live.statusCode || (props.room?.status_code ?? 'desconocido')).toLowerCase();
  return `lby-status--${c}`;
});

const rate = computed(() => props.exchangeRate ?? 1);

const formattedPrize = computed(() =>
  typeof live.prizePool === 'number'
    ? (live.prizePool * rate.value).toFixed(2)
    : '0.00'
);

const wsLabel = computed(() => ({
  connected:    'En vivo',
  connecting:   'Conectando…',
  disconnected: 'Sin conexión',
  error:        'Error',
}[wsStatus.value]));

// ── Progreso de cartones mínimos ──
const minCardsToStart = computed(() => props.room?.min_cards_to_start ?? null);

const showCardsProgress = computed(() =>
  minCardsToStart.value != null && minCardsToStart.value > 0
);

const cardsProgressPct = computed(() => {
  if (!minCardsToStart.value) return 0;
  return Math.min(100, Math.round((live.totalCards / minCardsToStart.value) * 100));
});

const cardsProgressReady = computed(() =>
  minCardsToStart.value != null && live.totalCards >= minCardsToStart.value
);

// ── Lifecycle ──
onMounted(() => {
  startCountdown();
});

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer);
});

// ── Purchase handlers ──
function openPurchase() {
  if (!canPurchaseCards.value) return;
  modal.open(roomAsGameRoom.value);
}

function onPurchaseSuccess(_roomId: string, result: { prizePool: number; ctns: number }) {
  live.prizePool  = result.prizePool;
  live.userCards += result.ctns;
  flash(prizeFlash);
}
</script>

<template>
  <div class="lby-page">

    <!-- Loading -->
    <div v-if="isRoomLoading" class="lby-loading">
      <div class="lby-spinner" />
    </div>

    <template v-else>
      <div class="lby-shell">

        <!-- ── Encabezado ── -->
        <div class="lby-page__header">
          <Link href="/bingo/game-rooms" class="lby-back">
            <i class="fas fa-chevron-left" /> Salas
          </Link>
          <div class="lby-title-wrap">
            <span class="lby-dice lby-dice--1">&#9858;</span>
            <h1 class="lby-page__title">Sala de Bingo</h1>
            <span class="lby-dice lby-dice--2">&#9858;</span>
          </div>
          <div class="lby-ws" :class="`lby-ws--${wsStatus}`">
            <span class="lby-ws__dot" />
            <span class="lby-ws__label">{{ wsLabel }}</span>
          </div>
        </div>

        <!-- ── HERO: banner + premio, ancho completo ── -->
        <div class="lby-hero">
          <img :src="bingoRoomImg" alt="Sala" class="lby-hero__img" />
          <div class="lby-hero__overlay" />

          <div class="lby-hero__top">
            <div class="lby-hero__chips">
              <span class="lby-chip lby-chip--type">
                <i class="fas fa-globe" /> {{ room.room_type_description ?? 'Pública' }}
              </span>
              <span class="lby-chip lby-chip--mode">
                <i class="fas fa-gamepad" /> {{ room.game_mode_description ?? 'Online' }}
              </span>
              <span v-if="room.in_live" class="lby-chip lby-chip--live">
                <span class="lby-live-dot lby-live-dot--sm" /> En Vivo
              </span>
            </div>

            <div class="lby-hero__badges">
              <div
                v-if="countdown"
                class="lby-timer"
                :class="{ 'lby-timer--live': isLive, 'lby-timer--urgent': isUrgent && !isLive }"
              >
                <template v-if="isLive"><span class="lby-live-dot" /> EN VIVO</template>
                <template v-else><i class="fas fa-clock" /> {{ countdown }}</template>
              </div>
              <span class="lby-status-chip" :class="statusClass">{{ roomStatusText }}</span>
            </div>
          </div>

          <div class="lby-hero__bottom">
            <div class="lby-prize" :class="{ 'lby-flash': prizeFlash }">
              <span class="lby-prize__lbl">
                <i class="fas fa-trophy" /> Premio Acumulado
              </span>
              <div class="lby-prize__val">
                <span class="lby-prize__num">{{ formattedPrize }}</span>
                <span class="lby-prize__sym">{{ live.currencySymbol }}</span>
              </div>
            </div>

            <div v-if="room.full_house_prize || room.serial_prize" class="lby-prize-sub">
              <div v-if="room.full_house_prize" class="lby-prize-sub__item">
                <span class="lby-prize-sub__lbl">Casa Llena</span>
                <span class="lby-prize-sub__val">{{ (Number(room.full_house_prize) * rate).toFixed(2) }}</span>
              </div>
              <div v-if="room.serial_prize" class="lby-prize-sub__item">
                <span class="lby-prize-sub__lbl">Serial</span>
                <span class="lby-prize-sub__val">{{ (Number(room.serial_prize) * rate).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── PROGRESO CARTONES MÍNIMOS ── -->
        <div
          v-if="showCardsProgress"
          class="lby-cards-progress"
          :class="{ 'lby-cards-progress--ready': cardsProgressReady }"
        >
          <div class="lby-cards-progress__top">
            <span class="lby-cards-progress__label">
              <i class="fas fa-layer-group" />
              {{ cardsProgressReady ? '¡Listo para iniciar!' : 'Cartones para iniciar' }}
            </span>
            <span class="lby-cards-progress__count">{{ live.totalCards }} / {{ minCardsToStart }}</span>
          </div>
          <div class="lby-cards-progress__track">
            <div class="lby-cards-progress__fill" :style="{ width: cardsProgressPct + '%' }" />
          </div>
        </div>

        <!-- ── STATS + ACCIONES, ancho completo ── -->
        <div class="lby-bottom">
          <div class="lby-stats">
            <div class="lby-stat" :class="{ 'lby-flash': playersFlash }">
              <i class="fas fa-users lby-stat__icon" />
              <span class="lby-stat__val">{{ live.totalPlayers }}</span>
              <span class="lby-stat__lbl">Jugadores</span>
            </div>
            <div class="lby-stat">
              <i class="fas fa-ticket-alt lby-stat__icon" />
              <span class="lby-stat__val">{{ live.userCards }}</span>
              <span class="lby-stat__lbl">Mis cartones</span>
            </div>
            <div v-if="live.cardPrice" class="lby-stat">
              <i class="fas fa-dollar-sign lby-stat__icon" />
              <span class="lby-stat__val">{{ Number(live.cardPrice).toFixed(2) }}</span>
              <span class="lby-stat__lbl">Precio ({{ live.currencySymbol }})</span>
            </div>
            <div v-if="room.rounds" class="lby-stat">
              <i class="fas fa-layer-group lby-stat__icon" />
              <span class="lby-stat__val">{{ room.rounds }}</span>
              <span class="lby-stat__lbl">Rondas</span>
            </div>
            <div v-if="room.time_chip" class="lby-stat">
              <i class="fas fa-stopwatch lby-stat__icon" />
              <span class="lby-stat__val">{{ room.time_chip }}s</span>
              <span class="lby-stat__lbl">Ficha</span>
            </div>
          </div>

          <div class="lby-actions">
            <Link
              v-if="canNavigateGame"
              :href="`/bingo/${roomRouteKey}/game`"
              class="lby-btn lby-btn--play"
            >
              <i class="fas fa-sign-in-alt" /> Entrar al Juego
            </Link>
            <button v-else class="lby-btn lby-btn--disabled" disabled>
              <i class="fas fa-sign-in-alt" /> Entrar al Juego
            </button>

            <button v-if="canPurchaseCards" class="lby-btn lby-btn--buy" @click="openPurchase">
              <i class="fas fa-shopping-cart" />
              {{ live.userCards > 0 ? 'Más cartones' : 'Comprar Cartones' }}
            </button>
          </div>
        </div>

        <p v-if="!canNavigateGame" class="lby-warn">
          <i class="fas fa-exclamation-triangle" /> Sala sin clave válida — recarga la página.
        </p>

      </div>
    </template>

    <!-- Modal de compra -->
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
   PÁGINA — mismo fondo estelar que /bingo/game-rooms
═══════════════════════════════════════════════════════ */
.lby-page {
  flex: 1;
  min-height: calc(100vh - 56px - 41px + 0.5rem);
  margin: -0.5rem -0.5rem 0;
  padding: 24px 20px 48px;
  position: relative;
  isolation: isolate;
  background: linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}

/* ── Capa 1: estrellas finas ── */
.lby-page::before {
  content: "";
  position: absolute; inset: 0; z-index: -2; pointer-events: none;
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

/* ── Capa 2: estrellas grandes con halo ── */
.lby-page::after {
  content: "";
  position: absolute; inset: 0; z-index: -1; pointer-events: none;
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

@keyframes lbyTwinkleA {
  0%   { opacity: 0.55; } 40%  { opacity: 0.95; }
  70%  { opacity: 0.70; } 100% { opacity: 0.85; }
}
@keyframes lbyTwinkleB {
  0%   { opacity: 0.70; } 35%  { opacity: 0.50; }
  65%  { opacity: 1.00; } 100% { opacity: 0.65; }
}

/* ═══════════════════════════════════
   LOADING
═══════════════════════════════════ */
.lby-loading {
  display: flex; align-items: center; justify-content: center;
  min-height: 60vh;
}
.lby-spinner {
  width: 32px; height: 32px;
  border: 2px solid rgba(100,140,255,.15); border-top-color: #7b4fff;
  border-radius: 50%; animation: lbySpin 1s linear infinite;
}
@keyframes lbySpin { to { transform: rotate(360deg); } }

/* ═══════════════════════════════════
   SHELL — ocupa todo el ancho disponible, sin tope artificial bajo
   1440px (pantallas de gestión típicas); en monitores muy anchos se
   limita para no estirar las cards a un tamaño absurdo.
═══════════════════════════════════ */
.lby-shell {
  position: relative; z-index: 1;
  width: 100%; max-width: 1440px;
  margin: 0 auto;
  display: flex; flex-direction: column; gap: 18px;
  animation: lbyFadeUp .5s cubic-bezier(.22,1,.36,1) both;
}
@keyframes lbyFadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════════
   ENCABEZADO
═══════════════════════════════════ */
.lby-page__header {
  position: relative;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 4px;
}

.lby-back {
  position: absolute; left: 0; top: 50%; transform: translateY(-50%);
  display: inline-flex; align-items: center; gap: 6px;
  color: rgba(255,255,255,.55); font-size: 12px; font-weight: 700;
  text-decoration: none; letter-spacing: .3px; transition: color .2s;
  z-index: 2;
}
.lby-back:hover { color: #a8c4ff; }
.lby-back i { font-size: 10px; }

.lby-title-wrap {
  display: flex; align-items: center; justify-content: center; gap: 14px;
}
.lby-dice {
  font-size: 22px;
  filter: drop-shadow(0 0 10px rgba(255,184,77,0.5));
  animation: lbySpinDice 8s linear infinite;
  color: #ffb84d;
}
.lby-dice--2 { animation-direction: reverse; animation-duration: 10s; color: #7b4fff; }
@keyframes lbySpinDice { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.lby-page__title {
  font-family: "Anton", sans-serif;
  font-size: clamp(22px, 3.5vw, 30px);
  letter-spacing: 3.5px; margin: 0;
  color: #e0eaff;
  text-shadow: 0 0 40px rgba(255,184,77,0.15), 0 0 80px rgba(123,79,255,0.12);
  background: linear-gradient(135deg, #fff3d6 0%, #e0eaff 45%, #a8c4ff 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.lby-ws {
  position: absolute; right: 0; top: 50%; transform: translateY(-50%);
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px; border-radius: 20px;
  font-size: 10px; font-weight: 700;
  background: rgba(0,0,0,.4); border: 1px solid rgba(255,255,255,.1);
  backdrop-filter: blur(6px);
  z-index: 2;
}
.lby-ws__dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.lby-ws__label { color: #5578bb; }
.lby-ws--connected   .lby-ws__dot { background: #2af598; box-shadow: 0 0 5px rgba(42,245,152,.7); animation: lbyDotPulse 2s infinite; }
.lby-ws--connected   .lby-ws__label { color: #2af598; }
.lby-ws--connecting  .lby-ws__dot { background: #ffc336; animation: lbyDotBlink .8s infinite; }
.lby-ws--connecting  .lby-ws__label { color: #ffc336; }
.lby-ws--disconnected .lby-ws__dot, .lby-ws--error .lby-ws__dot { background: #ff4b6d; }
.lby-ws--disconnected .lby-ws__label, .lby-ws--error .lby-ws__label { color: #ff4b6d; }
@keyframes lbyDotPulse { 0%,100%{opacity:1} 50%{opacity:.35} }
@keyframes lbyDotBlink  { 0%,100%{opacity:1} 50%{opacity:.1}  }

/* ═══════════════════════════════════
   HERO — banner + premio integrados, ancho completo
═══════════════════════════════════ */
.lby-hero {
  position: relative; border-radius: 22px; overflow: hidden;
  border: 1px solid rgba(255,184,77,.22);
  box-shadow: 0 24px 70px rgba(0,0,0,.65), 0 0 0 1px rgba(123,79,255,.08) inset, 0 0 50px rgba(80,60,160,.1);
  min-height: 280px;
  display: flex; flex-direction: column; justify-content: space-between;
  transition: box-shadow .3s, border-color .3s;
}
.lby-hero:hover {
  border-color: rgba(255,184,77,.4);
  box-shadow: 0 28px 80px rgba(0,0,0,.7), 0 0 0 1px rgba(255,184,77,.14) inset, 0 0 64px rgba(255,184,77,.1);
}
.lby-hero__img {
  position: absolute; inset: 0;
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .6s ease;
}
.lby-hero:hover .lby-hero__img { transform: scale(1.04); }
.lby-hero__overlay {
  position: absolute; inset: 0;
  background:
    linear-gradient(180deg, rgba(6,10,28,.55) 0%, rgba(6,10,28,.35) 35%, rgba(6,10,28,.78) 75%, rgba(6,10,28,.95) 100%),
    radial-gradient(ellipse 60% 80% at 15% 100%, rgba(123,79,255,.28) 0%, transparent 60%);
}

.lby-hero__top {
  position: relative; z-index: 2;
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 12px; flex-wrap: wrap;
  padding: 18px 22px;
}
.lby-hero__chips { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.lby-hero__badges { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.lby-timer {
  background: rgba(8,14,40,.85); border: 1px solid rgba(255,184,77,.35);
  backdrop-filter: blur(8px); color: #ffd89a;
  border-radius: 30px; padding: 5px 16px;
  font-family: 'Anton', sans-serif; font-size: 15px; letter-spacing: 1px;
  display: inline-flex; align-items: center; gap: 6px;
  white-space: nowrap;
}
.lby-timer i { font-size: 12px; opacity: .7; }
.lby-timer--live   { border-color: rgba(255,70,70,.55); color: #ff8080; animation: lbyPulseLive 1.8s infinite; }
.lby-timer--urgent { border-color: rgba(255,195,54,.45); color: #ffc336; animation: lbyPulseUrgent 1.2s infinite; }
@keyframes lbyPulseLive   { 0%,100%{box-shadow:0 0 0 0 rgba(255,60,60,0)}  50%{box-shadow:0 0 0 4px rgba(255,60,60,.2)} }
@keyframes lbyPulseUrgent { 0%,100%{box-shadow:0 0 0 0 rgba(255,195,54,0)} 50%{box-shadow:0 0 0 4px rgba(255,195,54,.2)} }

.lby-live-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #ff4d4d; box-shadow: 0 0 5px #ff4d4d;
  animation: lbyBlinkDot 1s infinite; flex-shrink: 0;
}
.lby-live-dot--sm { width: 5px; height: 5px; }
@keyframes lbyBlinkDot { 0%,100%{opacity:1} 50%{opacity:.25} }

.lby-status-chip {
  padding: 4px 14px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase;
  white-space: nowrap;
  backdrop-filter: blur(8px);
}
.lby-status--active      { background: rgba(42,245,152,.16); border: 1px solid rgba(42,245,152,.36); color: #2af598; }
.lby-status--in_progress { background: rgba(0,158,253,.16);  border: 1px solid rgba(0,158,253,.36);  color: #7cd4ff; }
.lby-status--pending     { background: rgba(255,195,54,.14); border: 1px solid rgba(255,195,54,.32); color: #ffc336; }
.lby-status--finished    { background: rgba(255,75,109,.14); border: 1px solid rgba(255,75,109,.32); color: #ff4b6d; }
.lby-status--offline     { background: rgba(100,100,120,.12);border: 1px solid rgba(120,130,160,.2); color: #8090aa; }
.lby-status--desconocido { background: rgba(100,140,255,.10);border: 1px solid rgba(100,140,255,.2); color: #7c9dff; }

.lby-chip {
  padding: 4px 11px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: .3px;
  display: inline-flex; align-items: center; gap: 5px;
  white-space: nowrap;
  backdrop-filter: blur(8px);
}
.lby-chip--type { background: rgba(123,79,255,.2); border: 1px solid rgba(123,79,255,.4); color: #b48fff; }
.lby-chip--mode { background: rgba(0,158,253,.15); border: 1px solid rgba(0,158,253,.35); color: #7cd4ff; }
.lby-chip--live { background: rgba(255,75,109,.15); border: 1px solid rgba(255,75,109,.38); color: #ff4b6d; display:inline-flex; align-items:center; gap:5px; }

.lby-hero__bottom {
  position: relative; z-index: 2;
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 24px; flex-wrap: wrap;
  padding: 20px 26px 26px;
}

.lby-prize { display: flex; flex-direction: column; }

.lby-prize__lbl {
  font-size: 11px; color: #ffc94d;
  text-transform: uppercase; letter-spacing: 1.6px;
  display: inline-flex; align-items: center; gap: 6px;
  margin-bottom: 6px;
}
.lby-prize__lbl i { font-size: 11px; }
.lby-prize__val { display: flex; align-items: baseline; gap: 8px; }
.lby-prize__num {
  font-family: 'Anton', sans-serif; font-size: clamp(40px, 5.5vw, 64px); line-height: 1; letter-spacing: .5px;
  background: linear-gradient(135deg, #fff3c4 0%, #ffe566 35%, #ffab00 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 3px 18px rgba(255,180,0,.5));
}
.lby-prize__sym { font-size: 18px; font-weight: 800; color: #ffd060; }

.lby-prize-sub {
  display: flex; gap: 26px;
  padding-bottom: 6px;
}
.lby-prize-sub__item { text-align: right; }
.lby-prize-sub__lbl { display: block; font-size: 9px; color: #9fb3da; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 2px; }
.lby-prize-sub__val { font-family: 'Anton', sans-serif; font-size: 18px; color: #d8e4ff; }

.lby-flash { animation: lbyFlash 1.8s ease-in-out; }
@keyframes lbyFlash { 0%,100%{filter:none} 40%{filter:drop-shadow(0 0 12px rgba(42,245,152,.8)) brightness(1.25)} }

/* ═══════════════════════════════════
   PROGRESO CARTONES MÍNIMOS
═══════════════════════════════════ */
.lby-cards-progress {
  background: linear-gradient(160deg, #0d1635 0%, #080f28 100%);
  border: 1px solid rgba(100,140,255,.18);
  border-radius: 16px;
  padding: 14px 20px 16px;
  position: relative; overflow: hidden;
  box-shadow: 0 6px 24px rgba(0,0,0,.4);
  transition: border-color .3s, box-shadow .3s;
}
.lby-cards-progress::before {
  content: ''; position: absolute; top: -24px; right: -10px; width: 160px; height: 90px;
  background: radial-gradient(ellipse, rgba(0,158,253,.18), transparent 70%);
  pointer-events: none;
}
.lby-cards-progress__top {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 9px; position: relative; z-index: 1;
}
.lby-cards-progress__label {
  font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  color: #7c9dff; display: inline-flex; align-items: center; gap: 7px;
}
.lby-cards-progress__label i { font-size: 11px; opacity: .8; }
.lby-cards-progress__count {
  font-family: 'Anton', sans-serif; font-size: 16px; letter-spacing: .5px; color: #d8e4ff;
}
.lby-cards-progress__track {
  height: 9px; border-radius: 20px; overflow: hidden;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(100,140,255,.1);
  position: relative; z-index: 1;
}
.lby-cards-progress__fill {
  height: 100%; border-radius: 20px;
  background: linear-gradient(90deg, #2a6ad8 0%, #2af598 100%);
  box-shadow: 0 0 12px rgba(42,160,255,.45);
  transition: width .6s cubic-bezier(.22,1,.36,1);
}
.lby-cards-progress--ready {
  border-color: rgba(42,245,152,.4);
  box-shadow: 0 6px 28px rgba(42,245,152,.18);
}
.lby-cards-progress--ready .lby-cards-progress__label { color: #2af598; }
.lby-cards-progress--ready .lby-cards-progress__fill {
  background: linear-gradient(90deg, #2af598 0%, #00ffd0 100%);
  animation: lbyCpReadyPulse 1.6s ease-in-out infinite;
}
@keyframes lbyCpReadyPulse {
  0%,100% { box-shadow: 0 0 10px rgba(42,245,152,.5); }
  50%     { box-shadow: 0 0 20px rgba(42,245,152,.85); }
}

/* ═══════════════════════════════════
   FILA INFERIOR — stats + acciones, ancho completo
═══════════════════════════════════ */
.lby-bottom {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 16px;
  align-items: stretch;
}

.lby-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
  gap: 10px;
}
.lby-stat {
  background: linear-gradient(160deg, rgba(14,20,46,.85) 0%, rgba(8,12,32,.7) 100%);
  border: 1px solid rgba(100,140,255,.12);
  border-radius: 14px; padding: 14px 8px;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
  transition: border-color .2s, transform .2s, box-shadow .2s;
}
.lby-stat:hover {
  border-color: rgba(255,184,77,.32);
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0,0,0,.4);
}
.lby-stat__icon { font-size: 15px; color: #3d5280; margin-bottom: 2px; transition: color .2s; }
.lby-stat:hover .lby-stat__icon { color: #ffb84d; }
.lby-stat__val  { font-family: 'Anton', sans-serif; font-size: 19px; color: #d8e4ff; line-height: 1; letter-spacing: .3px; }
.lby-stat__lbl  { font-size: 9px; color: #3d5280; text-transform: uppercase; letter-spacing: .6px; text-align: center; }

/* ═══════════════════════════════════
   ACCIONES
═══════════════════════════════════ */
.lby-actions { display: flex; gap: 10px; }

.lby-btn {
  padding: 0 26px; border: none; border-radius: 14px;
  font-size: 13px; font-weight: 800; letter-spacing: 1.1px; text-transform: uppercase;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 9px;
  text-decoration: none; transition: filter .2s, transform .15s, box-shadow .2s;
  position: relative; overflow: hidden; white-space: nowrap;
  min-width: 168px;
}
.lby-btn::after {
  content: ''; position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
  transform: skewX(-18deg); transition: left .5s ease;
}
.lby-btn:hover::after { left: 130%; }

.lby-btn--play {
  background: linear-gradient(90deg, #7b4fff 0%, #009efd 100%);
  color: #fff; box-shadow: 0 8px 28px rgba(123,79,255,.4), 0 0 0 1px rgba(255,255,255,.06) inset;
}
.lby-btn--play:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 12px 36px rgba(123,79,255,.55), 0 0 0 1px rgba(255,255,255,.1) inset; }

.lby-btn--buy {
  background: linear-gradient(90deg, #ffb84d 0%, #ff7a30 100%);
  color: #2b1200; box-shadow: 0 8px 28px rgba(255,148,30,.32), 0 0 0 1px rgba(255,255,255,.1) inset;
}
.lby-btn--buy:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 12px 36px rgba(255,148,30,.46), 0 0 0 1px rgba(255,255,255,.14) inset; }

.lby-btn--disabled {
  background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.07);
  color: #263550; box-shadow: none; cursor: not-allowed;
}
.lby-btn--disabled::after { display: none; }

.lby-warn {
  font-size: 11px; color: #9a7020; text-align: center; margin: 0;
  display: flex; align-items: center; justify-content: center; gap: 6px;
}

/* ═══════════════════════════════════
   RESPONSIVE
═══════════════════════════════════ */
@media (max-width: 768px) {
  .lby-bottom { grid-template-columns: 1fr; }
  .lby-actions { flex-direction: column; }
  .lby-btn { width: 100%; padding: 15px 14px; }
  .lby-hero__bottom { align-items: flex-start; flex-direction: column; gap: 14px; }
  .lby-prize-sub { gap: 18px; }
  .lby-prize-sub__item { text-align: left; }
}

@media (max-width: 480px) {
  .lby-page { padding: 18px 12px 40px; }
  .lby-page__header { flex-direction: column; gap: 10px; }
  .lby-back, .lby-ws { position: static; transform: none; }
  .lby-hero { min-height: 240px; }
  .lby-hero__top, .lby-hero__bottom { padding-left: 16px; padding-right: 16px; }
  .lby-prize__num { font-size: 38px; }
  .lby-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
