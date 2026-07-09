<script setup lang="ts">
import { computed } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { formatCountdown, BINGO_ROOM_IMG } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'

// DEAD-02: eliminadas props muertas (isPurchasing, roomDetail, numCtns, totalAmount, prizePoolLabel, loading)
const props = defineProps<{
  room: BingoGameRoom
  exchangeRate: number
  currencySym: string
  countdown: RoomCountdown | undefined
  index?: number
}>()

const emit = defineEmits<{ (e: 'select', roomId: string): void }>()

const isAvailable  = computed(() => props.room.status_code === 'ACTIVE')
const isInProgress = computed(() => props.room.status_code === 'IN_PROGRESS')
const playerCtns   = computed(() => props.room.players_room?.[0]?.ctns ?? 0)
const hasTime      = computed(() => props.countdown && !props.countdown.expired)
const animDelay    = computed(() => `${(props.index ?? 0) * 70}ms`)

const prizeFormatted = computed(() =>
  (props.room.prize_pool * props.exchangeRate).toFixed(2)
)
const cardPriceFormatted = computed(() => {
  if (props.room.card_price != null) return props.room.card_price.toFixed(2)
  return ((props.room.config?.card_price_usd ?? 0) * props.exchangeRate).toFixed(2)
})
const sym = computed(() => props.room.currency_symbol ?? props.currencySym)

const roundsCount = computed(() => props.room.rounds_count ?? 1)

const minCardsToStart   = computed(() => props.room.config?.min_cards_to_start ?? null)
const totalCards        = computed(() => props.room.total_cards ?? 0)
const showCardsProgress = computed(() => minCardsToStart.value != null && minCardsToStart.value > 0)
const cardsProgressPct  = computed(() => {
  if (!minCardsToStart.value) return 0
  return Math.min(100, Math.round((totalCards.value / minCardsToStart.value) * 100))
})
const cardsProgressReady = computed(() =>
  minCardsToStart.value != null && totalCards.value >= minCardsToStart.value
)

// BUG-02: reemplazado timerStr local por formatCountdown importado de gameRoomUtils
// BUG-04: reemplazado bingoRoomImg local por BINGO_ROOM_IMG importado de gameRoomUtils

function handleClick() {
  emit('select', props.room.id)
}
</script>

<template>
  <div
    class="rc"
    :class="{
      'rc--active': isAvailable,
      'rc--live':   isInProgress,
      'rc--off':    !isAvailable && !isInProgress,
    }"
    :style="{ '--d': animDelay }"
    :tabindex="!isAvailable && !isInProgress ? -1 : undefined"
    @click="handleClick"
  >
    <div class="rc__glow" />
    <div class="rc__shimmer" />

    <div class="rc__inner">

      <!-- UX-02: etiqueta "Cerrada" cuando la sala no está activa ni en progreso -->
      <div v-if="!isAvailable && !isInProgress" class="rc__closed-label">
        <i class="fas fa-lock" /> Cerrada
      </div>

      <!-- ── Thumbnail ── -->
      <div class="rc__thumb">
        <img :src="BINGO_ROOM_IMG" alt="" class="rc__img" />
        <div class="rc__img-veil" />
        <div class="rc__side-accent" />

        <!-- Estado: top centrado -->
        <div
          class="rc__status-badge"
          :class="isAvailable ? 'rc__status-badge--ok' : isInProgress ? 'rc__status-badge--live' : 'rc__status-badge--off'"
        >
          <span class="rc__status-dot" />
          {{ isAvailable ? 'ACTIVA' : isInProgress ? 'EN JUEGO' : 'CERRADA' }}
        </div>

        <!-- Timer: bottom centrado -->
        <div class="rc__timer-badge" :class="{ 'rc__timer-badge--live': !hasTime }">
          <template v-if="hasTime">
            <i class="far fa-clock" /><b>{{ formatCountdown(props.countdown) }}</b>
          </template>
          <template v-else>
            <span class="rc__blink" /><b>LIVE</b>
          </template>
        </div>
      </div>

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- Fila 1: rondas | precio -->
        <div class="rc__row rc__row--top">
          <span class="rc__tag rc__tag--rounds">
            <i class="fas fa-rotate" />{{ roundsCount }} Ronda{{ roundsCount !== 1 ? 's' : '' }}
          </span>
          <span class="rc__tag rc__tag--price">
            <i class="fas fa-ticket-alt" />{{ cardPriceFormatted }} {{ sym }}
          </span>
        </div>

        <!-- Barra de progreso cartones -->
        <div
          v-if="showCardsProgress"
          class="rc__progress"
          :class="{ 'rc__progress--ready': cardsProgressReady }"
        >
          <div class="rc__progress__header">
            <span class="rc__progress__label">
              <i class="fas fa-layer-group" />
              {{ cardsProgressReady ? '¡Listo!' : 'Cartones para iniciar' }}
            </span>
            <span class="rc__progress__count">{{ totalCards }} / {{ minCardsToStart }}</span>
          </div>
          <div class="rc__progress__track">
            <div class="rc__progress__fill" :style="{ width: cardsProgressPct + '%' }" />
          </div>
        </div>

        <!-- Fila 2: premio | stats -->
        <div class="rc__row rc__row--bottom">
          <!-- Premio -->
          <div class="rc__prize">
            <i class="fas fa-trophy rc__trophy" />
            <span class="rc__prize-num">{{ prizeFormatted }}</span>
            <span class="rc__prize-sym">{{ sym }}</span>
          </div>

          <!-- Stats: jugadores + mis cartones -->
          <div class="rc__stats">
            <span class="rc__stat" :class="{ 'rc__stat--mine': playerCtns > 0 }">
              <i class="fas fa-users" />
              <span>{{ room.total_players }}</span>
            </span>
            <span v-if="playerCtns > 0" class="rc__stat rc__stat--ctns">
              <i class="fas fa-check-circle" />
              <span>{{ playerCtns }}</span>
            </span>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.rc,
.rc *,
.rc *::before,
.rc *::after {
  box-sizing: border-box;
}

/* ══════════════════════════════════
   BASE
══════════════════════════════════ */
.rc {
  --accent:   #2af598;
  --glow-clr: rgba(42,245,152,.1);
  --d: 0ms;

  position: relative;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(145deg, #0d1b36 0%, #070c1a 100%);
  border: 1px solid rgba(255,255,255,.07);
  box-shadow: 0 4px 22px rgba(0,0,0,.5), 0 1px 0 rgba(255,255,255,.04) inset;
  transition: transform .22s cubic-bezier(.22,.68,0,1.2),
              box-shadow .22s ease, border-color .22s ease;
  animation: rcIn .36s ease both;
  animation-delay: var(--d);
}
@keyframes rcIn {
  from { opacity: 0; transform: translateY(6px) scale(.99); }
  to   { opacity: 1; transform: none; }
}

.rc--live { --accent: #ff4d6d; --glow-clr: rgba(255,77,109,.1); }

.rc--active:hover {
  transform: translateY(-3px) scale(1.002);
  border-color: rgba(42,245,152,.18);
  box-shadow: 0 12px 38px rgba(0,0,0,.6),
              0 0 0 1px rgba(42,245,152,.14),
              0 0 20px rgba(42,245,152,.07);
}
.rc--live:hover {
  transform: translateY(-2px);
  border-color: rgba(255,77,109,.18);
  box-shadow: 0 10px 34px rgba(0,0,0,.6), 0 0 0 1px rgba(255,77,109,.14);
}
.rc--off {
  opacity: .2; cursor: default;
  filter: saturate(.08) brightness(.6);
  pointer-events: none;
}

.rc:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

/* glow de fondo */
.rc__glow {
  position: absolute; inset: 0; z-index: 0; pointer-events: none;
  background: radial-gradient(ellipse 90% 70% at 5% 115%, var(--glow-clr), transparent 60%);
}

/* shimmer en hover */
.rc__shimmer {
  position: absolute; inset: 0; z-index: 1; pointer-events: none;
  background: linear-gradient(110deg, transparent 30%, rgba(255,255,255,.025) 50%, transparent 70%);
  transform: translateX(-110%);
}
.rc--active:hover .rc__shimmer,
.rc--live:hover   .rc__shimmer { animation: shimmer .48s ease forwards; }
@keyframes shimmer { to { transform: translateX(110%); } }

/* inner */
.rc__inner {
  position: relative; z-index: 2;
  display: flex; align-items: stretch;
  min-height: 92px;
}

@media (max-width: 380px) {
  .rc__inner { min-height: 96px; }
}

/* ══════════════════════════════════
   THUMBNAIL
══════════════════════════════════ */
.rc__thumb {
  position: relative;
  flex: 0 0 82px;
  overflow: hidden;
}

@media (max-width: 380px) {
  .rc__thumb { flex-basis: 92px; }
}
.rc__img {
  width: 100%; height: 100%;
  object-fit: cover; display: block;
  transition: transform .5s ease;
}
.rc--active:hover .rc__img,
.rc--live:hover   .rc__img { transform: scale(1.07); }

.rc__img-veil {
  position: absolute; inset: 0;
  background: linear-gradient(90deg,
    rgba(7,12,26,.05) 0%,
    rgba(7,12,26,.45) 55%,
    rgba(7,12,26,.96) 100%);
}

/* acento lateral */
.rc__side-accent {
  position: absolute; left: 0; top: 0; bottom: 0; width: 2.5px;
  background: linear-gradient(180deg, var(--accent) 0%, transparent 90%);
  opacity: .75;
}

/* badge estado — top centrado */
.rc__status-badge {
  position: absolute; top: 5px; left: 50%; transform: translateX(-50%);
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 6px; border-radius: 20px;
  font-size: 6.5px; font-weight: 800; letter-spacing: .7px; text-transform: uppercase;
  white-space: nowrap; backdrop-filter: blur(10px);
  box-shadow: 0 2px 8px rgba(0,0,0,.4); z-index: 3;
}
.rc__status-dot {
  width: 3.5px; height: 3.5px; border-radius: 50%;
  background: currentColor; flex-shrink: 0;
}
.rc__status-badge--ok   { background: rgba(8,28,18,.8); border: 1px solid rgba(42,245,152,.35); color: #2af598; }
.rc__status-badge--live { background: rgba(28,8,16,.8); border: 1px solid rgba(255,77,109,.35); color: #ff6e8a; }
.rc__status-badge--off  { background: rgba(8,12,24,.8); border: 1px solid rgba(255,255,255,.1);  color: #3a5068; }
.rc__status-badge--ok   .rc__status-dot { animation: dp 2s infinite; }
.rc__status-badge--live .rc__status-dot { animation: dp 1s infinite; }
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.08} }

/* timer — bottom centrado */
.rc__timer-badge {
  position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%);
  display: inline-flex; align-items: center; gap: 4px;
  padding: 2px 8px; border-radius: 20px;
  white-space: nowrap; backdrop-filter: blur(8px);
  box-shadow: 0 2px 8px rgba(0,0,0,.4);
  color: #9ecaf0; background: rgba(10,22,48,.65);
  border: 1px solid rgba(100,160,230,.25); z-index: 3;
}
.rc__timer-badge i { font-size: 7.5px; opacity: .55; }
.rc__timer-badge b {
  font-family: 'Orbitron', 'Anton', monospace;
  font-size: 8.5px; font-weight: 800; letter-spacing: .5px; color: #9ecaf0;
}
.rc__timer-badge--live { color: #ff8aaa; background: rgba(36,10,20,.65); border-color: rgba(255,77,109,.3); }
.rc__timer-badge--live b { color: #ff8aaa; text-shadow: 0 0 8px rgba(255,77,109,.4); }

.rc__blink {
  width: 4.5px; height: 4.5px; border-radius: 50%;
  background: currentColor; display: inline-block;
  animation: bk 1s infinite;
}
@keyframes bk { 0%,100%{opacity:1} 50%{opacity:.06} }

/* ══════════════════════════════════
   BODY
══════════════════════════════════ */
.rc__body {
  flex: 1; min-width: 0;
  padding: 8px 11px;
  display: flex; flex-direction: column; justify-content: space-between;
  gap: 5px;
}

@media (max-width: 380px) {
  .rc__body { padding: 9px 12px; gap: 6px; }
}

/* ── Fila genérica ── */
.rc__row {
  display: flex; align-items: center;
  justify-content: space-between; gap: 6px;
}

/* Fila 1: rondas | precio */
.rc__row--top { flex-shrink: 0; }

.rc__tag {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 7px; border-radius: 5px;
  font-size: 9px; font-weight: 700; letter-spacing: .25px;
  white-space: nowrap; flex-shrink: 0;
}
.rc__tag i { font-size: 7.5px; }
.rc__tag--rounds {
  color: #a878e8;
  background: rgba(130,60,220,.1); border: 1px solid rgba(130,60,220,.22);
}
.rc__tag--rounds i { color: #7a50c0; }
.rc__tag--price {
  color: #6aaad8;
  background: rgba(40,110,190,.1); border: 1px solid rgba(40,110,190,.2);
}
.rc__tag--price i { color: #3a80b8; }

/* ── Progreso cartones ── */
.rc__progress { display: flex; flex-direction: column; gap: 3px; flex-shrink: 0; }
.rc__progress__header {
  display: flex; align-items: center; justify-content: space-between; gap: 6px;
}
.rc__progress__label {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 8px; font-weight: 700; letter-spacing: .25px;
  color: #4a6080; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__progress__label i { font-size: 7px; }
.rc__progress__count {
  font-size: 8.5px; font-weight: 800; color: #6a8aac;
  flex-shrink: 0; white-space: nowrap;
}
.rc__progress__track {
  height: 4px; border-radius: 4px; overflow: hidden;
  background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.05);
}
.rc__progress__fill {
  height: 100%; border-radius: 4px;
  background: linear-gradient(90deg, #2260c0 0%, #2af598 100%);
  box-shadow: 0 0 7px rgba(42,245,152,.3);
  transition: width .5s cubic-bezier(.22,.68,0,1.1);
}
.rc__progress--ready .rc__progress__label { color: #2af598; }
.rc__progress--ready .rc__progress__count { color: #2af598; }
.rc__progress--ready .rc__progress__fill {
  background: linear-gradient(90deg, #2af598 0%, #7bffcc 100%);
  animation: cpPulse 1.5s ease-in-out infinite;
}
@keyframes cpPulse {
  0%,100% { box-shadow: 0 0 6px rgba(42,245,152,.35); }
  50%     { box-shadow: 0 0 12px rgba(42,245,152,.7); }
}

/* ── Fila 2: premio + stats ── */
.rc__row--bottom {
  padding-top: 5px;
  border-top: 1px solid rgba(255,255,255,.055);
  flex-shrink: 0;
}

/* Premio */
.rc__prize {
  display: flex; align-items: baseline; gap: 4px;
  flex-shrink: 1; min-width: 0; overflow: hidden;
}
.rc__trophy {
  font-size: 10px; color: #c8a028; align-self: center; flex-shrink: 0;
  filter: drop-shadow(0 0 4px rgba(200,160,40,.4));
}
.rc__prize-num {
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 19px; font-weight: 900; line-height: 1; letter-spacing: -.3px;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 6px rgba(255,150,0,.28));
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__prize-sym {
  font-size: 11px; font-weight: 700;
  color: #e0a020; -webkit-text-fill-color: #e0a020;
  flex-shrink: 0;
}

/* Stats: jugadores + cartones míos */
.rc__stats {
  display: flex; align-items: center; gap: 4px; flex-shrink: 0;
}
.rc__stat {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2.5px 7px; border-radius: 5px;
  font-size: 9px; font-weight: 700;
  color: #4a6080;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.07);
  white-space: nowrap;
  transition: border-color .2s, background .2s;
}
.rc__stat i { font-size: 8px; color: #2a4058; }

.rc--active:hover .rc__stat,
.rc--live:hover   .rc__stat {
  background: rgba(255,255,255,.06);
  border-color: rgba(255,255,255,.11);
}

/* jugadores cuando el jugador participa en la sala */
.rc__stat--mine {
  color: #6a8aac;
}

/* mis cartones */
.rc__stat--ctns {
  color: #a070e0;
  background: rgba(110,55,210,.1);
  border-color: rgba(110,55,210,.22);
}
.rc__stat--ctns i { color: #7840c0; }

/* UX-02: etiqueta "Cerrada" para salas rc--off */
.rc__closed-label {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  z-index: 5; pointer-events: none;
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 12px; border-radius: 20px;
  font-size: 10px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase;
  background: rgba(8,12,28,.8); border: 1px solid rgba(255,255,255,.12);
  color: rgba(255,255,255,.35); white-space: nowrap;
  backdrop-filter: blur(4px);
}
.rc__closed-label i { font-size: 9px; opacity: .7; }
</style>
