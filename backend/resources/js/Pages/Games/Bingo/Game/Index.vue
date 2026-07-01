<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link } from "@inertiajs/vue3";
import "@/css/games/bingo/bingo-in-game.css";
import echo from "@js/plugins/echo";
import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useAntiDebug } from "@js/composables/games/bingo/useAntiDebug";
import { useBingoSound } from "@js/composables/games/bingo/useBingoSound";
import BingoCartons from "@js/Components/Games/Bingo/views/inGame/Cartons.vue";
import type { BingoRoom, BingoCard } from "@js/Services/bingoService";
import Swal from "sweetalert2";

// ─────────────────────────────────────────────────────────────────────────────
// Anti-Debug Protection (solo en producción)
// ─────────────────────────────────────────────────────────────────────────────
useAntiDebug()

// ─────────────────────────────────────────────────────────────────────────────
// Audio
// ─────────────────────────────────────────────────────────────────────────────
const { muted, toggleMute, playNext, playWinner, preload } = useBingoSound()

// ─────────────────────────────────────────────────────────────────────────────
// End-of-Game Modal State
// ─────────────────────────────────────────────────────────────────────────────
interface WinnerInfo {
  id: number;
  name: string;
  card_id: number | string;
  card_serial?: string;
  pattern: string;
  prize: number;
}

interface WinnerEntry {
  user_id: number | string;
  user_name: string;
  card_id: string;
  card_serial: string;
  prize: number;
  pattern?: string;
  round?: number;
}

interface GameEndData {
  hasWinner: boolean;
  totalDrawn: number;
  winners: WinnerEntry[];
  prize: number;
  endTime: string;
}

const showWinnerModal  = ref(false);
const gameEndData      = ref<GameEndData | null>(null);
const winnerInfo       = ref<WinnerInfo | null>(null);   // compat: WinnerDeclared individual
const isMyWin          = ref(false);
const checkingBingo    = ref(false);

// Players/winners modal
const showPlayersModal = ref(false);
// Card viewer within the players modal
const viewingCard      = ref<null | { serialNumber: string; cardMatrix: number[][]; loading?: boolean }>(null);
const viewingCardOwner = ref('');

function toRowMatrix(cardNumbers: Record<string, (number | null)[]> | number[][], variant?: string): number[][] {
  if (Array.isArray(cardNumbers)) return cardNumbers as number[][];

  const obj  = cardNumbers as Record<string, (number | null)[]>;
  const cols = variantCols.value.map(c => c.label);

  // Number of rows = length of first column array (already normalized by backend)
  const numRows = cols.length > 0 ? (obj[cols[0]]?.length ?? 0) : 0;

  const rows: number[][] = [];
  for (let r = 0; r < numRows; r++) {
    rows.push(cols.map(c => (obj[c]?.[r] ?? null) as number));
  }
  return rows;
}

// Posiciones fijas de confetti (evita re-render random en cada update)
const confettiPieces = Array.from({ length: 40 }, (_, i) => ({
  left:     `${(i * 2.618033) % 100}%`,
  delay:    `${(i * 0.07) % 2}s`,
  duration: `${2.5 + (i % 5) * 0.3}s`,
  color:    ['#7b4fff','#2af598','#ffd700','#ff4466','#00c6ff','#ff8c00'][i % 6],
}));

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────
interface Player { ctns: number }

interface Props {
  title?: string;
  room: BingoRoom & { Config: { rounds?: number; bingo_patterns?: boolean } };
  userId?: string | null;
  cartons?: BingoCard[];
  customerLogo?: string;
  currencySym?: string;
  prizePool?: number;
  exchangeRate?: number;
  totalPlayers?: number;
  prizePoolType?: string;
  round?: number;
  rounds?: number;
  bingoPattern?: string;
  vanDraw?: number[];
  player?: Player;
  encrypt?: boolean;
  chipsRest?: number[];
  draw?: number[];
  timeServ?: number;
  allChips?: number[];
  infoGame?: any;
  creatorType?: string;
  creatorId?: string;
  currencyId?: string;
  cardPrice?: number;
  startTime?: number;
  closingRoute?: string;
  isLocal?: boolean;
  roomId?: string;
  roomRouteKey?: string;
  initialWinners?: WinnerEntry[];
  gameVariant?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: "Juego de Bingo",
  cartons: () => [],
  customerLogo: null,
  currencySym: "$",
  prizePool: 0,
  exchangeRate: 1,
  totalPlayers: 0,
  prizePoolType: "PREMIO",
  round: 1,
  bingoPattern: "",
  vanDraw: () => [],
  player: () => ({ ctns: 0 }),
  encrypt: false,
  chipsRest: () => [],
  draw: () => [],
  timeServ: 0,
  allChips: () => [],
  infoGame: null,
  creatorType: "",
  creatorId: "",
  currencyId: "",
  cardPrice: 0,
  startTime: 0,
  closingRoute: "",
  isLocal: false,
  roomId: "",
  roomRouteKey: "",
  gameVariant: "BINGO_75",
});

// ─────────────────────────────────────────────────────────────────────────────
// Reactive state (updated via WebSocket)
// ─────────────────────────────────────────────────────────────────────────────
const livePrize   = ref(props.prizePool ?? 0);
const liveExchangeRate = ref(props.exchangeRate ?? 1);
const livePlayers = ref(props.totalPlayers ?? 0);
const liveDrawn   = ref<number[]>([...(props.draw ?? [])]);
const prizeFlash  = ref(false);
const playersFlash = ref(false);

// Progreso de jugadores (cuántas fichas le faltan al cartón más cerca de ganar)
interface PlayerProgress { user_id: number; user_name: string; missing: number }
const playersProgress = ref<PlayerProgress[]>([]);

// ─────────────────────────────────────────────────────────────────────────────
// Countdown — mismo composable que GameRooms
// ─────────────────────────────────────────────────────────────────────────────
const gameStarted  = ref(false);
const drawStarting = ref(false);   // 10-s pre-draw countdown
const drawStartingSecs = ref(10);  // contador visual regresivo
let   drawStartTimer: ReturnType<typeof setInterval> | null = null;

// Fake single-room list so we can reuse useGameRoomCountdown as-is
const fakeRoom = computed(() => ({
  id: props.room?.id?.toString() ?? "0",
  config: { unix_date: props.startTime ?? 0 },
}));

const { init: initCountdown, getCountdown, stop: stopCountdown, syncTime, updateRoomTime } = useGameRoomCountdown(
  Date.now(),
  () => {
    gameStarted.value = true;
    // Iniciar sorteo 10 segundos después del fin del countdown
    scheduleDrawStart();
  },
);

const cd = computed(() => getCountdown(fakeRoom.value.id));
const hasTime = computed(() => cd.value && !cd.value.expired);

function pad(n: number) { return String(n).padStart(2, "0"); }

// ─────────────────────────────────────────────────────────────────────────────
// Draw scheduling (fires 10 s after countdown expires)
// ─────────────────────────────────────────────────────────────────────────────

function clearDrawTimer() {
  if (drawStartTimer !== null) {
    clearInterval(drawStartTimer);
    drawStartTimer = null;
  }
  drawStarting.value = false;
  drawStartingSecs.value = 10;
}

async function triggerStartDraw() {
  try {
    const routeKey = props.roomRouteKey || roomId.value;
    await fetch(`/bingo/${routeKey}/dev-start-draw`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    // Response may take a long time (75 chips × 5 s). Numbers arrive via WebSocket.
  } catch (e) {
    console.error('[BingoDraw] Error al iniciar sorteo:', e);
  }
}

function scheduleDrawStart() {
  clearDrawTimer();
  drawStarting.value = true;
  drawStartingSecs.value = 10;

  drawStartTimer = setInterval(() => {
    drawStartingSecs.value -= 1;
    if (drawStartingSecs.value <= 0) {
      clearDrawTimer();
      triggerStartDraw();
    }
  }, 1_000);
}

// ─────────────────────────────────────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────────────────────────────────────
// Sala finalizada: basado en la prop inicial O en gameEndData recibido vía WebSocket
const isFinished = computed(() =>
  gameEndData.value !== null ||
  String(props.room?.status ?? '').toUpperCase() === 'FINISHED' ||
  String((props.room as any)?.status_code ?? '').toUpperCase() === 'FINISHED'
);

const totalWinners = computed(() => gameEndData.value?.winners?.length ?? 0);

const prizeFormatted = computed(() => {
  const convertedPrize = livePrize.value * liveExchangeRate.value;
  return convertedPrize % 1 === 0 ? convertedPrize.toFixed(0) : convertedPrize.toFixed(2);
});

const cardCount = computed(() => props.cartons?.length ?? 0);

// ── Variante de juego — determina totalChips y etiqueta de columna ──
const VARIANT_CHIPS: Record<string, number> = {
  BINGO_75: 75, BINGO_90: 90, BINGO_80: 80, BINGO_30: 30,
};
const VARIANT_COLS: Record<string, { min: number; max: number; label: string }[]> = {
  BINGO_75: [
    { min: 1,  max: 15, label: 'B' },
    { min: 16, max: 30, label: 'I' },
    { min: 31, max: 45, label: 'N' },
    { min: 46, max: 60, label: 'G' },
    { min: 61, max: 75, label: 'O' },
  ],
  BINGO_90: [
    { min:  1, max:  9, label: '1' },
    { min: 10, max: 19, label: '2' },
    { min: 20, max: 29, label: '3' },
    { min: 30, max: 39, label: '4' },
    { min: 40, max: 49, label: '5' },
    { min: 50, max: 59, label: '6' },
    { min: 60, max: 69, label: '7' },
    { min: 70, max: 79, label: '8' },
    { min: 80, max: 90, label: '9' },
  ],
  BINGO_80: [
    { min:  1, max: 20, label: '1' },
    { min: 21, max: 40, label: '2' },
    { min: 41, max: 60, label: '3' },
    { min: 61, max: 80, label: '4' },
  ],
  BINGO_30: [
    { min:  1, max: 10, label: '1' },
    { min: 11, max: 20, label: '2' },
    { min: 21, max: 30, label: '3' },
  ],
};
const totalChips = computed(() => VARIANT_CHIPS[props.gameVariant ?? 'BINGO_75'] ?? 75);
const variantCols = computed(() => VARIANT_COLS[props.gameVariant ?? 'BINGO_75'] ?? VARIANT_COLS.BINGO_75);

// Mostrar el panel de "jugadores cerca de ganar" cuando quedan 10 fichas o menos
const remainingChips = computed(() => totalChips.value - liveDrawn.value.length);
const showPlayersProgress = computed(() =>
  liveDrawn.value.length > 0 && remainingChips.value <= 10 && playersProgress.value.length > 0
);

function chipLetter(n: number): string {
  for (const col of variantCols.value) {
    if (n >= col.min && n <= col.max) return col.label;
  }
  return '?';
}

const roomId = computed(() => props.roomId || props.room?.id?.toString() || "");

// ─────────────────────────────────────────────────────────────────────────────
// Bingo Detection & Winner Handling
// ─────────────────────────────────────────────────────────────────────────────
async function handleBingoDetected(card: BingoCard) {
  if (checkingBingo.value) return;
  checkingBingo.value = true;

  try {
    const routeKey = props.roomRouteKey || roomId.value;
    const res = await fetch(`/bingo/${routeKey}/check-bingo`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const data = await res.json();

    if (data.success && data.hasBingo && data.winner) {
      winnerInfo.value = data.winner;
      isMyWin.value = true;
      showWinnerModal.value = true;
    }
  } catch (e) {
    console.error('[Bingo] Error verificando bingo:', e);
  } finally {
    checkingBingo.value = false;
  }
}

function handleWinnerDeclared(winner: WinnerInfo) {
  // Solo muestra si aún no se abrió el modal de fin de juego
  winnerInfo.value = winner;
  isMyWin.value = String(winner.id) === String(props.userId);
}

function handleGameEnded(data: { has_winner: boolean; total_drawn: number; winners: any[]; prize: number; end_time: string }) {
  gameEndData.value = {
    hasWinner:  data.has_winner,
    totalDrawn: data.total_drawn,
    winners:    (data.winners ?? []).map((w: any) => ({
      user_id:     w.user_id,
      user_name:   w.user_name,
      card_id:     String(w.card_id ?? ''),
      card_serial: w.card_serial ?? '',
      prize:       Number(w.prize ?? 0),
      pattern:     w.pattern ?? '',
      round:       w.round ?? 1,
    })),
    prize:      data.prize ?? 0,
    endTime:    data.end_time,
  };
  const myId = props.userId;
  if (myId && data.winners?.some((w: any) => String(w.user_id) === String(myId))) {
    isMyWin.value = true;
  }
  showWinnerModal.value = true;
}

function closeWinnerModal() {
  showWinnerModal.value = false;
}

async function openCardViewer(winner: WinnerEntry) {
  if (!winner.card_id) return;
  viewingCardOwner.value = winner.user_name;
  viewingCard.value = { serialNumber: winner.card_serial, cardMatrix: [], loading: true };
  try {
    const routeKey = props.roomRouteKey || roomId.value;
    const res = await fetch(`/bingo/${routeKey}/card/${winner.card_id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const data = await res.json();
    if (data.success && data.card) {
      viewingCard.value = {
        serialNumber: data.card.serialNumber ?? winner.card_serial,
        cardMatrix:   toRowMatrix(data.card.cardMatrix ?? {}),
        loading: false,
      };
    } else {
      viewingCard.value = null;
    }
  } catch {
    viewingCard.value = null;
  }
}

function closeCardViewer() {
  viewingCard.value = null;
  viewingCardOwner.value = '';
}

// ─────────────────────────────────────────────────────────────────────────────
// WebSocket
// ─────────────────────────────────────────────────────────────────────────────
const ROOM_EVENTS = [
  '.RoomUpdated', '.PlayerJoined', '.BingoNumberCalled', '.PlayersProgress',
  '.GameStarted', '.WinnerDeclared', '.GameEnded', '.RoomTimeSync',
] as const;

let roomChannelName: string | null = null;

function flash(target: Ref<boolean>) {
  target.value = true;
  setTimeout(() => { target.value = false; }, 1000);
}

function subscribeToRoom() {
  if (!roomId.value) return;

  roomChannelName = `bingo.room.${roomId.value}`;
  const channel = echo.channel(roomChannelName);

  // Prize pool / players count updated
  channel.listen(".RoomUpdated", (e: { prize_pool: number; total_players: number }) => {
    if (e.prize_pool !== undefined) {
      livePrize.value = e.prize_pool;
      flash(prizeFlash);
    }
    if (e.total_players !== undefined) {
      livePlayers.value = e.total_players;
      flash(playersFlash);
    }
  });

  // Player joined
  channel.listen(".PlayerJoined", (e: { current_players: number }) => {
    livePlayers.value = e.current_players;
    flash(playersFlash);
  });

  // Number drawn
  channel.listen(".BingoNumberCalled", (e: { number: number }) => {
    if (e.number && !liveDrawn.value.includes(e.number)) {
      liveDrawn.value = [...liveDrawn.value, e.number];
      playNext();
    }
  });

  // Progreso de jugadores — cuántas fichas le faltan al más cercano de ganar
  channel.listen(".PlayersProgress", (e: { players: PlayerProgress[] }) => {
    if (Array.isArray(e.players)) {
      playersProgress.value = e.players;
    }
  });

  // Game started (broadcast from backend when draw begins)
  channel.listen(".GameStarted", () => {
    gameStarted.value = true;
    clearDrawTimer();
  });

  // Winner declared — individual notification
  channel.listen(".WinnerDeclared", (e: { winner: WinnerInfo }) => {
    if (e.winner) handleWinnerDeclared(e.winner);
  });

  // Game ended — resumen final automático para todos
  channel.listen(".GameEnded", (e: any) => {
    handleGameEnded(e);
    playWinner();
  });

  // Room time sync - sincroniza el contador con el tiempo del servidor
  // Esto corrige el desfase cuando el usuario vuelve después de estar fuera de la página
  channel.listen(".RoomTimeSync", (e: { server_timestamp: number; unix_date: number; remaining_seconds: number }) => {
    if (e.server_timestamp && e.unix_date) {
      // Sincronizar el tiempo del cliente con el servidor
      const serverTimeMs = e.server_timestamp * 1000;
      syncTime(serverTimeMs);

      // Actualizar el tiempo de la sala si ha cambiado
      if (e.unix_date !== props.startTime) {
        updateRoomTime(roomId.value, e.unix_date);
      }

      console.log('[WebSocket] Tiempo sincronizado con servidor:', new Date(serverTimeMs).toISOString());
    }
  });
}

function unsubscribeFromRoom() {
  if (!roomChannelName) return;
  const channel = echo.channel(roomChannelName);
  for (const event of ROOM_EVENTS) channel.stopListening(event);
  echo.leaveChannel(roomChannelName);
  roomChannelName = null;
}

// ─────────────────────────────────────────────────────────────────────────────
// [DEV] Reset start time
// ─────────────────────────────────────────────────────────────────────────────
const devResetting = ref(false);

async function devResetTime() {
  if (devResetting.value) return;

  // Verificar si ya existe un sorteo previo
  const hasExistingDraw = liveDrawn.value.length > 0;

  if (hasExistingDraw) {
    const result = await Swal.fire({
      title: '¿Regenerar sorteo?',
      text: 'Ya existe un sorteo cargado/finalizado. ¿Deseas regenerarlo? Esto reiniciará el juego.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, regenerar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#7b4fff',
      cancelButtonColor: '#6c757d',
      background: '#1a1f3a',
      color: '#fff',
      customClass: {
        popup: 'swal-dark',
        confirmButton: 'swal-confirm-btn',
        cancelButton: 'swal-cancel-btn'
      }
    });

    if (!result.isConfirmed) {
      return; // Usuario canceló
    }
  }

  devResetting.value = true;
  try {
    const routeKey = props.roomRouteKey || roomId.value;
    const res = await fetch(`/bingo/${routeKey}/dev-reset-time`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const data = await res.json();
    if (data.startTime) {
      // Cancel any pending draw start
      clearDrawTimer();
      liveDrawn.value = [];
      gameStarted.value = false;
      // Re-init countdown with new unix_date
      initCountdown([{ id: roomId.value, config: { unix_date: data.startTime } } as any]);

      Swal.fire({
        title: '¡Sorteo iniciado!',
        text: 'El sorteo ha sido regenerado exitosamente.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false,
        background: '#1a1f3a',
        color: '#fff'
      });
    }
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'No se pudo regenerar el sorteo. Inténtalo de nuevo.',
      icon: 'error',
      background: '#1a1f3a',
      color: '#fff'
    });
  } finally {
    devResetting.value = false;
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// TypeScript helper
// ─────────────────────────────────────────────────────────────────────────────
type Ref<T> = ReturnType<typeof ref<T>>;

// ─────────────────────────────────────────────────────────────────────────────
// Lifecycle
// ─────────────────────────────────────────────────────────────────────────────
const revealed = ref(false);

onMounted(() => {
  setTimeout(() => { revealed.value = true; }, 80);
  initCountdown([fakeRoom.value as any]);
  subscribeToRoom();
  preload();

  // Para salas ya finalizadas en el momento de cargar la página
  if (props.initialWinners && props.initialWinners.length > 0 && gameEndData.value === null) {
    gameEndData.value = {
      hasWinner:  true,
      totalDrawn: liveDrawn.value.length,
      winners:    props.initialWinners,
      prize:      props.prizePool ?? 0,
      endTime:    '',
    };
    const myId = props.userId;
    if (myId && props.initialWinners.some(w => String(w.user_id) === String(myId))) {
      isMyWin.value = true;
    }
  }
});

onUnmounted(() => {
  stopCountdown();
  clearDrawTimer();
  unsubscribeFromRoom();
});
</script>

<template>
  <div class="ig-page" :class="{ 'ig-page--revealed': revealed }">
    <div class="ig-inner">

      <!-- ══════════════════════════════════════════
           HEADER PANEL
      ══════════════════════════════════════════ -->
      <div class="ig-panel">
        <!-- línea top decorativa -->
        <div class="ig-panel__topline" />

        <!-- ── Fila 1: Premio | Estado | Acciones ── -->
        <div class="ig-panel__row ig-panel__row--top">

          <!-- Premio -->
          <div class="ig-prize" :class="{ 'ig-prize--flash': prizeFlash }">
            <span class="ig-prize__label">
              <i class="fas fa-trophy ig-prize__icon" /> {{ prizePoolType }}
            </span>
            <div class="ig-prize__amount">
              <span class="ig-prize__sym">{{ currencySym }}</span>
              <span class="ig-prize__num">{{ prizeFormatted }}</span>
            </div>
          </div>

          <div class="ig-panel__divider" />

          <!-- Countdown / Live -->
          <div class="ig-countdown" v-if="hasTime && cd">
            <span class="ig-countdown__label"><i class="fas fa-clock" /> Inicia en</span>
            <div class="ig-timer">
              <template v-if="cd.time.days > 0">
                <div class="timer__unit"><span class="timer__num">{{ cd.time.days }}</span><span class="timer__lbl">D</span></div>
                <span class="timer__sep">:</span>
              </template>
              <template v-if="cd.time.hours > 0">
                <div class="timer__unit"><span class="timer__num">{{ cd.time.hours }}</span><span class="timer__lbl">H</span></div>
                <span class="timer__sep">:</span>
              </template>
              <div class="timer__unit"><span class="timer__num">{{ pad(cd.time.minutes) }}</span><span class="timer__lbl">M</span></div>
              <span class="timer__sep">:</span>
              <div class="timer__unit"><span class="timer__num">{{ pad(cd.time.seconds) }}</span><span class="timer__lbl">S</span></div>
            </div>
          </div>
          <div v-else-if="isFinished" class="ig-live-badge ig-live-badge--finished">
            <i class="fas fa-flag-checkered" /> FINALIZADA
          </div>
          <div v-else class="ig-live-badge">
            <span class="ig-live-badge__dot" /> EN VIVO
          </div>

          <!-- Spacer -->
          <div style="flex:1" />

          <!-- Mute + Salas -->
          <div class="ig-panel__actions">
            <Link
              v-if="isFinished"
              href="/bingo/game-rooms"
              class="ig-action-btn ig-action-btn--rooms"
            >
              <i class="fas fa-door-open" /> Volver a salas
            </Link>
            <button class="ig-action-btn ig-action-btn--mute" :class="{ 'ig-action-btn--muted': muted }" @click="toggleMute" :title="muted ? 'Activar sonido' : 'Silenciar'">
              <i :class="muted ? 'fas fa-volume-mute' : 'fas fa-volume-up'" />
            </button>
          </div>
        </div>

        <!-- separador horizontal -->
        <div class="ig-panel__hline" />

        <!-- ── Fila 2: Stats ── -->
        <div class="ig-panel__row ig-panel__row--stats">

          <div
            class="ig-stat ig-stat--clickable"
            :class="{ 'ig-stat--flash': playersFlash }"
            @click="showPlayersModal = true"
            title="Ver lista de jugadores / ganadores"
          >
            <i class="fas fa-users" />
            <div class="ig-stat__body">
              <span class="ig-stat__val">
                {{ livePlayers }}
                <small v-if="totalWinners > 0" class="ig-stat__winners-badge">
                  <i class="fas fa-trophy" /> {{ totalWinners }}
                </small>
              </span>
              <span class="ig-stat__lbl">jugadores</span>
            </div>
          </div>

          <div class="ig-stat__sep" />

          <div class="ig-stat">
            <i class="fas fa-ticket-alt" />
            <div class="ig-stat__body">
              <span class="ig-stat__val">{{ cardCount }}</span>
              <span class="ig-stat__lbl">cartone{{ cardCount === 1 ? '' : 's' }}</span>
            </div>
          </div>

          <div v-if="rounds" class="ig-stat__sep" />
          <div v-if="rounds" class="ig-stat">
            <i class="fas fa-sync-alt" />
            <div class="ig-stat__body">
              <span class="ig-stat__val">{{ round }}<small>/{{ rounds }}</small></span>
              <span class="ig-stat__lbl">ronda</span>
            </div>
          </div>

          <div v-if="liveDrawn.length > 0" class="ig-stat__sep" />
          <div v-if="liveDrawn.length > 0" class="ig-stat">
            <i class="fas fa-random" />
            <div class="ig-stat__body">
              <span class="ig-stat__val">{{ liveDrawn.length }}<small>/{{ totalChips }}</small></span>
              <span class="ig-stat__lbl">sorteados</span>
            </div>
            <!-- mini barra de progreso -->
            <div class="ig-stat__bar">
              <div class="ig-stat__bar-fill" :style="{ width: `${(liveDrawn.length / totalChips) * 100}%` }" />
            </div>
          </div>

          <div v-if="bingoPattern" class="ig-stat__sep" />
          <div v-if="bingoPattern" class="ig-stat">
            <i class="fas fa-th" />
            <div class="ig-stat__body">
              <span class="ig-stat__val ig-stat__val--sm">{{ bingoPattern }}</span>
              <span class="ig-stat__lbl">patrón</span>
            </div>
          </div>

        </div>
      </div>

      <!-- ══════════════════════════════════════════
           [DEV] Reset start time button
      ══════════════════════════════════════════ -->
      <div v-if="isLocal" class="ig-dev-bar">
        <button class="ig-dev-btn" :disabled="devResetting" @click="devResetTime">
          <span v-if="devResetting" class="ig-dev-btn__spinner" />
          <i v-else class="fas fa-play-circle" />
          {{ devResetting ? 'Actualizando...' : 'Iniciar Partida (+2 min)' }}
        </button>
        <span class="ig-dev-label">DEV · solo visible en local</span>
      </div>

      <!-- ══════════════════════════════════════════
           PRE-DRAW BANNER
      ══════════════════════════════════════════ -->
      <Transition name="ig-fade">
        <div v-if="drawStarting" class="ig-predraw-banner">
          <span class="ig-predraw-banner__icon">🎱</span>
          <span class="ig-predraw-banner__text">
            El sorteo inicia en
            <strong class="ig-predraw-banner__secs">{{ drawStartingSecs }}</strong>
            segundo{{ drawStartingSecs === 1 ? '' : 's' }}…
          </span>
        </div>
      </Transition>

      <!-- ══════════════════════════════════════════
           DRAWN NUMBERS BOARD
      ══════════════════════════════════════════ -->
      <Transition name="ig-fade">
        <div v-if="liveDrawn.length > 0" class="ig-drawn-board">

          <!-- ── Fila superior: última bola + info + recientes ── -->
          <div class="ig-db-top">

            <!-- Última bola -->
            <div class="ig-db-last">
              <div class="ig-db-last__ring" />
              <div class="ig-chip ig-chip--lg" :class="`ig-chip--${chipLetter(liveDrawn[liveDrawn.length-1])}`">
                <span class="ig-chip__letter">{{ chipLetter(liveDrawn[liveDrawn.length-1]) }}</span>
                <span class="ig-chip__num">{{ liveDrawn[liveDrawn.length-1] }}</span>
              </div>
            </div>

            <!-- Info central -->
            <div class="ig-db-info">
              <span class="ig-db-info__label"><i class="fas fa-sort-amount-up-alt" /> ÚLTIMO NÚMERO</span>
              <span class="ig-db-info__num">{{ liveDrawn[liveDrawn.length - 1] }}</span>
              <div class="ig-db-info__progress">
                <div class="ig-db-info__bar" :style="{ width: `${(liveDrawn.length / totalChips) * 100}%` }" />
                <span class="ig-db-info__pct">{{ liveDrawn.length }}<em>/{{ totalChips }}</em></span>
              </div>
            </div>

            <!-- Recientes -->
            <div class="ig-db-recent">
              <span class="ig-db-recent__label">RECIENTES</span>
              <div class="ig-db-recent__row">
                <div
                  v-for="n in [...liveDrawn].slice(-5).reverse()"
                  :key="`r-${n}`"
                  class="ig-chip ig-chip--sm"
                  :class="`ig-chip--${chipLetter(n)}`"
                >
                  <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
                  <span class="ig-chip__num">{{ n }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- separador -->
          <div class="ig-db-sep" />

          <!-- ── Todas las bolas sorteadas en orden cronológico ── -->
          <div class="ig-db-chips">
            <div
              v-for="n in [...liveDrawn].reverse()"
              :key="n"
              class="ig-chip ig-chip--sm"
              :class="[`ig-chip--${chipLetter(n)}`, { 'ig-chip--latest': n === liveDrawn[liveDrawn.length-1] }]"
            >
              <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
              <span class="ig-chip__num">{{ n }}</span>
            </div>
          </div>

        </div>
      </Transition>

      <!-- ══════════════════════════════════════════
           JUGADORES CERCA DE GANAR (≤10 fichas restantes)
      ══════════════════════════════════════════ -->
      <Transition name="ig-fade">
        <div v-if="showPlayersProgress" class="ig-near-win">
          <span class="ig-near-win__label">
            <i class="fas fa-bullseye" /> ¡Quedan {{ remainingChips }} ficha{{ remainingChips === 1 ? '' : 's' }}! Jugadores más cerca de ganar
          </span>
          <div class="ig-near-win__row">
            <div
              v-for="p in playersProgress"
              :key="p.user_id"
              class="ig-near-win__player"
              :class="{ 'ig-near-win__player--hot': p.missing <= 2 }"
            >
              <div class="ig-near-win__avatar">
                <i class="fas fa-user" />
              </div>
              <div class="ig-near-win__body">
                <span class="ig-near-win__name">{{ p.user_name }}</span>
                <span class="ig-near-win__missing">
                  {{ p.missing }} {{ p.missing === 1 ? 'ficha' : 'fichas' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ══════════════════════════════════════════
           CARTONES
      ══════════════════════════════════════════ -->
      <div class="ig-cards-section">
        <BingoCartons
          :cartons="cartons ?? []"
          :customerLogo="customerLogo ?? null"
          :roomId="roomId"
          :drawnNumbers="liveDrawn"
          :totalDrawn="liveDrawn.length"
          :gameVariant="gameVariant"
          @bingo-detected="handleBingoDetected"
        />
      </div>

    </div>

    <!-- ══════════════════════════════════════════
         MODAL JUGADORES / GANADORES
    ══════════════════════════════════════════ -->
    <Transition name="gm-fade">
      <div v-if="showPlayersModal" class="gm-overlay" @click.self="showPlayersModal = false">
        <div class="gm-modal pm-modal">
          <button class="gm-close" @click="showPlayersModal = false"><i class="fas fa-times" /></button>

          <div class="gm-header">
            <h2 class="gm-title" style="-webkit-text-fill-color:inherit;background:none;color:#e0eaff">
              <i class="fas fa-users" style="font-size:22px;margin-right:8px;color:#7c9dff" />
              Jugadores
            </h2>
            <p class="gm-subtitle">
              {{ livePlayers }} jugador{{ livePlayers === 1 ? '' : 'es' }}
              <template v-if="totalWinners > 0"> · <i class="fas fa-trophy" style="color:#ffd700" /> {{ totalWinners }} ganador{{ totalWinners === 1 ? '' : 'es' }}</template>
            </p>
          </div>

          <!-- Ganadores -->
          <template v-if="totalWinners > 0">
            <div class="gm-winners">
              <div class="gm-winners__title"><i class="fas fa-star" /> Ganadores</div>
              <div
                v-for="(w, idx) in gameEndData?.winners ?? []"
                :key="`pm-w-${w.card_id}-${idx}`"
                class="gm-winner-card pm-winner-row"
                :class="{ 'gm-winner-card--me': String(w.user_id) === String(props.userId) }"
              >
                <div class="gm-winner-card__rank">#{{ idx + 1 }}</div>
                <div class="gm-winner-card__info">
                  <div class="gm-winner-card__name">
                    {{ w.user_name }}
                    <span v-if="String(w.user_id) === String(props.userId)" class="gm-winner-card__you">TÚ</span>
                  </div>
                  <div class="gm-winner-card__serial">
                    Cartón {{ w.card_serial }}
                    <span v-if="w.round" class="pm-round-badge">Ronda {{ w.round }}</span>
                    <span v-if="w.pattern" class="pm-pattern-badge">{{ w.pattern }}</span>
                  </div>
                </div>
                <div class="gm-winner-card__prize">
                  <span class="gm-winner-card__sym">{{ currencySym }}</span>
                  {{ Number(w.prize).toFixed(2) }}
                </div>
                <button
                  v-if="w.card_id"
                  class="pm-view-card-btn"
                  title="Ver cartón ganador"
                  @click="openCardViewer(w)"
                >
                  <i class="fas fa-th" />
                </button>
              </div>
            </div>
          </template>
          <div v-else class="gm-no-winner">
            <i class="fas fa-info-circle" /> Aún no hay ganadores registrados.
          </div>

          <div class="gm-actions">
            <button class="gm-btn gm-btn--primary" @click="showPlayersModal = false">
              <i class="fas fa-check" /> Cerrar
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════
         MODAL VER CARTÓN GANADOR
    ══════════════════════════════════════════ -->
    <Transition name="gm-fade">
      <div v-if="viewingCard" class="gm-overlay" @click.self="closeCardViewer">
        <div class="gm-modal pm-card-modal">
          <button class="gm-close" @click="closeCardViewer"><i class="fas fa-times" /></button>
          <div class="gm-header">
            <h2 class="gm-title" style="-webkit-text-fill-color:inherit;background:none;color:#e0eaff">
              <i class="fas fa-th" style="font-size:20px;margin-right:8px;color:#7c9dff" />
              Cartón Ganador
            </h2>
            <p class="gm-subtitle">
              {{ viewingCardOwner }} · Serie {{ viewingCard.serialNumber }}
            </p>
          </div>

          <div v-if="viewingCard.loading" class="pm-card-loading">
            <div class="pm-card-spinner" /> Cargando cartón…
          </div>
          <template v-else-if="viewingCard.cardMatrix && viewingCard.cardMatrix.length">
            <div class="pm-bingo-card">
              <!-- Cabecera B I N G O -->
              <div class="pm-bingo-header">
                <span v-for="l in ['B','I','N','G','O']" :key="l" class="pm-bingo-header__letter">{{ l }}</span>
              </div>
              <!-- Filas de la matriz -->
              <div
                v-for="(row, r) in viewingCard.cardMatrix"
                :key="`row-${r}`"
                class="pm-bingo-row"
              >
                <div
                  v-for="(num, c) in row"
                  :key="`cell-${r}-${c}`"
                  class="pm-bingo-cell"
                  :class="{
                    'pm-bingo-cell--drawn': num > 0 && liveDrawn.includes(num),
                    'pm-bingo-cell--free': num === 0
                  }"
                >
                  <template v-if="num === 0">★</template>
                  <template v-else>{{ num }}</template>
                </div>
              </div>
            </div>
          </template>
          <div v-else class="gm-no-winner">
            <i class="fas fa-exclamation-triangle" /> No se pudo cargar el cartón.
          </div>

          <div class="gm-actions">
            <button class="gm-btn gm-btn--primary" @click="closeCardViewer">
              <i class="fas fa-arrow-left" /> Volver
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════
         MODAL FIN DE JUEGO
    ══════════════════════════════════════════ -->
    <Transition name="gm-fade">
      <div v-if="showWinnerModal" class="gm-overlay" @click.self="closeWinnerModal">

        <!-- Confetti (solo si hay ganador) -->
        <template v-if="gameEndData?.hasWinner || isMyWin">
          <div
            v-for="(cf, i) in confettiPieces"
            :key="`cf-${i}`"
            class="gm-confetti"
            :style="{ left: cf.left, animationDelay: cf.delay, animationDuration: cf.duration, background: cf.color }"
          />
        </template>

        <div class="gm-modal" :class="{ 'gm-modal--win': gameEndData?.hasWinner || isMyWin, 'gm-modal--no-winner': gameEndData && !gameEndData.hasWinner }">
          <button class="gm-close" @click="closeWinnerModal"><i class="fas fa-times" /></button>

          <!-- Header -->
          <div class="gm-header">
            <div v-if="gameEndData?.hasWinner || isMyWin" class="gm-trophy">
              <div class="gm-trophy__ring" />
              <span class="gm-trophy__icon">🏆</span>
            </div>
            <div v-else class="gm-trophy gm-trophy--end">
              <span class="gm-trophy__icon">🎱</span>
            </div>

            <h2 class="gm-title">
              <template v-if="isMyWin">¡BINGO! ¡Ganaste!</template>
              <template v-else-if="gameEndData?.hasWinner">¡Tenemos Ganador!</template>
              <template v-else>Sorteo Finalizado</template>
            </h2>
            <p class="gm-subtitle">
              <template v-if="gameEndData">
                {{ gameEndData.totalDrawn }} fichas sorteadas de {{ totalChips }}
              </template>
            </p>

            <!-- Barra de progreso del sorteo -->
            <div v-if="gameEndData" class="gm-progress">
              <div class="gm-progress__bar" :style="{ width: `${(gameEndData.totalDrawn / totalChips) * 100}%` }" />
              <span class="gm-progress__label">{{ gameEndData.totalDrawn }}/{{ totalChips }}</span>
            </div>
          </div>

          <!-- Ganadores -->
          <div v-if="gameEndData?.winners?.length" class="gm-winners">
            <div class="gm-winners__title">
              <i class="fas fa-star" /> {{ gameEndData.winners.length === 1 ? 'Ganador' : 'Ganadores' }}
            </div>
            <div
              v-for="(w, idx) in gameEndData.winners"
              :key="w.card_id"
              class="gm-winner-card"
              :class="{ 'gm-winner-card--me': String(w.user_id) === String(props.userId) }"
            >
              <div class="gm-winner-card__rank">#{{ idx + 1 }}</div>
              <div class="gm-winner-card__info">
                <div class="gm-winner-card__name">
                  {{ w.user_name }}
                  <span v-if="String(w.user_id) === String(props.userId)" class="gm-winner-card__you">TÚ</span>
                </div>
                <div class="gm-winner-card__serial">Cartón {{ w.card_serial }}</div>
              </div>
              <div class="gm-winner-card__prize">
                <span class="gm-winner-card__sym">{{ currencySym }}</span>
                {{ w.prize.toFixed(2) }}
              </div>
            </div>
          </div>

          <!-- Sin ganador -->
          <div v-else-if="gameEndData && !gameEndData.hasWinner" class="gm-no-winner">
            <i class="fas fa-info-circle" />
            Ningún cartón completó el bingo en este sorteo.
          </div>

          <!-- Fichas del sorteo resumidas -->
          <div v-if="liveDrawn.length > 0" class="gm-chips-summary">
            <div class="gm-chips-summary__label">Fichas sorteadas</div>
            <div class="gm-chips-summary__grid">
              <div
                v-for="n in [...liveDrawn]"
                :key="`gs-${n}`"
                class="ig-chip ig-chip--sm"
                :class="`ig-chip--${chipLetter(n)}`"
              >
                <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
                <span class="ig-chip__num">{{ n }}</span>
              </div>
            </div>
          </div>

          <!-- CTA -->
          <div class="gm-actions">
            <button class="gm-btn gm-btn--primary" @click="closeWinnerModal">
              <i class="fas fa-check" /> Cerrar
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>


<style scoped>
/* ══════════════════════════════════════
   PAGE — fondo estelar (igual que game-rooms/lobby)
══════════════════════════════════════ */
.ig-page {
  min-height: calc(100vh - 56px - 44px);
  margin: -0.5rem -0.5rem 0;
  padding: 20px 16px;
  position: relative;
  isolation: isolate;
  overflow-x: hidden;
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.4s ease, transform 0.4s ease;
  background:
    radial-gradient(ellipse 90% 55% at 15%  5%,  rgba(80,  0, 200, 0.30) 0%, transparent 60%),
    radial-gradient(ellipse 70% 50% at 85% 12%,  rgba( 0, 60, 220, 0.24) 0%, transparent 58%),
    radial-gradient(ellipse 60% 45% at 50% 85%,  rgba( 0,150, 110, 0.16) 0%, transparent 62%),
    radial-gradient(ellipse 55% 65% at 92% 68%,  rgba(130,  0, 210, 0.20) 0%, transparent 58%),
    radial-gradient(ellipse 45% 38% at  8% 58%,  rgba( 0, 90, 255, 0.14) 0%, transparent 52%),
    radial-gradient(ellipse 35% 30% at 40% 40%,  rgba( 0,180, 255, 0.08) 0%, transparent 50%),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
}
.ig-page--revealed { opacity: 1; transform: translateY(0) }

.ig-page::before {
  content: "";
  position: fixed; inset: 0;
  z-index: -2; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  6%  8%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(1px 1px at 14% 22%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 24%  4%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 38%, rgba(255,255,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 16%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  7%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 66% 30%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 76% 12%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 88%  5%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 95% 22%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at  3% 45%, rgba(255,255,255,0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 11% 60%, rgba(255,255,255,0.40) 0%, transparent 100%),
    radial-gradient(1px 1px at 20% 75%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 53% 48%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 83% 70%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 18% 18%, rgba(180,200,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 48% 10%, rgba(200,180,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 78% 28%, rgba(180,220,255,0.92) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 60% 58%, rgba(200,255,220,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 90% 45%, rgba(255,240,180,0.80) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}
.ig-page::after {
  content: "";
  position: fixed; inset: 0;
  z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at 10% 15%, rgba(255,255,255,0.95) 0%, transparent 100%),
    radial-gradient(2px 2px at 45%  8%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 65% 25%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 82% 18%, rgba(180,200,255,0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 23% 70%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 55% 62%, rgba(220,200,255,0.85) 0%, transparent 100%),
    radial-gradient(3px 3px at 37% 20%, rgba(255,255,255,1.00) 0%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(220,200,255,0.96) 0%, transparent 100%),
    radial-gradient(3px 3px at 15% 82%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(4px 4px at 62% 35%, rgba(255,255,255,1.00) 0%, rgba(200,220,255,0.3) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0% { opacity: 0.55 } 40% { opacity: 0.95 } 70% { opacity: 0.70 } 100% { opacity: 0.85 }
}
@keyframes twinkle-b {
  0% { opacity: 0.70 } 35% { opacity: 0.50 } 65% { opacity: 1.00 } 100% { opacity: 0.65 }
}

/* ══════════════════════════════════════
   INNER
══════════════════════════════════════ */
.ig-inner {
  position: relative;
  z-index: 1;
  max-width: 1100px;
  width: 100%;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-width: 0;
}

/* ══════════════════════════════════════
   PANEL
══════════════════════════════════════ */
.ig-panel {
  display: flex;
  flex-direction: column;
  gap: 0;
  background: linear-gradient(160deg, rgba(11,18,48,0.97) 0%, rgba(6,10,30,0.97) 100%);
  border: 1px solid rgba(80,120,255,0.16);
  border-radius: 18px;
  overflow: hidden;
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 48px rgba(0,0,0,0.55), 0 1px 0 rgba(255,255,255,0.04) inset;
  position: relative;
  min-width: 0;
}

/* línea top degradada */
.ig-panel__topline {
  height: 2px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(80,140,255,0.6) 20%,
    rgba(42,245,152,0.8) 50%,
    rgba(123,79,255,0.6) 80%,
    transparent 100%);
  flex-shrink: 0;
}

/* separador horizontal entre filas */
.ig-panel__hline {
  height: 1px;
  background: rgba(80,120,255,0.1);
  margin: 0;
  flex-shrink: 0;
}

/* ── Filas ── */
.ig-panel__row {
  display: flex;
  align-items: center;
  padding: 10px 18px;
  gap: 0;
}
.ig-panel__row--top  { min-height: 60px; }
.ig-panel__row--stats {
  padding: 8px 18px;
  gap: 0;
  flex-wrap: wrap;
  justify-content: space-between;
  background: rgba(255,255,255,0.018);
}

/* ── Prize ── */
.ig-prize {
  display: flex; flex-direction: column; gap: 1px;
  flex-shrink: 0;
  transition: transform 0.3s ease;
}
.ig-prize--flash { animation: prize-pop 0.65s ease }
@keyframes prize-pop {
  0%  { transform: scale(1) }
  40% { transform: scale(1.07) }
  100%{ transform: scale(1) }
}
.ig-prize__label {
  display: flex; align-items: center; gap: 5px;
  font-size: 9.5px; font-weight: 700; color: #4a68a8;
  text-transform: uppercase; letter-spacing: 1.3px;
}
.ig-prize__icon { color: #f5c518; font-size: 9.5px; }
.ig-prize__amount { display: flex; align-items: baseline; gap: 5px; }
.ig-prize__sym { font-size: 15px; font-weight: 700; color: #ffc840; }
.ig-prize__num {
  font-family: 'Anton', sans-serif;
  font-size: 32px; line-height: 1;
  background: linear-gradient(130deg, #ffe566 0%, #ffb800 55%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 8px rgba(255,180,0,0.3));
}

/* ── Divider vertical ── */
.ig-panel__divider {
  width: 1px; height: 38px;
  background: linear-gradient(180deg, transparent, rgba(80,120,255,0.2), transparent);
  margin: 0 16px;
  flex-shrink: 0;
}

/* ── Countdown ── */
.ig-countdown { display: flex; flex-direction: column; gap: 3px; flex-shrink: 0; }
.ig-countdown__label {
  font-size: 9.5px; font-weight: 700; color: #4a68a8;
  text-transform: uppercase; letter-spacing: 1px;
  display: flex; align-items: center; gap: 4px;
}
.ig-timer { display: flex; align-items: center; gap: 2px; }
.timer__unit {
  display: flex; flex-direction: column; align-items: center;
  background: rgba(80,120,255,0.08);
  border: 1px solid rgba(80,120,255,0.14);
  border-radius: 6px; padding: 2px 6px; min-width: 30px;
}
.timer__num { font-size: 16px; font-weight: 800; color: #e0eaff; line-height: 1.1; font-family: 'Courier New', monospace; }
.timer__lbl { font-size: 7px; color: #5a7acc; text-transform: uppercase; letter-spacing: 0.5px; }
.timer__sep { color: #3a5080; font-size: 18px; font-weight: 300; margin: 0 2px; padding-bottom: 10px; }

/* ── Live badge ── */
.ig-live-badge {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 16px;
  background: rgba(42,245,152,0.08);
  border: 1px solid rgba(42,245,152,0.25);
  border-radius: 30px;
  font-size: 12px; font-weight: 900; color: #2af598;
  letter-spacing: 2px; flex-shrink: 0;
  box-shadow: 0 0 20px rgba(42,245,152,0.08);
}
.ig-live-badge__dot {
  width: 8px; height: 8px; background: #2af598; border-radius: 50%;
  animation: dot-pulse 1.4s ease-in-out infinite;
  box-shadow: 0 0 6px rgba(42,245,152,0.8);
}
@keyframes dot-pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(42,245,152,0.7); }
  50%      { box-shadow: 0 0 0 7px rgba(42,245,152,0); }
}
.ig-live-badge--finished {
  background: rgba(100,120,160,0.10);
  border-color: rgba(100,120,160,0.25);
  color: #7a90b8;
  box-shadow: none;
  letter-spacing: 1.5px;
}

/* ── Acciones (mute + rooms) ── */
.ig-panel__actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

.ig-action-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 20px;
  font-size: 11px; font-weight: 700; cursor: pointer;
  transition: all 0.18s ease; border: 1px solid; text-decoration: none;
}
.ig-action-btn--rooms {
  background: rgba(100,140,255,0.1);
  border-color: rgba(100,140,255,0.25);
  color: #7c9dff;
}
.ig-action-btn--rooms:hover {
  background: rgba(100,140,255,0.2); color: #b0c8ff;
  box-shadow: 0 4px 14px rgba(100,140,255,0.2);
}
.ig-action-btn--mute {
  width: 34px; height: 34px; padding: 0;
  justify-content: center;
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
  color: #6a88c0; font-size: 13px;
  border-radius: 50%;
}
.ig-action-btn--mute:hover { background: rgba(255,255,255,0.1); color: #a0c0ff; }
.ig-action-btn--muted { color: #2a3055; border-color: rgba(255,255,255,0.06); }

/* ── Stats fila 2 ── */
.ig-stat {
  display: flex; align-items: center; gap: 8px;
  padding: 5px 14px;
  flex: 1; min-width: 0; position: relative;
}
.ig-stat i {
  width: 26px; height: 26px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; color: #7c9dff;
  background: rgba(100,140,255,0.1);
  border: 1px solid rgba(100,140,255,0.16);
  border-radius: 50%;
  transition: background 0.2s ease, color 0.2s ease;
}
.ig-stat__body { display: flex; flex-direction: column; gap: 0; min-width: 0; }
.ig-stat__val {
  font-size: 14px; font-weight: 800; color: #c8d8ff; line-height: 1.1;
  font-family: 'Courier New', monospace;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ig-stat__val small { font-size: 9px; color: #3a5080; font-weight: 500; }
.ig-stat__val--sm { font-size: 11px; font-family: inherit; }
.ig-stat__lbl {
  font-size: 8px; font-weight: 700; color: #3a5080;
  text-transform: uppercase; letter-spacing: 0.7px;
}
.ig-stat--flash i { background: rgba(42,245,152,0.16); color: #2af598; }
.ig-stat--flash .ig-stat__val { animation: stat-pop 0.5s ease; }
@keyframes stat-pop {
  0%  { color: #2af598; transform: scale(1); }
  40% { color: #2af598; transform: scale(1.15); }
  100%{ color: #c8d8ff; transform: scale(1); }
}

/* barra de progreso inline */
.ig-stat__bar {
  position: absolute; bottom: 2px; left: 48px; right: 14px; height: 2px;
  background: rgba(80,120,255,0.1); border-radius: 2px; overflow: hidden;
}
.ig-stat__bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #4a7fff, #2af598);
  border-radius: 2px;
  transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}

/* separador vertical entre stats */
.ig-stat__sep {
  width: 1px; align-self: stretch; margin: 4px 0;
  background: linear-gradient(180deg, transparent, rgba(80,120,255,0.14), transparent);
  flex-shrink: 0;
}

/* ── Mute btn legacy (eliminado del panel, mantenido por compatibilidad) ── */
.ig-mute-btn { display: none; }

/* ══════════════════════════════════════
   CARDS SECTION
══════════════════════════════════════ */
.ig-cards-section { width: 100% }

/* ══════════════════════════════════════
   DEV BAR
══════════════════════════════════════ */
.ig-dev-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: rgba(255, 171, 0, 0.06);
  border: 1px dashed rgba(255, 171, 0, 0.3);
  border-radius: 12px;
}
.ig-dev-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 18px;
  border-radius: 30px;
  border: 1px solid rgba(255, 171, 0, 0.4);
  background: rgba(255, 171, 0, 0.12);
  color: #ffd060;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
}
.ig-dev-btn:not(:disabled):hover {
  background: rgba(255, 171, 0, 0.22);
  transform: translateY(-1px);
}
.ig-dev-btn:disabled { opacity: 0.5; cursor: not-allowed }
.ig-dev-btn i { font-size: 13px }
.ig-dev-btn__spinner {
  width: 12px; height: 12px;
  border: 2px solid rgba(255,171,0,0.3);
  border-top-color: #ffd060;
  border-radius: 50%;
  animation: dev-spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes dev-spin { to { transform: rotate(360deg) } }
.ig-dev-label {
  font-size: 10px;
  color: rgba(255,171,0,0.45);
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

/* ══════════════════════════════════════
   PRE-DRAW BANNER
══════════════════════════════════════ */
.ig-predraw-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 20px;
  background: linear-gradient(135deg, rgba(123,79,255,0.18) 0%, rgba(0,158,253,0.12) 100%);
  border: 1px solid rgba(123,79,255,0.35);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  animation: banner-pulse 2s ease-in-out infinite;
}
@keyframes banner-pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(123,79,255,0.2) }
  50%     { box-shadow: 0 0 0 10px rgba(123,79,255,0) }
}
.ig-predraw-banner__icon { font-size: 22px }
.ig-predraw-banner__text {
  font-size: 14px;
  font-weight: 600;
  color: #c4aaff;
  letter-spacing: 0.3px;
}
.ig-predraw-banner__secs {
  display: inline-block;
  min-width: 20px;
  text-align: center;
  font-size: 18px;
  font-weight: 800;
  color: #fff;
  font-variant-numeric: tabular-nums;
}

/* ══════════════════════════════════════
   DRAWN NUMBERS BOARD
══════════════════════════════════════ */
/* ══════════════════════════════════════
   DRAWN BOARD
══════════════════════════════════════ */
.ig-drawn-board {
  background: linear-gradient(160deg, rgba(10,16,44,0.97) 0%, rgba(6,10,28,0.97) 100%);
  border: 1px solid rgba(80,120,255,0.14);
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 6px 36px rgba(0,0,0,0.5);
  display: flex; flex-direction: column;
}

/* ── Fila top ── */
.ig-db-top {
  display: flex; align-items: center; gap: 0;
  padding: 14px 18px;
}

/* Última bola */
.ig-db-last {
  position: relative; flex-shrink: 0;
  width: 56px; height: 56px;
  display: flex; align-items: center; justify-content: center;
  margin-right: 16px;
}
.ig-db-last__ring {
  position: absolute; inset: -5px; border-radius: 50%;
  border: 2px solid transparent;
  background: linear-gradient(135deg, #7b4fff, #2af598) border-box;
  -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out; mask-composite: exclude;
  animation: ring-spin 4s linear infinite;
}
@keyframes ring-spin { to { transform: rotate(360deg); } }

/* Info central */
.ig-db-info {
  display: flex; flex-direction: column; gap: 5px;
  flex: 1; min-width: 0;
}
.ig-db-info__label {
  font-size: 9px; font-weight: 700; color: #344870;
  text-transform: uppercase; letter-spacing: 1.2px;
  display: flex; align-items: center; gap: 4px;
}
.ig-db-info__label i { color: #5a3aaa; font-size: 9px; }
.ig-db-info__num {
  font-family: 'Anton', sans-serif; font-size: 36px; line-height: 1;
  background: linear-gradient(130deg, #d8e8ff 0%, #8090ff 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  letter-spacing: -1px;
}
.ig-db-info__progress {
  display: flex; align-items: center; gap: 8px;
  height: 4px; position: relative;
  background: rgba(80,120,255,0.1); border-radius: 4px; overflow: visible;
}
.ig-db-info__bar {
  height: 100%; border-radius: 4px;
  background: linear-gradient(90deg, #6040ff, #2af598);
  transition: width 0.7s cubic-bezier(0.4,0,0.2,1);
  box-shadow: 0 0 8px rgba(42,245,152,0.4);
}
.ig-db-info__pct {
  position: absolute; right: -42px; top: 50%; transform: translateY(-50%);
  font-size: 10px; font-weight: 800; color: #6080c0; white-space: nowrap;
}
.ig-db-info__pct em { font-style: normal; color: #2a3a5a; font-weight: 500; }

/* Recientes */
.ig-db-recent {
  display: flex; flex-direction: column; align-items: flex-end; gap: 5px;
  flex-shrink: 0; margin-left: 52px;
}
.ig-db-recent__label {
  font-size: 8px; font-weight: 700; color: #2a3a5a;
  text-transform: uppercase; letter-spacing: 1px;
}
.ig-db-recent__row { display: flex; gap: 5px; align-items: center; }

/* Separador horizontal */
.ig-db-sep {
  height: 1px; background: rgba(80,120,255,0.08); margin: 0;
}

/* ── Lista plana de bolas sorteadas ── */
.ig-db-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  padding: 10px 16px 14px;
}

/* ── Jugadores cerca de ganar (≤10 fichas restantes) ── */
.ig-near-win {
  background: linear-gradient(135deg, rgba(123,79,255,0.16) 0%, rgba(42,245,152,0.10) 100%);
  border: 1px solid rgba(123,79,255,0.30);
  border-radius: 18px;
  padding: 14px 18px 16px;
  display: flex; flex-direction: column; gap: 12px;
}
.ig-near-win__label {
  font-size: 12px; font-weight: 700; letter-spacing: 0.4px;
  color: #d8d8ff; display: flex; align-items: center; gap: 8px;
}
.ig-near-win__label i { color: #2af598; }
.ig-near-win__row {
  display: flex; flex-wrap: wrap; gap: 12px;
}
.ig-near-win__player {
  display: flex; align-items: center; gap: 10px;
  background: rgba(10,16,44,0.55);
  border: 1px solid rgba(80,120,255,0.18);
  border-radius: 999px;
  padding: 6px 16px 6px 6px;
  flex: 0 0 auto;
}
.ig-near-win__player--hot {
  border-color: rgba(42,245,152,0.55);
  box-shadow: 0 0 14px rgba(42,245,152,0.25);
}
.ig-near-win__avatar {
  width: 34px; height: 34px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #7b4fff 0%, #00c6ff 100%);
  color: #fff; font-size: 14px; flex-shrink: 0;
}
.ig-near-win__player--hot .ig-near-win__avatar {
  background: linear-gradient(135deg, #2af598 0%, #00c6ff 100%);
}
.ig-near-win__body {
  display: flex; flex-direction: column; line-height: 1.25;
}
.ig-near-win__name {
  font-size: 13px; font-weight: 700; color: #f0f0ff;
}
.ig-near-win__missing {
  font-size: 11px; font-weight: 600; color: #9aa6d4;
}
.ig-near-win__player--hot .ig-near-win__missing {
  color: #2af598;
}

/* ── CSS Bingo Chips ── */
.ig-chip {
  border-radius: 50%;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  line-height: 1; user-select: none;
  position: relative; flex-shrink: 0;
}

/* tamaños */
.ig-chip--lg {
  width: 52px; height: 52px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.6),
              inset 0 2px 0 rgba(255,255,255,0.3),
              inset 0 -2px 4px rgba(0,0,0,0.2);
  animation: chip-enter 0.45s cubic-bezier(0.34,1.56,0.64,1) both;
  z-index: 1;
}
.ig-chip--md {
  width: 34px; height: 34px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.45),
              inset 0 1px 0 rgba(255,255,255,0.25),
              inset 0 -1px 3px rgba(0,0,0,0.15);
  animation: chip-pop 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  cursor: default;
}
.ig-chip--md:hover {
  transform: scale(1.2);
  box-shadow: 0 4px 14px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.3);
}
.ig-chip--sm {
  width: 24px; height: 24px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.22);
  opacity: 0.75; transition: opacity 0.18s, transform 0.15s;
}
.ig-chip--sm:hover { opacity: 1; transform: scale(1.18); }

/* colores por letra — gradiente radial para esfera */
.ig-chip--B { background: radial-gradient(circle at 38% 32%, #5080ff, #0d35b0); }
.ig-chip--I { background: radial-gradient(circle at 38% 32%, #ff5050, #900000); }
.ig-chip--N { background: radial-gradient(circle at 38% 32%, #f0d040, #806e00); }
.ig-chip--G { background: radial-gradient(circle at 38% 32%, #30d870, #057030); }
.ig-chip--O { background: radial-gradient(circle at 38% 32%, #c050ff, #5808a8); }

/* última bola sorteada */
.ig-chip--latest {
  box-shadow:
    0 0 0 2px rgba(255,255,255,0.7),
    0 0 18px rgba(255,255,255,0.3),
    0 2px 6px rgba(0,0,0,0.45),
    inset 0 1px 0 rgba(255,255,255,0.3) !important;
  animation: chip-enter 0.45s cubic-bezier(0.34,1.56,0.64,1) both !important;
}

/* texto chips */
.ig-chip__letter {
  font-family: 'Anton', sans-serif; line-height: 1;
  color: rgba(255,255,255,0.5);
}
.ig-chip--lg .ig-chip__letter { font-size: 8.5px; margin-bottom: 1px; }
.ig-chip--md .ig-chip__letter { font-size: 7.5px; }
.ig-chip--sm .ig-chip__letter { display: none; }

.ig-chip__num {
  font-family: 'Anton', sans-serif; line-height: 1; color: #fff;
  text-shadow: 0 1px 4px rgba(0,0,0,0.55);
}
.ig-chip--lg .ig-chip__num { font-size: 19px; }
.ig-chip--md .ig-chip__num { font-size: 12px; }
.ig-chip--sm .ig-chip__num { font-size: 10.5px; }

@keyframes chip-enter {
  0%   { transform: scale(0) rotate(-25deg); opacity: 0; }
  65%  { transform: scale(1.15) rotate(4deg); opacity: 1; }
  100% { transform: scale(1) rotate(0deg); }
}
@keyframes chip-pop {
  from { transform: scale(0); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

/* ══════════════════════════════════════
   CARDS SECTION
══════════════════════════════════════ */

/* Drawn counter badge */
.ig-badge--drawn {
  background: rgba(42,245,152,0.08);
  border-color: rgba(42,245,152,0.2);
  color: #2af598;
}

/* ══════════════════════════════════════
   TRANSITION
══════════════════════════════════════ */
.ig-fade-enter-active,
.ig-fade-leave-active { transition: opacity 0.35s ease, transform 0.35s ease }
.ig-fade-enter-from,
.ig-fade-leave-to { opacity: 0; transform: translateY(-6px) }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */

/* ── Tablet ── */
@media (max-width: 768px) {
  .ig-inner { gap: 10px; }
  .ig-panel__row--top { padding: 10px 14px; }
  .ig-stat { padding: 5px 10px; }
  .ig-stat__sep { display: none; }
}

/* ── Mobile ── */
@media (max-width: 540px) {
  .ig-page { padding: 12px 10px; }
  .ig-inner { gap: 8px; }

  /* Header: prize arriba-izq, badge/countdown centro, mute arriba-der */
  .ig-panel__row--top {
    padding: 10px 12px;
    min-height: auto;
    flex-wrap: nowrap;
    gap: 0;
    align-items: center;
  }
  .ig-panel__divider { display: none; }
  .ig-prize { flex-direction: row; align-items: center; gap: 6px; }
  .ig-prize__label { display: none; }
  .ig-prize__sym { font-size: 12px; }
  .ig-prize__num { font-size: 24px; }

  /* Countdown/badge más compacto */
  .ig-countdown { gap: 1px; }
  .ig-countdown__label { font-size: 8px; }
  .timer__unit { min-width: 24px; padding: 2px 4px; }
  .timer__num { font-size: 13px; }
  .timer__lbl { font-size: 6px; }
  .timer__sep { font-size: 14px; margin: 0 1px; padding-bottom: 8px; }
  .ig-live-badge { padding: 4px 10px; font-size: 10px; letter-spacing: 1px; }

  /* Stats: grid 2×2 en lugar de fila horizontal */
  .ig-panel__row--stats {
    padding: 6px 10px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
  }
  .ig-stat__sep { display: none; }
  .ig-stat {
    padding: 6px 8px;
    flex: none;
    border-bottom: 1px solid rgba(80,120,255,0.07);
  }
  .ig-stat:nth-child(odd)  { border-right: 1px solid rgba(80,120,255,0.07); }
  .ig-stat i { width: 22px; height: 22px; font-size: 10px; }
  .ig-stat__val { font-size: 13px; }
  .ig-stat__lbl { font-size: 7.5px; }
  .ig-stat__bar { left: 36px; right: 8px; }

  /* Chips board */
  .ig-db-top { padding: 10px 12px; gap: 0; flex-wrap: wrap; }
  .ig-db-last { width: 44px; height: 44px; margin-right: 10px; }
  .ig-chip--lg { width: 44px; height: 44px; }
  .ig-chip--lg .ig-chip__num { font-size: 15px; }
  .ig-db-info__num { font-size: 28px; }
  .ig-db-recent { margin-left: 0; margin-top: 8px; width: 100%; align-items: flex-start; }
  .ig-db-recent__row { flex-wrap: wrap; gap: 4px; }
  .ig-chip--sm { width: 22px; height: 22px; }
  .ig-chip--sm .ig-chip__num { font-size: 9px; }
  .ig-db-chips { padding: 8px 12px 10px; gap: 4px; }

  /* Dev bar */
  .ig-dev-bar { padding: 8px 10px; gap: 8px; flex-wrap: wrap; }
  .ig-dev-btn { padding: 7px 14px; font-size: 11px; }
  .ig-dev-label { font-size: 9px; }
}

/* ── Mobile XS (≤360px) ── */
@media (max-width: 360px) {
  .ig-page { padding: 10px 8px; }
  .ig-prize__num { font-size: 20px; }
  .ig-panel__row--top { padding: 8px 10px; }
  .timer__unit { min-width: 20px; padding: 1px 3px; }
  .timer__num { font-size: 11px; }
  .ig-stat { padding: 5px 6px; }
  .ig-stat__val { font-size: 12px; }
}

/* ══════════════════════════════════════════
   GAME-END MODAL
══════════════════════════════════════════ */

/* Confetti pieces */
.gm-confetti {
  position: fixed;
  top: -12px;
  width: 10px; height: 14px;
  border-radius: 2px;
  z-index: 10000;
  animation: cf-fall linear both;
  pointer-events: none;
}
@keyframes cf-fall {
  0%   { transform: translateY(0) rotate(0deg) scaleX(1); opacity: 1; }
  80%  { opacity: 1; }
  100% { transform: translateY(110vh) rotate(720deg) scaleX(-1); opacity: 0; }
}

/* Overlay */
.gm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  background: rgba(4, 8, 26, 0.88);
  backdrop-filter: blur(10px);
  padding: 12px;
}

/* Modal box */
.gm-modal {
  position: relative;
  width: 100%; max-width: 520px;
  max-height: 90vh; overflow-y: auto;
  background: linear-gradient(160deg, #0d1633 0%, #080f26 100%);
  border: 1.5px solid rgba(123,79,255,0.35);
  border-radius: 24px;
  padding: 28px 24px 24px;
  animation: gm-enter 0.45s cubic-bezier(0.34,1.56,0.64,1) both;
  scrollbar-width: thin;
  scrollbar-color: rgba(123,79,255,0.3) transparent;
}
.gm-modal--win {
  border-color: rgba(42,245,152,0.45);
  box-shadow: 0 0 60px rgba(42,245,152,0.15), 0 0 100px rgba(123,79,255,0.1);
}
.gm-modal--no-winner {
  border-color: rgba(100,140,255,0.25);
}
@keyframes gm-enter {
  from { transform: scale(0.82) translateY(24px); opacity: 0; }
  to   { transform: scale(1) translateY(0);       opacity: 1; }
}

/* Close button */
.gm-close {
  position: absolute; top: 14px; right: 14px;
  width: 32px; height: 32px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.05);
  color: #7c9dff; font-size: 13px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: background 0.2s, color 0.2s;
}
.gm-close:hover { background: rgba(255,255,255,0.12); color: #fff; }

/* Header */
.gm-header { text-align: center; margin-bottom: 20px; }

.gm-trophy {
  position: relative; display: inline-flex;
  align-items: center; justify-content: center;
  width: 80px; height: 80px; margin-bottom: 14px;
}
.gm-trophy__ring {
  position: absolute; inset: 0; border-radius: 50%;
  border: 2px solid transparent;
  background: linear-gradient(135deg,#ffd700,#7b4fff,#2af598) border-box;
  -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out; mask-composite: exclude;
  animation: ring-spin 3s linear infinite;
}
.gm-trophy__icon { font-size: 48px; animation: trophy-bounce 1.2s ease-in-out infinite; }
.gm-trophy--end .gm-trophy__icon { animation: none; }

@keyframes trophy-bounce {
  0%,100% { transform: scale(1) rotate(-4deg); }
  50%      { transform: scale(1.1) rotate(4deg); }
}

.gm-title {
  font-family: 'Anton', sans-serif; font-size: 26px; letter-spacing: 1px;
  margin: 0 0 6px;
  background: linear-gradient(135deg,#ffe566,#ffab00);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.gm-modal--win .gm-title {
  background: linear-gradient(135deg,#2af598,#00c853);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.gm-modal--no-winner .gm-title {
  background: linear-gradient(135deg,#7c9dff,#5578bb);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.gm-subtitle {
  font-size: 13px; color: #5578bb; margin: 0 0 12px;
}

/* Progress */
.gm-progress {
  position: relative; height: 6px; border-radius: 6px;
  background: rgba(100,140,255,0.1); overflow: hidden; margin: 0 auto;
  max-width: 260px;
}
.gm-progress__bar {
  height: 100%; border-radius: 6px;
  background: linear-gradient(90deg,#7b4fff,#2af598);
  transition: width 0.8s ease;
}
.gm-progress__label {
  position: absolute; right: 0; top: -18px;
  font-size: 10px; color: #5578bb;
}

/* Winners list */
.gm-winners { margin: 16px 0; }
.gm-winners__title {
  font-size: 11px; font-weight: 700; color: #5578bb;
  text-transform: uppercase; letter-spacing: 1px;
  margin-bottom: 10px;
  display: flex; align-items: center; gap: 6px;
}
.gm-winners__title i { color: #ffd700; }

.gm-winner-card {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; border-radius: 14px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(100,140,255,0.1);
  margin-bottom: 8px;
  transition: border-color 0.2s;
}
.gm-winner-card--me {
  background: rgba(42,245,152,0.06);
  border-color: rgba(42,245,152,0.3);
}

.gm-winner-card__rank {
  font-family: 'Anton', sans-serif; font-size: 18px;
  color: #ffd700; min-width: 28px;
}
.gm-winner-card--me .gm-winner-card__rank { color: #2af598; }

.gm-winner-card__info { flex: 1; }
.gm-winner-card__name {
  font-size: 14px; font-weight: 700; color: #e0eaff;
  display: flex; align-items: center; gap: 6px;
}
.gm-winner-card__you {
  font-size: 9px; font-weight: 800; letter-spacing: 1px;
  background: linear-gradient(135deg,#2af598,#00c853);
  color: #000; padding: 1px 6px; border-radius: 8px;
}
.gm-winner-card__serial {
  font-size: 11px; color: #4a6aaa; margin-top: 2px;
}

.gm-winner-card__prize {
  font-family: 'Anton', sans-serif; font-size: 20px;
  color: #ffd060; display: flex; align-items: baseline; gap: 2px;
}
.gm-winner-card__sym { font-size: 12px; color: #b09040; }

/* No winner */
.gm-no-winner {
  text-align: center; padding: 20px;
  font-size: 13px; color: #5578bb;
  background: rgba(100,140,255,0.04);
  border-radius: 12px; border: 1px solid rgba(100,140,255,0.08);
  display: flex; align-items: center; justify-content: center; gap: 8px;
  margin: 8px 0 16px;
}
.gm-no-winner i { color: #4a6aaa; }

/* Chips summary */
.gm-chips-summary { margin: 16px 0; }
.gm-chips-summary__label {
  font-size: 10px; font-weight: 700; color: #3d5280;
  text-transform: uppercase; letter-spacing: 0.8px;
  margin-bottom: 8px;
}
.gm-chips-summary__grid {
  display: flex; flex-wrap: wrap; gap: 4px;
  max-height: 120px; overflow-y: auto;
  scrollbar-width: thin; scrollbar-color: rgba(123,79,255,0.2) transparent;
}

/* Actions */
.gm-actions { display: flex; gap: 10px; margin-top: 20px; }
.gm-btn {
  flex: 1; padding: 13px 20px; border: none; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 7px;
  transition: transform 0.18s, box-shadow 0.18s;
}
.gm-btn--primary {
  background: linear-gradient(135deg,#7b4fff,#009efd);
  color: #fff;
  box-shadow: 0 4px 16px rgba(123,79,255,0.3);
}
.gm-modal--win .gm-btn--primary {
  background: linear-gradient(135deg,#2af598,#00c853);
  color: #003316;
  box-shadow: 0 4px 16px rgba(42,245,152,0.3);
}
.gm-btn--primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(123,79,255,0.45); }

/* Transition */
.gm-fade-enter-active, .gm-fade-leave-active { transition: opacity 0.28s ease; }
.gm-fade-enter-from, .gm-fade-leave-to { opacity: 0; }
.gm-fade-enter-active .gm-modal, .gm-fade-leave-active .gm-modal {
  transition: transform 0.32s cubic-bezier(0.34,1.56,0.64,1), opacity 0.28s ease;
}
.gm-fade-enter-from .gm-modal, .gm-fade-leave-to .gm-modal {
  transform: scale(0.88); opacity: 0;
}

@media (max-width: 500px) {
  .gm-modal { padding: 22px 16px 18px; }
  .gm-title { font-size: 22px; }
  .gm-trophy__icon { font-size: 40px; }
  .gm-winner-card__prize { font-size: 17px; }
}

/* ══════════════════════════════════════
   PLAYERS / WINNERS MODAL
══════════════════════════════════════ */
.pm-modal { max-width: 480px; }
.pm-card-modal { max-width: 400px; }

.pm-winner-row { gap: 10px; }

.pm-round-badge, .pm-pattern-badge {
  display: inline-block;
  margin-left: 6px;
  font-size: 9px; font-weight: 700; letter-spacing: 0.5px;
  padding: 1px 6px; border-radius: 8px;
}
.pm-round-badge {
  background: rgba(100,140,255,0.15);
  border: 1px solid rgba(100,140,255,0.25);
  color: #7c9dff;
}
.pm-pattern-badge {
  background: rgba(123,79,255,0.15);
  border: 1px solid rgba(123,79,255,0.25);
  color: #c4aaff;
  text-transform: uppercase;
}

.pm-view-card-btn {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(100,140,255,0.25);
  background: rgba(100,140,255,0.08);
  color: #7c9dff; font-size: 12px;
  cursor: pointer; transition: background 0.2s, color 0.2s, transform 0.15s;
}
.pm-view-card-btn:hover {
  background: rgba(100,140,255,0.2); color: #b0c8ff;
  transform: scale(1.1);
}

/* ── Stat clickable ── */
.ig-stat--clickable {
  cursor: pointer;
  border-radius: 10px;
  transition: background 0.2s ease;
}
.ig-stat--clickable:hover {
  background: rgba(100,140,255,0.08);
}
.ig-stat__winners-badge {
  font-size: 11px; font-weight: 700;
  color: #ffd700; margin-left: 6px;
  font-family: inherit;
}
.ig-stat__winners-badge i { font-size: 9px; }

/* ── Card viewer loading ── */
.pm-card-loading {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  padding: 32px; font-size: 13px; color: #5578bb;
}
.pm-card-spinner {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid rgba(100,140,255,0.2);
  border-top-color: #7c9dff;
  animation: dev-spin 0.7s linear infinite; flex-shrink: 0;
}

/* ── Bingo card grid ── */
.pm-bingo-card {
  margin: 8px auto 16px;
  max-width: 320px;
  border-radius: 14px; overflow: hidden;
  border: 1px solid rgba(80,120,255,0.18);
  box-shadow: 0 4px 20px rgba(0,0,0,0.4);
}
.pm-bingo-header {
  display: grid; grid-template-columns: repeat(5, 1fr);
  background: linear-gradient(135deg, rgba(123,79,255,0.5) 0%, rgba(0,158,253,0.4) 100%);
}
.pm-bingo-header__letter {
  text-align: center; padding: 8px 0;
  font-family: 'Anton', sans-serif; font-size: 16px; color: #fff;
  letter-spacing: 1px;
}
.pm-bingo-row {
  display: grid; grid-template-columns: repeat(5, 1fr);
}
.pm-bingo-row:nth-child(even) { background: rgba(255,255,255,0.025); }
.pm-bingo-cell {
  text-align: center; padding: 10px 4px;
  font-size: 14px; font-weight: 700; color: #8090c0;
  border: 1px solid rgba(80,120,255,0.06);
  transition: background 0.2s, color 0.2s;
}
.pm-bingo-cell--drawn {
  background: rgba(42,245,152,0.18);
  color: #2af598;
  border-color: rgba(42,245,152,0.25);
}
.pm-bingo-cell--free {
  background: rgba(123,79,255,0.15);
  color: #c4aaff; font-size: 16px;
}

/* ── Botón mute ── */
.ig-mute-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; border-radius: 50%;
  border: 1px solid rgba(100,140,255,0.22);
  background: rgba(255,255,255,0.05);
  color: #6a8ac8; font-size: 11px;
  cursor: pointer; flex-shrink: 0;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.ig-mute-btn:hover { background: rgba(100,140,255,0.12); color: #a0c0ff; border-color: rgba(100,140,255,0.4); }
.ig-mute-btn--off { color: #3a3a5a; border-color: rgba(80,80,120,0.2); background: rgba(0,0,0,0.2); }
</style>
