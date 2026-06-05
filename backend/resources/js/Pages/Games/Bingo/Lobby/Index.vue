<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, reactive } from "vue";
import { Link } from "@inertiajs/vue3";
import { BingoService } from "@js/Services/bingoService";
import { getEchoInstance } from "@js/plugins/echo";
import BingoPurchaseModal from "@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue";
import { useGameRoomPurchaseModal } from "@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal";
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
  card_price?: number;       // precio en moneda del jugador
  card_price_usd?: number;   // precio base en USD
  room_type_description?: string;
  game_mode_description?: string;
  unix_date?: number | null;
  date?: string;
  time?: string;
  time_chip?: number;
  rounds?: number;
  in_live?: boolean;
}

interface Props {
  room: LobbyRoom;
  roomRouteKey?: string;
  userCards?: number;
  exchangeRate?: number;
}

const props = defineProps<Props>();

// ── Reactive live state ──
const liveRoom = reactive({
  prizePool:    props.room?.prize_pool    ?? 0,
  totalPlayers: props.room?.total_players ?? 0,
  userCards:    props.userCards           ?? 0,
});

// ── WS status ──
type WsStatus = 'connecting' | 'connected' | 'disconnected' | 'error';
const wsStatus = ref<WsStatus>('connecting');

// ── Flash animations ──
const prizeFlash   = ref(false);
const playersFlash = ref(false);

// ── Countdown ──
const countdown = ref('');
const isLive    = ref(false);
const isUrgent  = ref(false);
let countdownTimer: ReturnType<typeof setInterval> | null = null;
let echoChannel: any = null;

// ── Purchase modal (reutiliza el mismo composable que GameRooms) ──
const modal = useGameRoomPurchaseModal();

const bingoRoomImg = '/img/games/bingo/estelar_fd.png';

// ── Adaptador LobbyRoom → BingoGameRoom (para el modal) ──
const roomAsGameRoom = computed((): BingoGameRoom => ({
  id:               props.room.id,
  // encrypted = hash Hashids que usan las rutas /bingo-card/*
  encrypted:        props.roomRouteKey ?? undefined,
  uuid_code:        props.room.uuid_code ?? null,
  prize_pool:       liveRoom.prizePool,
  total_players:    liveRoom.totalPlayers,
  creator_user_id:  '',
  room_type:        'PUBLIC',
  game_mode:        props.room.game_mode_description ?? null,
  status_code:      props.room.status_code ?? '',
  currency_symbol:  props.room.currency_symbol,
  card_price:       props.room.card_price ?? props.room.card_price_usd,
  players_room:     [{ ctns: liveRoom.userCards }],
  config: props.room.unix_date ? {
    room_id:         props.room.id,
    card_price_usd:  props.room.card_price_usd ?? 0,
    prize_pool_type: 1,
    unix_date:       props.room.unix_date,
    date:            props.room.date ?? '',
    time:            props.room.time ?? '',
  } : null,
}));

// ── Computed ──
const roomRouteKey = computed(() =>
  props.roomRouteKey?.trim() || props.room?.id?.toString() || ''
);

const canNavigateGame = computed(() => Boolean(props.roomRouteKey?.trim()));

const roomStatusText = computed(() =>
  BingoService.getRoomStatusText(props.room?.status_code ?? '')
);

const isRoomLoading = computed(() => !props.room);

const canPurchaseCards = computed(() => {
  if (!props.room) return false;
  return props.room.status_code?.toUpperCase() === 'ACTIVE' && !props.room.close_room;
});

const statusClass = computed(() => {
  const c = props.room?.status_code?.toLowerCase() ?? 'desconocido';
  return `lby-status--${c}`;
});

const currencySymbol = computed(() => props.room?.currency_symbol ?? 'USD');

const rate = computed(() => props.exchangeRate ?? 1)

const formattedPrize = computed(() =>
  typeof liveRoom.prizePool === 'number'
    ? (liveRoom.prizePool * rate.value).toFixed(2)
    : '0.00'
);

const wsLabel = computed(() => ({
  connected:    'En vivo',
  connecting:   'Conectando…',
  disconnected: 'Sin conexión',
  error:        'Error',
}[wsStatus.value]));

// ── Lifecycle ──
onMounted(() => {
  startMeteors();
  setupEcho();
  startCountdown();
});

onUnmounted(() => {
  echoChannel?.stopListening('.RoomUpdated');
  if (countdownTimer) clearInterval(countdownTimer);
  if (meteorTimer) clearTimeout(meteorTimer);
});

// ── Countdown ──
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
  isLive.value = false;
  isUrgent.value = diff < 300;
  const h = Math.floor(diff / 3600);
  const m = Math.floor((diff % 3600) / 60);
  const s = diff % 60;
  if (h > 0) {
    countdown.value = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  } else {
    countdown.value = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  }
}

// ── Echo WebSocket ──
function setupEcho() {
  if (!props.room?.id) return;
  wsStatus.value = 'connecting';
  try {
    const echo = getEchoInstance();
    const pusher = echo.connector.pusher;
    const state = pusher.connection.state;
    if (state === 'connected') wsStatus.value = 'connected';
    pusher.connection.bind('connected',    () => { wsStatus.value = 'connected'; });
    pusher.connection.bind('disconnected', () => { wsStatus.value = 'disconnected'; });
    pusher.connection.bind('error',        () => { wsStatus.value = 'error'; });
    pusher.connection.bind('connecting',   () => { wsStatus.value = 'connecting'; });

    echoChannel = echo.channel(`bingo.room.${props.room.id}`);
    echoChannel.listen('.RoomUpdated', (event: any) => {
      const prizeChanged   = liveRoom.prizePool    !== event.prize_pool;
      const playersChanged = liveRoom.totalPlayers !== event.total_players;
      liveRoom.prizePool    = event.prize_pool    ?? liveRoom.prizePool;
      liveRoom.totalPlayers = event.total_players ?? liveRoom.totalPlayers;
      if (prizeChanged)   flash(prizeFlash);
      if (playersChanged) flash(playersFlash);
    });
  } catch { /* echo no disponible aún */ }
}

function flash(target: ReturnType<typeof ref<boolean>>) {
  target.value = true;
  setTimeout(() => { target.value = false; }, 1800);
}

// ── Purchase ──
function openPurchase() {
  if (!canPurchaseCards.value) return;
  modal.open(roomAsGameRoom.value);
}

function onPurchaseSuccess(_roomId: string, result: { prizePool: number; ctns: number }) {
  liveRoom.prizePool  = result.prizePool;
  liveRoom.userCards += result.ctns;
  flash(prizeFlash);
}

// ── Meteoros (idéntico a GameRooms.vue) ──
let meteorTimer: ReturnType<typeof setTimeout> | null = null;

function spawnMeteor() {
  const container = document.getElementById('lby-meteors');
  if (!container || container.children.length >= 22) return;
  const m = document.createElement('div');
  m.className = 'lby-meteor';
  const fromRight = Math.random() > 0.5;
  const size     = 110 + Math.random() * 130;
  const duration = 5  + Math.random() * 5;
  const opacity  = 0.5 + Math.random() * 0.4;
  const startX   = fromRight ? 50 + Math.random() * 60 : -10 + Math.random() * 60;
  const startY   = -5 + Math.random() * 10;
  const dx       = fromRight ? -(80 + Math.random() * 60) : (80 + Math.random() * 60);
  const dy       = 80 + Math.random() * 40;
  m.style.cssText = `
    left:${startX}vw; top:${startY}vh; width:${size}px; opacity:${opacity};
    --dx:${dx}vw; --dy:${dy}vh; --duration:${duration}s; --rot:${fromRight ? 145 : 35}deg;
  `;
  container.appendChild(m);
  m.addEventListener('animationend', () => m.remove(), { once: true });
}

function startMeteors() {
  setTimeout(() => spawnMeteor(), 200);
  setTimeout(() => spawnMeteor(), 800);
  setTimeout(() => spawnMeteor(), 1400);
  function schedule() {
    const delay = 1200 + Math.random() * 2000;
    meteorTimer = setTimeout(() => {
      const burst = 1 + Math.floor(Math.random() * 3);
      for (let i = 0; i < burst; i++) setTimeout(spawnMeteor, i * 150);
      schedule();
    }, delay) as any;
  }
  schedule();
}
</script>

<template>
  <div class="lby-page">

    <div id="lby-meteors" class="lby-meteors" aria-hidden="true" />

    <!-- Loading -->
    <div v-if="isRoomLoading" class="lby-loading">
      <div class="lby-spinner" />
    </div>

    <template v-else>
      <div class="lby-shell">

        <!-- ── BANNER compacto ── -->
        <div class="lby-banner">
          <img :src="bingoRoomImg" alt="Sala" class="lby-banner__img" />
          <div class="lby-banner__overlay" />

          <!-- Nav row sobre imagen -->
          <div class="lby-banner__nav">
            <Link href="/bingo/game-rooms" class="lby-back">
              <i class="fas fa-chevron-left" /> Salas
            </Link>
            <div class="lby-ws" :class="`lby-ws--${wsStatus}`">
              <span class="lby-ws__dot" />
              <span class="lby-ws__label">{{ wsLabel }}</span>
            </div>
          </div>

          <!-- Título + timer centrado -->
          <div class="lby-banner__center">
            <h1 class="lby-banner__title">Sala de Bingo</h1>
            <div
              v-if="countdown"
              class="lby-timer"
              :class="{ 'lby-timer--live': isLive, 'lby-timer--urgent': isUrgent && !isLive }"
            >
              <template v-if="isLive"><span class="lby-live-dot" /> EN VIVO</template>
              <template v-else><i class="fas fa-clock" /> {{ countdown }}</template>
            </div>
          </div>

          <!-- Chips + status en fila inferior -->
          <div class="lby-banner__foot">
            <span class="lby-chip lby-chip--type">
              <i class="fas fa-globe" /> {{ room.room_type_description ?? 'Pública' }}
            </span>
            <span class="lby-chip lby-chip--mode">
              <i class="fas fa-gamepad" /> {{ room.game_mode_description ?? 'Online' }}
            </span>
            <span v-if="room.in_live" class="lby-chip lby-chip--live">
              <span class="lby-live-dot lby-live-dot--sm" /> En Vivo
            </span>
            <span class="lby-status-chip" :class="statusClass">{{ roomStatusText }}</span>
          </div>
        </div>

        <!-- ── PRIZE ROW ── -->
        <div class="lby-prize-row">
          <div class="lby-prize-main" :class="{ 'lby-flash': prizeFlash }">
            <span class="lby-prize-main__lbl">Premio</span>
            <div class="lby-prize-main__val">
              <span class="lby-prize-main__num">{{ formattedPrize }}</span>
              <span class="lby-prize-main__sym">{{ currencySymbol }}</span>
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

        <!-- ── STATS ROW ── -->
        <div class="lby-stats">
          <div class="lby-stat" :class="{ 'lby-flash': playersFlash }">
            <i class="fas fa-users lby-stat__icon" />
            <span class="lby-stat__val">{{ liveRoom.totalPlayers }}</span>
            <span class="lby-stat__lbl">Jugadores</span>
          </div>
          <div class="lby-stat">
            <i class="fas fa-ticket-alt lby-stat__icon" />
            <span class="lby-stat__val">{{ liveRoom.userCards }}</span>
            <span class="lby-stat__lbl">Mis cartones</span>
          </div>
          <div v-if="room.card_price ?? room.card_price_usd" class="lby-stat">
            <i class="fas fa-dollar-sign lby-stat__icon" />
            <span class="lby-stat__val">{{ Number(room.card_price ?? room.card_price_usd).toFixed(2) }}</span>
            <span class="lby-stat__lbl">Precio ({{ currencySymbol }})</span>
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

        <!-- ── ACCIONES ── -->
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
            {{ liveRoom.userCards > 0 ? 'Más cartones' : 'Comprar Cartones' }}
          </button>
        </div>

        <p v-if="!canNavigateGame" class="lby-warn">
          <i class="fas fa-exclamation-triangle" /> Sala sin clave válida — recarga la página.
        </p>

      </div>
    </template>

    <!-- Modal de compra (el mismo de GameRooms) -->
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
      @close="modal.close()"
      @select-country="modal.selectCountry($event)"
      @next="modal.nextStep()"
      @prev="modal.prevStep()"
      @increment="modal.increment()"
      @decrement="modal.decrement()"
      @set-ctns="modal.setNumCtns($event)"
      @purchase="modal.purchase(result => onPurchaseSuccess(modal.selectedRoom.value!.id, result))"
    />

  </div>
</template>

<style>
/* ══ RESET LAYOUT ══ */
html {
  height: 100% !important;
  overflow: hidden !important;
  background: #04081a !important;
}
body {
  height: 100% !important;
  overflow: hidden !important;
  background: #04081a !important;
}
body, body.hold-transition, #app-wrapper, .wrapper, .content-wrapper {
  background-color: #04081a !important; background-image: none !important;
}
/* AdminLTE wrappers — deben propagar la altura sin cortar */
.wrapper {
  height: 100% !important; min-height: 100% !important;
  overflow: hidden !important; display: flex; flex-direction: column;
}
.content-wrapper {
  flex: 1 !important; height: 0 !important; /* flex-child: crece sin desbordarse */
  overflow: hidden !important; display: flex; flex-direction: column;
}
.content-header { display: none !important; min-height: 0 !important; height: 0 !important; }
.content-wrapper > .content {
  flex: 1 !important; padding: 0 !important;
  display: flex; flex-direction: column;
  overflow: hidden !important; height: 100% !important;
}
.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1; display: flex; flex-direction: column;
  height: 100% !important; overflow: hidden !important;
}

/* ── Meteoros ── */
.lby-meteor {
  position: absolute; height: 1px; border-radius: 999px;
  background: linear-gradient(90deg, transparent 0%, rgba(200,225,255,.04) 30%, rgba(220,238,255,.22) 65%, rgba(255,255,255,.90) 90%, rgba(255,255,255,1) 100%);
  box-shadow: 2px 0 6px 1px rgba(255,255,255,.55);
  transform: rotate(var(--rot,35deg));
  animation: lbyMeteorFall var(--duration,7s) linear forwards;
  pointer-events: none; will-change: transform, opacity;
}
@keyframes lbyMeteorFall {
  0%  { transform: rotate(var(--rot,35deg)) translate(0,0); opacity:0; }
  6%  { opacity:1; }
  88% { opacity:.85; }
  100%{ transform: rotate(var(--rot,35deg)) translate(var(--dx,80vw),var(--dy,90vh)); opacity:0; }
}
</style>

<style scoped>
/* ═══════════════════════════════════
   PÁGINA
═══════════════════════════════════ */
.lby-page {
  flex: 1;
  height: 100%;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  position: relative; isolation: isolate;
  overflow-x: hidden;
  overflow-y: auto;         /* scroll solo si el contenido no cabe */
  scrollbar-width: none;    /* scrollbar invisible */
  background: transparent;
  padding: 16px 0;
}
.lby-page::-webkit-scrollbar { display: none; }

/* Fondo nebula fijo */
.lby-page::before {
  content: ""; position: fixed; inset: 0; z-index: -10; pointer-events: none;
  background:
    radial-gradient(ellipse 90% 55% at 15%  5%, rgba(80,0,200,.30) 0%, transparent 60%),
    radial-gradient(ellipse 70% 50% at 85% 12%, rgba(0,60,220,.24) 0%, transparent 58%),
    radial-gradient(ellipse 60% 45% at 50% 85%, rgba(0,150,110,.16) 0%, transparent 62%),
    radial-gradient(ellipse 55% 65% at 92% 68%, rgba(130,0,210,.20) 0%, transparent 58%),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
}

/* Estrellas capa 1 */
.lby-page::after {
  content: ""; position: fixed; inset: 0; z-index: -2; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  3%  4%, rgba(255,255,255,.92) 0%,transparent 100%),
    radial-gradient(1px 1px at 16%  2%, rgba(255,255,255,.82) 0%,transparent 100%),
    radial-gradient(1px 1px at 29%  9%, rgba(255,255,255,.74) 0%,transparent 100%),
    radial-gradient(1px 1px at 42%  5%, rgba(255,255,255,.88) 0%,transparent 100%),
    radial-gradient(1px 1px at 57%  8%, rgba(255,255,255,.80) 0%,transparent 100%),
    radial-gradient(1px 1px at 70%  3%, rgba(255,255,255,.86) 0%,transparent 100%),
    radial-gradient(1px 1px at 84%  7%, rgba(255,255,255,.75) 0%,transparent 100%),
    radial-gradient(1px 1px at 97% 12%, rgba(255,255,255,.84) 0%,transparent 100%),
    radial-gradient(1px 1px at 21% 67%, rgba(255,255,255,.78) 0%,transparent 100%),
    radial-gradient(1px 1px at 51% 80%, rgba(255,255,255,.82) 0%,transparent 100%),
    radial-gradient(1.5px 1.5px at 11% 16%, rgba(190,210,255,.95) 0%,transparent 100%),
    radial-gradient(1.5px 1.5px at 46% 12%, rgba(180,225,255,.90) 0%,transparent 100%),
    radial-gradient(3px 3px at 19% 20%, rgba(255,255,255,1) 0%,rgba(210,230,255,.20) 55%,transparent 100%),
    radial-gradient(3px 3px at 69% 42%, rgba(255,255,255,.94) 0%,rgba(225,245,255,.15) 55%,transparent 100%);
  animation: lbyTwinkle 8s ease-in-out infinite alternate;
}
@keyframes lbyTwinkle { 0%{opacity:.55} 50%{opacity:.95} 100%{opacity:.70} }

/* Meteoros */
.lby-meteors { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }

/* ═══════════════════════════════════
   LOADING
═══════════════════════════════════ */
.lby-loading {
  display: flex; align-items: center; justify-content: center;
  min-height: 100vh;
}
.lby-spinner {
  width: 32px; height: 32px;
  border: 2px solid rgba(100,140,255,.15); border-top-color: #7b4fff;
  border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ═══════════════════════════════════
   SHELL — contenedor central compacto
═══════════════════════════════════ */
.lby-shell {
  position: relative; z-index: 1;
  width: 100%; max-width: 480px;
  padding: 0 14px;
  display: flex; flex-direction: column; gap: 8px;
  animation: lbyFadeUp .4s cubic-bezier(.22,1,.36,1) both;
}
@keyframes lbyFadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════════
   BANNER — compacto con todo encima
═══════════════════════════════════ */
.lby-banner {
  position: relative; border-radius: 18px; overflow: hidden;
  border: 1px solid rgba(100,140,255,.22);
  box-shadow: 0 20px 60px rgba(0,0,0,.7), 0 0 0 1px rgba(80,120,255,.08) inset;
  height: 164px;
}
.lby-banner__img {
  width: 100%; height: 100%; object-fit: cover; display: block;
  transition: transform .5s ease;
}
.lby-banner:hover .lby-banner__img { transform: scale(1.03); }
.lby-banner__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(4,8,26,.55) 0%, rgba(4,8,26,.82) 60%, rgba(4,8,26,.96) 100%);
}

/* Nav row: back + ws */
.lby-banner__nav {
  position: absolute; top: 0; left: 0; right: 0; z-index: 2;
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px;
}

.lby-back {
  display: inline-flex; align-items: center; gap: 6px;
  color: rgba(255,255,255,.55); font-size: 12px; font-weight: 600;
  text-decoration: none; letter-spacing: .3px; transition: color .2s;
}
.lby-back:hover { color: #a8c4ff; }
.lby-back i { font-size: 9px; }

/* WS pill */
.lby-ws {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700;
  background: rgba(0,0,0,.45); border: 1px solid rgba(255,255,255,.1);
  backdrop-filter: blur(6px);
}
.lby-ws__dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.lby-ws__label { color: #5578bb; }
.lby-ws--connected   .lby-ws__dot { background: #2af598; box-shadow: 0 0 5px rgba(42,245,152,.7); animation: dotPulse 2s infinite; }
.lby-ws--connected   .lby-ws__label { color: #2af598; }
.lby-ws--connecting  .lby-ws__dot { background: #ffc336; animation: dotBlink .8s infinite; }
.lby-ws--connecting  .lby-ws__label { color: #ffc336; }
.lby-ws--disconnected .lby-ws__dot, .lby-ws--error .lby-ws__dot { background: #ff4b6d; }
.lby-ws--disconnected .lby-ws__label, .lby-ws--error .lby-ws__label { color: #ff4b6d; }
@keyframes dotPulse { 0%,100%{opacity:1} 50%{opacity:.35} }
@keyframes dotBlink  { 0%,100%{opacity:1} 50%{opacity:.1}  }

/* Título + timer — centro del banner */
.lby-banner__center {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px; pointer-events: none;
}
.lby-banner__title {
  font-family: 'Anton', sans-serif; font-size: clamp(18px,3.5vw,26px);
  letter-spacing: 3px; margin: 0;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 55%, #7b4fff 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  text-shadow: none;
}

/* Timer pill */
.lby-timer {
  background: rgba(8,14,40,.85); border: 1px solid rgba(100,140,255,.35);
  backdrop-filter: blur(8px); color: #c8d8ff;
  border-radius: 30px; padding: 4px 14px;
  font-family: 'Anton', sans-serif; font-size: 14px; letter-spacing: 1px;
  display: inline-flex; align-items: center; gap: 5px;
  white-space: nowrap; pointer-events: auto;
}
.lby-timer i { font-size: 11px; opacity: .7; }
.lby-timer--live   { border-color: rgba(255,70,70,.55); color: #ff8080; animation: lbyPulseLive 1.8s infinite; }
.lby-timer--urgent { border-color: rgba(255,195,54,.45); color: #ffc336; animation: lbyPulseUrgent 1.2s infinite; }
@keyframes lbyPulseLive   { 0%,100%{box-shadow:0 0 0 0 rgba(255,60,60,0)}  50%{box-shadow:0 0 0 4px rgba(255,60,60,.2)} }
@keyframes lbyPulseUrgent { 0%,100%{box-shadow:0 0 0 0 rgba(255,195,54,0)} 50%{box-shadow:0 0 0 4px rgba(255,195,54,.2)} }

/* Live dot */
.lby-live-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #ff4d4d; box-shadow: 0 0 5px #ff4d4d;
  animation: lbyBlinkDot 1s infinite; flex-shrink: 0;
}
.lby-live-dot--sm { width: 5px; height: 5px; }
@keyframes lbyBlinkDot { 0%,100%{opacity:1} 50%{opacity:.25} }

/* Fila inferior del banner: chips + status */
.lby-banner__foot {
  position: absolute; bottom: 0; left: 0; right: 0; z-index: 2;
  display: flex; align-items: center; gap: 6px;
  padding: 8px 12px; flex-wrap: nowrap; overflow: hidden;
}

.lby-chip {
  padding: 2px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .3px;
  display: inline-flex; align-items: center; gap: 4px;
  white-space: nowrap;
}
.lby-chip--type { background: rgba(123,79,255,.2); border: 1px solid rgba(123,79,255,.4); color: #b48fff; }
.lby-chip--mode { background: rgba(0,158,253,.15); border: 1px solid rgba(0,158,253,.35); color: #7cd4ff; }
.lby-chip--live { background: rgba(255,75,109,.15); border: 1px solid rgba(255,75,109,.38); color: #ff4b6d; display:inline-flex; align-items:center; gap:4px; }

.lby-status-chip {
  margin-left: auto; padding: 2px 10px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase;
  white-space: nowrap;
}
.lby-status--active      { background: rgba(42,245,152,.16); border: 1px solid rgba(42,245,152,.36); color: #2af598; }
.lby-status--in_progress { background: rgba(0,158,253,.16);  border: 1px solid rgba(0,158,253,.36);  color: #7cd4ff; }
.lby-status--pending     { background: rgba(255,195,54,.14); border: 1px solid rgba(255,195,54,.32); color: #ffc336; }
.lby-status--finished    { background: rgba(255,75,109,.14); border: 1px solid rgba(255,75,109,.32); color: #ff4b6d; }
.lby-status--offline     { background: rgba(100,100,120,.12);border: 1px solid rgba(120,130,160,.2); color: #8090aa; }
.lby-status--desconocido { background: rgba(100,140,255,.10);border: 1px solid rgba(100,140,255,.2); color: #7c9dff; }

/* ═══════════════════════════════════
   PRIZE ROW — número grande + sub
═══════════════════════════════════ */
.lby-prize-row {
  background: linear-gradient(160deg, #0d1635 0%, #080f28 100%);
  border: 1px solid rgba(100,140,255,.2);
  border-radius: 16px;
  padding: 12px 18px;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: 0 8px 32px rgba(0,0,0,.5);
  position: relative; overflow: hidden;
}
.lby-prize-row::before {
  content: ''; position: absolute; top: -30px; left: 0; right: 0; height: 80px;
  background: radial-gradient(ellipse 70% 60% at 30% 0%, rgba(123,79,255,.2), transparent 70%);
  pointer-events: none;
}

.lby-prize-main { position: relative; z-index: 1; }
.lby-prize-main__lbl {
  font-size: 9px; color: #5578bb;
  text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 2px;
}
.lby-prize-main__val { display: flex; align-items: baseline; gap: 6px; }
.lby-prize-main__num {
  font-family: 'Anton', sans-serif; font-size: 42px; line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 3px 10px rgba(255,180,0,.45));
}
.lby-prize-main__sym { font-size: 15px; font-weight: 800; color: #ffd060; -webkit-text-fill-color: #ffd060; }

.lby-prize-sub {
  display: flex; flex-direction: column; gap: 6px;
  padding-left: 16px; border-left: 1px solid rgba(100,140,255,.12);
  position: relative; z-index: 1;
}
.lby-prize-sub__item { text-align: right; }
.lby-prize-sub__lbl { display: block; font-size: 8px; color: #3d5280; text-transform: uppercase; letter-spacing: .6px; }
.lby-prize-sub__val { font-family: 'Anton', sans-serif; font-size: 13px; color: #8090b0; }

/* Flash */
.lby-flash { animation: lbyFlash 1.8s ease-in-out; }
@keyframes lbyFlash { 0%,100%{filter:none} 40%{filter:drop-shadow(0 0 10px rgba(42,245,152,.8)) brightness(1.3)} }

/* ═══════════════════════════════════
   STATS — fila horizontal compacta
═══════════════════════════════════ */
.lby-stats {
  display: flex; gap: 6px;
}
.lby-stat {
  flex: 1;
  background: rgba(8,12,32,.7); border: 1px solid rgba(100,140,255,.12);
  border-radius: 12px; padding: 10px 6px;
  display: flex; flex-direction: column; align-items: center; gap: 3px;
  transition: border-color .2s;
}
.lby-stat:hover { border-color: rgba(100,140,255,.28); }
.lby-stat__icon { font-size: 13px; color: #3d5280; margin-bottom: 1px; }
.lby-stat__val  { font-family: 'Anton', sans-serif; font-size: 17px; color: #d8e4ff; line-height: 1; }
.lby-stat__lbl  { font-size: 8px; color: #3d5280; text-transform: uppercase; letter-spacing: .6px; text-align: center; }

/* ═══════════════════════════════════
   ACCIONES — 2 botones en fila
═══════════════════════════════════ */
.lby-actions { display: flex; gap: 8px; }

.lby-btn {
  flex: 1; padding: 13px 10px; border: none; border-radius: 13px;
  font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  text-decoration: none; transition: filter .2s, transform .15s, box-shadow .2s;
  position: relative; overflow: hidden; white-space: nowrap;
}
.lby-btn::after {
  content: ''; position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
  transform: skewX(-18deg); transition: left .5s ease;
}
.lby-btn:hover::after { left: 130%; }

.lby-btn--play {
  background: linear-gradient(90deg, #7b4fff 0%, #009efd 100%);
  color: #fff; box-shadow: 0 5px 20px rgba(123,79,255,.38);
}
.lby-btn--play:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(123,79,255,.52); }

.lby-btn--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #031a10; box-shadow: 0 5px 20px rgba(42,245,152,.3);
}
.lby-btn--buy:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(42,245,152,.44); }

.lby-btn--disabled {
  background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.07);
  color: #263550; box-shadow: none; cursor: not-allowed;
}
.lby-btn--disabled::after { display: none; }

.lby-warn {
  font-size: 11px; color: #9a7020; text-align: center; margin: 0;
  display: flex; align-items: center; justify-content: center; gap: 6px;
}
</style>
