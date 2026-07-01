<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, reactive, watch } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { BingoService } from "@js/Services/bingoService";
import BingoPurchaseModal from "@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue";
import BingoRulesModal from "@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue";
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
  bingoRules?: string | null;
  imageBingoRules?: string | null;
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

// ── Toast de compra realtime ──
interface SaleToast { id: number; text: string }
const saleToasts = ref<SaleToast[]>([]);
let toastSeq = 0;

function pushSaleToast(totalCards: number) {
  const id = ++toastSeq;
  saleToasts.value.push({ id, text: `+1 cartón vendido · ${totalCards} total` });
  setTimeout(() => {
    saleToasts.value = saleToasts.value.filter(t => t.id !== id);
  }, 3200);
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
    onCardsChanged:   (totalCards) => pushSaleToast(totalCards),
    onCardsUpdated: (cardPrice, currencySymbol) => {
      if (cardPrice      !== null) live.cardPrice      = cardPrice;
      if (currencySymbol !== null) live.currencySymbol = currencySymbol;
    },
  },
);

// ── Purchase modal ──
const modal = useGameRoomPurchaseModal();

// ── Rules modal ──
const showRulesModal = ref(false);

// ── Players modal ──
const showPlayersModal = ref(false);
interface LobbyPlayer { user_id: string; name: string; cards_purchased: number; role: string }
const playersList     = ref<LobbyPlayer[]>([]);
const playersLoading  = ref(false);

async function openPlayersModal() {
  showPlayersModal.value = true;
  if (playersList.value.length > 0) return; // ya cargado
  playersLoading.value = true;
  try {
    const res = await fetch(`/bingo/${props.roomRouteKey}/lobby-players`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await res.json();
    playersList.value = data.players ?? [];
  } catch {
    playersList.value = [];
  } finally {
    playersLoading.value = false;
  }
}

// Re-cargar lista cuando el conteo WS cambia
watch(() => live.totalPlayers, () => {
  if (showPlayersModal.value) {
    playersList.value = []; // forzar recarga en próxima apertura
  }
});

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

// ── Redirección automática si el sorteo inicia ──
const redirecting = ref(false);
let redirectTimer: ReturnType<typeof setTimeout> | null = null;

function triggerGameRedirect(fromWs = false) {
  if (redirecting.value || !props.roomRouteKey) return;
  redirecting.value = true;
  const delay = fromWs ? 3000 : 1500;
  redirectTimer = setTimeout(() => {
    router.visit(`/bingo/${props.roomRouteKey}/game`);
  }, delay);
}

// Al montar: si la sala ya está en progreso, redirigir de inmediato
onMounted(() => {
  startCountdown();
  if (live.statusCode?.toUpperCase() === 'IN_PROGRESS') {
    triggerGameRedirect(false);
  }
});

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer);
  if (redirectTimer)  clearTimeout(redirectTimer);
});

// WS: cuando el status cambia a IN_PROGRESS dar feedback y redirigir
watch(() => live.statusCode, (next) => {
  if (next?.toUpperCase() === 'IN_PROGRESS') {
    triggerGameRedirect(true);
  }
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

    <!-- FAB: reglamento del bingo (solo ícono) -->
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
    <div v-if="isRoomLoading" class="lby-loading">
      <div class="lby-spinner" />
    </div>

    <template v-else>
      <div class="lby-shell">

        <!-- ── HERO: banner + info flotante ── -->
        <div class="lby-hero">
          <div class="lby-hero__clip">
            <img :src="bingoRoomImg" alt="Sala" class="lby-hero__img" />
            <div class="lby-hero__overlay" />

            <!-- Nav flotante: solo WS badge (sin botón retroceso) -->
            <div class="lby-hero__nav">
              <!-- chips de sala -->
              <div class="lby-hero__chips">
                <span class="lby-chip lby-chip--type">
                  <i class="fas fa-globe" /> {{ room.room_type_description ?? 'Pública' }}
                </span>
                <span class="lby-chip lby-chip--mode">
                  <i class="fas fa-gamepad" /> {{ room.game_mode_description ?? 'Online' }}
                </span>
                <span v-if="room.rounds" class="lby-chip lby-chip--rounds">
                  <i class="fas fa-rotate" /> {{ room.rounds }}R
                </span>
                <span v-if="room.game_variant" class="lby-chip lby-chip--variant">
                  {{ room.game_variant }}
                </span>
              </div>
              <!-- Badge combinado: "En vivo · Disponible" si activa y conectado -->
              <div
                v-if="wsStatus === 'connected' && (live.statusCode || room?.status_code)?.toUpperCase() === 'ACTIVE'"
                class="lby-ws lby-ws--connected lby-ws--live-active"
              >
                <span class="lby-ws__dot" />
                <span class="lby-ws__label">En vivo</span>
                <span class="lby-ws__sep">·</span>
                <span class="lby-ws__status">Disponible</span>
              </div>
              <div v-else class="lby-ws" :class="`lby-ws--${wsStatus}`">
                <span class="lby-ws__dot" />
                <span class="lby-ws__label">{{ wsLabel }}</span>
              </div>
            </div>

            <!-- Zona inferior del hero: premio + timer + sub-premios -->
            <div class="lby-hero__bottom">
              <!-- Premio principal -->
              <div class="lby-prize" :class="{ 'lby-flash': prizeFlash }">
                <span class="lby-prize__lbl"><i class="fas fa-trophy" /> Premio Acumulado</span>
                <div class="lby-prize__val">
                  <span class="lby-prize__num">{{ formattedPrize }}</span>
                  <span class="lby-prize__sym">{{ live.currencySymbol }}</span>
                </div>
              </div>

              <!-- Lado derecho: timer + status + sub-premios -->
              <div class="lby-hero__right">
                <div
                  v-if="countdown && !isLive"
                  class="lby-timer"
                  :class="{ 'lby-timer--urgent': isUrgent }"
                >
                  <i class="fas fa-clock" /> {{ countdown }}
                </div>
                <span
                  v-if="!(wsStatus === 'connected' && (live.statusCode || room?.status_code)?.toUpperCase() === 'ACTIVE')"
                  class="lby-status-chip"
                  :class="statusClass"
                >{{ roomStatusText }}</span>

                <div v-if="room.full_house_prize || room.serial_prize" class="lby-prize-sub">
                  <div v-if="room.full_house_prize" class="lby-prize-sub__item">
                    <span class="lby-prize-sub__lbl">Casa Llena</span>
                    <span class="lby-prize-sub__val">{{ (Number(room.full_house_prize) * rate).toFixed(2) }} <em class="lby-prize-sub__sym">{{ live.currencySymbol }}</em></span>
                  </div>
                  <div v-if="room.serial_prize" class="lby-prize-sub__item">
                    <span class="lby-prize-sub__lbl">Serial</span>
                    <span class="lby-prize-sub__val">{{ (Number(room.serial_prize) * rate).toFixed(2) }} <em class="lby-prize-sub__sym">{{ live.currencySymbol }}</em></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── PANEL: progreso realtime ── -->
        <div
          v-if="showCardsProgress"
          class="lby-progress-panel"
          :class="{ 'lby-progress-panel--ready': cardsProgressReady }"
        >
          <div class="lby-progress-panel__head">
            <span class="lby-progress-panel__title">
              <i class="fas fa-layer-group" />
              {{ cardsProgressReady ? '¡Listos para iniciar!' : 'Cartones para iniciar' }}
            </span>
            <span class="lby-progress-panel__count">
              <span class="lby-progress-panel__cur">{{ live.totalCards }}</span>
              <span class="lby-progress-panel__sep">/</span>
              <span class="lby-progress-panel__max">{{ minCardsToStart }}</span>
            </span>
          </div>
          <div class="lby-progress-panel__track">
            <div
              class="lby-progress-panel__fill"
              :style="{ width: cardsProgressPct + '%' }"
            />
            <div
              v-if="!cardsProgressReady"
              class="lby-progress-panel__remaining"
              :style="{ width: (100 - cardsProgressPct) + '%' }"
            />
          </div>
          <div v-if="!cardsProgressReady" class="lby-progress-panel__hint">
            <i class="fas fa-hourglass-half" />
            Faltan <strong>{{ minCardsToStart! - live.totalCards }}</strong> cartones para iniciar
          </div>
        </div>

        <!-- ── PANEL: stats + acciones ── -->
        <div class="lby-panel">

          <!-- Stats row -->
          <div class="lby-stats">
            <div
              class="lby-stat lby-stat--clickable"
              :class="{ 'lby-flash': playersFlash }"
              title="Ver lista de jugadores"
              @click="openPlayersModal"
            >
              <div class="lby-stat__icon-wrap"><i class="fas fa-users" /></div>
              <span class="lby-stat__val">{{ live.totalPlayers }}</span>
              <span class="lby-stat__lbl">Jugadores</span>
            </div>
            <div class="lby-stat lby-stat--mine">
              <div class="lby-stat__icon-wrap"><i class="fas fa-ticket-alt" /></div>
              <span class="lby-stat__val">{{ live.userCards }}</span>
              <span class="lby-stat__lbl">Mis cartones</span>
            </div>
            <div class="lby-stat" :class="{ 'lby-stat--ready': cardsProgressReady, 'lby-stat--closed': room.close_room }">
              <div class="lby-stat__icon-wrap">
                <i :class="minCardsToStart ? (cardsProgressReady ? 'fas fa-check-circle' : 'fas fa-hourglass-half') : (room.close_room ? 'fas fa-lock' : 'fas fa-lock-open')" />
              </div>
              <span class="lby-stat__val lby-stat__val--sm">
                <template v-if="minCardsToStart">
                  {{ cardsProgressReady ? '¡Listo!' : minCardsToStart! - live.totalCards }}
                </template>
                <template v-else>
                  {{ room.close_room ? 'Cerrada' : 'Abierta' }}
                </template>
              </span>
              <span class="lby-stat__lbl">
                {{ minCardsToStart ? (cardsProgressReady ? 'Meta alcanzada' : 'Faltan') : 'Sala' }}
              </span>
            </div>
            <div v-if="live.cardPrice" class="lby-stat">
              <div class="lby-stat__icon-wrap"><i class="fas fa-tag" /></div>
              <span class="lby-stat__val">{{ Number(live.cardPrice).toFixed(2) }}</span>
              <span class="lby-stat__lbl">Precio / cartón</span>
            </div>
            <div v-if="room.time_chip" class="lby-stat">
              <div class="lby-stat__icon-wrap"><i class="fas fa-stopwatch" /></div>
              <span class="lby-stat__val">{{ room.time_chip }}s</span>
              <span class="lby-stat__lbl">Tiempo ficha</span>
            </div>
          </div>

          <!-- Divisor -->
          <div class="lby-divider" />

          <!-- Acciones -->
          <div class="lby-actions">
            <Link
              v-if="canNavigateGame"
              :href="`/bingo/${roomRouteKey}/game`"
              class="lby-btn lby-btn--play"
            >
              <i class="fas fa-play" /> Entrar al Juego
            </Link>
            <button v-else class="lby-btn lby-btn--disabled" disabled>
              <i class="fas fa-play" /> Entrar al Juego
            </button>

            <button v-if="canPurchaseCards" class="lby-btn lby-btn--buy" @click="openPurchase">
              <i class="fas fa-plus" />
              {{ live.userCards > 0 ? 'Más cartones' : 'Comprar cartones' }}
            </button>
          </div>

          <p v-if="!canNavigateGame" class="lby-warn">
            <i class="fas fa-exclamation-triangle" /> Sala sin clave válida — recarga la página.
          </p>
        </div>

      </div>
    </template>

    <!-- Toasts de compra realtime -->
    <div class="lby-toasts">
      <TransitionGroup name="lby-toast">
        <div v-for="t in saleToasts" :key="t.id" class="lby-toast">
          <i class="fas fa-ticket-alt lby-toast__icon" />
          <span>{{ t.text }}</span>
        </div>
      </TransitionGroup>
    </div>

    <!-- Modal de jugadores -->
    <Transition name="lby-modal">
      <div v-if="showPlayersModal" class="lby-overlay" @click.self="showPlayersModal = false">
        <div class="lby-modal">
          <button class="lby-modal__close" @click="showPlayersModal = false">
            <i class="fas fa-times" />
          </button>
          <div class="lby-modal__header">
            <i class="fas fa-trophy lby-modal__icon" />
            <div>
              <h3 class="lby-modal__title">Top Jugadores</h3>
              <p class="lby-modal__sub">{{ live.totalPlayers }} jugador{{ live.totalPlayers === 1 ? '' : 'es' }} · ranking por cartones</p>
            </div>
          </div>

          <div v-if="playersLoading" class="lby-modal__loading">
            <div class="lby-spinner" /> Cargando…
          </div>
          <div v-else-if="playersList.length === 0" class="lby-modal__empty">
            <i class="fas fa-user-slash" /> Sin jugadores registrados
          </div>
          <div v-else class="lby-modal__list">
            <!-- Top 10 visibles -->
            <div
              v-for="(p, i) in playersList.slice(0, 10)"
              :key="p.user_id"
              class="lby-player-row"
              :class="[
                i === 0 ? 'lby-player-row--gold' :
                i === 1 ? 'lby-player-row--silver' :
                i === 2 ? 'lby-player-row--bronze' : '',
                { 'lby-player-row--me': String(p.user_id) === String(page.props.auth?.user?.id) }
              ]"
            >
              <!-- Medalla / rank -->
              <span class="lby-player-row__medal">
                <template v-if="i === 0"><i class="fas fa-medal lby-medal--gold" /></template>
                <template v-else-if="i === 1"><i class="fas fa-medal lby-medal--silver" /></template>
                <template v-else-if="i === 2"><i class="fas fa-medal lby-medal--bronze" /></template>
                <span v-else class="lby-player-row__rank">{{ i + 1 }}</span>
              </span>

              <span class="lby-player-row__name">
                {{ p.name }}
                <span v-if="String(p.user_id) === String(page.props.auth?.user?.id)" class="lby-player-row__you">TÚ</span>
              </span>
              <span class="lby-player-row__ctns">
                <i class="fas fa-ticket-alt" /> {{ p.cards_purchased }}
              </span>
            </div>

            <!-- Resto difuminado (si hay más de 10) -->
            <template v-if="playersList.length > 10">
              <div class="lby-modal__rest-fade">
                <div
                  v-for="(p, i) in playersList.slice(10, 13)"
                  :key="p.user_id"
                  class="lby-player-row lby-player-row--faded"
                  :style="{ opacity: 0.35 - i * 0.1 }"
                >
                  <span class="lby-player-row__medal">
                    <span class="lby-player-row__rank">{{ 11 + i }}</span>
                  </span>
                  <span class="lby-player-row__name">{{ p.name }}</span>
                  <span class="lby-player-row__ctns">
                    <i class="fas fa-ticket-alt" /> {{ p.cards_purchased }}
                  </span>
                </div>
                <div class="lby-modal__rest-label">
                  <i class="fas fa-ellipsis-h" />
                  {{ playersList.length - 10 }} jugador{{ playersList.length - 10 === 1 ? '' : 'es' }} más
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Banner: sorteo iniciado → redirigiendo -->
    <Transition name="lby-redirect">
      <div v-if="redirecting" class="lby-redirect-banner">
        <div class="lby-redirect-banner__inner">
          <div class="lby-redirect-banner__spinner" />
          <div>
            <p class="lby-redirect-banner__title">¡El sorteo ha comenzado!</p>
            <p class="lby-redirect-banner__sub">Entrando al juego…</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal de reglamento -->
    <BingoRulesModal
      v-model="showRulesModal"
      :rules="props.bingoRules"
      :image="props.imageBingoRules"
    />

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
   PÁGINA — fondo idéntico a /bingo/game-rooms
═══════════════════════════════════════════════════════ */
.lby-page {
  flex: 1;
  width: 100%;
  box-sizing: border-box;
  min-height: calc(100vh - 56px - 44px);
  margin: -0.5rem -0.5rem 0;
  padding: 18px 20px 36px;
  display: flex;
  flex-direction: column;
  position: relative;
  isolation: isolate;
  background: linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}

/* ── Capa 1: estrellas finas (mismo que game-rooms) ── */
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
  0%   { opacity: 0.55; } 40% { opacity: 0.95; }
  70%  { opacity: 0.70; } 100% { opacity: 0.85; }
}
@keyframes lbyTwinkleB {
  0%   { opacity: 0.70; } 35% { opacity: 0.50; }
  65%  { opacity: 1.00; } 100% { opacity: 0.65; }
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
  width: 100%; max-width: 860px;
  margin: 0 auto;
  flex: 1;
  display: flex; flex-direction: column; justify-content: center; gap: 10px;
  animation: lbyFadeUp .45s cubic-bezier(.22,1,.36,1) both;
}
@keyframes lbyFadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════════
   HERO — banner + nav integrada
═══════════════════════════════════ */
.lby-hero {
  position: relative; border-radius: 16px;
  border: 1px solid rgba(255,255,255,.07);
  box-shadow: 0 12px 40px rgba(0,0,0,.6), 0 1px 0 rgba(255,255,255,.05) inset;
  min-height: 170px;
  transition: box-shadow .3s, border-color .3s;
}
.lby-hero:hover {
  border-color: rgba(42,245,152,.18);
  box-shadow: 0 18px 52px rgba(0,0,0,.7), 0 0 32px rgba(42,245,152,.06);
}
.lby-hero__clip {
  position: relative; border-radius: inherit; overflow: hidden;
  min-height: inherit; height: 100%;
  display: flex; flex-direction: column; justify-content: space-between;
}
.lby-hero__img {
  position: absolute; inset: 0;
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .7s ease;
}
.lby-hero:hover .lby-hero__img { transform: scale(1.04); }
.lby-hero__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg,
    rgba(4,8,20,.70) 0%,
    rgba(4,8,20,.20) 30%,
    rgba(4,8,20,.82) 68%,
    rgba(4,8,20,.98) 100%);
}

/* ── nav: chips izq + ws badge der ── */
.lby-hero__nav {
  position: relative; z-index: 5;
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  padding: 10px 14px 0;
}
.lby-hero__chips { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }

.lby-chip {
  padding: 2px 9px; border-radius: 5px;
  font-size: 9px; font-weight: 700; letter-spacing: .3px;
  display: inline-flex; align-items: center; gap: 4px;
  white-space: nowrap; backdrop-filter: blur(10px);
}
.lby-chip i { font-size: 8px; }
.lby-chip--type    { background: rgba(40,110,190,.18); border: 1px solid rgba(40,110,190,.35); color: #6aaad8; }
.lby-chip--mode    { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); color: #9fb3da; }
.lby-chip--rounds  { background: rgba(130,60,220,.15); border: 1px solid rgba(130,60,220,.32); color: #a878e8; }
.lby-chip--variant { background: rgba(255,150,0,.1); border: 1px solid rgba(255,150,0,.25); color: #e09020; }

/* ws badge */
.lby-ws {
  display: inline-flex; align-items: center; gap: 5px; flex-shrink: 0;
  padding: 3px 11px; border-radius: 20px;
  font-size: 10px; font-weight: 700;
  background: rgba(4,8,20,.6); border: 1px solid rgba(255,255,255,.12);
  backdrop-filter: blur(10px);
}
.lby-ws__dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.lby-ws__label { color: #3a5068; }
.lby-ws--connected   .lby-ws__dot { background: #2af598; box-shadow: 0 0 5px rgba(42,245,152,.7); animation: lbyDotPulse 2s infinite; }
.lby-ws--connected   .lby-ws__label { color: #2af598; }
.lby-ws--connecting  .lby-ws__dot { background: #ffc336; animation: lbyDotBlink .8s infinite; }
.lby-ws--connecting  .lby-ws__label { color: #ffc336; }
.lby-ws--disconnected .lby-ws__dot, .lby-ws--error .lby-ws__dot { background: #ff4d6d; }
.lby-ws--disconnected .lby-ws__label, .lby-ws--error .lby-ws__label { color: #ff4d6d; }
@keyframes lbyDotPulse { 0%,100%{opacity:1} 50%{opacity:.3} }
@keyframes lbyDotBlink  { 0%,100%{opacity:1} 50%{opacity:.1} }
.lby-ws--live-active {
  background: rgba(4,18,10,.7);
  border-color: rgba(42,245,152,.38);
  animation: lbyNavBadgePulse 3s ease-in-out infinite;
}
.lby-ws__sep { color: rgba(255,255,255,.2); font-size: 8px; margin: 0 1px; }
.lby-ws__status { color: #9ecaf0; font-size: 9.5px; font-weight: 600; }
@keyframes lbyNavBadgePulse {
  0%,100% { box-shadow: 0 0 6px rgba(42,245,152,.1); border-color: rgba(42,245,152,.3); }
  50%     { box-shadow: 0 0 14px rgba(42,245,152,.3); border-color: rgba(42,245,152,.55); }
}

/* ── parte inferior del hero: premio + derecha ── */
.lby-hero__bottom {
  position: relative; z-index: 5;
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 10px; flex-wrap: wrap;
  padding: 8px 14px 12px;
}
.lby-hero__right {
  display: flex; flex-direction: column; align-items: flex-end; gap: 6px;
}

/* premio */
.lby-prize { display: flex; flex-direction: column; min-width: 0; }
.lby-prize__lbl {
  font-size: 8.5px; color: #b8901c;
  text-transform: uppercase; letter-spacing: 1.8px;
  display: inline-flex; align-items: center; gap: 4px; margin-bottom: 2px;
}
.lby-prize__val { display: flex; align-items: baseline; gap: 6px; }
.lby-prize__num {
  font-family: 'Anton', sans-serif;
  font-size: clamp(22px, 3.2vw, 34px); line-height: 1; letter-spacing: .3px;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 12px rgba(255,150,0,.35));
}
.lby-prize__sym { font-size: 14px; font-weight: 800; color: #d09010; }

/* sub-premios */
.lby-prize-sub { display: flex; gap: 14px; }
.lby-prize-sub__item { text-align: right; }
.lby-prize-sub__lbl { display: block; font-size: 7px; color: #3a5068; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 1px; }
.lby-prize-sub__val { font-family: 'Anton', sans-serif; font-size: 12px; color: #b0c4e0; }
.lby-prize-sub__sym { font-style: normal; font-size: 9px; font-weight: 700; color: #6a8090; margin-left: 2px; }

/* timer + status (derecha del hero) */
.lby-timer {
  background: rgba(4,8,20,.85); border: 1px solid rgba(255,255,255,.1);
  backdrop-filter: blur(10px); color: #9ecaf0;
  border-radius: 30px; padding: 3px 13px;
  font-family: 'Anton', sans-serif; font-size: 14px; letter-spacing: 1px;
  display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
}
.lby-timer i { font-size: 9px; opacity: .55; }
.lby-timer--urgent {
  border-color: rgba(255,77,109,.4); color: #ff8aaa;
  animation: lbyPulseUrgent 1.2s infinite;
}
@keyframes lbyPulseUrgent { 0%,100%{box-shadow:none} 50%{box-shadow:0 0 0 4px rgba(255,77,109,.12)} }

.lby-status-chip {
  padding: 3px 12px; border-radius: 20px;
  font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
  white-space: nowrap; backdrop-filter: blur(8px);
}
.lby-status--active      { background: rgba(42,245,152,.1); border: 1px solid rgba(42,245,152,.3); color: #2af598; }
.lby-status--in_progress { background: rgba(255,77,109,.1); border: 1px solid rgba(255,77,109,.3); color: #ff6e8a; }
.lby-status--pending     { background: rgba(255,195,54,.08); border: 1px solid rgba(255,195,54,.25); color: #ffc336; }
.lby-status--finished    { background: rgba(255,77,109,.06); border: 1px solid rgba(255,77,109,.18); color: #a04060; }
.lby-status--offline,
.lby-status--desconocido { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); color: #3a5068; }

/* flash genérico */
.lby-flash { animation: lbyFlash 1.8s ease-in-out; }
@keyframes lbyFlash { 0%,100%{filter:none} 40%{filter:drop-shadow(0 0 12px rgba(42,245,152,.7)) brightness(1.2)} }

/* ═══════════════════════════════════
   PANEL DE PROGRESO (realtime)
═══════════════════════════════════ */
.lby-progress-panel {
  background: rgba(255,255,255,.022);
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 13px; padding: 9px 14px;
  display: flex; flex-direction: column; gap: 6px;
  backdrop-filter: blur(6px);
  box-shadow: 0 4px 20px rgba(0,0,0,.4);
  transition: border-color .3s;
}
.lby-progress-panel--ready {
  border-color: rgba(42,245,152,.22);
  box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 0 20px rgba(42,245,152,.06);
}

.lby-progress-panel__head {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.lby-progress-panel__title {
  font-size: 9.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  color: #3a5068; display: inline-flex; align-items: center; gap: 6px;
}
.lby-progress-panel__title i { font-size: 9px; }
.lby-progress-panel--ready .lby-progress-panel__title { color: #2af598; }

.lby-progress-panel__count {
  display: flex; align-items: baseline; gap: 2px; flex-shrink: 0;
}
.lby-progress-panel__cur {
  font-family: 'Anton', sans-serif; font-size: 15px; color: #c8d8f0;
  transition: color .3s;
}
.lby-progress-panel--ready .lby-progress-panel__cur { color: #2af598; }
.lby-progress-panel__sep { font-size: 11px; color: #2a3f58; margin: 0 1px; }
.lby-progress-panel__max { font-family: 'Anton', sans-serif; font-size: 12px; color: #3a5068; }

.lby-progress-panel__track {
  height: 6px; border-radius: 20px; overflow: hidden;
  background: rgba(255,255,255,.05);
  display: flex;
}
.lby-progress-panel__fill {
  height: 100%; border-radius: 20px 0 0 20px;
  background: linear-gradient(90deg, #1450b8 0%, #2af598 100%);
  box-shadow: 0 0 10px rgba(42,245,152,.3);
  transition: width .7s cubic-bezier(.22,1,.36,1);
}
.lby-progress-panel--ready .lby-progress-panel__fill {
  border-radius: 20px;
  background: linear-gradient(90deg, #2af598 0%, #00ffd0 100%);
  animation: lbyCpPulse 1.8s ease-in-out infinite;
}
.lby-progress-panel__remaining {
  height: 100%; border-radius: 0 20px 20px 0;
  background: rgba(255,255,255,.03);
}
@keyframes lbyCpPulse {
  0%,100% { box-shadow: 0 0 6px rgba(42,245,152,.35); }
  50%     { box-shadow: 0 0 16px rgba(42,245,152,.7); }
}

.lby-progress-panel__hint {
  font-size: 9px; color: #3a5068;
  display: flex; align-items: center; gap: 5px;
}
.lby-progress-panel__hint i { font-size: 8px; opacity: .6; }
.lby-progress-panel__hint strong { color: #9ecaf0; font-weight: 700; }

/* ═══════════════════════════════════
   PANEL STATS + ACCIONES
═══════════════════════════════════ */
.lby-panel {
  background: rgba(255,255,255,.022);
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 13px; padding: 10px 14px;
  display: flex; flex-direction: column; gap: 8px;
  box-shadow: 0 4px 24px rgba(0,0,0,.45);
  backdrop-filter: blur(6px);
}

.lby-stats {
  display: flex; gap: 6px; align-items: stretch;
}
.lby-stat {
  flex: 1;
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 9px; padding: 6px 5px;
  display: flex; flex-direction: column; align-items: center; gap: 3px;
  transition: border-color .2s, transform .18s, background .2s;
  min-width: 0;
}
.lby-stat:hover {
  border-color: rgba(42,245,152,.2);
  background: rgba(42,245,152,.03);
  transform: translateY(-1px);
}
.lby-stat--mine {
  border-color: rgba(42,245,152,.15);
  background: rgba(42,245,152,.04);
}
.lby-stat--mine:hover { border-color: rgba(42,245,152,.32); }
.lby-stat--ready { border-color: rgba(42,245,152,.2); background: rgba(42,245,152,.04); }
.lby-stat--ready .lby-stat__icon-wrap { color: #2af598; border-color: rgba(42,245,152,.25); background: rgba(42,245,152,.1); }
.lby-stat--ready .lby-stat__val { color: #2af598; }
.lby-stat--closed { border-color: rgba(255,77,109,.15); background: rgba(255,77,109,.03); }
.lby-stat--closed .lby-stat__icon-wrap { color: #ff6e8a; border-color: rgba(255,77,109,.2); background: rgba(255,77,109,.08); }
.lby-stat--closed .lby-stat__val { color: #ff6e8a; }

.lby-stat__icon-wrap {
  width: 22px; height: 22px; border-radius: 6px;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #3a5068;
  transition: background .2s, color .2s, border-color .2s;
}
.lby-stat:hover .lby-stat__icon-wrap {
  background: rgba(42,245,152,.1); border-color: rgba(42,245,152,.25); color: #2af598;
}
.lby-stat--mine .lby-stat__icon-wrap { color: #2af598; border-color: rgba(42,245,152,.2); background: rgba(42,245,152,.08); }

.lby-stat__val { font-family: 'Anton', sans-serif; font-size: 16px; color: #c8d8f0; line-height: 1; }
.lby-stat__val--sm { font-size: 12px; }
.lby-stat--mine .lby-stat__val { color: #2af598; }
.lby-stat__lbl { font-size: 7px; color: #2a3f58; text-transform: uppercase; letter-spacing: .6px; text-align: center; white-space: nowrap; }

.lby-divider {
  height: 1px; background: rgba(255,255,255,.05); margin: 0 -2px;
}

/* acciones */
.lby-actions { display: flex; gap: 8px; }

.lby-btn {
  height: 38px; padding: 0 18px; border: none; border-radius: 10px;
  font-size: 11px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  text-decoration: none; white-space: nowrap; min-width: 0; flex: 1;
  position: relative; overflow: hidden;
  transition: transform .15s, box-shadow .2s, filter .2s;
}
.lby-btn::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.1) 50%, transparent 100%);
  transform: translateX(-100%); transition: transform .5s ease;
}
.lby-btn:hover::after { transform: translateX(100%); }

.lby-btn--play {
  background: linear-gradient(100deg, #14804e 0%, #2af598 100%);
  color: #021a0c;
  box-shadow: 0 6px 24px rgba(42,245,152,.24), inset 0 1px 0 rgba(255,255,255,.12);
}
.lby-btn--play:hover {
  filter: brightness(1.08); transform: translateY(-2px);
  box-shadow: 0 10px 32px rgba(42,245,152,.38), inset 0 1px 0 rgba(255,255,255,.15);
}

.lby-btn--buy {
  background: rgba(200,150,0,.08);
  border: 1px solid rgba(255,191,0,.22); color: #c89020;
  box-shadow: 0 3px 14px rgba(0,0,0,.3);
}
.lby-btn--buy:hover {
  background: rgba(255,191,0,.12); border-color: rgba(255,191,0,.42);
  transform: translateY(-2px); box-shadow: 0 7px 22px rgba(0,0,0,.38);
}

.lby-btn--disabled {
  background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.05);
  color: #2a3f58; cursor: not-allowed;
}
.lby-btn--disabled::after { display: none; }

.lby-warn {
  font-size: 10px; color: #907020; text-align: center; margin: 0;
  display: flex; align-items: center; justify-content: center; gap: 5px;
}

/* ═══════════════════════════════════
   FAB — REGLAMENTO (solo ícono)
═══════════════════════════════════ */
.lby-rules-fab {
  position: fixed; right: 20px; bottom: 69px; z-index: 200;
  width: 42px; height: 42px; border-radius: 50%;
  border: 1px solid rgba(42,245,152,.28);
  background: rgba(4,8,20,.88); color: #2af598;
  font-size: 14px;
  cursor: pointer; backdrop-filter: blur(14px);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 22px rgba(0,0,0,.55);
  transition: transform .2s, box-shadow .2s, border-color .2s, background .2s;
  animation: lbyFabPulse 3.5s ease-in-out infinite;
}
.lby-rules-fab:hover {
  transform: translateY(-2px) scale(1.08);
  background: rgba(4,18,10,.95);
  border-color: rgba(42,245,152,.6);
  box-shadow: 0 6px 26px rgba(0,0,0,.6), 0 0 20px rgba(42,245,152,.25);
  animation: none;
}
.lby-rules-fab:active { transform: scale(.94); }
@keyframes lbyFabPulse {
  0%,100% { box-shadow: 0 4px 22px rgba(0,0,0,.55), 0 0 0 0 rgba(42,245,152,.3); }
  50%     { box-shadow: 0 4px 22px rgba(0,0,0,.55), 0 0 0 7px rgba(42,245,152,.0); }
}

/* ═══════════════════════════════════
   MODAL JUGADORES
═══════════════════════════════════ */
.lby-overlay {
  position: fixed; inset: 0; z-index: 400;
  background: rgba(2,5,16,.78); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}
.lby-modal {
  position: relative;
  width: 100%; max-width: 420px; max-height: 80vh;
  background: linear-gradient(160deg, #0d1530 0%, #0a1020 100%);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.04) inset;
  display: flex; flex-direction: column; overflow: hidden;
}
.lby-modal__close {
  position: absolute; top: 12px; right: 12px;
  width: 28px; height: 28px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,.1);
  background: rgba(255,255,255,.05); color: rgba(255,255,255,.5);
  font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s, color .2s;
}
.lby-modal__close:hover { background: rgba(255,77,109,.15); color: #ff6e8a; border-color: rgba(255,77,109,.25); }

.lby-modal__header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 18px 14px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.lby-modal__icon {
  font-size: 18px; color: #ffc200; flex-shrink: 0;
  width: 40px; height: 40px; border-radius: 10px;
  background: rgba(255,194,0,.08); border: 1px solid rgba(255,194,0,.18);
  display: flex; align-items: center; justify-content: center;
  filter: drop-shadow(0 0 6px rgba(255,194,0,.2));
}
.lby-modal__title { font-size: 15px; font-weight: 700; color: #e0eaff; margin: 0 0 2px; }
.lby-modal__sub   { font-size: 10px; color: #3a5068; margin: 0; }

.lby-modal__loading {
  display: flex; align-items: center; gap: 10px;
  padding: 28px 18px; color: #4a6080; font-size: 11px;
}
.lby-modal__empty {
  padding: 28px 18px; text-align: center;
  color: #3a5068; font-size: 11px;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}

.lby-modal__list {
  overflow-y: auto; flex: 1;
  padding: 8px 0;
}
.lby-modal__list::-webkit-scrollbar { width: 3px; }
.lby-modal__list::-webkit-scrollbar-track { background: transparent; }
.lby-modal__list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 2px; }

/* filas */
.lby-player-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 18px;
  border-bottom: 1px solid rgba(255,255,255,.035);
  transition: background .15s;
  position: relative;
}
.lby-player-row:last-child { border-bottom: none; }
.lby-player-row:hover { background: rgba(255,255,255,.02); }

/* medalla / rank */
.lby-player-row__medal {
  width: 22px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.lby-player-row__rank { font-size: 9px; color: #2a3f58; font-weight: 700; }

/* oro */
.lby-player-row--gold {
  background: linear-gradient(90deg, rgba(255,191,0,.07) 0%, transparent 100%);
}
.lby-player-row--gold:hover { background: linear-gradient(90deg, rgba(255,191,0,.12) 0%, transparent 100%); }
.lby-medal--gold { font-size: 15px; color: #ffc200; filter: drop-shadow(0 0 5px rgba(255,194,0,.55)); }

/* plata */
.lby-player-row--silver {
  background: linear-gradient(90deg, rgba(180,200,220,.06) 0%, transparent 100%);
}
.lby-medal--silver { font-size: 15px; color: #b0c4d8; filter: drop-shadow(0 0 4px rgba(176,196,216,.4)); }

/* bronce */
.lby-player-row--bronze {
  background: linear-gradient(90deg, rgba(180,100,30,.07) 0%, transparent 100%);
}
.lby-medal--bronze { font-size: 15px; color: #c87840; filter: drop-shadow(0 0 4px rgba(200,120,64,.4)); }

/* usuario actual */
.lby-player-row--me { background: rgba(42,245,152,.04); }
.lby-player-row--me:hover { background: rgba(42,245,152,.07); }
.lby-player-row--me .lby-player-row__name { color: #2af598; }
.lby-player-row--me .lby-player-row__ctns { color: #2af598; }

.lby-player-row__name {
  flex: 1; font-size: 11.5px; color: #c8d8f0; font-weight: 600;
  min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  display: flex; align-items: center; gap: 6px;
}
.lby-player-row__you {
  font-size: 8px; font-weight: 800; letter-spacing: .5px;
  background: rgba(42,245,152,.15); border: 1px solid rgba(42,245,152,.3); color: #2af598;
  padding: 1px 5px; border-radius: 4px; flex-shrink: 0;
}
.lby-player-row__ctns {
  font-size: 10px; font-weight: 700; color: #3a5068; flex-shrink: 0;
  display: flex; align-items: center; gap: 4px;
}
.lby-player-row__ctns i { font-size: 8px; }

/* difuminado de los que van después del top 10 */
.lby-modal__rest-fade {
  position: relative;
  /* máscara que desvanece hacia abajo */
  mask-image: linear-gradient(180deg, rgba(0,0,0,.5) 0%, transparent 85%);
  -webkit-mask-image: linear-gradient(180deg, rgba(0,0,0,.5) 0%, transparent 85%);
  pointer-events: none;
}
.lby-player-row--faded { transition: none; }
.lby-modal__rest-label {
  position: absolute; bottom: 2px; left: 0; right: 0;
  text-align: center; font-size: 9px; color: #2a3f58;
  display: flex; align-items: center; justify-content: center; gap: 5px;
  padding-bottom: 6px;
}

/* stat clickeable */
.lby-stat--clickable { cursor: pointer; }
.lby-stat--clickable:hover { border-color: rgba(100,170,216,.3) !important; background: rgba(40,110,190,.06) !important; }
.lby-stat--clickable:hover .lby-stat__icon-wrap { background: rgba(40,110,190,.15) !important; border-color: rgba(40,110,190,.3) !important; color: #6aaad8 !important; }

/* transiciones modal */
.lby-modal-enter-active, .lby-modal-leave-active { transition: opacity .2s, transform .25s cubic-bezier(.22,1,.36,1); }
.lby-modal-enter-from, .lby-modal-leave-to { opacity: 0; }
.lby-modal-enter-from .lby-modal, .lby-modal-leave-to .lby-modal { transform: scale(.95) translateY(8px); }

/* ═══════════════════════════════════
   TOASTS — compra realtime
═══════════════════════════════════ */
.lby-toasts {
  position: fixed; left: 20px; bottom: 74px; z-index: 300;
  display: flex; flex-direction: column-reverse; gap: 6px;
  pointer-events: none;
}
.lby-toast {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 7px 14px 7px 10px; border-radius: 10px;
  background: rgba(4,8,20,.88);
  border: 1px solid rgba(42,245,152,.25);
  backdrop-filter: blur(14px);
  box-shadow: 0 4px 18px rgba(0,0,0,.5), 0 0 12px rgba(42,245,152,.1);
  font-size: 11px; font-weight: 600; color: #c8d8f0;
  white-space: nowrap;
}
.lby-toast__icon { color: #2af598; font-size: 10px; flex-shrink: 0; }

/* TransitionGroup */
.lby-toast-enter-active { transition: opacity .25s ease, transform .3s cubic-bezier(.22,1,.36,1); }
.lby-toast-leave-active { transition: opacity .3s ease, transform .3s ease; }
.lby-toast-enter-from   { opacity: 0; transform: translateX(-16px) scale(.95); }
.lby-toast-leave-to     { opacity: 0; transform: translateX(-8px) scale(.97); }

/* ═══════════════════════════════════
   BANNER REDIRECCIÓN — sorteo iniciado
═══════════════════════════════════ */
.lby-redirect-banner {
  position: fixed; inset: 0; z-index: 500;
  background: rgba(2,5,16,.82); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
}
.lby-redirect-banner__inner {
  display: flex; align-items: center; gap: 18px;
  background: linear-gradient(135deg, #0d1a10 0%, #081510 100%);
  border: 1px solid rgba(42,245,152,.35);
  border-radius: 18px; padding: 24px 32px;
  box-shadow: 0 0 60px rgba(42,245,152,.12), 0 24px 60px rgba(0,0,0,.7);
  animation: lbyRedirectPop .35s cubic-bezier(.22,1,.36,1) both;
}
@keyframes lbyRedirectPop {
  from { opacity: 0; transform: scale(.9) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.lby-redirect-banner__spinner {
  width: 36px; height: 36px; flex-shrink: 0;
  border: 3px solid rgba(42,245,152,.15); border-top-color: #2af598;
  border-radius: 50%; animation: lbySpin 1s linear infinite;
}
.lby-redirect-banner__title {
  margin: 0 0 3px; font-size: 15px; font-weight: 700; color: #2af598;
}
.lby-redirect-banner__sub {
  margin: 0; font-size: 11px; color: #3a6050;
}

.lby-redirect-enter-active { transition: opacity .25s ease; }
.lby-redirect-leave-active { transition: opacity .2s ease; }
.lby-redirect-enter-from,
.lby-redirect-leave-to { opacity: 0; }

/* ═══════════════════════════════════
   RESPONSIVE
═══════════════════════════════════ */
@media (max-width: 640px) {
  .lby-actions { flex-direction: column; }
  .lby-hero__bottom { align-items: flex-start; flex-direction: column; gap: 10px; }
  .lby-hero__right { align-items: flex-start; }
}

@media (max-width: 480px) {
  .lby-page { padding: 10px 10px 28px; }
  .lby-hero__nav { padding: 10px 12px 0; }
  .lby-hero__bottom { padding: 10px 12px 14px; }
  .lby-prize__num { font-size: 28px; }
  .lby-stats { flex-wrap: wrap; }
  .lby-stat { min-width: calc(50% - 3px); }
  .lby-panel, .lby-progress-panel { padding: 10px 12px; }
  .lby-rules-fab { right: 14px; bottom: 18px; width: 38px; height: 38px; font-size: 13px; }
}
</style>
