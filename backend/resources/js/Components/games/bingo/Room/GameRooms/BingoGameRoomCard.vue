<script setup lang="ts">
import { computed, ref } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import {
  GAME_VARIANT_LABELS,
  isAccumulatedPrize,
  getTimerLevel,
} from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'
import { getBingoVariantDef } from '@js/modules/games/bingo/utils/bingoVariants'
import { useSellingFastIndicator } from '@js/composables/games/bingo/room/game-rooms/useSellingFastIndicator'
import RoomCardThumbnail from './RoomCard/RoomCardThumbnail.vue'
import RoomCardPrizeProgress from './RoomCard/RoomCardPrizeProgress.vue'
import RoomCardFooter from './RoomCard/RoomCardFooter.vue'

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

// Semáforo del timer: verde (>10min) → amarillo (2-10min) → rojo (<2min)
const timerLevel = computed(() => getTimerLevel(props.countdown))

const prizeFormatted = computed(() =>
  (props.room.prize_pool * props.exchangeRate).toFixed(2)
)
const cardPriceFormatted = computed(() => {
  if (props.room.card_price != null) return props.room.card_price.toFixed(2)
  return ((props.room.config?.card_price_usd ?? 0) * props.exchangeRate).toFixed(2)
})
const sym = computed(() => props.room.currency_symbol ?? props.currencySym)

const roundsCount = computed(() => props.room.rounds_count ?? 1)

// Fecha y hora de inicio — derivado de unix_date (timestamp Unix en segundos)
// para evitar ambigüedades de parseo de strings de fecha (Invalid Date en Safari/Firefox).
const startLabel = computed(() => {
  const unix = props.room.config?.unix_date
  if (!unix) return null
  const d = new Date(unix * 1000)
  const fecha = d.toLocaleDateString('es', { day: 'numeric', month: 'short' })
  const hora  = d.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit', hour12: false })
  return `${fecha} · ${hora}`
})

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

// Cupo de la sala: "X/X" (actual/máximo) cuando la sala define un límite de jugadores
const maxPlayers = computed(() => props.room.config?.max_players ?? null)

const { showSellingFast } = useSellingFastIndicator(computed(() => props.room.total_cards))

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

      <RoomCardThumbnail
        :is-available="isAvailable"
        :is-in-progress="isInProgress"
        :has-time="!!hasTime"
        :timer-level="timerLevel"
        :countdown="countdown"
        :uuid-code="room.uuid_code"
      />

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- Metadata: variante · rondas · fecha, una sola línea compacta -->
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
          <template v-if="startLabel">
            <span class="rc__meta-dot" />
            <span v-tooltip="'Fecha y hora programada de inicio del sorteo'" class="rc__meta-item">
              <i class="far fa-calendar-alt" />{{ startLabel }}
            </span>
          </template>
        </div>

        <RoomCardPrizeProgress
          :is-accumulated="isAccumulated"
          :prize-formatted="prizeFormatted"
          :sym="sym"
          :show-cards-progress="showCardsProgress"
          :cards-progress-ready="cardsProgressReady"
          :cards-remaining="cardsRemaining"
          :show-selling-fast="showSellingFast"
          :total-cards="totalCards"
          :min-cards-to-start="minCardsToStart"
          :cards-progress-pct="cardsProgressPct"
        />

        <RoomCardFooter
          :variant-label="variantLabel"
          :variant-balls="variantBalls"
          :card-price-formatted="cardPriceFormatted"
          :sym="sym"
          :min-entry-cost-formatted="minEntryCostFormatted"
          :min-cards-to-start="minCardsToStart"
          :players-preview="playersPreview"
          :extra-players-count="extraPlayersCount"
          :total-players="room.total_players"
          :player-ctns="playerCtns"
          :max-players="maxPlayers"
        />

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
  --glow-clr: rgba(42,245,152,.08);
  --d: 0ms;

  position: relative;
  width: 100%;
  max-width: 208px;
  border-radius: 18px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(165deg, #0c1230 0%, #060a18 65%);
  box-shadow: 0 4px 22px rgba(0,0,0,.45), 0 1px 0 rgba(255,255,255,.04) inset;
  transition: transform .32s cubic-bezier(.16,1,.3,1),
              box-shadow .32s cubic-bezier(.16,1,.3,1),
              border-color .32s ease;
  animation: rcIn .5s cubic-bezier(.16,1,.3,1) both, rcPulse 3.6s ease-in-out .5s infinite;
  animation-delay: var(--d), calc(var(--d) * 1.4 + .5s);
}
@keyframes rcIn {
  from { opacity: 0; transform: translateY(10px) scale(.98); }
  to   { opacity: 1; transform: none; }
}

/* Pulso ambiental — borde + halo que respira con el color de estado de la card
   (verde activa / rojo en vivo), mismo lenguaje que .rim-card del modal de info.
   Es la animación de "vida" principal de la card en reposo, así que se nota un
   poco más que un simple parpadeo — sin llegar a distraer. */
@keyframes rcPulse {
  0%, 100% {
    border: 1px solid rgba(255,255,255,.07);
    box-shadow: 0 4px 22px rgba(0,0,0,.45), 0 1px 0 rgba(255,255,255,.04) inset, 0 0 0 0 transparent;
  }
  50% {
    border: 1px solid var(--glow-clr);
    box-shadow: 0 4px 22px rgba(0,0,0,.45), 0 1px 0 rgba(255,255,255,.04) inset, 0 0 26px 2px var(--glow-clr);
  }
}
@media (prefers-reduced-motion: reduce) {
  .rc { animation: rcIn .5s cubic-bezier(.16,1,.3,1) both; border: 1px solid rgba(255,255,255,.07); }
}

.rc--live { --accent: #ff4d6d; --glow-clr: rgba(255,77,109,.14); }

.rc--active:hover {
  transform: translateY(-5px);
  border-color: rgba(42,245,152,.18);
  box-shadow: 0 18px 44px rgba(0,0,0,.55),
              0 0 0 1px rgba(42,245,152,.14),
              0 0 26px rgba(42,245,152,.07);
  animation-play-state: paused;
}
.rc--live:hover {
  transform: translateY(-4px);
  border-color: rgba(255,77,109,.18);
  box-shadow: 0 16px 40px rgba(0,0,0,.55), 0 0 0 1px rgba(255,77,109,.14);
  animation-play-state: paused;
}
.rc--off {
  opacity: .22; cursor: default;
  filter: saturate(.08) brightness(.62);
  pointer-events: none;
  animation: rcIn .5s cubic-bezier(.16,1,.3,1) both;
  border: 1px solid rgba(255,255,255,.07);
}

.rc:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

/* glow de fondo — respiración muy sutil, refuerza la sensación de "vivo" sin distraer */
.rc__glow {
  position: absolute; inset: 0; z-index: 0; pointer-events: none;
  background: radial-gradient(ellipse 90% 70% at 5% 115%, var(--glow-clr), transparent 60%);
  animation: glowBreathe 7s ease-in-out infinite;
}
@keyframes glowBreathe {
  0%, 100% { opacity: .75; }
  50%      { opacity: 1; }
}

/* shimmer en hover — más lento y discreto, efecto "satinado" en vez de destello */
.rc__shimmer {
  position: absolute; inset: 0; z-index: 1; pointer-events: none;
  background: linear-gradient(110deg, transparent 35%, rgba(255,255,255,.02) 50%, transparent 65%);
  transform: translateX(-110%);
}
.rc--active:hover .rc__shimmer,
.rc--live:hover   .rc__shimmer { animation: shimmer .7s cubic-bezier(.16,1,.3,1) forwards; }
@keyframes shimmer { to { transform: translateX(110%); } }

/* inner */
.rc__inner {
  position: relative; z-index: 2;
  display: flex; flex-direction: column; align-items: stretch;
}
@media (prefers-reduced-motion: reduce) {
  .rc__inner { animation: none; }
}

/* Hover cascades hacia hijos (RoomCardThumbnail/RoomCardFooter) — deben vivir
   aquí porque :hover se aplica sobre .rc, que no es visible dentro del scope
   de esos componentes hijos. */
.rc--active:hover :deep(.rc__img),
.rc--live:hover   :deep(.rc__img) { transform: scale(1.5); }

.rc--active:hover :deep(.rc__stat),
.rc--live:hover   :deep(.rc__stat) {
  background: rgba(255,255,255,.055);
  border-color: rgba(255,255,255,.1);
}

/* ══════════════════════════════════
   BODY
══════════════════════════════════ */
.rc__body {
  flex: 1; min-width: 0;
  padding: 10px 14px 12px;
  display: flex; flex-direction: column;
  gap: 8px;
}

/* ── Metadata: variante · rondas · fecha, una fila compacta y flexible ── */
.rc__meta {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  flex-shrink: 0; flex-wrap: wrap; row-gap: 2px;
}
.rc__meta-item {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 9.5px; font-weight: 600; letter-spacing: .25px;
  color: #6a86ac;
  white-space: nowrap;
}
.rc__meta-item i { font-size: 8.5px; color: #4a6488; }
.rc__meta-dot {
  width: 3px; height: 3px; border-radius: 50%;
  background: #34486a; flex-shrink: 0;
}

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
  background: radial-gradient(circle, rgba(255,255,255,.45) 0%, transparent 70%);
  animation: rippleExpand .6s cubic-bezier(.16,1,.3,1) forwards;
}
@keyframes rippleExpand {
  from { transform: scale(1);  opacity: .7; }
  to   { transform: scale(24); opacity: 0; }
}
</style>
