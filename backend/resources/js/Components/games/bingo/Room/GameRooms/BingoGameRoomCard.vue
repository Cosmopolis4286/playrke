<script setup lang="ts">
import { computed, ref, watch, onUnmounted } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import {
  formatCountdown,
  BINGO_ROOM_IMG,
  GAME_VARIANT_LABELS,
  isAccumulatedPrize,
} from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'
import { getBingoVariantDef } from '@js/modules/games/bingo/utils/bingoVariants'

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

// Urgencia: <60s restantes para el inicio del sorteo — el timer cambia de color y pulsa más rápido
const isUrgent = computed(() => {
  if (!hasTime.value || !props.countdown) return false
  const { days, hours, minutes } = props.countdown.time
  return days === 0 && hours === 0 && minutes === 0
})

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

const gameVariant     = computed(() => props.room.config?.game_variant ?? 'BINGO_75')
const variantLabel    = computed(() => GAME_VARIANT_LABELS[gameVariant.value] ?? gameVariant.value)
const variantBalls    = computed(() => getBingoVariantDef(gameVariant.value).totalChips)
const isAccumulated   = computed(() => isAccumulatedPrize(props.room.config?.prize_pool_type))
const cardsRemaining  = computed(() => Math.max(0, (minCardsToStart.value ?? 0) - totalCards.value))

// Costo total de entrada: precio del cartón × mínimo requerido para iniciar (si la sala lo define)
const minEntryCostFormatted = computed(() => {
  if (!minCardsToStart.value) return null
  const price = props.room.card_price ?? ((props.room.config?.card_price_usd ?? 0) * props.exchangeRate)
  return (price * minCardsToStart.value).toFixed(2)
})

// Prueba social: preview anónimo de jugadores (solo iniciales), máx. 4 + contador de restantes
const playersPreview = computed(() => props.room.players_preview ?? [])
const extraPlayersCount = computed(() =>
  Math.max(0, props.room.total_players - playersPreview.value.length)
)

// "Vendiendo rápido": detecta un salto de +2 o más cartones vendidos entre dos actualizaciones
// de la prop (polling/websocket externo) y muestra el badge unos segundos. Sin llamadas propias
// al backend — solo compara el valor entrante con el anterior.
const showSellingFast = ref(false)
let sellingFastTimeout: ReturnType<typeof setTimeout> | null = null

watch(
  () => props.room.total_cards,
  (next, prev) => {
    if (prev == null || next == null || next - prev < 2) return
    showSellingFast.value = true
    if (sellingFastTimeout) clearTimeout(sellingFastTimeout)
    sellingFastTimeout = setTimeout(() => { showSellingFast.value = false }, 8000)
  },
)

onUnmounted(() => {
  if (sellingFastTimeout) clearTimeout(sellingFastTimeout)
})

// BUG-02: reemplazado timerStr local por formatCountdown importado de gameRoomUtils
// BUG-04: reemplazado bingoRoomImg local por BINGO_ROOM_IMG importado de gameRoomUtils

/** Coordenadas del último ripple de click, relativas a la card (para posicionar el efecto) */
const ripple = ref<{ x: number; y: number; key: number } | null>(null)

function handleClick(event: MouseEvent) {
  const target = event.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  ripple.value = { x: event.clientX - rect.left, y: event.clientY - rect.top, key: Date.now() }
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

      <!-- ── Banner: cielo estrellado + imagen como acento sutil ── -->
      <div class="rc__thumb">
        <div class="rc__stars rc__stars--a" />
        <div class="rc__stars rc__stars--b" />
        <img :src="BINGO_ROOM_IMG" alt="" class="rc__img" />
        <div class="rc__img-veil" />
        <div class="rc__side-accent" />

        <div
          v-tooltip="isAvailable
            ? 'Sala abierta: puedes comprar cartones ahora mismo'
            : isInProgress
              ? 'El sorteo ya comenzó — no se pueden comprar más cartones'
              : 'Esta sala ya no admite compras'"
          class="rc__status-badge"
          :class="isAvailable ? 'rc__status-badge--ok' : isInProgress ? 'rc__status-badge--live' : 'rc__status-badge--off'"
        >
          <span class="rc__status-dot" />
          {{ isAvailable ? 'ACTIVA' : isInProgress ? 'EN JUEGO' : 'CERRADA' }}
        </div>

        <div
          v-tooltip="hasTime ? 'Tiempo restante para el inicio del sorteo' : 'El sorteo está en curso ahora mismo'"
          class="rc__timer-badge"
          :class="{ 'rc__timer-badge--live': !hasTime, 'rc__timer-badge--urgent': isUrgent }"
        >
          <template v-if="hasTime">
            <i class="far fa-clock" /><b>{{ formatCountdown(props.countdown) }}</b>
          </template>
          <template v-else>
            <span class="rc__blink" /><b>LIVE</b>
          </template>
        </div>

        <div class="rc__orbit-dot" />
      </div>

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- Subtítulo: variante · rondas, discreto, una sola línea de metadata -->
        <div class="rc__meta">
          <span v-tooltip="variantLabel" class="rc__meta-item">{{ variantBalls }}B</span>
          <span class="rc__meta-dot" />
          <span
            v-tooltip="roundsCount > 1
              ? `Esta sala juega ${roundsCount} rondas seguidas con los mismos cartones`
              : 'Sala de ronda única'"
            class="rc__meta-item"
          >
            <i class="fas fa-rotate" />{{ roundsCount }} Ronda{{ roundsCount !== 1 ? 's' : '' }}
          </span>
        </div>

        <!-- Premio — bloque hero, centrado y protagonista -->
        <div
          v-tooltip="isAccumulated
            ? 'Bote acumulado: crece con cada cartón vendido en la sala'
            : 'Premio fijo para esta sala'"
          class="rc__prize"
        >
          <div class="rc__prize-label">
            <i class="fas fa-trophy" />
            {{ isAccumulated ? 'Bote acumulado' : 'Premio' }}
            <i v-if="isAccumulated" class="fas fa-arrow-trend-up rc__prize-trend" />
          </div>
          <div class="rc__prize-amount">
            <span class="rc__prize-num">{{ prizeFormatted }}</span>
            <span class="rc__prize-sym">{{ sym }}</span>
          </div>
        </div>

        <!-- Barra de progreso cartones -->
        <div
          v-if="showCardsProgress"
          v-tooltip="cardsProgressReady
            ? 'Ya se alcanzó el mínimo de cartones — el sorteo puede iniciar'
            : `Faltan ${cardsRemaining} cartón${cardsRemaining !== 1 ? 'es' : ''} vendido${cardsRemaining !== 1 ? 's' : ''} para que la sala pueda iniciar`"
          class="rc__progress"
          :class="{ 'rc__progress--ready': cardsProgressReady }"
        >
          <div class="rc__progress__header">
            <span
              class="rc__progress__label"
              :class="{ 'rc__progress__label--hot': showSellingFast && !cardsProgressReady }"
            >
              <i :class="showSellingFast && !cardsProgressReady ? 'fas fa-fire' : 'fas fa-layer-group'" />
              {{
                cardsProgressReady
                  ? '¡Listo!'
                  : (showSellingFast ? 'Vendiendo rápido' : 'Cartones para iniciar')
              }}
            </span>
            <span class="rc__progress__count">{{ totalCards }} / {{ minCardsToStart }}</span>
          </div>
          <div class="rc__progress__track">
            <div class="rc__progress__fill" :style="{ width: cardsProgressPct + '%' }" />
          </div>
        </div>

        <!-- Footer: precio del cartón (+ costo mínimo de entrada) | jugadores -->
        <div class="rc__footer">
          <div class="rc__price-block">
            <span
              v-tooltip="`${variantLabel} · ${variantBalls} bolas en total`"
              class="rc__price"
            >
              <i class="fas fa-ticket-alt" />{{ cardPriceFormatted }} {{ sym }}
            </span>
            <span
              v-if="minEntryCostFormatted"
              v-tooltip="`Costo estimado para alcanzar el mínimo de ${minCardsToStart} cartones`"
              class="rc__price-min"
            >
              desde {{ minEntryCostFormatted }} {{ sym }}
            </span>
          </div>

          <div class="rc__stats">
            <!-- Prueba social: iniciales anónimas de jugadores ya en la sala -->
            <div
              v-if="playersPreview.length > 0"
              v-tooltip="`${room.total_players} jugador${room.total_players !== 1 ? 'es' : ''} en esta sala`"
              class="rc__avatars"
            >
              <span
                v-for="(p, i) in playersPreview"
                :key="i"
                class="rc__avatar"
                :style="{ zIndex: playersPreview.length - i }"
              >{{ p.initial }}</span>
              <span v-if="extraPlayersCount > 0" class="rc__avatar rc__avatar--more">+{{ extraPlayersCount }}</span>
            </div>
            <span
              v-else
              v-tooltip="`${room.total_players} jugador${room.total_players !== 1 ? 'es' : ''} en esta sala`"
              class="rc__stat"
              :class="{ 'rc__stat--mine': playerCtns > 0 }"
            >
              <i class="fas fa-users" />
              <span>{{ room.total_players }}</span>
            </span>
            <span
              v-if="playerCtns > 0"
              v-tooltip="`Ya compraste ${playerCtns} cartón${playerCtns !== 1 ? 'es' : ''} en esta sala`"
              class="rc__stat rc__stat--ctns"
            >
              <i class="fas fa-check-circle" />
              <span>{{ playerCtns }}</span>
            </span>
          </div>
        </div>

      </div>
    </div>

    <!-- Ripple de feedback al hacer click -->
    <span
      v-if="ripple"
      :key="ripple.key"
      class="rc__ripple"
      :style="{ left: ripple.x + 'px', top: ripple.y + 'px' }"
      @animationend="ripple = null"
    />
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
  max-width: 208px;
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(165deg, #0c1230 0%, #060a18 65%);
  border: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 4px 22px rgba(0,0,0,.5), 0 1px 0 rgba(255,255,255,.05) inset;
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
  transform: translateY(-4px) scale(1.006);
  border-color: rgba(42,245,152,.2);
  box-shadow: 0 14px 40px rgba(0,0,0,.6),
              0 0 0 1px rgba(42,245,152,.16),
              0 0 22px rgba(42,245,152,.08);
}
.rc--live:hover {
  transform: translateY(-3px) scale(1.004);
  border-color: rgba(255,77,109,.2);
  box-shadow: 0 12px 36px rgba(0,0,0,.6), 0 0 0 1px rgba(255,77,109,.16);
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

/* glow de fondo — respiración sutil, refuerza la sensación de "vivo" */
.rc__glow {
  position: absolute; inset: 0; z-index: 0; pointer-events: none;
  background: radial-gradient(ellipse 90% 70% at 5% 115%, var(--glow-clr), transparent 60%);
  animation: glowBreathe 5s ease-in-out infinite;
}
@keyframes glowBreathe {
  0%, 100% { opacity: .7; }
  50%      { opacity: 1; }
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
  display: flex; flex-direction: column; align-items: stretch;
}

/* ══════════════════════════════════
   THUMBNAIL — banner cósmico: nebulosa + estrellas + imagen sutil
══════════════════════════════════ */
.rc__thumb {
  position: relative;
  width: 100%;
  height: 84px;
  flex: 0 0 auto;
  overflow: hidden;
  background:
    radial-gradient(ellipse at 20% 15%, rgba(123,79,255,.24) 0%, transparent 55%),
    radial-gradient(ellipse at 82% 75%, rgba(0,158,253,.18) 0%, transparent 50%),
    linear-gradient(160deg, #10163a 0%, #0a0f24 60%, #060a18 100%);
}

/* campo de estrellas — dos capas, mismo lenguaje visual que GameRooms/Game Index */
.rc__stars {
  position: absolute; inset: 0; pointer-events: none;
}
.rc__stars--a {
  background-image:
    radial-gradient(1px 1px at 12% 30%, rgba(255,255,255,.9), transparent 100%),
    radial-gradient(1px 1px at 32% 68%, rgba(190,210,255,.8), transparent 100%),
    radial-gradient(1px 1px at 58% 22%, rgba(210,185,255,.85), transparent 100%),
    radial-gradient(1px 1px at 75% 55%, rgba(255,255,255,.7), transparent 100%),
    radial-gradient(1px 1px at 91% 35%, rgba(200,255,220,.75), transparent 100%),
    radial-gradient(1px 1px at 20% 82%, rgba(255,240,180,.7), transparent 100%);
  animation: twinkle-a 4.2s ease-in-out infinite;
}
.rc__stars--b {
  background-image:
    radial-gradient(1.5px 1.5px at 45% 40%, rgba(255,255,255,.95), transparent 100%),
    radial-gradient(1.5px 1.5px at 68% 78%, rgba(190,210,255,.9), transparent 100%),
    radial-gradient(1.5px 1.5px at 85% 15%, rgba(255,255,255,.85), transparent 100%),
    radial-gradient(1.5px 1.5px at 8% 55%, rgba(210,185,255,.8), transparent 100%);
  animation: twinkle-b 3.4s ease-in-out infinite;
}
@keyframes twinkle-a { 0%{opacity:.55} 40%{opacity:.95} 70%{opacity:.7} 100%{opacity:.85} }
@keyframes twinkle-b { 0%{opacity:.7} 35%{opacity:.5} 65%{opacity:1} 100%{opacity:.65} }

.rc__img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover; object-position: center 22%; display: block;
  transform: scale(1.5);
  transition: transform .5s ease;
  opacity: .5;
  mix-blend-mode: screen;
}
.rc--active:hover .rc__img,
.rc--live:hover   .rc__img { transform: scale(1.56); }

.rc__img-veil {
  position: absolute; inset: 0;
  background:
    linear-gradient(180deg,
      rgba(7,12,26,.25) 0%,
      rgba(7,12,26,.4) 55%,
      rgba(7,12,26,.94) 100%);
}

/* acento inferior — línea cósmica, animada tipo "escaneo" */
.rc__side-accent {
  position: absolute; left: 0; right: 0; bottom: 0; height: 2px;
  background: linear-gradient(90deg, transparent 0%, #7b4fff 15%, var(--accent) 50%, #009efd 85%, transparent 100%);
  background-size: 220% 100%;
  opacity: .85;
  animation: scanLine 3.6s linear infinite;
}
@keyframes scanLine {
  0%   { background-position: 0% 0; }
  100% { background-position: -220% 0; }
}

/* punto orbital decorativo — pequeño satélite que recorre el borde del banner */
.rc__orbit-dot {
  position: absolute; z-index: 2; pointer-events: none;
  width: 3px; height: 3px; border-radius: 50%;
  background: var(--accent);
  box-shadow: 0 0 5px 1px var(--accent);
  top: 10%; left: 4%;
  animation: orbitDrift 7s ease-in-out infinite;
  opacity: .8;
}
@keyframes orbitDrift {
  0%, 100% { transform: translate(0, 0); opacity: .3; }
  50%      { transform: translate(148px, 46px); opacity: .85; }
}

/* badge estado — top centrado */
.rc__status-badge {
  position: absolute; top: 8px; left: 50%; transform: translateX(-50%);
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px; border-radius: 20px;
  font-size: 9.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase;
  white-space: nowrap; backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0,0,0,.45); z-index: 3;
}
.rc__status-dot {
  width: 5px; height: 5px; border-radius: 50%;
  background: currentColor; flex-shrink: 0;
  box-shadow: 0 0 6px currentColor;
}
.rc__status-badge--ok   { background: rgba(8,28,18,.85); border: 1px solid rgba(42,245,152,.4); color: #2af598; }
.rc__status-badge--live { background: rgba(28,8,16,.85); border: 1px solid rgba(255,77,109,.4); color: #ff6e8a; }
.rc__status-badge--off  { background: rgba(8,12,24,.85); border: 1px solid rgba(255,255,255,.12); color: #3a5068; }
.rc__status-badge--ok   .rc__status-dot { animation: dp 2s infinite; }
.rc__status-badge--live .rc__status-dot { animation: dp 1s infinite; }
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.08} }

/* timer — bottom centrado, su propia fila (sin compartir con el estado) */
.rc__timer-badge {
  position: absolute; bottom: 7px; left: 50%; transform: translateX(-50%);
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 20px;
  white-space: nowrap; backdrop-filter: blur(8px);
  box-shadow: 0 2px 8px rgba(0,0,0,.4);
  color: #9ecaf0; background: rgba(10,22,48,.7);
  border: 1px solid rgba(100,160,230,.3); z-index: 3;
}
.rc__timer-badge i { font-size: 8.5px; opacity: .65; }
.rc__timer-badge b {
  font-family: 'Orbitron', 'Anton', monospace;
  font-size: 9.5px; font-weight: 800; letter-spacing: .3px; color: #9ecaf0;
}
.rc__timer-badge--live { color: #ff8aaa; background: rgba(36,10,20,.7); border-color: rgba(255,77,109,.32); }
.rc__timer-badge--live b { color: #ff8aaa; text-shadow: 0 0 8px rgba(255,77,109,.4); }

/* timer urgente: <1 minuto para el sorteo — color de alerta + pulso rápido */
.rc__timer-badge--urgent {
  color: #ffb020;
  background: rgba(42,24,6,.78);
  border-color: rgba(255,176,32,.45);
  animation: urgentPulse 0.9s ease-in-out infinite;
}
.rc__timer-badge--urgent b { color: #ffb020; text-shadow: 0 0 8px rgba(255,176,32,.5); }
.rc__timer-badge--urgent i { opacity: 1; }
@keyframes urgentPulse {
  0%, 100% { box-shadow: 0 2px 8px rgba(0,0,0,.4); }
  50%      { box-shadow: 0 2px 14px rgba(255,176,32,.55); }
}

.rc__blink {
  width: 5px; height: 5px; border-radius: 50%;
  background: currentColor; display: inline-block;
  box-shadow: 0 0 6px currentColor;
  animation: bk 1s infinite;
}
@keyframes bk { 0%,100%{opacity:1} 50%{opacity:.06} }

/* ══════════════════════════════════
   BODY
══════════════════════════════════ */
.rc__body {
  flex: 1; min-width: 0;
  padding: 12px 14px 13px;
  display: flex; flex-direction: column;
  gap: 10px;
}

/* ── Metadata: subtítulo discreto ── */
.rc__meta {
  display: flex; align-items: center; justify-content: center; gap: 7px;
  flex-shrink: 0;
}
.rc__meta-item {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; letter-spacing: .3px;
  color: #6a86ac;
  white-space: nowrap;
}
.rc__meta-item i { font-size: 8.5px; color: #4a6488; }
.rc__meta-dot {
  width: 3px; height: 3px; border-radius: 50%;
  background: #34486a; flex-shrink: 0;
}

/* ── Premio — bloque hero, centrado ── */
.rc__prize {
  display: flex; flex-direction: column; align-items: center; gap: 2px;
  padding: 10px 8px 11px;
  border-radius: 10px;
  background: radial-gradient(ellipse 90% 100% at 50% 0%, rgba(255,180,40,.09), transparent 70%);
  flex-shrink: 0;
}
.rc__prize-label {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  color: #c8a028; opacity: .85;
}
.rc__prize-label i { font-size: 9px; filter: drop-shadow(0 0 3px rgba(200,160,40,.4)); }
.rc__prize-trend {
  color: #2af598; font-size: 9px;
  animation: prizeTrendPulse 1.8s ease-in-out infinite;
}
@keyframes prizeTrendPulse {
  0%,100% { opacity: .55; transform: translateY(0); }
  50%     { opacity: 1;   transform: translateY(-1px); }
}
.rc__prize-amount {
  display: flex; align-items: baseline; gap: 5px;
  max-width: 100%;
}
.rc__prize-num {
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 27px; font-weight: 900; line-height: 1.1; letter-spacing: -.4px;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 8px rgba(255,150,0,.32));
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__prize-sym {
  font-size: 13px; font-weight: 700;
  color: #e0a020; -webkit-text-fill-color: #e0a020;
  flex-shrink: 0;
}

/* ── Progreso cartones ── */
.rc__progress { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; }
.rc__progress__header {
  display: flex; align-items: center; justify-content: space-between; gap: 6px;
}
.rc__progress__label {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 9.5px; font-weight: 700; letter-spacing: .2px;
  color: #5a7ca0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__progress__label i { font-size: 8px; }

/* "Vendiendo rápido" — pico de ventas detectado entre actualizaciones */
.rc__progress__label--hot {
  color: #ff9430;
  animation: hotPulse 1s ease-in-out infinite;
}
.rc__progress__label--hot i { color: #ff7a1a; }
@keyframes hotPulse {
  0%, 100% { opacity: .8; }
  50%      { opacity: 1; }
}
.rc__progress__count {
  font-size: 10px; font-weight: 800; color: #7a9ac0;
  flex-shrink: 0; white-space: nowrap;
}
.rc__progress__track {
  height: 5px; border-radius: 4px; overflow: hidden;
  background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.06);
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

/* ── Footer: precio + stats ── */
.rc__footer {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 6px;
  padding-top: 9px;
  border-top: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}

.rc__price-block {
  display: flex; flex-direction: column; gap: 1px;
  min-width: 0;
}

.rc__price {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10.5px; font-weight: 700; letter-spacing: .2px;
  color: #7ec0ec;
  white-space: nowrap;
}
.rc__price i { font-size: 9px; color: #4a94cc; }

/* Costo total estimado para alcanzar el mínimo de cartones — ayuda a decidir sin abrir la sala */
.rc__price-min {
  font-size: 8.5px; font-weight: 600; letter-spacing: .1px;
  color: #4a6488;
  white-space: nowrap;
}

/* Avatares apilados — prueba social con iniciales anónimas */
.rc__avatars {
  display: flex; align-items: center;
}
.rc__avatar {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%;
  margin-left: -6px;
  font-size: 8px; font-weight: 800; color: #cfe0f5;
  background: linear-gradient(145deg, #2a4068 0%, #16233f 100%);
  border: 1.5px solid #0c1230;
  box-shadow: 0 1px 3px rgba(0,0,0,.4);
}
.rc__avatar:first-child { margin-left: 0; }
.rc__avatar--more {
  color: #8aa0c0;
  background: rgba(255,255,255,.06);
  font-size: 7px;
}

/* Stats: jugadores + cartones míos */
.rc__stats {
  display: flex; align-items: center; gap: 4px; flex-shrink: 0;
}
.rc__stat {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 3px 7px; border-radius: 5px;
  font-size: 10px; font-weight: 700;
  color: #5a7ca0;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.09);
  white-space: nowrap;
  transition: border-color .2s, background .2s;
}
.rc__stat i { font-size: 8.5px; color: #3a5878; }

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

/* Ripple de feedback al hacer click — confirma la interacción antes de navegar */
.rc__ripple {
  position: absolute; z-index: 10; pointer-events: none;
  width: 10px; height: 10px; margin: -5px 0 0 -5px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,255,255,.5) 0%, transparent 70%);
  animation: rippleExpand .5s ease-out forwards;
}
@keyframes rippleExpand {
  from { transform: scale(1);  opacity: .8; }
  to   { transform: scale(22); opacity: 0; }
}
</style>
