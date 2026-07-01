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
          <div class="lby-ws" :class="`lby-ws--${wsStatus}`">
            <span class="lby-ws__dot" />
            <span class="lby-ws__label">{{ wsLabel }}</span>
          </div>
        </div>

        <!-- ── HERO: banner + premio, ancho completo ── -->
        <div class="lby-hero">
          <!-- Wrapper de recorte separado del contenedor con box-shadow: si
               overflow:hidden viviera en .lby-hero junto al box-shadow, también
               recortaría la sombra del propio elemento (se ve "cortada" en seco). -->
          <div class="lby-hero__clip">
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
                <span v-if="room.rounds" class="lby-chip lby-chip--rounds">
                  <i class="fas fa-rotate" /> {{ room.rounds }} Rondas
                </span>
              </div>

              <div class="lby-hero__badges">
                <div
                  v-if="countdown && !isLive"
                  class="lby-timer"
                  :class="{ 'lby-timer--urgent': isUrgent }"
                >
                  <i class="fas fa-clock" /> {{ countdown }}
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
        </div>

        <!-- ── PANEL INFERIOR: progreso + stats + acciones, compacto ── -->
        <div class="lby-panel">
          <div
            v-if="showCardsProgress"
            class="lby-cards-progress"
            :class="{ 'lby-cards-progress--ready': cardsProgressReady }"
          >
            <span class="lby-cards-progress__label">
              <i class="fas fa-layer-group" />
              {{ cardsProgressReady ? '¡Listo para iniciar!' : 'Cartones para iniciar' }}
            </span>
            <div class="lby-cards-progress__track">
              <div class="lby-cards-progress__fill" :style="{ width: cardsProgressPct + '%' }" />
            </div>
            <span class="lby-cards-progress__count">{{ live.totalCards }} / {{ minCardsToStart }}</span>
          </div>

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
  width: 100%;
  box-sizing: border-box;
  /* navbar 56px + footer 44px (ver footer.blade.php min-height: 44px !important).
     El margin -0.5rem de abajo ya cancela el mt-2 del wrapper padre, así que no
     se resta/suma aparte aquí — sumarlo duplicaba ese espacio y generaba scroll. */
  min-height: calc(100vh - 56px - 44px);
  margin: -0.5rem -0.5rem 0;
  padding: 18px 20px 36px;
  display: flex;
  flex-direction: column;
  position: relative;
  isolation: isolate;
  background: linear-gradient(160deg, #0f1e3a 0%, #0a1428 45%, #070d1c 100%);
}

/* ── Estrellas: una sola capa sutil, discreta ── */
.lby-page::before {
  content: "";
  position: absolute; inset: 0; z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  6%  10%, rgba(220,230,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 18%  28%, rgba(220,230,255,0.35) 0%, transparent 100%),
    radial-gradient(1px 1px at 32%   8%, rgba(220,230,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 47%  34%, rgba(220,230,255,0.30) 0%, transparent 100%),
    radial-gradient(1px 1px at 61%  14%, rgba(220,230,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 76%  30%, rgba(220,230,255,0.35) 0%, transparent 100%),
    radial-gradient(1px 1px at 89%  9%,  rgba(220,230,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 14%  60%, rgba(220,230,255,0.32) 0%, transparent 100%),
    radial-gradient(1px 1px at 40%  72%, rgba(220,230,255,0.40) 0%, transparent 100%),
    radial-gradient(1px 1px at 70%  64%, rgba(220,230,255,0.30) 0%, transparent 100%),
    radial-gradient(1px 1px at 92%  80%, rgba(220,230,255,0.38) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 24% 18%, rgba(150,200,255,0.5) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 55% 50%, rgba(150,200,255,0.4) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 83% 45%, rgba(150,200,255,0.45) 0%, transparent 100%);
  animation: lbyTwinkle 8s ease-in-out infinite alternate;
}

@keyframes lbyTwinkle {
  0%   { opacity: 0.5; }
  100% { opacity: 0.9; }
}

/* ═══════════════════════════════════
   LOADING
═══════════════════════════════════ */
.lby-loading {
  flex: 1;
  display: flex; align-items: center; justify-content: center;
  min-height: 60vh;
}
.lby-spinner {
  width: 32px; height: 32px;
  border: 2px solid rgba(100,140,255,.15); border-top-color: #2af598;
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
  margin: auto auto 0;
  flex: 1;
  display: flex; flex-direction: column; justify-content: center; gap: 12px;
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
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 4px;
}

.lby-back {
  display: inline-flex; align-items: center; gap: 6px;
  color: rgba(255,255,255,.55); font-size: 12px; font-weight: 700;
  text-decoration: none; letter-spacing: .3px; transition: color .2s;
}
.lby-back:hover { color: #a8c4ff; }
.lby-back i { font-size: 10px; }

.lby-ws {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px; border-radius: 20px;
  font-size: 10px; font-weight: 700;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
  backdrop-filter: blur(6px);
}
.lby-ws__dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.lby-ws__label { color: #5578bb; }
.lby-ws--connected   .lby-ws__dot { background: #2af598; box-shadow: 0 0 5px rgba(42,245,152,.7); animation: lbyDotPulse 2s infinite; }
.lby-ws--connected   .lby-ws__label { color: #2af598; }
.lby-ws--connecting  .lby-ws__dot { background: #ffc336; animation: lbyDotBlink .8s infinite; }
.lby-ws--connecting  .lby-ws__label { color: #ffc336; }
.lby-ws--disconnected .lby-ws__dot, .lby-ws--error .lby-ws__dot { background: #ff4d6d; }
.lby-ws--disconnected .lby-ws__label, .lby-ws--error .lby-ws__label { color: #ff4d6d; }
@keyframes lbyDotPulse { 0%,100%{opacity:1} 50%{opacity:.35} }
@keyframes lbyDotBlink  { 0%,100%{opacity:1} 50%{opacity:.1}  }

/* ═══════════════════════════════════
   HERO — banner + premio integrados, ancho completo
═══════════════════════════════════ */
.lby-hero {
  position: relative; border-radius: 16px;
  border: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 14px 40px rgba(0,0,0,.5), 0 1px 0 rgba(255,255,255,.05) inset;
  min-height: 168px;
  transition: box-shadow .3s, border-color .3s;
}
.lby-hero:hover {
  border-color: rgba(42,245,152,.18);
  box-shadow: 0 24px 64px rgba(0,0,0,.6), 0 0 0 1px rgba(42,245,152,.12), 0 0 40px rgba(42,245,152,.06);
}
.lby-hero__clip {
  position: relative;
  border-radius: inherit;
  overflow: hidden;
  min-height: inherit;
  height: 100%;
  display: flex; flex-direction: column; justify-content: space-between;
}
.lby-hero__img {
  position: absolute; inset: 0;
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .6s ease;
}
.lby-hero:hover .lby-hero__img { transform: scale(1.04); }
.lby-hero__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(7,13,28,.6) 0%, rgba(7,13,28,.4) 35%, rgba(7,13,28,.82) 75%, rgba(7,13,28,.96) 100%);
}

.lby-hero__top {
  position: relative; z-index: 2;
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 10px; flex-wrap: wrap;
  padding: 14px 18px;
}
.lby-hero__chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.lby-hero__badges { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.lby-timer {
  background: rgba(10,20,40,.85); border: 1px solid rgba(255,255,255,.12);
  backdrop-filter: blur(8px); color: #9ecaf0;
  border-radius: 30px; padding: 3px 12px;
  font-family: 'Anton', sans-serif; font-size: 13px; letter-spacing: .8px;
  display: inline-flex; align-items: center; gap: 5px;
  white-space: nowrap;
}
.lby-timer i { font-size: 10px; opacity: .7; }
.lby-timer--urgent { border-color: rgba(255,77,109,.4); color: #ff8aaa; animation: lbyPulseUrgent 1.2s infinite; }
@keyframes lbyPulseUrgent { 0%,100%{box-shadow:0 0 0 0 rgba(255,77,109,0)} 50%{box-shadow:0 0 0 4px rgba(255,77,109,.18)} }

.lby-status-chip {
  padding: 3px 12px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase;
  white-space: nowrap;
  backdrop-filter: blur(8px);
}
.lby-status--active      { background: rgba(42,245,152,.14); border: 1px solid rgba(42,245,152,.35); color: #2af598; }
.lby-status--in_progress { background: rgba(255,77,109,.14); border: 1px solid rgba(255,77,109,.35); color: #ff6e8a; }
.lby-status--pending     { background: rgba(255,195,54,.12); border: 1px solid rgba(255,195,54,.3);  color: #ffc336; }
.lby-status--finished    { background: rgba(255,77,109,.1);  border: 1px solid rgba(255,77,109,.25); color: #c0566c; }
.lby-status--offline     { background: rgba(255,255,255,.04);border: 1px solid rgba(255,255,255,.1); color: #6a8aac; }
.lby-status--desconocido { background: rgba(255,255,255,.04);border: 1px solid rgba(255,255,255,.1); color: #6a8aac; }

.lby-chip {
  padding: 3px 9px; border-radius: 6px;
  font-size: 10px; font-weight: 700; letter-spacing: .3px;
  display: inline-flex; align-items: center; gap: 4px;
  white-space: nowrap;
  backdrop-filter: blur(8px);
}
.lby-chip i { font-size: 9px; }
.lby-chip--type   { background: rgba(40,110,190,.14); border: 1px solid rgba(40,110,190,.28); color: #6aaad8; }
.lby-chip--mode   { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: #9fb3da; }
.lby-chip--rounds { background: rgba(130,60,220,.12); border: 1px solid rgba(130,60,220,.26); color: #a878e8; }

.lby-hero__bottom {
  position: relative; z-index: 2;
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 20px; flex-wrap: wrap;
  padding: 12px 18px 16px;
}

.lby-prize { display: flex; flex-direction: column; }

.lby-prize__lbl {
  font-size: 9.5px; color: #c8a028;
  text-transform: uppercase; letter-spacing: 1.4px;
  display: inline-flex; align-items: center; gap: 5px;
  margin-bottom: 2px;
}
.lby-prize__lbl i { font-size: 9.5px; }
.lby-prize__val { display: flex; align-items: baseline; gap: 6px; }
.lby-prize__num {
  font-family: 'Anton', sans-serif; font-size: clamp(28px, 3.6vw, 40px); line-height: 1; letter-spacing: .5px;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 12px rgba(255,150,0,.3));
}
.lby-prize__sym { font-size: 14px; font-weight: 800; color: #e0a020; }

.lby-prize-sub {
  display: flex; gap: 18px;
  padding-bottom: 2px;
}
.lby-prize-sub__item { text-align: right; }
.lby-prize-sub__lbl { display: block; font-size: 8px; color: #6a8aac; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 1px; }
.lby-prize-sub__val { font-family: 'Anton', sans-serif; font-size: 14px; color: #d8e4ff; }

.lby-flash { animation: lbyFlash 1.8s ease-in-out; }
@keyframes lbyFlash { 0%,100%{filter:none} 40%{filter:drop-shadow(0 0 12px rgba(42,245,152,.8)) brightness(1.25)} }

/* ═══════════════════════════════════
   PANEL INFERIOR — agrupa progreso + stats + acciones en un solo bloque
═══════════════════════════════════ */
.lby-panel {
  background: linear-gradient(140deg, #0f1e3a 0%, #070d1c 100%);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 14px;
  padding: 14px 18px;
  display: flex; flex-direction: column; gap: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,.4);
}

/* ── Progreso cartones: una sola fila ── */
.lby-cards-progress {
  display: flex; align-items: center; gap: 12px;
}
.lby-cards-progress__label {
  font-size: 10.5px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
  color: #6a8aac; display: inline-flex; align-items: center; gap: 6px;
  white-space: nowrap; flex-shrink: 0;
}
.lby-cards-progress__label i { font-size: 10.5px; opacity: .8; }
.lby-cards-progress__count {
  font-family: 'Anton', sans-serif; font-size: 13px; letter-spacing: .4px; color: #9ecaf0;
  flex-shrink: 0;
}
.lby-cards-progress__track {
  flex: 1;
  height: 7px; border-radius: 20px; overflow: hidden;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.06);
  position: relative;
}
.lby-cards-progress__fill {
  height: 100%; border-radius: 20px;
  background: linear-gradient(90deg, #2a6ad8 0%, #2af598 100%);
  box-shadow: 0 0 10px rgba(42,245,152,.3);
  transition: width .6s cubic-bezier(.22,1,.36,1);
}
.lby-cards-progress--ready .lby-cards-progress__label { color: #2af598; }
.lby-cards-progress--ready .lby-cards-progress__count { color: #2af598; }
.lby-cards-progress--ready .lby-cards-progress__fill {
  background: linear-gradient(90deg, #2af598 0%, #00ffd0 100%);
  animation: lbyCpReadyPulse 1.6s ease-in-out infinite;
}
@keyframes lbyCpReadyPulse {
  0%,100% { box-shadow: 0 0 8px rgba(42,245,152,.45); }
  50%     { box-shadow: 0 0 16px rgba(42,245,152,.8); }
}

/* ═══════════════════════════════════
   FILA INFERIOR — stats + acciones
═══════════════════════════════════ */
.lby-bottom {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: stretch;
}

.lby-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
  gap: 8px;
}
.lby-stat {
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 10px; padding: 8px 6px;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
  transition: border-color .2s, transform .2s, box-shadow .2s;
}
.lby-stat:hover {
  border-color: rgba(42,245,152,.25);
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0,0,0,.4);
}
.lby-stat__icon { font-size: 13px; color: #3a5068; transition: color .2s; }
.lby-stat:hover .lby-stat__icon { color: #2af598; }
.lby-stat__val  { font-family: 'Anton', sans-serif; font-size: 16px; color: #d8e4ff; line-height: 1; letter-spacing: .3px; }
.lby-stat__lbl  { font-size: 8px; color: #3a5068; text-transform: uppercase; letter-spacing: .5px; text-align: center; }

/* ═══════════════════════════════════
   ACCIONES
═══════════════════════════════════ */
.lby-actions { display: flex; gap: 8px; }

.lby-btn {
  padding: 0 20px; border: none; border-radius: 11px;
  font-size: 12px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  text-decoration: none; transition: filter .2s, transform .15s, box-shadow .2s;
  position: relative; white-space: nowrap;
  min-width: 0;
}
/* El brillo deslizante se recorta con clip-path (no overflow:hidden en el botón):
   overflow:hidden en el propio elemento también recortaría su box-shadow, dejando
   la sombra "cortada" en seco en el borde en vez de difuminarse hacia afuera. */
.lby-btn::after {
  content: ''; position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
  transform: skewX(-18deg); transition: left .5s ease;
  clip-path: inset(0 round 11px);
  pointer-events: none;
}
.lby-btn:hover::after { left: 130%; }

.lby-btn--play {
  background: linear-gradient(90deg, #1e9e6a 0%, #2af598 100%);
  color: #04130c; box-shadow: 0 8px 28px rgba(42,245,152,.28), 0 0 0 1px rgba(255,255,255,.06) inset;
}
.lby-btn--play:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 12px 36px rgba(42,245,152,.4), 0 0 0 1px rgba(255,255,255,.1) inset; }

.lby-btn--buy {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,191,0,.3);
  color: #e0a020; box-shadow: 0 4px 16px rgba(0,0,0,.3);
}
.lby-btn--buy:hover { background: rgba(255,191,0,.1); border-color: rgba(255,191,0,.45); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.4); }

.lby-btn--disabled {
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.06);
  color: #3a5068; box-shadow: none; cursor: not-allowed;
}
.lby-btn--disabled::after { display: none; }

.lby-warn {
  font-size: 11px; color: #c0a040; text-align: center; margin: 0;
  display: flex; align-items: center; justify-content: center; gap: 6px;
}

/* ═══════════════════════════════════
   RESPONSIVE
═══════════════════════════════════ */
@media (max-width: 768px) {
  .lby-bottom { grid-template-columns: 1fr; }
  .lby-actions { flex-direction: column; }
  .lby-btn { width: 100%; padding: 13px 14px; }
  .lby-hero__bottom { align-items: flex-start; flex-direction: column; gap: 10px; }
  .lby-prize-sub { gap: 18px; }
  .lby-prize-sub__item { text-align: left; }
  .lby-cards-progress { flex-wrap: wrap; }
  .lby-cards-progress__track { flex-basis: 100%; order: 3; }
}

@media (max-width: 480px) {
  .lby-page { padding: 14px 10px 32px; }
  .lby-hero__top, .lby-hero__bottom { padding-left: 12px; padding-right: 12px; }
  .lby-prize__num { font-size: 28px; }
  .lby-stats { grid-template-columns: repeat(2, 1fr); }
  .lby-panel { padding: 12px 14px; }
}
</style>
