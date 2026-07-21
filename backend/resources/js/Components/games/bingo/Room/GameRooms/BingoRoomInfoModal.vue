<script setup lang="ts">
import { computed } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { formatCountdown, isLive, getTimerLevel } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'
import { getBingoVariantDef } from '@js/modules/games/bingo/utils/bingoVariants'

const props = defineProps<{
  room: BingoGameRoom
  countdown: RoomCountdown | undefined
  exchangeRate: number
  currencySym: string
  canBuy: boolean
  bingoRoomImg: string
  /** true mientras Inertia está navegando al lobby */
  redirecting?: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'go-lobby'): void
  (e: 'buy'): void
}>()

// BUG-03: computed en lugar de funciones sueltas
const cardPrice = computed(() => {
  const r = props.room
  if (r.card_price != null) return r.card_price.toFixed(2)
  return ((r.config?.card_price_usd ?? 0) * props.exchangeRate).toFixed(2)
})
const prize    = computed(() => ((props.room.prize_pool ?? 0) * props.exchangeRate).toFixed(2))
const myCardCount   = computed(() => props.room.players_room?.[0]?.card_count ?? 0)
const priceSym = computed(() => props.room.currency_symbol ?? props.currencySym ?? 'USD')

// UX-01: botón "Ir al Lobby" deshabilitado si la sala no está activa/en progreso
const isLobbyAllowed = computed(() =>
  props.room.status_code === 'ACTIVE' || props.room.status_code === 'IN_PROGRESS'
)

const startLabel = computed(() => {
  const unix = props.room.config?.unix_date
  if (!unix) return null
  const d = new Date(unix * 1000)
  const fecha = d.toLocaleDateString('es', { day: 'numeric', month: 'short' })
  const hora  = d.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit', hour12: false })
  return `${fecha} · ${hora}`
})

const gameVariant  = computed(() => props.room.config?.game_variant ?? 'BINGO_75')
const variantBalls = computed(() => getBingoVariantDef(gameVariant.value).totalChips)
const roundsCount   = computed(() => props.room.rounds_count ?? 1)

// Código corto de sala — solo los últimos 6 caracteres, suficiente para identificar/soporte
// sin exponer el UUID completo como ruido visual en el banner.
const shortRoomCode = computed(() => {
  const code = props.room.uuid_code
  if (!code) return null
  return code.length > 6 ? code.slice(-6).toUpperCase() : code.toUpperCase()
})

// ── Progreso de cartones mínimos para iniciar (realtime, igual que la card/lobby) ──
const minCardsToStart = computed(() => props.room.config?.min_cards_to_start ?? null)
const totalCards      = computed(() => props.room.total_cards ?? 0)

const showCardsProgress = computed(() =>
  minCardsToStart.value != null && minCardsToStart.value > 0
)

const cardsProgressPct = computed(() => {
  if (!minCardsToStart.value) return 0
  return Math.min(100, Math.round((totalCards.value / minCardsToStart.value) * 100))
})

const cardsProgressReady = computed(() =>
  minCardsToStart.value != null && totalCards.value >= minCardsToStart.value
)

// Semáforo del timer: verde (>10min) → amarillo (2-10min) → rojo (<2min), igual criterio que la card
const timerLevel = computed(() => getTimerLevel(props.countdown))

// Feedback de urgencia: countdown corriendo (<2 min) y aún no se alcanza el mínimo de cartones —
// riesgo real de que el sorteo no inicie a tiempo, información que ayuda a decidir comprar ya.
const showUrgencyHint = computed(() =>
  timerLevel.value === 'urgent' && showCardsProgress.value && !cardsProgressReady.value
)

// Cupo de la sala: "X/X" (actual/máximo) cuando la sala define un límite de jugadores
const maxPlayers = computed(() => props.room.config?.max_players ?? null)
const playersLabel = computed(() =>
  maxPlayers.value ? `${props.room.total_players}/${maxPlayers.value}` : `${props.room.total_players}`
)
const isRoomFull = computed(() =>
  maxPlayers.value != null && props.room.total_players >= maxPlayers.value
)
</script>

<template>
  <Teleport to="body">
    <Transition name="rim-fade">
      <div class="rim-backdrop" @click.self="emit('close')">
        <div class="rim-card" role="dialog" aria-modal="true" aria-labelledby="rim-title">

          <!-- Overlay de redirección al lobby -->
          <Transition name="rim-overlay">
            <div v-if="redirecting" class="rim-redirect-overlay" aria-hidden="true">
              <span class="rim-redirect-spinner" />
              <span class="rim-redirect-label">Redirigiendo…</span>
            </div>
          </Transition>

          <!-- ── Imagen banner compacta ── -->
          <div class="rim-banner">
            <img :src="bingoRoomImg" alt="sala" class="rim-banner__img" />
            <div class="rim-banner__overlay" />
            <div class="rim-banner__sparkle" />
            <div class="rim-banner__accent" />

            <!-- Código corto de sala — discreto, solo para identificar/soporte -->
            <span v-if="shortRoomCode" class="rim-room-code">
              <i class="fas fa-hashtag" />{{ shortRoomCode }}
            </span>

            <!-- Cerrar -->
            <button class="rim-close" aria-label="Cerrar" @click="emit('close')">
              <i class="fas fa-times" />
            </button>

            <!-- Timer — único elemento centrado sobre la imagen; semáforo por tiempo restante
                 (igual criterio que la card), rojo fijo si el sorteo ya está en vivo -->
            <div id="rim-title" class="rim-timer-row">
              <span
                class="rim-timer"
                :class="isLive(countdown) ? 'rim-timer--live' : `rim-timer--${timerLevel}`"
              >
                <template v-if="isLive(countdown)">
                  EN VIVO
                </template>
                <template v-else>
                  <i class="fas fa-clock" /> {{ formatCountdown(countdown) }}
                </template>
              </span>
            </div>
          </div>

          <!-- ── Cuerpo ── -->
          <div class="rim-body">

            <!-- Metadata: bolas · rondas · fecha — misma línea visual que la card de salas -->
            <div class="rim-meta-row rim-reveal" style="--rd: 20ms">
              <span class="rim-meta-item">{{ variantBalls }}B</span>
              <span class="rim-meta-dot" />
              <span class="rim-meta-item">
                <i class="fas fa-rotate" /> {{ roundsCount }} Ronda{{ roundsCount !== 1 ? 's' : '' }}
              </span>
              <template v-if="startLabel">
                <span class="rim-meta-dot" />
                <span class="rim-meta-item">
                  <i class="far fa-calendar-alt" /> {{ startLabel }}
                </span>
              </template>
            </div>

            <!-- Premio — bloque hero con anillo orbital ambiental -->
            <div class="rim-prize rim-reveal" style="--rd: 60ms">
              <div class="rim-prize__aura" />
              <span class="rim-prize__lbl"><i class="fas fa-trophy" />Premio acumulado</span>
              <div class="rim-prize__val">
                <span class="rim-prize__num">{{ prize }}</span>
                <span class="rim-prize__sym">{{ currencySym }}</span>
              </div>
              <span v-if="myCardCount > 0" class="rim-participating">
                <i class="fas fa-check-circle" /> Participas con {{ myCardCount }} {{ myCardCount === 1 ? 'cartón' : 'cartones' }}
              </span>
            </div>

            <!-- Progreso de cartones mínimos para iniciar (realtime) -->
            <div
              v-if="showCardsProgress"
              class="rim-cards-progress rim-reveal"
              style="--rd: 120ms"
              :class="{ 'rim-cards-progress--ready': cardsProgressReady }"
            >
              <div class="rim-cards-progress__top">
                <span class="rim-cards-progress__label">
                  <i class="fas fa-layer-group" />
                  {{ cardsProgressReady ? '¡Listo para iniciar!' : 'Cartones para iniciar' }}
                </span>
                <span class="rim-cards-progress__count">{{ totalCards }} / {{ minCardsToStart }}</span>
              </div>
              <div class="rim-cards-progress__track">
                <div class="rim-cards-progress__fill" :style="{ width: cardsProgressPct + '%' }" />
              </div>
            </div>

            <!-- Stats row -->
            <div class="rim-stats rim-reveal" style="--rd: 170ms">
              <div class="rim-stat" :class="{ 'rim-stat--full': isRoomFull }">
                <span class="rim-stat__val">{{ playersLabel }}</span>
                <span class="rim-stat__lbl">Jugadores</span>
              </div>
              <div class="rim-stat" :class="{ 'rim-stat--mine': myCardCount > 0 }">
                <span class="rim-stat__val">{{ myCardCount }}</span>
                <span class="rim-stat__lbl">Mis cartones</span>
              </div>
              <div class="rim-stat">
                <span class="rim-stat__val">
                  {{ cardPrice }}
                  <small>{{ priceSym }}</small>
                </span>
                <span class="rim-stat__lbl">Precio</span>
              </div>
            </div>

            <!-- Feedback de urgencia: el sorteo está por comenzar y aún falta cupo mínimo -->
            <div v-if="showUrgencyHint" class="rim-hint rim-hint--urgent rim-reveal" style="--rd: 195ms">
              <i class="fas fa-triangle-exclamation" />
              Quedan menos de 2 min. Compra ahora para asegurar que la sala inicie.
            </div>

            <!-- Feedback informativo: por qué no se puede entrar al lobby todavía -->
            <div v-else-if="!isLobbyAllowed" class="rim-hint rim-reveal" style="--rd: 195ms">
              <i class="fas fa-circle-info" />
              Esta sala ya no admite compras ni acceso al lobby.
            </div>

            <!-- Acciones -->
            <div class="rim-actions rim-reveal" style="--rd: 220ms">
              <button
                class="rim-btn rim-btn--lobby"
                :disabled="!isLobbyAllowed || redirecting"
                :class="{ 'rim-btn--disabled': !isLobbyAllowed || redirecting }"
                @click="emit('go-lobby')"
              >
                <span v-if="redirecting" class="rim-btn-spinner" />
                <i v-else class="fas fa-sign-in-alt" />
                {{ redirecting ? 'Redirigiendo…' : 'Ir al Lobby' }}
              </button>
              <button
                v-if="canBuy && room.status_code === 'ACTIVE'"
                class="rim-btn rim-btn--buy"
                @click="emit('buy')"
              >
                <i class="fas fa-shopping-cart" /> Comprar
              </button>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.rim-backdrop,
.rim-backdrop *,
.rim-backdrop *::before,
.rim-backdrop *::after {
  box-sizing: border-box;
}

/* ── Backdrop ── */
.rim-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(2,5,20,.82); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}

/* ── Card ── */
.rim-card {
  width: 100%; max-width: 360px;
  background: linear-gradient(160deg, #0c1430 0%, #080e24 100%);
  border-radius: 22px; overflow: hidden;
  position: relative;
  animation: rimCardPulse 3.2s ease-in-out infinite;
}
/* Pulso ambiental del contenedor completo — borde + halo externo que respira con claridad,
   refuerza que el modal está "vivo" incluso después de que terminan las animaciones de entrada. */
@keyframes rimCardPulse {
  0%, 100% {
    border: 1px solid rgba(100,140,255,.2);
    box-shadow: 0 24px 64px rgba(0,0,0,.8), 0 0 0 1px rgba(80,120,255,.06) inset, 0 0 0 0 rgba(123,79,255,0);
  }
  50% {
    border: 1px solid rgba(123,79,255,.45);
    box-shadow: 0 24px 64px rgba(0,0,0,.8), 0 0 0 1px rgba(80,120,255,.06) inset, 0 0 38px 4px rgba(123,79,255,.28);
  }
}
@media (prefers-reduced-motion: reduce) {
  .rim-card { animation: none; border: 1px solid rgba(100,140,255,.2); box-shadow: 0 24px 64px rgba(0,0,0,.8), 0 0 0 1px rgba(80,120,255,.06) inset; }
}

/* glow superior — respiración ambiental lenta, sin competir con los chips */
.rim-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 120px;
  background: radial-gradient(ellipse 80% 55% at 50% 0%, rgba(123,79,255,.18), transparent 70%);
  pointer-events: none; z-index: 0;
  animation: rimGlowBreathe 6s ease-in-out infinite;
}
@keyframes rimGlowBreathe { 0%,100% { opacity: .8; } 50% { opacity: 1; } }
@media (prefers-reduced-motion: reduce) {
  .rim-card::before { animation: none; }
}

/* ── Banner ── */
.rim-banner {
  position: relative; height: 90px; overflow: hidden;
}
/* acento inferior — línea cósmica animada, mismo lenguaje que .rc__side-accent de la card */
.rim-banner__accent {
  position: absolute; left: 0; right: 0; bottom: 0; height: 2px; z-index: 2;
  background: linear-gradient(90deg, transparent 0%, #7b4fff 20%, #2af598 50%, #009efd 80%, transparent 100%);
  background-size: 220% 100%;
  opacity: .8;
  animation: rimScanLine 4.5s linear infinite;
}
@keyframes rimScanLine {
  0%   { background-position: 0% 0; }
  100% { background-position: -220% 0; }
}
@media (prefers-reduced-motion: reduce) {
  .rim-banner__accent { animation: none; background-position: 0 0; }
}

/* Código corto de sala — discreto, esquina superior izquierda, simétrico al botón cerrar */
.rim-room-code {
  position: absolute; top: 11px; left: 12px; z-index: 10;
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 8.5px; font-weight: 600; letter-spacing: .4px;
  color: rgba(255,255,255,.4);
  text-shadow: 0 1px 3px rgba(0,0,0,.6);
}
.rim-room-code i { font-size: 7px; opacity: .7; }
.rim-banner__img {
  width: 100%; height: 100%; object-fit: cover; display: block;
  opacity: .55;
  animation: rimImgDrift 12s ease-in-out infinite alternate;
}
@keyframes rimImgDrift {
  from { transform: scale(1.04) translateY(0); }
  to   { transform: scale(1.12) translateY(-3%); }
}
@media (prefers-reduced-motion: reduce) {
  .rim-banner__img { animation: none; }
}
.rim-banner__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(4,8,26,.45) 0%, rgba(4,8,26,.9) 100%);
}
/* motas doradas ambientales — refuerzan la sensación de "premio especial" sin saturar */
.rim-banner__sparkle {
  position: absolute; inset: 0; pointer-events: none;
  background-image:
    radial-gradient(1.5px 1.5px at 15% 25%, rgba(255,214,96,.8), transparent 100%),
    radial-gradient(1px 1px at 38% 55%, rgba(255,255,255,.6), transparent 100%),
    radial-gradient(1.5px 1.5px at 62% 20%, rgba(200,185,255,.65), transparent 100%),
    radial-gradient(1px 1px at 82% 48%, rgba(255,214,96,.7), transparent 100%),
    radial-gradient(1.5px 1.5px at 92% 70%, rgba(255,255,255,.55), transparent 100%);
  animation: rimSparkle 5s ease-in-out infinite;
}
@keyframes rimSparkle { 0%,100% { opacity: .5; } 50% { opacity: 1; } }

/* Cerrar */
.rim-close {
  position: absolute; top: 10px; right: 10px; z-index: 10;
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(0,0,0,.5); border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.7); font-size: 11px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .2s, transform .15s;
}
.rim-close:hover { background: rgba(255,255,255,.15); transform: rotate(90deg); }

/* Timer — arriba, entre el código de sala y el botón cerrar, no flotando al fondo */
.rim-timer-row {
  position: absolute; top: 10px; left: 50%; transform: translateX(-50%);
  width: 100%; padding: 0 44px;
  display: flex; justify-content: center;
}
/* Timer — agrandado para mejor legibilidad; el color lo define el nivel (ok/warning/urgent/live) */
.rim-timer {
  backdrop-filter: blur(6px);
  border-radius: 30px; padding: 6px 18px;
  font-family: 'Anton', sans-serif; font-size: 17px; letter-spacing: 1.4px;
  display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
  box-shadow: 0 4px 14px rgba(0,0,0,.35);
}
.rim-timer i { font-size: 12px; opacity: .8; }

/* Verde: sorteo tranquilo, >10 minutos — mismo lenguaje que .rc__status-badge--ok de la card */
.rim-timer--ok {
  background: rgba(8,28,18,.82); border: 1px solid rgba(42,245,152,.4); color: #2af598;
  animation: rimTimerGlowOk 2.6s ease-in-out infinite;
}
@keyframes rimTimerGlowOk {
  0%, 100% { box-shadow: 0 4px 14px rgba(0,0,0,.35), 0 0 16px rgba(42,245,152,.12); }
  50%      { box-shadow: 0 4px 14px rgba(0,0,0,.35), 0 0 22px rgba(42,245,152,.28); }
}

/* Amarillo: 2-10 minutos restantes */
.rim-timer--warning {
  background: rgba(42,32,6,.82); border: 1px solid rgba(255,207,77,.42); color: #ffcf4d;
  animation: rimTimerGlowWarning 1.8s ease-in-out infinite;
}
@keyframes rimTimerGlowWarning {
  0%, 100% { box-shadow: 0 4px 14px rgba(0,0,0,.35); }
  50%      { box-shadow: 0 4px 14px rgba(0,0,0,.35), 0 0 18px rgba(255,207,77,.35); }
}

/* Rojo: <2 minutos restantes — máxima urgencia */
.rim-timer--urgent {
  background: rgba(42,8,8,.82); border: 1px solid rgba(255,90,90,.48); color: #ff5a5a;
  animation: rimTimerGlowUrgent 1.1s ease-in-out infinite;
}
@keyframes rimTimerGlowUrgent {
  0%, 100% { box-shadow: 0 4px 14px rgba(0,0,0,.35); }
  50%      { box-shadow: 0 4px 14px rgba(0,0,0,.35), 0 0 22px rgba(255,90,90,.45); }
}

/* Timer en vivo — rojo fijo, mismo lenguaje que .rc__status-badge--live de la card */
.rim-timer--live {
  background: rgba(28,8,16,.82); border: 1px solid rgba(255,77,109,.45); color: #ff6e8a;
  animation: rimLivePulse 2.4s ease-in-out infinite;
}
@keyframes rimLivePulse {
  0%, 100% { box-shadow: 0 4px 14px rgba(0,0,0,.35); }
  50%      { box-shadow: 0 4px 14px rgba(0,0,0,.35), 0 0 0 5px rgba(255,77,109,.14); }
}

@media (prefers-reduced-motion: reduce) {
  .rim-timer, .rim-timer--ok, .rim-timer--warning, .rim-timer--urgent, .rim-timer--live { animation: none; }
}

/* Metadata: bolas · rondas · fecha — texto discreto con separadores, igual lenguaje
   visual que .rc__meta en BingoGameRoomCard.vue (consistencia entre card y modal). */
.rim-meta-row {
  display: flex; align-items: center; justify-content: center; gap: 7px;
  flex-wrap: wrap; row-gap: 3px;
}
.rim-meta-item {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10.5px; font-weight: 600; letter-spacing: .25px;
  color: #8aa4cc; white-space: nowrap;
}
.rim-meta-item i { font-size: 9px; color: #5578bb; }
.rim-meta-dot {
  width: 3px; height: 3px; border-radius: 50%;
  background: #34486a; flex-shrink: 0;
}

/* ── Body ── */
.rim-body {
  padding: 14px 16px 16px;
  position: relative; z-index: 1;
  display: flex; flex-direction: column; gap: 11px;
}

/* Entrada escalonada de las secciones del body — cascada tras el modal/banner */
.rim-reveal {
  animation: rimReveal .5s cubic-bezier(.16,1,.3,1) both;
  animation-delay: var(--rd, 0ms);
}
@keyframes rimReveal {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
  .rim-reveal { animation: none; }
}

/* Premio — bloque hero con sombra dorada de fondo, mismo lenguaje que .rc__prize de la card
   (elipse ancha cubriendo todo el bloque, no un aro circular pequeño). */
.rim-prize {
  text-align: center; position: relative;
  padding: 8px 10px 10px;
  border-radius: 14px;
  background: radial-gradient(ellipse 95% 100% at 50% 0%, rgba(255,180,0,.1), transparent 75%);
}
.rim-prize__aura {
  position: absolute; inset: 0; z-index: 0; pointer-events: none;
  border-radius: inherit;
  background: radial-gradient(ellipse 95% 100% at 50% 0%, rgba(255,180,0,.08), transparent 75%);
  animation: rimAuraBreathe 3.4s ease-in-out infinite;
}
@keyframes rimAuraBreathe {
  0%, 100% { opacity: .7; }
  50%      { opacity: 1; }
}
@media (prefers-reduced-motion: reduce) {
  .rim-prize__aura { animation: none; }
}
.rim-prize__lbl {
  position: relative; z-index: 1;
  font-size: 9px; color: #6a8ac4; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1.5px;
  display: inline-flex; align-items: center; gap: 5px; margin-bottom: 4px;
}
.rim-prize__lbl i { font-size: 9px; color: #c8a028; filter: drop-shadow(0 0 3px rgba(200,160,40,.4)); }

.rim-participating {
  position: relative; z-index: 1;
  display: inline-flex; align-items: center; gap: 5px;
  margin-top: 7px; padding: 3px 11px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .3px;
  background: rgba(42,245,152,.1); border: 1px solid rgba(42,245,152,.26);
  color: #7be8b8;
}
.rim-participating i { font-size: 10px; color: #2af598; }

.rim-prize__val { position: relative; z-index: 1; display: flex; align-items: baseline; justify-content: center; gap: 6px; }
.rim-prize__num {
  font-family: 'Anton', sans-serif; font-size: 42px; line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  display: inline-block;
  animation: rimPrizeIn .55s cubic-bezier(.16,1,.3,1) .1s both, rimPrizeGlow 2.8s ease-in-out .65s infinite;
}
@keyframes rimPrizeIn {
  from { opacity: 0; transform: scale(.85); }
  to   { opacity: 1; transform: scale(1); }
}
/* Brillo dorado pulsante — misma intensidad base que .rc__prize-num de la card,
   con una variación sutil en vez de un pulso marcado. */
@keyframes rimPrizeGlow {
  0%, 100% { filter: drop-shadow(0 2px 10px rgba(255,150,0,.28)); }
  50%      { filter: drop-shadow(0 2px 13px rgba(255,150,0,.4)); }
}
@media (prefers-reduced-motion: reduce) {
  .rim-prize__num { animation: none; filter: drop-shadow(0 2px 10px rgba(255,150,0,.28)); }
}
.rim-prize__sym { font-size: 15px; font-weight: 800; color: #ffd060; -webkit-text-fill-color: #ffd060; }

/* Progreso de cartones mínimos (realtime) */
.rim-cards-progress {
  background: rgba(255,255,255,.03); border: 1px solid rgba(100,140,255,.12);
  border-radius: 12px; padding: 9px 12px 11px;
  position: relative; overflow: hidden;
  transition: border-color .3s, box-shadow .3s;
}
.rim-cards-progress__top {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 7px;
}
.rim-cards-progress__label {
  font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  color: #7c9dff; display: inline-flex; align-items: center; gap: 6px;
}
.rim-cards-progress__label i { font-size: 9px; opacity: .8; }
.rim-cards-progress__count {
  font-family: 'Anton', sans-serif; font-size: 13px; letter-spacing: .5px; color: #d8e4ff;
}
.rim-cards-progress__track {
  height: 7px; border-radius: 20px; overflow: hidden;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(100,140,255,.1);
}
.rim-cards-progress__fill {
  height: 100%; border-radius: 20px;
  background: linear-gradient(90deg, #2a6ad8 0%, #2af598 100%);
  box-shadow: 0 0 10px rgba(42,160,255,.45);
  transition: width .6s cubic-bezier(.22,1,.36,1);
}
.rim-cards-progress--ready {
  border-color: rgba(42,245,152,.4);
  box-shadow: 0 4px 22px rgba(42,245,152,.16);
}
.rim-cards-progress--ready .rim-cards-progress__label { color: #2af598; }
.rim-cards-progress--ready .rim-cards-progress__fill {
  background: linear-gradient(90deg, #2af598 0%, #00ffd0 100%);
  animation: rimCpReadyPulse 2.2s ease-in-out infinite;
}
@keyframes rimCpReadyPulse {
  0%,100% { box-shadow: 0 0 8px rgba(42,245,152,.45); }
  50%     { box-shadow: 0 0 14px rgba(42,245,152,.7); }
}

/* Stats */
.rim-stats { display: flex; gap: 8px; }
.rim-stat {
  flex: 1;
  background: rgba(255,255,255,.04); border: 1px solid rgba(100,140,255,.1);
  border-radius: 11px; padding: 9px 6px; text-align: center;
  transition: background .25s ease, border-color .25s ease;
}
.rim-stat__val {
  font-family: 'Anton', sans-serif; font-size: 18px; color: #dce8ff;
  display: block; margin-bottom: 2px; line-height: 1;
}
.rim-stat__val small { font-family: inherit; font-size: 9px; color: #4a6090; }
.rim-stat__lbl { font-size: 8px; color: #3d5280; text-transform: uppercase; letter-spacing: .6px; }

/* Stat "mis cartones" destacado cuando el jugador ya participa */
.rim-stat--mine {
  background: rgba(42,245,152,.08); border-color: rgba(42,245,152,.28);
}
.rim-stat--mine .rim-stat__val { color: #7be8b8; }
.rim-stat--mine .rim-stat__lbl { color: #4a9a78; }

/* Stat "jugadores" cuando la sala alcanzó su cupo máximo */
.rim-stat--full {
  background: rgba(255,148,48,.08); border-color: rgba(255,148,48,.26);
}
.rim-stat--full .rim-stat__val { color: #ff9430; }
.rim-stat--full .rim-stat__lbl { color: #b06a28; }

/* Feedback contextual — urgencia (ámbar) o informativo (neutro) según el estado de la sala */
.rim-hint {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 12px; border-radius: 11px;
  font-size: 10.5px; font-weight: 600; line-height: 1.35;
  background: rgba(255,255,255,.03); border: 1px solid rgba(100,140,255,.12);
  color: #7c96c4;
}
.rim-hint i { font-size: 12px; color: #5578bb; flex-shrink: 0; }
.rim-hint--urgent {
  background: rgba(255,176,32,.08); border-color: rgba(255,176,32,.3);
  color: #ffcb7a;
  animation: rimHintUrgent 1.8s ease-in-out infinite;
}
.rim-hint--urgent i { color: #ffb020; }
@keyframes rimHintUrgent {
  0%, 100% { border-color: rgba(255,176,32,.3); }
  50%      { border-color: rgba(255,176,32,.55); }
}
@media (prefers-reduced-motion: reduce) {
  .rim-hint--urgent { animation: none; }
}

/* Acciones */
.rim-actions { display: flex; gap: 8px; }
.rim-btn {
  flex: 1; padding: 12px 8px; border: none; border-radius: 12px;
  font-size: 12px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  transition: filter .25s ease, transform .25s cubic-bezier(.16,1,.3,1), box-shadow .25s ease;
  position: relative; overflow: hidden;
}
.rim-btn::after {
  content: ''; position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent);
  transform: skewX(-18deg); transition: left .55s cubic-bezier(.16,1,.3,1);
}
.rim-btn:hover::after { left: 130%; }

.rim-btn--lobby {
  background: linear-gradient(90deg, #7b4fff 0%, #009efd 100%);
  color: #fff; box-shadow: 0 4px 18px rgba(123,79,255,.32);
}
.rim-btn--lobby:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 8px 26px rgba(123,79,255,.4); }
.rim-btn--lobby:active { transform: translateY(0); }

/* Botón de compra — CTA principal del modal: respiración ambiental sutil para atraer la mirada */
.rim-btn--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #031a10; box-shadow: 0 4px 18px rgba(42,245,152,.28);
  animation: rimBuyBreathe 2.6s ease-in-out infinite;
}
@keyframes rimBuyBreathe {
  0%, 100% { box-shadow: 0 4px 18px rgba(42,245,152,.28); }
  50%      { box-shadow: 0 4px 24px rgba(42,245,152,.48); }
}
.rim-btn--buy:hover { filter: brightness(1.06); transform: translateY(-2px); animation-play-state: paused; box-shadow: 0 8px 28px rgba(42,245,152,.5); }
.rim-btn--buy:active { transform: translateY(0); }
@media (prefers-reduced-motion: reduce) {
  .rim-btn--buy { animation: none; }
}

/* UX-01: botón lobby deshabilitado */
.rim-btn--disabled {
  opacity: .38; cursor: not-allowed;
  filter: saturate(.2) brightness(.7);
  pointer-events: none;
}

/* ── Overlay de redirección al lobby ── */
.rim-redirect-overlay {
  position: absolute; inset: 0; z-index: 20;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 12px;
  background: rgba(6, 10, 28, 0.78);
  backdrop-filter: blur(4px);
  border-radius: inherit;
}
.rim-redirect-spinner {
  display: inline-block;
  width: 38px; height: 38px;
  border: 3px solid rgba(123, 79, 255, 0.2);
  border-top-color: #7b4fff;
  border-radius: 50%;
  animation: rimSpin .7s linear infinite;
}
@keyframes rimSpin { to { transform: rotate(360deg); } }
.rim-redirect-label {
  font-size: 13px; font-weight: 700; letter-spacing: .4px;
  color: rgba(255, 255, 255, 0.7);
}

/* Spinner pequeño dentro del botón lobby */
.rim-btn-spinner {
  display: inline-block;
  width: 13px; height: 13px;
  border: 2px solid rgba(255, 255, 255, 0.25);
  border-top-color: #fff;
  border-radius: 50%;
  animation: rimSpin .7s linear infinite;
  flex-shrink: 0;
}

/* Transición del overlay */
.rim-overlay-enter-active, .rim-overlay-leave-active { transition: opacity .2s ease; }
.rim-overlay-enter-from, .rim-overlay-leave-to { opacity: 0; }

/* Transición modal */
.rim-fade-enter-active { transition: opacity .3s ease, transform .35s cubic-bezier(.16,1,.3,1); }
.rim-fade-leave-active { transition: opacity .2s ease, transform .2s ease; }
.rim-fade-enter-from, .rim-fade-leave-to { opacity: 0; transform: scale(.96) translateY(8px); }
</style>
