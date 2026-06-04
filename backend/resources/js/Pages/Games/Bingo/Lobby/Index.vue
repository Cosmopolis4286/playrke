<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, reactive } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { BingoService } from "@js/Services/bingoService";
import axios from "axios";
import { getEchoInstance } from "@js/plugins/echo";

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
  room_type_description?: string;
  game_mode_description?: string;
  unix_date?: number | null;
  date?: string;
  time?: string;
  card_price_usd?: number;
  time_chip?: number;
  rounds?: number;
  in_live?: boolean;
}

interface Props {
  room: LobbyRoom;
  roomRouteKey?: string;
  userCards?: number;
}

const props = defineProps<Props>();

// ── Reactive room state (evita mutar props) ──
const liveRoom = reactive({
  prizePool:     props.room?.prize_pool     ?? 0,
  totalPlayers:  props.room?.total_players  ?? 0,
});

// ── Purchase modal state ──
const showPurchaseModal   = ref(false);
const purchaseQuantity    = ref(1);
const selectedCountry     = ref('');
const availableCountries  = ref<{ id: string; name: string }[]>([]);
const cardPrice           = ref(props.room?.card_price_usd ?? 0);
const isPurchasing        = ref(false);
const purchaseError       = ref('');
const loadingPurchaseData = ref(false);

// ── Flash animations ──
const prizeFlash   = ref(false);
const playersFlash = ref(false);

// ── Countdown ──
const countdown = ref('');
let countdownTimer: ReturnType<typeof setInterval> | null = null;
let echoChannel: any = null;

const bingoRoomImg = '/img/games/bingo/estelar_fd.png';

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
  const code = props.room.status_code?.toString().toUpperCase();
  return code === 'ACTIVE' && !props.room.close_room;
});

const statusClass = computed(() => {
  const c = props.room?.status_code?.toLowerCase() ?? 'desconocido';
  return `lby-status--${c}`;
});

const currencySymbol = computed(() => props.room?.currency_symbol ?? 'USD');

const formattedPrize = computed(() => {
  const val = liveRoom.prizePool;
  return typeof val === 'number' ? val.toFixed(2) : '0.00';
});

const totalPurchase = computed(() =>
  (cardPrice.value * purchaseQuantity.value).toFixed(2)
);

const maxQty = computed(() => 10);

// ── Lifecycle ──
onMounted(() => {
  document.body.classList.add('game-rooms-bg');
  setupEcho();
  startCountdown();
});

onUnmounted(() => {
  document.body.classList.remove('game-rooms-bg');
  echoChannel?.stopListening('.RoomUpdated');
  if (countdownTimer) clearInterval(countdownTimer);
});

// ── Countdown timer ──
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
    countdown.value = 'En curso';
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
    return;
  }
  const h = Math.floor(diff / 3600);
  const m = Math.floor((diff % 3600) / 60);
  const s = diff % 60;
  if (h > 0) {
    countdown.value = `${h}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
  } else {
    countdown.value = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  }
}

// ── Echo WebSocket ──
function setupEcho() {
  if (!props.room?.id) return;
  const echo = getEchoInstance();
  echoChannel = echo.channel(`bingo.room.${props.room.id}`);
  echoChannel.listen('.RoomUpdated', (event: any) => {
    const prizeChanged   = liveRoom.prizePool    !== event.prize_pool;
    const playersChanged = liveRoom.totalPlayers !== event.total_players;

    liveRoom.prizePool    = event.prize_pool;
    liveRoom.totalPlayers = event.total_players;

    if (prizeChanged)   flash(prizeFlash);
    if (playersChanged) flash(playersFlash);
  });
}

function flash(target: ReturnType<typeof ref<boolean>>) {
  target.value = true;
  setTimeout(() => { target.value = false; }, 1800);
}

// ── Purchase ──
function openPurchaseModal() {
  if (!canPurchaseCards.value) return;
  purchaseError.value  = '';
  purchaseQuantity.value = 1;
  showPurchaseModal.value = true;
  loadPurchaseData();
}

async function loadPurchaseData() {
  loadingPurchaseData.value = true;
  try {
    const [cr, pr] = await Promise.all([
      axios.get('/bingo-card/fetch-room'),
      axios.get(`/bingo-card/${roomRouteKey.value}/card-price`),
    ]);
    availableCountries.value = cr.data.data ?? [];
    cardPrice.value = pr.data.data?.card_price ?? props.room?.card_price_usd ?? 0;
    if (availableCountries.value.length > 0) {
      selectedCountry.value = availableCountries.value[0].id;
    }
  } catch {
    purchaseError.value = 'No se pudo cargar la información de compra.';
  } finally {
    loadingPurchaseData.value = false;
  }
}

async function confirmPurchase() {
  if (purchaseQuantity.value < 1 || isPurchasing.value) return;
  purchaseError.value = '';
  isPurchasing.value  = true;
  try {
    await axios.post(`/api/bingo/rooms/${roomRouteKey.value}/purchase`, {
      num_ctns:   purchaseQuantity.value,
      country_id: selectedCountry.value,
    });
    showPurchaseModal.value = false;
    router.reload({ only: ['userCards'] });
  } catch (e: any) {
    purchaseError.value = e.response?.data?.message ?? 'Error al procesar la compra.';
  } finally {
    isPurchasing.value = false;
  }
}

function decQty() { if (purchaseQuantity.value > 1) purchaseQuantity.value--; }
function incQty() { if (purchaseQuantity.value < maxQty.value) purchaseQuantity.value++; }
</script>

<template>
  <div class="lby-page">

    <!-- Orbes espaciales -->
    <Teleport to="body">
      <div class="space-orb space-orb--purple" />
      <div class="space-orb space-orb--blue" />
      <div class="space-orb space-orb--green" />
    </Teleport>

    <div class="lby-wrap">

      <!-- Back link -->
      <Link href="/bingo/game-rooms" class="lby-back">
        <i class="fas fa-chevron-left" />
        Volver a salas
      </Link>

      <!-- Loading -->
      <div v-if="isRoomLoading" class="lby-loading">
        <div class="lby-spinner" />
        <span>Cargando sala…</span>
      </div>

      <template v-else>

        <!-- HERO -->
        <div class="lby-hero">

          <!-- Banner imagen -->
          <div class="lby-hero__img-wrap">
            <img :src="bingoRoomImg" alt="Sala de Bingo" class="lby-hero__img" />
            <div class="lby-hero__img-fade" />

            <!-- Status badge -->
            <span class="lby-status lby-status--over" :class="statusClass">
              {{ roomStatusText }}
            </span>

            <!-- Countdown sobre la imagen -->
            <div v-if="countdown" class="lby-countdown">
              <i class="fas fa-clock" />
              {{ countdown }}
            </div>
          </div>

          <!-- Cuerpo hero -->
          <div class="lby-hero__body">
            <div class="lby-hero__glow" />

            <h1 class="lby-hero__title">Sala de Bingo</h1>
            <p v-if="room.uuid_code" class="lby-hero__code">
              <i class="fas fa-hashtag" />{{ room.uuid_code }}
            </p>

            <!-- Tipo + modo -->
            <div class="lby-hero__chips">
              <span class="lby-chip lby-chip--type">
                <i class="fas fa-globe-americas" />
                {{ room.room_type_description ?? 'Pública' }}
              </span>
              <span class="lby-chip lby-chip--mode">
                <i class="fas fa-gamepad" />
                {{ room.game_mode_description ?? 'Mixto' }}
              </span>
              <span v-if="room.in_live" class="lby-chip lby-chip--live">
                <i class="fas fa-circle" />
                En Vivo
              </span>
            </div>

            <!-- Premio principal -->
            <div class="lby-hero__prize">
              <span class="lby-hero__prize-lbl">PREMIO ACUMULADO</span>
              <div class="lby-hero__prize-val" :class="{ 'lby-flash': prizeFlash }">
                <span class="lby-hero__prize-num">{{ formattedPrize }}</span>
                <span class="lby-hero__prize-sym">{{ currencySymbol }}</span>
              </div>
            </div>

            <!-- Premios secundarios -->
            <div
              v-if="room.full_house_prize || room.serial_prize"
              class="lby-hero__prizes-row"
            >
              <div v-if="room.full_house_prize" class="lby-prize-mini">
                <span class="lby-prize-mini__lbl">Casa Llena</span>
                <span class="lby-prize-mini__val">
                  {{ Number(room.full_house_prize).toFixed(2) }} {{ currencySymbol }}
                </span>
              </div>
              <div v-if="room.serial_prize" class="lby-prize-mini">
                <span class="lby-prize-mini__lbl">Serial</span>
                <span class="lby-prize-mini__val">
                  {{ Number(room.serial_prize).toFixed(2) }} {{ currencySymbol }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats grid -->
        <div class="lby-stats">
          <div class="lby-stat">
            <div class="lby-stat__icon lby-stat__icon--green">
              <i class="fas fa-trophy" />
            </div>
            <span class="lby-stat__val" :class="{ 'lby-flash': prizeFlash }">
              {{ formattedPrize }}
            </span>
            <span class="lby-stat__lbl">Premio</span>
          </div>

          <div class="lby-stat">
            <div class="lby-stat__icon lby-stat__icon--blue">
              <i class="fas fa-users" />
            </div>
            <span class="lby-stat__val" :class="{ 'lby-flash': playersFlash }">
              {{ liveRoom.totalPlayers }}
            </span>
            <span class="lby-stat__lbl">Jugadores</span>
          </div>

          <div class="lby-stat">
            <div class="lby-stat__icon lby-stat__icon--purple">
              <i class="fas fa-ticket-alt" />
            </div>
            <span class="lby-stat__val">{{ userCards ?? 0 }}</span>
            <span class="lby-stat__lbl">Tus cartones</span>
          </div>

          <div v-if="room.rounds" class="lby-stat">
            <div class="lby-stat__icon lby-stat__icon--gold">
              <i class="fas fa-layer-group" />
            </div>
            <span class="lby-stat__val">{{ room.rounds }}</span>
            <span class="lby-stat__lbl">Rondas</span>
          </div>

          <div v-if="room.card_price_usd" class="lby-stat">
            <div class="lby-stat__icon lby-stat__icon--teal">
              <i class="fas fa-dollar-sign" />
            </div>
            <span class="lby-stat__val">{{ Number(room.card_price_usd).toFixed(2) }}</span>
            <span class="lby-stat__lbl">Precio cartón</span>
          </div>

          <div v-if="room.time_chip" class="lby-stat">
            <div class="lby-stat__icon lby-stat__icon--orange">
              <i class="fas fa-stopwatch" />
            </div>
            <span class="lby-stat__val">{{ room.time_chip }}s</span>
            <span class="lby-stat__lbl">Tiempo ficha</span>
          </div>
        </div>

        <!-- Acciones -->
        <div class="lby-actions">

          <!-- Comprar cartones (visible siempre si puede comprar) -->
          <button
            v-if="canPurchaseCards"
            class="lby-btn lby-btn--buy"
            @click="openPurchaseModal"
          >
            <i class="fas fa-shopping-cart" />
            {{ (userCards ?? 0) > 0 ? 'Comprar más cartones' : 'Comprar Cartones' }}
          </button>

          <!-- Entrar al juego -->
          <Link
            v-if="canNavigateGame"
            :href="`/bingo/${roomRouteKey}/game`"
            class="lby-btn lby-btn--play"
          >
            <i class="fas fa-play-circle" />
            Entrar al Juego
          </Link>
          <button v-else class="lby-btn lby-btn--play lby-btn--disabled" disabled>
            <i class="fas fa-play-circle" />
            Entrar al Juego
          </button>

          <p v-if="!canNavigateGame" class="lby-warn">
            <i class="fas fa-exclamation-triangle" />
            Sala sin clave válida. Recarga la página.
          </p>
        </div>

      </template>
    </div>

    <!-- ══ Modal Compra ══ -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="showPurchaseModal"
          class="lby-modal-overlay"
          @click.self="showPurchaseModal = false"
        >
          <div class="lby-modal">
            <button class="lby-modal__close" @click="showPurchaseModal = false">
              <i class="fas fa-times" />
            </button>

            <div class="lby-modal__head">
              <i class="fas fa-ticket-alt lby-modal__head-icon" />
              <h3 class="lby-modal__title">Comprar Cartones</h3>
            </div>

            <!-- Loading datos -->
            <div v-if="loadingPurchaseData" class="lby-modal__loading">
              <div class="lby-spinner lby-spinner--sm" />
              <span>Cargando precios…</span>
            </div>

            <template v-else>
              <div class="lby-modal__body">

                <!-- Error inline -->
                <div v-if="purchaseError" class="lby-modal__error">
                  <i class="fas fa-exclamation-circle" />
                  {{ purchaseError }}
                </div>

                <!-- Info precio -->
                <div class="lby-modal__price-row">
                  <div class="lby-modal__price-item">
                    <span class="lby-modal__price-lbl">Precio c/u</span>
                    <span class="lby-modal__price-val">${{ cardPrice.toFixed(2) }}</span>
                  </div>
                  <div class="lby-modal__price-divider" />
                  <div class="lby-modal__price-item">
                    <span class="lby-modal__price-lbl">Total</span>
                    <span class="lby-modal__price-val lby-modal__price-val--total">
                      ${{ totalPurchase }}
                    </span>
                  </div>
                </div>

                <!-- Cantidad -->
                <div class="lby-modal__field">
                  <label class="lby-modal__label">Cantidad de cartones</label>
                  <div class="lby-modal__qty">
                    <button
                      class="lby-modal__qty-btn"
                      :disabled="purchaseQuantity <= 1"
                      @click="decQty"
                    ><i class="fas fa-minus" /></button>
                    <input
                      type="number"
                      :min="1"
                      :max="maxQty"
                      v-model.number="purchaseQuantity"
                      class="lby-modal__qty-input"
                    />
                    <button
                      class="lby-modal__qty-btn"
                      :disabled="purchaseQuantity >= maxQty"
                      @click="incQty"
                    ><i class="fas fa-plus" /></button>
                  </div>
                </div>

                <!-- País -->
                <div v-if="availableCountries.length > 0" class="lby-modal__field">
                  <label class="lby-modal__label">País</label>
                  <select v-model="selectedCountry" class="lby-modal__select">
                    <option v-for="c in availableCountries" :key="c.id" :value="c.id">
                      {{ c.name }}
                    </option>
                  </select>
                </div>

              </div>

              <div class="lby-modal__foot">
                <button
                  class="lby-modal__btn lby-modal__btn--cancel"
                  @click="showPurchaseModal = false"
                >
                  Cancelar
                </button>
                <button
                  class="lby-modal__btn lby-modal__btn--confirm"
                  :disabled="isPurchasing || purchaseQuantity < 1"
                  @click="confirmPurchase"
                >
                  <span v-if="isPurchasing" class="lby-spinner lby-spinner--xs" />
                  {{ isPurchasing ? 'Procesando…' : 'Confirmar' }}
                </button>
              </div>
            </template>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style>
/* ══════════════════════════════════════════════════
   FONDO ESPACIAL — se activa con body.game-rooms-bg
══════════════════════════════════════════════════ */
html, body {
  height: auto !important;
  min-height: 100%;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  background: #03060f !important;
}

body.game-rooms-bg,
body.game-rooms-bg.hold-transition,
body.game-rooms-bg #app-wrapper,
body.game-rooms-bg .wrapper,
body.game-rooms-bg .content-wrapper,
body.game-rooms-bg .content-wrapper > .content {
  background: transparent !important;
  background-color: transparent !important;
  background-image: none !important;
}

body.game-rooms-bg::before {
  content: "";
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background:
    radial-gradient(ellipse 80% 60% at 10%  0%,  rgba(90,0,230,.30)  0%, transparent 50%),
    radial-gradient(ellipse 70% 55% at 92%  4%,  rgba(0,70,220,.24)  0%, transparent 50%),
    radial-gradient(ellipse 60% 50% at 50% 100%, rgba(0,180,100,.14) 0%, transparent 55%),
    radial-gradient(ellipse 55% 65% at 100% 60%, rgba(150,0,200,.18) 0%, transparent 52%),
    radial-gradient(ellipse 45% 40% at 0%   65%, rgba(0,100,255,.13) 0%, transparent 50%),
    #03060f;
  animation: nebulaShift 30s ease-in-out infinite alternate;
}

body.game-rooms-bg::after {
  content: "";
  position: fixed; inset: 0; z-index: 1; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  4%  7%,  rgba(255,255,255,.90) 0%, transparent 100%),
    radial-gradient(1px 1px at 13% 21%,  rgba(255,255,255,.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 23%  3%,  rgba(255,255,255,.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 32% 37%,  rgba(255,255,255,.42) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 15%,  rgba(255,255,255,.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 56%  9%,  rgba(255,255,255,.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 66% 29%,  rgba(255,255,255,.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 76% 12%,  rgba(255,255,255,.76) 0%, transparent 100%),
    radial-gradient(1px 1px at 88%  5%,  rgba(255,255,255,.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 95% 23%,  rgba(255,255,255,.80) 0%, transparent 100%),
    radial-gradient(1px 1px at  3% 46%,  rgba(255,255,255,.64) 0%, transparent 100%),
    radial-gradient(1px 1px at 11% 59%,  rgba(255,255,255,.40) 0%, transparent 100%),
    radial-gradient(1px 1px at 20% 74%,  rgba(255,255,255,.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 42% 67%,  rgba(255,255,255,.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 53% 48%,  rgba(255,255,255,.86) 0%, transparent 100%),
    radial-gradient(1px 1px at 72% 54%,  rgba(255,255,255,.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 83% 69%,  rgba(255,255,255,.56) 0%, transparent 100%),
    radial-gradient(1px 1px at  8% 88%,  rgba(255,255,255,.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 63% 92%,  rgba(255,255,255,.54) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 18% 18%, rgba(190,210,255,.92) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 48% 10%, rgba(210,190,255,.86) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 78% 28%, rgba(180,225,255,.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 60% 58%, rgba(200,255,225,.82) 0%, transparent 100%),
    radial-gradient(3px 3px at 37% 20%, rgba(255,255,255,1)   0%, rgba(210,230,255,.22) 55%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(225,205,255,.97) 0%, rgba(185,165,255,.18) 55%, transparent 100%),
    radial-gradient(4px 4px at 62% 35%, rgba(255,255,255,1)   0%, rgba(205,225,255,.30) 50%, transparent 100%);
  animation: starDrift 60s linear infinite;
}

@keyframes nebulaShift {
  0%   { filter: brightness(1);    }
  33%  { filter: brightness(1.08); }
  66%  { filter: brightness(.95);  }
  100% { filter: brightness(1.05); }
}
@keyframes starDrift {
  from { transform: translate(0,0);      opacity: .82; }
  25%  { transform: translate(-4px,6px); opacity: .95; }
  50%  { transform: translate(3px,-5px); opacity: .75; }
  75%  { transform: translate(-2px,8px); opacity: .90; }
  to   { transform: translate(0,0);      opacity: .82; }
}

body.game-rooms-bg .space-orb {
  position: fixed; border-radius: 50%;
  pointer-events: none; filter: blur(90px); z-index: 2;
}
body.game-rooms-bg .space-orb--purple {
  width:600px; height:600px; top:-180px; left:-140px;
  background: radial-gradient(circle, rgba(123,79,255,.28), transparent 65%);
  animation: orbP 20s ease-in-out infinite alternate;
}
body.game-rooms-bg .space-orb--blue {
  width:500px; height:500px; top:-120px; right:-120px;
  background: radial-gradient(circle, rgba(0,158,253,.22), transparent 65%);
  animation: orbB 25s ease-in-out infinite alternate;
}
body.game-rooms-bg .space-orb--green {
  width:400px; height:400px; bottom:-100px; left:50%; transform:translateX(-50%);
  background: radial-gradient(circle, rgba(42,245,152,.16), transparent 65%);
  animation: orbG 18s ease-in-out infinite alternate;
}
@keyframes orbP { from{transform:translate(0,0) scale(1)} to{transform:translate(80px,100px) scale(1.2)} }
@keyframes orbB { from{transform:translate(0,0) scale(1)} to{transform:translate(-70px,80px) scale(1.15)} }
@keyframes orbG { from{transform:translateX(-50%) scale(1)} to{transform:translateX(-50%) scale(1.25)} }

.content-header {
  display: none !important; padding: 0 !important;
  min-height: 0 !important; height: 0 !important;
}
.content-wrapper > .content {
  padding: 0 !important; display: flex; flex-direction: column;
  overflow: visible !important; height: auto !important; min-height: 100vh;
}
.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1; display: flex; flex-direction: column;
  height: auto !important; min-height: 100%; overflow: visible !important;
  position: relative; z-index: 1;
}
.wrapper, .content-wrapper {
  height: auto !important; min-height: 100vh; overflow: visible !important;
}
</style>

<style scoped>
/* ════════════════════════════════
   PÁGINA
════════════════════════════════ */
.lby-page {
  min-height: 100vh;
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 40px 16px 80px;
  z-index: 3;
}

/* ── Contenedor central ── */
.lby-wrap {
  width: 100%; max-width: 580px;
  display: flex; flex-direction: column;
  animation: fadeUp .5s ease both;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Back ── */
.lby-back {
  display: inline-flex; align-items: center; gap: 7px;
  color: #5578bb; font-size: 13px; font-weight: 600;
  text-decoration: none; letter-spacing: .4px;
  margin-bottom: 28px;
  transition: color .2s;
}
.lby-back:hover { color: #a8bdff; }
.lby-back i { font-size: 11px; }

/* ── Loading ── */
.lby-loading {
  display: flex; flex-direction: column; align-items: center;
  gap: 16px; color: #4e6491; font-size: 14px; padding: 80px 0;
}
.lby-spinner {
  width: 38px; height: 38px;
  border: 3px solid rgba(100,140,255,.18);
  border-top-color: #7b4fff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
.lby-spinner--sm { width: 22px; height: 22px; border-width: 2.5px; }
.lby-spinner--xs { width: 16px; height: 16px; border-width: 2px; display: inline-block; vertical-align: middle; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ════════════════════════════════
   HERO
════════════════════════════════ */
.lby-hero {
  position: relative;
  background: linear-gradient(160deg, #0d1635 0%, #080f28 100%);
  border: 1px solid rgba(100,140,255,.22);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 24px 70px rgba(0,0,0,.7), 0 0 0 1px rgba(80,120,255,.08) inset;
  margin-bottom: 14px;
}

.lby-hero__img-wrap {
  position: relative; height: 180px; overflow: hidden;
}
.lby-hero__img {
  width: 100%; height: 100%; object-fit: cover; display: block;
  transition: transform .5s ease;
}
.lby-hero:hover .lby-hero__img { transform: scale(1.04); }
.lby-hero__img-fade {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(5,10,28,.15) 0%, rgba(8,15,40,.92) 100%);
}

/* Status badge */
.lby-status--over {
  position: absolute; bottom: 14px; right: 16px; z-index: 2;
}

/* Countdown */
.lby-countdown {
  position: absolute; bottom: 14px; left: 16px; z-index: 2;
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(0,0,0,.55); backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.14);
  border-radius: 20px; padding: 5px 13px;
  font-size: 12px; font-weight: 700; color: #e0eaff;
  letter-spacing: .5px;
}
.lby-countdown i { color: #7b4fff; font-size: 11px; }

/* Cuerpo */
.lby-hero__body {
  position: relative; padding: 20px 26px 26px; text-align: center;
}
.lby-hero__glow {
  position: absolute; top: -40px; left: 0; right: 0; height: 180px;
  background: radial-gradient(ellipse 80% 55% at 50% 0%, rgba(123,79,255,.28), transparent 70%);
  pointer-events: none;
}

.lby-hero__title {
  font-family: 'Anton', sans-serif;
  font-size: clamp(22px, 3.5vw, 30px);
  letter-spacing: 3px;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  margin: 0 0 4px;
  position: relative; z-index: 1;
}

.lby-hero__code {
  font-size: 11px; color: #3d5280; letter-spacing: .6px;
  margin: 0 0 14px; position: relative; z-index: 1;
}
.lby-hero__code i { margin-right: 3px; }

/* Estado chips */
.lby-status {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 14px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: .8px;
  text-transform: uppercase;
}
.lby-status--active     { background: rgba(42,245,152,.18); border: 1px solid rgba(42,245,152,.4); color: #2af598; }
.lby-status--in_progress{ background: rgba(0,158,253,.18);  border: 1px solid rgba(0,158,253,.4);  color: #7cd4ff; }
.lby-status--pending    { background: rgba(255,195,54,.14); border: 1px solid rgba(255,195,54,.35); color: #ffc336; }
.lby-status--finished   { background: rgba(255,75,109,.14); border: 1px solid rgba(255,75,109,.35); color: #ff4b6d; }
.lby-status--offline    { background: rgba(100,100,120,.14);border: 1px solid rgba(120,130,160,.2); color: #8090aa; }
.lby-status--desconocido{ background: rgba(100,140,255,.10);border: 1px solid rgba(100,140,255,.2); color: #7c9dff; }

/* Chips tipo/modo */
.lby-hero__chips {
  display: flex; justify-content: center; gap: 8px;
  margin-bottom: 20px; flex-wrap: wrap;
  position: relative; z-index: 1;
}
.lby-chip {
  padding: 4px 13px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: .4px;
  display: flex; align-items: center; gap: 5px;
}
.lby-chip--type { background: rgba(123,79,255,.18); border: 1px solid rgba(123,79,255,.38); color: #b48fff; }
.lby-chip--mode { background: rgba(0,158,253,.14);  border: 1px solid rgba(0,158,253,.32);  color: #7cd4ff; }
.lby-chip--live {
  background: rgba(255,75,109,.18); border: 1px solid rgba(255,75,109,.4); color: #ff4b6d;
  animation: pulseLive 2s ease-in-out infinite;
}
.lby-chip--live i { font-size: 7px; }
@keyframes pulseLive {
  0%,100% { box-shadow: 0 0 0 0 rgba(255,75,109,0); }
  50%      { box-shadow: 0 0 0 5px rgba(255,75,109,.12); }
}

/* Premio principal */
.lby-hero__prize { position: relative; z-index: 1; }
.lby-hero__prize-lbl {
  display: block; font-size: 10px; color: #4e6491;
  text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;
}
.lby-hero__prize-val {
  display: flex; align-items: baseline; justify-content: center; gap: 8px;
}
.lby-hero__prize-num {
  font-family: 'Anton', sans-serif; font-size: 52px; line-height: 1;
  background: linear-gradient(135deg, #ffd166, #ff9f1c);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 4px 16px rgba(255,165,0,.45));
}
.lby-hero__prize-sym {
  font-size: 20px; font-weight: 700; color: #ffc336; -webkit-text-fill-color: #ffc336;
}

/* Premios secundarios */
.lby-hero__prizes-row {
  display: flex; justify-content: center; gap: 20px;
  margin-top: 16px; padding-top: 14px;
  border-top: 1px solid rgba(100,140,255,.1);
  position: relative; z-index: 1;
}
.lby-prize-mini { text-align: center; }
.lby-prize-mini__lbl {
  display: block; font-size: 9px; color: #4e6491;
  text-transform: uppercase; letter-spacing: .8px; margin-bottom: 3px;
}
.lby-prize-mini__val {
  font-family: 'Anton', sans-serif; font-size: 16px;
  color: #c8d8ff;
}

/* flash */
.lby-flash { animation: valFlash 1.8s ease-in-out; }
@keyframes valFlash {
  0%,100% { filter: none; }
  40%      { filter: drop-shadow(0 0 10px #2af598) brightness(1.3); }
}

/* ════════════════════════════════
   STATS
════════════════════════════════ */
.lby-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px; margin-bottom: 14px;
}
.lby-stat {
  background: linear-gradient(160deg, #0d1635 0%, #080f28 100%);
  border: 1px solid rgba(100,140,255,.14);
  border-radius: 18px;
  padding: 18px 10px;
  display: flex; flex-direction: column; align-items: center; gap: 7px;
  transition: border-color .2s, transform .2s;
}
.lby-stat:hover { border-color: rgba(100,140,255,.3); transform: translateY(-2px); }

.lby-stat__icon {
  width: 42px; height: 42px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-size: 17px;
}
.lby-stat__icon--green  { background: rgba(42,245,152,.14);  color: #2af598; }
.lby-stat__icon--blue   { background: rgba(0,158,253,.14);   color: #009efd; }
.lby-stat__icon--purple { background: rgba(123,79,255,.14);  color: #7b4fff; }
.lby-stat__icon--gold   { background: rgba(255,193,7,.14);   color: #ffc107; }
.lby-stat__icon--teal   { background: rgba(32,201,151,.14);  color: #20c997; }
.lby-stat__icon--orange { background: rgba(253,126,20,.14);  color: #fd7e14; }

.lby-stat__val {
  font-family: 'Anton', sans-serif; font-size: 20px; color: #e0eaff;
}
.lby-stat__lbl {
  font-size: 9px; color: #4e6491;
  text-transform: uppercase; letter-spacing: .8px; text-align: center;
}

/* ════════════════════════════════
   ACCIONES
════════════════════════════════ */
.lby-actions { display: flex; flex-direction: column; gap: 10px; }

.lby-btn {
  width: 100%; padding: 16px; border: none; border-radius: 16px;
  font-size: 14px; font-weight: 800; letter-spacing: 1.3px;
  text-transform: uppercase; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  text-decoration: none;
  transition: filter .2s, transform .15s, box-shadow .2s;
  position: relative; overflow: hidden;
}
.lby-btn::after {
  content: ''; position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.2), transparent);
  transform: skewX(-18deg); transition: left .45s ease;
}
.lby-btn:hover::after { left: 130%; }

.lby-btn--play {
  background: linear-gradient(90deg, #7b4fff 0%, #009efd 100%);
  color: #fff; box-shadow: 0 8px 28px rgba(123,79,255,.4);
}
.lby-btn--play:hover {
  filter: brightness(1.1); transform: translateY(-2px);
  box-shadow: 0 14px 38px rgba(123,79,255,.55);
}
.lby-btn--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #041a0e; box-shadow: 0 8px 28px rgba(42,245,152,.32);
}
.lby-btn--buy:hover {
  filter: brightness(1.08); transform: translateY(-2px);
  box-shadow: 0 14px 38px rgba(42,245,152,.48);
}
.lby-btn--disabled {
  background: rgba(255,255,255,.07); color: #2f4060;
  box-shadow: none; cursor: not-allowed;
}
.lby-btn--disabled::after { display: none; }

.lby-warn { font-size: 12px; color: #ffc336; text-align: center; margin: 0; }

/* ════════════════════════════════
   MODAL COMPRA
════════════════════════════════ */
.lby-modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(2,5,18,.88); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.lby-modal {
  width: 100%; max-width: 400px;
  background: linear-gradient(160deg, #0d1635 0%, #080f28 100%);
  border: 1px solid rgba(100,140,255,.26); border-radius: 22px;
  overflow: hidden; box-shadow: 0 28px 70px rgba(0,0,0,.8); position: relative;
}
.lby-modal__close {
  position: absolute; top: 12px; right: 12px;
  background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.14);
  color: #c8d8ff; width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 12px; transition: background .2s, transform .15s;
}
.lby-modal__close:hover { background: rgba(255,255,255,.16); transform: rotate(90deg); }

.lby-modal__head {
  padding: 22px 24px 16px;
  background: linear-gradient(90deg, rgba(0,74,255,.14), rgba(0,158,253,.1));
  border-bottom: 1px solid rgba(100,140,255,.12);
  display: flex; align-items: center; gap: 10px;
}
.lby-modal__head-icon { font-size: 18px; color: #7c9dff; }
.lby-modal__title { font-family: 'Anton',sans-serif; font-size: 18px; color: #c8d8ff; margin: 0; letter-spacing: 1px; }

.lby-modal__loading {
  display: flex; align-items: center; justify-content: center; gap: 12px;
  padding: 40px; color: #4e6491; font-size: 13px;
}

.lby-modal__body { padding: 18px 24px; display: flex; flex-direction: column; gap: 16px; }

.lby-modal__error {
  background: rgba(255,75,109,.12); border: 1px solid rgba(255,75,109,.3);
  border-radius: 10px; padding: 10px 14px;
  font-size: 13px; color: #ff4b6d;
  display: flex; align-items: center; gap: 8px;
}

.lby-modal__price-row {
  display: flex; align-items: center;
  background: rgba(255,255,255,.04); border: 1px solid rgba(100,140,255,.12);
  border-radius: 14px; padding: 14px 18px;
}
.lby-modal__price-item { flex: 1; text-align: center; }
.lby-modal__price-divider { width: 1px; height: 36px; background: rgba(100,140,255,.18); margin: 0 12px; }
.lby-modal__price-lbl { display: block; font-size: 10px; color: #4e6491; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 4px; }
.lby-modal__price-val { font-family: 'Anton',sans-serif; font-size: 20px; color: #e0eaff; }
.lby-modal__price-val--total { color: #2af598; }

.lby-modal__field { display: flex; flex-direction: column; gap: 8px; }
.lby-modal__label { font-size: 11px; color: #5578bb; text-transform: uppercase; letter-spacing: .8px; font-weight: 700; }

.lby-modal__qty { display: flex; align-items: center; gap: 12px; }
.lby-modal__qty-btn {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1.5px solid rgba(100,140,255,.3);
  background: rgba(255,255,255,.04); color: #7c9dff;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 12px; transition: all .2s;
}
.lby-modal__qty-btn:not(:disabled):hover { background: rgba(100,140,255,.15); border-color: rgba(100,140,255,.6); color: #fff; }
.lby-modal__qty-btn:disabled { opacity: .3; cursor: not-allowed; }
.lby-modal__qty-input {
  flex: 1; background: rgba(255,255,255,.05);
  border: 1.5px solid rgba(100,140,255,.2); border-radius: 10px;
  color: #e0eaff; font-family: 'Anton',sans-serif; font-size: 22px;
  text-align: center; padding: 6px 0; outline: none;
  -moz-appearance: textfield; appearance: none;
}
.lby-modal__qty-input::-webkit-inner-spin-button,
.lby-modal__qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }
.lby-modal__qty-input:focus { border-color: rgba(100,140,255,.5); }

.lby-modal__select {
  background: rgba(255,255,255,.05); border: 1.5px solid rgba(100,140,255,.2);
  border-radius: 10px; color: #e0eaff; font-size: 14px; padding: 10px 14px;
  outline: none; width: 100%; cursor: pointer;
}
.lby-modal__select:focus { border-color: rgba(100,140,255,.5); }

.lby-modal__foot { display: flex; gap: 10px; padding: 14px 24px 20px; }
.lby-modal__btn {
  flex: 1; padding: 13px; border: none; border-radius: 12px;
  font-size: 13px; font-weight: 800; letter-spacing: 1px;
  text-transform: uppercase; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: filter .2s, transform .15s;
}
.lby-modal__btn--cancel {
  background: rgba(255,255,255,.06); border: 1px solid rgba(100,140,255,.2); color: #5578bb;
}
.lby-modal__btn--cancel:hover { background: rgba(255,255,255,.1); }
.lby-modal__btn--confirm { background: linear-gradient(90deg,#2af598,#009efd); color: #031520; }
.lby-modal__btn--confirm:not(:disabled):hover { filter: brightness(1.08); transform: translateY(-1px); }
.lby-modal__btn--confirm:disabled { opacity: .55; cursor: not-allowed; }

/* Transition modal */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity .25s ease, transform .25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.95); }

/* ── Responsive ── */
@media (max-width: 560px) {
  .lby-stats { grid-template-columns: repeat(2, 1fr); }
  .lby-hero__prize-num { font-size: 42px; }
  .lby-hero__prizes-row { gap: 14px; }
}
@media (max-width: 380px) {
  .lby-stats { grid-template-columns: 1fr; }
}
</style>
