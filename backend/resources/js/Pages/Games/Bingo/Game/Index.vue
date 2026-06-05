<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link } from "@inertiajs/vue3";
import "@/css/games/bingo/bingo-in-game.css";
import { getEchoInstance } from "@js/plugins/echo";
import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useAntiDebug } from "@js/composables/games/bingo/useAntiDebug";
import BingoCartons from "@js/Components/Games/Bingo/views/inGame/Cartons.vue";
import type { BingoRoom, BingoCard } from "@js/Services/bingoService";
import Swal from "sweetalert2";

// ─────────────────────────────────────────────────────────────────────────────
// Anti-Debug Protection (solo en producción)
// ─────────────────────────────────────────────────────────────────────────────
useAntiDebug()

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

interface GameEndData {
  hasWinner: boolean;
  totalDrawn: number;
  winners: Array<{ user_id: number; user_name: string; card_id: string; card_serial: string; prize: number }>;
  prize: number;
  endTime: string;
}

const showWinnerModal  = ref(false);
const gameEndData      = ref<GameEndData | null>(null);
const winnerInfo       = ref<WinnerInfo | null>(null);   // compat: WinnerDeclared individual
const isMyWin          = ref(false);
const checkingBingo    = ref(false);

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
const prizeFormatted = computed(() => {
  const convertedPrize = livePrize.value * liveExchangeRate.value;
  return convertedPrize % 1 === 0 ? convertedPrize.toFixed(0) : convertedPrize.toFixed(2);
});

const cardCount = computed(() => props.cartons?.length ?? 0);

function chipLetter(n: number): string {
  if (n <= 15) return 'B';
  if (n <= 30) return 'I';
  if (n <= 45) return 'N';
  if (n <= 60) return 'G';
  return 'O';
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
  isMyWin.value = winner.id === (props as any).userId;
}

function handleGameEnded(data: { has_winner: boolean; total_drawn: number; winners: any[]; prize: number; end_time: string }) {
  gameEndData.value = {
    hasWinner:  data.has_winner,
    totalDrawn: data.total_drawn,
    winners:    data.winners ?? [],
    prize:      data.prize ?? 0,
    endTime:    data.end_time,
  };
  // Determinar si el usuario local es ganador
  const myId = (props as any).userId;
  if (myId && data.winners?.some((w: any) => w.user_id === myId || String(w.user_id) === String(myId))) {
    isMyWin.value = true;
  }
  showWinnerModal.value = true;
}

function closeWinnerModal() {
  showWinnerModal.value = false;
}

// ─────────────────────────────────────────────────────────────────────────────
// WebSocket
// ─────────────────────────────────────────────────────────────────────────────
let channel: any = null;

function flash(target: Ref<boolean>) {
  target.value = true;
  setTimeout(() => { target.value = false; }, 1000);
}

function subscribeToRoom() {
  if (!roomId.value) return;
  const echo = getEchoInstance();
  channel = echo.channel(`bingo.room.${roomId.value}`);

  // Prize pool / players count updated
  channel.listen(".RoomUpdated", (e: { prizePool: number; totalPlayers: number }) => {
    if (e.prizePool !== undefined) {
      livePrize.value = e.prizePool;
      flash(prizeFlash);
    }
    if (e.totalPlayers !== undefined) {
      livePlayers.value = e.totalPlayers;
      flash(playersFlash);
    }
  });

  // Player joined
  channel.listen(".PlayerJoined", (e: { currentPlayers: number }) => {
    livePlayers.value = e.currentPlayers;
    flash(playersFlash);
  });

  // Number drawn
  channel.listen(".BingoNumberCalled", (e: { number: number }) => {
    if (e.number && !liveDrawn.value.includes(e.number)) {
      liveDrawn.value = [...liveDrawn.value, e.number];
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
});

onUnmounted(() => {
  stopCountdown();
  clearDrawTimer();
  if (channel && roomId.value) {
    const echo = getEchoInstance();
    echo.leaveChannel(`bingo.room.${roomId.value}`);
  }
});
</script>

<template>
  <div class="ig-page" :class="{ 'ig-page--revealed': revealed }">
    <div class="ig-inner">

      <!-- ══════════════════════════════════════════
           HEADER PANEL
      ══════════════════════════════════════════ -->
      <div class="ig-panel">

        <!-- Prize -->
        <div class="ig-prize" :class="{ 'ig-prize--flash': prizeFlash }">
          <span class="ig-prize__label">
            <i class="fas fa-trophy ig-prize__icon" />
            {{ prizePoolType }}
          </span>
          <div class="ig-prize__amount">
            <span class="ig-prize__sym">{{ currencySym }}</span>
            <span class="ig-prize__num">{{ prizeFormatted }}</span>
          </div>
        </div>

        <div class="ig-panel__divider" />

        <!-- Countdown / Live -->
        <div class="ig-countdown" v-if="hasTime && cd">
          <span class="ig-countdown__label">
            <i class="fas fa-clock" /> Inicia en
          </span>
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
        <div v-else class="ig-live-badge">
          <span class="ig-live-badge__dot" /> EN VIVO
        </div>

        <div class="ig-panel__divider" />

        <!-- Meta badges -->
        <div class="ig-meta">
          <div class="ig-badge ig-badge--players" :class="{ 'ig-badge--flash': playersFlash }">
            <i class="fas fa-users" /><span>{{ livePlayers }}</span><small>jugadores</small>
          </div>
          <div v-if="rounds" class="ig-badge ig-badge--round">
            <i class="fas fa-sync-alt" /> RONDA <strong>#{{ round }}</strong>
          </div>
          <div v-if="bingoPattern" class="ig-badge ig-badge--pattern">
            <i class="fas fa-th" /> {{ bingoPattern }}
          </div>
          <div class="ig-badge ig-badge--cards">
            <i class="fas fa-ticket-alt" />
            {{ cardCount }} cartón{{ cardCount === 1 ? '' : 'es' }}
          </div>
          <!-- Progreso de aciertos total -->
          <div v-if="liveDrawn.length > 0" class="ig-badge ig-badge--drawn">
            <i class="fas fa-random" /> {{ liveDrawn.length }}<small>/75</small>
          </div>
          <Link
            v-if="String(room?.status ?? '').toUpperCase() === 'FINISHED' || String(room?.status_code ?? '').toUpperCase() === 'FINISHED'"
            href="/bingo/game-rooms"
            class="ig-badge ig-badge--rooms"
          >
            <i class="fas fa-door-open" /> Salas
          </Link>
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
          <div class="ig-drawn-board__top">
            <!-- Última ficha prominente -->
            <div class="ig-last-chip-wrap">
              <div class="ig-last-chip-ring" />
              <div class="ig-chip ig-chip--lg" :class="`ig-chip--${chipLetter(liveDrawn[liveDrawn.length-1])}`">
                <span class="ig-chip__letter">{{ chipLetter(liveDrawn[liveDrawn.length-1]) }}</span>
                <span class="ig-chip__num">{{ liveDrawn[liveDrawn.length-1] }}</span>
              </div>
            </div>
            <!-- Info sorteo -->
            <div class="ig-drawn-board__info">
              <div class="ig-drawn-board__label">
                <i class="fas fa-random" /> Último número
              </div>
              <div class="ig-drawn-board__num">{{ liveDrawn[liveDrawn.length - 1] }}</div>
              <div class="ig-drawn-progress">
                <div class="ig-drawn-progress__bar" :style="{ width: `${(liveDrawn.length / 75) * 100}%` }" />
              </div>
              <div class="ig-drawn-board__count">{{ liveDrawn.length }} <span>de 75 sorteados</span></div>
            </div>
            <!-- Últimas fichas recientes -->
            <div class="ig-recent-chips">
              <div class="ig-recent-chips__label">Recientes</div>
              <div class="ig-recent-chips__row">
                <div
                  v-for="n in [...liveDrawn].slice(-6).reverse().slice(1)"
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
          <!-- Todas las fichas sorteadas -->
          <div class="ig-drawn-board__chips">
            <div
              v-for="n in [...liveDrawn].reverse()"
              :key="n"
              class="ig-chip ig-chip--md"
              :class="[`ig-chip--${chipLetter(n)}`, { 'ig-chip--latest': n === liveDrawn[liveDrawn.length-1] }]"
            >
              <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
              <span class="ig-chip__num">{{ n }}</span>
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
          @bingo-detected="handleBingoDetected"
        />
      </div>

    </div>

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
                {{ gameEndData.totalDrawn }} fichas sorteadas de 75
              </template>
            </p>

            <!-- Barra de progreso del sorteo -->
            <div v-if="gameEndData" class="gm-progress">
              <div class="gm-progress__bar" :style="{ width: `${(gameEndData.totalDrawn / 75) * 100}%` }" />
              <span class="gm-progress__label">{{ gameEndData.totalDrawn }}/75</span>
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
              :class="{ 'gm-winner-card--me': String(w.user_id) === String((props as any).userId) }"
            >
              <div class="gm-winner-card__rank">#{{ idx + 1 }}</div>
              <div class="gm-winner-card__info">
                <div class="gm-winner-card__name">
                  {{ w.user_name }}
                  <span v-if="String(w.user_id) === String((props as any).userId)" class="gm-winner-card__you">TÚ</span>
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

<style>
html, body {
  height: auto !important;
  min-height: 100%;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  background: #04081a !important;
}
body, body.hold-transition, #app-wrapper, .wrapper, .content-wrapper {
  background-color: #04081a !important;
  background-image: none !important;
}
/* ── Quitar línea/borde del navbar AdminLTE ── */
.main-header,
.main-header.navbar,
nav.main-header,
.navbar-bg,
.main-header .navbar-bg,
.app-header {
  border-bottom: 0 !important;
  border-bottom-width: 0 !important;
  box-shadow: none !important;
}
.main-header::after,
.main-header.navbar::after,
nav.main-header::after,
.navbar-bg::after {
  display: none !important;
  content: none !important;
}
.content-header {
  display: none !important;
  padding: 0 !important;
  min-height: 0 !important;
  height: 0 !important;
}
.content-wrapper > .content {
  padding: 0 !important;
  display: flex;
  flex-direction: column;
  overflow: visible !important;
  height: auto !important;
  min-height: 100vh;
}
.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1; display: flex; flex-direction: column;
  height: auto !important; min-height: 100vh; overflow: visible !important;
}
.wrapper, .content-wrapper {
  height: auto !important; min-height: 100vh; overflow: visible !important;
}
</style>

<style scoped>
/* ══════════════════════════════════════
   PAGE — fondo estelar
══════════════════════════════════════ */
.ig-page {
  flex: 1;
  min-height: 100vh;
  padding: 24px 16px 64px;
  position: relative;
  isolation: isolate;
  overflow-y: auto;
  overflow-x: hidden;
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.4s ease, transform 0.4s ease;
  scrollbar-width: thin;
  scrollbar-color: #7b4fff rgba(255,255,255,0.03);
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

.ig-page::-webkit-scrollbar { width: 4px }
.ig-page::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 10px }
.ig-page::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff 0%, #2af598 45%, #009efd 100%);
  border-radius: 10px;
}
.ig-page::-webkit-scrollbar-button { display: none }

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
  max-width: 960px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ══════════════════════════════════════
   PANEL
══════════════════════════════════════ */
.ig-panel {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0;
  background: linear-gradient(135deg, rgba(13,22,51,0.94) 0%, rgba(8,15,38,0.94) 100%);
  border: 1px solid rgba(100,140,255,0.18);
  border-radius: 20px;
  padding: 14px 20px;
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(80,120,255,0.06) inset;
  position: relative;
  overflow: hidden;
}
.ig-panel::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(100,140,255,0.4), rgba(42,245,152,0.3), transparent);
}

/* ── Prize ── */
.ig-prize {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex-shrink: 0;
  transition: transform 0.3s ease;
}
.ig-prize--flash { animation: prize-pop 0.6s ease }
@keyframes prize-pop {
  0%  { transform: scale(1) }
  40% { transform: scale(1.06) }
  100%{ transform: scale(1) }
}

.ig-prize__label {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10px;
  font-weight: 700;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 1.2px;
}
.ig-prize__icon { color: #f5c518; font-size: 10px }
.ig-prize__amount { display: flex; align-items: baseline; gap: 4px }
.ig-prize__sym { font-size: 16px; font-weight: 700; color: #ffd060 }
.ig-prize__num {
  font-family: 'Anton', sans-serif;
  font-size: 38px;
  line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ── Divider ── */
.ig-panel__divider {
  width: 1px; height: 52px;
  background: rgba(100,140,255,0.15);
  margin: 0 18px;
  flex-shrink: 0;
}

/* ── Countdown ── */
.ig-countdown { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0 }
.ig-countdown__label {
  font-size: 10px;
  font-weight: 700;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.ig-timer {
  display: flex;
  align-items: center;
  gap: 2px;
}
.timer__unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 28px;
}
.timer__num { font-size: 14px; font-weight: 700; color: #e0eaff; line-height: 1 }
.timer__lbl { font-size: 8px; color: #7c9dff; text-transform: uppercase; line-height: 1 }
.timer__sep { color: #4a6aaa; font-size: 13px; line-height: 1; margin: 0 1px; padding-bottom: 8px }

/* ── Live badge ── */
.ig-live-badge {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 5px 14px;
  background: rgba(42,245,152,0.1);
  border: 1px solid rgba(42,245,152,0.3);
  border-radius: 30px;
  font-size: 11px;
  font-weight: 800;
  color: #2af598;
  letter-spacing: 1.5px;
  flex-shrink: 0;
}
.ig-live-badge__dot {
  width: 7px; height: 7px;
  background: #2af598;
  border-radius: 50%;
  animation: dot-pulse 1.4s ease-in-out infinite;
}
@keyframes dot-pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(42,245,152,0.6) }
  50%      { box-shadow: 0 0 0 6px rgba(42,245,152,0) }
}

/* ── Meta badges ── */
.ig-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  align-items: center;
}
.ig-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  border-radius: 30px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.3px;
  border: 1px solid;
  transition: box-shadow 0.3s ease;
}
.ig-badge i { font-size: 10px; opacity: 0.75 }
.ig-badge small { font-size: 9px; opacity: 0.65; font-weight: 400 }

.ig-badge--flash { animation: badge-pop 0.5s ease }

/* ── Rooms badge (button) ── */
.ig-badge--rooms {
  background: rgba(100, 140, 255, 0.1);
  border-color: rgba(100, 140, 255, 0.3);
  color: #7c9dff;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
}
.ig-badge--rooms:hover {
  background: rgba(100, 140, 255, 0.2);
  border-color: rgba(100, 140, 255, 0.5);
  color: #a8bdff;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(100, 140, 255, 0.2);
}
@keyframes badge-pop {
  0%  { transform: scale(1); box-shadow: 0 0 0 0 rgba(42,245,152,0.4) }
  40% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(42,245,152,0) }
  100%{ transform: scale(1) }
}

.ig-badge--players {
  background: rgba(0,158,253,0.1);
  border-color: rgba(0,158,253,0.28);
  color: #66d4ff;
}
.ig-badge--round {
  background: rgba(123,79,255,0.12);
  border-color: rgba(123,79,255,0.3);
  color: #b49dff;
}
.ig-badge--pattern {
  background: rgba(255,171,0,0.1);
  border-color: rgba(255,171,0,0.25);
  color: #ffd060;
}
.ig-badge--cards {
  background: rgba(42,245,152,0.08);
  border-color: rgba(42,245,152,0.22);
  color: #7affba;
}

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
.ig-drawn-board {
  background: linear-gradient(135deg, rgba(13,22,51,0.94) 0%, rgba(8,15,38,0.94) 100%);
  border: 1px solid rgba(100,140,255,0.18);
  border-radius: 20px;
  padding: 16px 20px;
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 40px rgba(0,0,0,0.5);
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ig-drawn-board__top {
  display: flex;
  align-items: center;
  gap: 18px;
}

/* Last chip wrap */
.ig-last-chip-wrap {
  position: relative;
  flex-shrink: 0;
  width: 88px; height: 88px;
  display: flex; align-items: center; justify-content: center;
}
.ig-last-chip-ring {
  position: absolute; inset: -4px;
  border-radius: 50%;
  border: 2px solid transparent;
  background: linear-gradient(135deg, #7b4fff, #2af598) border-box;
  -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out;
  mask-composite: exclude;
  animation: ring-spin 3s linear infinite;
}
@keyframes ring-spin { to { transform: rotate(360deg) } }

/* ── CSS Bingo Chips ── */
.ig-chip {
  border-radius: 50%;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  line-height: 1;
  user-select: none;
  position: relative;
  flex-shrink: 0;
}
/* sizes */
.ig-chip--lg {
  width: 76px; height: 76px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.6), inset 0 2px 0 rgba(255,255,255,0.28);
  animation: chip-enter 0.45s cubic-bezier(0.34,1.56,0.64,1) both;
  position: relative; z-index: 1;
}
.ig-chip--md {
  width: 38px; height: 38px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.22);
  animation: chip-pop 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
  transition: transform 0.18s ease;
  cursor: default;
}
.ig-chip--md:hover { transform: scale(1.18); }
.ig-chip--sm {
  width: 30px; height: 30px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.2);
  opacity: 0.72; transition: opacity 0.18s;
}
.ig-chip--sm:hover { opacity: 1; }

/* letter colors */
.ig-chip--B { background: linear-gradient(145deg, #2060ff 0%, #0d35b0 100%); }
.ig-chip--I { background: linear-gradient(145deg, #ff3030 0%, #980000 100%); }
.ig-chip--N { background: linear-gradient(145deg, #d4ba00 0%, #8a7800 100%); }
.ig-chip--G { background: linear-gradient(145deg, #0cbb5a 0%, #067038 100%); }
.ig-chip--O { background: linear-gradient(145deg, #a830ff 0%, #6808c0 100%); }

/* latest highlight */
.ig-chip--latest {
  box-shadow: 0 2px 8px rgba(0,0,0,0.5), 0 0 14px rgba(255,255,255,0.35), inset 0 1px 0 rgba(255,255,255,0.22) !important;
}

/* chip text */
.ig-chip__letter {
  font-family: 'Anton', sans-serif;
  color: rgba(255,255,255,0.55);
  line-height: 1;
}
.ig-chip--lg .ig-chip__letter { font-size: 11px; margin-bottom: 1px; }
.ig-chip--md .ig-chip__letter { font-size: 8px; }
.ig-chip--sm .ig-chip__letter { font-size: 7px; }

.ig-chip__num {
  font-family: 'Anton', sans-serif;
  color: #fff;
  text-shadow: 0 1px 3px rgba(0,0,0,0.5);
  line-height: 1;
}
.ig-chip--lg .ig-chip__num { font-size: 26px; }
.ig-chip--md .ig-chip__num { font-size: 13px; }
.ig-chip--sm .ig-chip__num { font-size: 11px; }

@keyframes chip-enter {
  from { transform: scale(0) rotate(-20deg); opacity: 0; }
  to   { transform: scale(1) rotate(0deg);  opacity: 1; }
}
@keyframes chip-pop {
  from { transform: scale(0); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

/* Draw info */
.ig-drawn-board__info {
  flex: 1; display: flex; flex-direction: column; gap: 4px;
}
.ig-drawn-board__label {
  font-size: 10px; font-weight: 700; color: #5578bb;
  text-transform: uppercase; letter-spacing: 1px;
  display: flex; align-items: center; gap: 5px;
}
.ig-drawn-board__label i { color: #7b4fff }
.ig-drawn-board__num {
  font-family: 'Anton', sans-serif; font-size: 40px; line-height: 1;
  background: linear-gradient(135deg, #e0eaff, #7b4fff);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.ig-drawn-progress {
  height: 3px; background: rgba(100,140,255,0.12); border-radius: 3px; overflow: hidden;
}
.ig-drawn-progress__bar {
  height: 100%;
  background: linear-gradient(90deg, #7b4fff, #2af598);
  border-radius: 3px;
  transition: width 0.6s ease;
}
.ig-drawn-board__count {
  font-size: 12px; font-weight: 700; color: #7c9dff;
}
.ig-drawn-board__count span { font-weight: 400; color: #4a6aaa; }

/* Recent chips */
.ig-recent-chips {
  display: flex; flex-direction: column; gap: 6px; align-items: center;
}
.ig-recent-chips__label {
  font-size: 9px; font-weight: 700; color: #3d5280;
  text-transform: uppercase; letter-spacing: 0.8px;
}
.ig-recent-chips__row {
  display: flex; gap: 4px; align-items: center;
}
.ig-drawn-board__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
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
@media (max-width: 640px) {
  .ig-panel { gap: 10px }
  .ig-panel__divider { display: none }
  .ig-prize__num { font-size: 28px }
  .ig-cd-unit__num { font-size: 16px }
  .ig-cd-unit { min-width: 30px }
  .ig-chip--md { width: 30px; height: 30px }
  .ig-chip--lg { width: 56px; height: 56px }
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
</style>
