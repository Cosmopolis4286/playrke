<script setup lang="ts">
import { computed } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { formatCountdown, isLive, GAME_MODE_LABELS, ROOM_TYPE_LABELS } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'

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
const myCtns   = computed(() => props.room.players_room?.[0]?.ctns ?? 0)
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

            <!-- Cerrar -->
            <button class="rim-close" aria-label="Cerrar" @click="emit('close')">
              <i class="fas fa-times" />
            </button>

            <!-- Timer — solo, centrado, sin compartir fila con la fecha -->
            <div id="rim-title" class="rim-timer-row">
              <span class="rim-timer" :class="{ 'rim-timer--live': isLive(countdown) }">
                <template v-if="isLive(countdown)">
                  <span class="rim-dot" /> EN VIVO
                </template>
                <template v-else>
                  <i class="fas fa-clock" /> {{ formatCountdown(countdown) }}
                </template>
              </span>
            </div>

            <!-- Chips tipo/modo/rondas/variante + fecha — más resalto, acento por categoría -->
            <div class="rim-banner__foot">
              <span class="rim-chip rim-chip--violet">
                <i class="fas fa-lock" /> {{ ROOM_TYPE_LABELS[room.room_type ?? ''] ?? room.room_type }}
              </span>
              <span class="rim-chip rim-chip--violet">
                <i class="fas fa-signal" /> {{ GAME_MODE_LABELS[room.game_mode ?? ''] ?? room.game_mode ?? 'Online' }}
              </span>
              <span v-if="room.rounds_count" class="rim-chip rim-chip--blue">
                <i class="fas fa-layer-group" /> {{ room.rounds_count }} {{ room.rounds_count === 1 ? 'Ronda' : 'Rondas' }}
              </span>
              <span v-if="room.config?.game_variant" class="rim-chip rim-chip--blue">
                <i class="fas fa-dice" /> {{ room.config.game_variant.replace('_', ' ') }}
              </span>
              <span v-if="startLabel" class="rim-chip rim-chip--date">
                <i class="fas fa-calendar-alt" /> {{ startLabel }}
              </span>
            </div>
          </div>

          <!-- ── Cuerpo ── -->
          <div class="rim-body">

            <!-- Premio — bloque hero con anillo orbital ambiental -->
            <div class="rim-prize rim-reveal" style="--rd: 60ms">
              <div class="rim-prize__aura" />
              <span class="rim-prize__lbl"><i class="fas fa-trophy" />Premio acumulado</span>
              <div class="rim-prize__val">
                <span class="rim-prize__num">{{ prize }}</span>
                <span class="rim-prize__sym">{{ currencySym }}</span>
              </div>
              <span v-if="myCtns > 0" class="rim-participating">
                <i class="fas fa-check-circle" /> Participas con {{ myCtns }} {{ myCtns === 1 ? 'cartón' : 'cartones' }}
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
              <div class="rim-stat">
                <span class="rim-stat__val">{{ room.total_players }}</span>
                <span class="rim-stat__lbl">Jugadores</span>
              </div>
              <div class="rim-stat" :class="{ 'rim-stat--mine': myCtns > 0 }">
                <span class="rim-stat__val">{{ myCtns }}</span>
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
  border: 1px solid rgba(100,140,255,.2);
  border-radius: 22px; overflow: hidden;
  position: relative;
  animation: rimCardPulse 4s ease-in-out infinite;
}
/* Pulso ambiental del contenedor completo — sombra externa que respira, refuerza que el modal está "vivo" */
@keyframes rimCardPulse {
  0%, 100% { box-shadow: 0 24px 64px rgba(0,0,0,.8), 0 0 0 1px rgba(80,120,255,.06) inset, 0 0 0 0 rgba(123,79,255,0); }
  50%      { box-shadow: 0 24px 64px rgba(0,0,0,.8), 0 0 0 1px rgba(80,120,255,.06) inset, 0 0 32px 2px rgba(123,79,255,.16); }
}
@media (prefers-reduced-motion: reduce) {
  .rim-card { animation: none; box-shadow: 0 24px 64px rgba(0,0,0,.8), 0 0 0 1px rgba(80,120,255,.06) inset; }
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
  position: relative; height: 128px; overflow: hidden;
}
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

/* Timer + fecha — fila centrada, sin apilar en absolute separados */
.rim-timer-row {
  position: absolute; bottom: 34px; left: 50%; transform: translateX(-50%);
  display: flex; flex-direction: column; align-items: center; gap: 5px;
  width: 100%; padding: 0 12px;
}
.rim-timer {
  background: rgba(6,12,36,.85); border: 1px solid rgba(100,140,255,.28);
  backdrop-filter: blur(6px); color: #c8d8ff;
  border-radius: 30px; padding: 4px 14px;
  font-family: 'Anton', sans-serif; font-size: 14px; letter-spacing: 1.2px;
  display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
  box-shadow: 0 4px 14px rgba(0,0,0,.35);
}
.rim-timer i { font-size: 10px; opacity: .65; }
.rim-timer--live { border-color: rgba(255,60,60,.45); color: #ff7070; animation: rimLivePulse 2.4s ease-in-out infinite; }
@keyframes rimLivePulse { 0%,100%{box-shadow:0 4px 14px rgba(0,0,0,.35)} 50%{box-shadow:0 4px 14px rgba(0,0,0,.35), 0 0 0 5px rgba(255,60,60,.12)} }

.rim-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #ff4d4d; box-shadow: 0 0 5px #ff4d4d; flex-shrink: 0;
  animation: rimBlink 1.6s ease-in-out infinite;
}
@keyframes rimBlink { 0%,100%{opacity:1} 50%{opacity:.35} }

/* Chips pie del banner — más presencia: relleno sólido + acento por categoría */
.rim-banner__foot {
  position: absolute; bottom: 0; left: 0; right: 0; z-index: 2;
  display: flex; flex-wrap: wrap; gap: 6px; padding: 9px 12px;
}
.rim-chip {
  padding: 4px 11px; border-radius: 20px;
  font-size: 10.5px; font-weight: 700; letter-spacing: .3px;
  display: inline-flex; align-items: center; gap: 5px;
  white-space: nowrap;
  backdrop-filter: blur(4px);
  box-shadow: 0 2px 8px rgba(0,0,0,.3);
  animation: rimChipIn .4s cubic-bezier(.16,1,.3,1) both;
  transition: transform .2s ease, box-shadow .2s ease;
}
.rim-chip:hover { transform: translateY(-1px); }
.rim-chip:nth-child(1) { animation-delay: 80ms; }
.rim-chip:nth-child(2) { animation-delay: 120ms; }
.rim-chip:nth-child(3) { animation-delay: 160ms; }
.rim-chip:nth-child(4) { animation-delay: 200ms; }
.rim-chip:nth-child(5) { animation-delay: 240ms; }
@keyframes rimChipIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
  .rim-chip { animation: none; }
}
.rim-chip i { font-size: 9.5px; opacity: .85; }

.rim-chip--violet {
  background: rgba(123,79,255,.22); border: 1px solid rgba(123,79,255,.42);
  color: #c4aeff;
}
.rim-chip--blue {
  background: rgba(0,158,253,.18); border: 1px solid rgba(0,158,253,.4);
  color: #8ed4ff;
}
.rim-chip--date {
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18);
  color: #d8e0f0;
}

/* ── Body ── */
.rim-body {
  padding: 16px 16px 18px;
  position: relative; z-index: 1;
  display: flex; flex-direction: column; gap: 13px;
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

/* Premio — bloque hero con anillo orbital ambiental detrás del número */
.rim-prize { text-align: center; position: relative; }
.rim-prize__aura {
  position: absolute; left: 50%; top: 50%; z-index: 0; pointer-events: none;
  width: 130px; height: 130px; margin: -65px 0 0 -65px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,180,0,.14) 0%, transparent 68%);
  animation: rimAuraBreathe 3.4s ease-in-out infinite;
}
@keyframes rimAuraBreathe {
  0%, 100% { opacity: .7; transform: scale(1); }
  50%      { opacity: 1;  transform: scale(1.08); }
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
  filter: drop-shadow(0 2px 14px rgba(255,180,0,.35));
  animation: rimPrizeIn .55s cubic-bezier(.16,1,.3,1) .1s both;
}
@keyframes rimPrizeIn {
  from { opacity: 0; transform: scale(.85); }
  to   { opacity: 1; transform: scale(1); }
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
