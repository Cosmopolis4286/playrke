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
const prize    = computed(() => (props.room.prize_pool * props.exchangeRate).toFixed(2))
const myCtns   = computed(() => props.room.players_room?.[0]?.ctns ?? 0)
const priceSym = computed(() => props.room.currency_symbol ?? props.currencySym ?? 'USD')

// UX-01: botón "Ir al Lobby" deshabilitado si la sala no está activa/en progreso
const isLobbyAllowed = computed(() =>
  props.room.status_code === 'ACTIVE' || props.room.status_code === 'IN_PROGRESS'
)

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
      <div class="rim-backdrop" role="dialog" aria-modal="true" aria-labelledby="rim-title" @click.self="emit('close')">
        <div class="rim-card">

          <!-- ── Imagen banner compacta ── -->
          <div class="rim-banner">
            <img :src="bingoRoomImg" alt="sala" class="rim-banner__img" />
            <div class="rim-banner__overlay" />

            <!-- Cerrar -->
            <button class="rim-close" aria-label="Cerrar" @click="emit('close')">
              <i class="fas fa-times" />
            </button>

            <!-- Timer centrado -->
            <div id="rim-title" class="rim-timer" :class="{ 'rim-timer--live': isLive(countdown) }">
              <template v-if="isLive(countdown)">
                <span class="rim-dot" /> EN VIVO
              </template>
              <template v-else>
                <i class="fas fa-clock" /> {{ formatCountdown(countdown) }}
              </template>
            </div>

            <!-- Chips tipo/modo abajo -->
            <div class="rim-banner__foot">
              <span class="rim-chip rim-chip--type">
                {{ ROOM_TYPE_LABELS[room.room_type ?? ''] ?? room.room_type }}
              </span>
              <span class="rim-chip rim-chip--mode">
                {{ GAME_MODE_LABELS[room.game_mode ?? ''] ?? room.game_mode ?? 'Online' }}
              </span>
              <span v-if="room.rounds_count" class="rim-chip rim-chip--rounds">
                <i class="fas fa-layer-group" /> {{ room.rounds_count }} {{ room.rounds_count === 1 ? 'Ronda' : 'Rondas' }}
              </span>
            </div>
          </div>

          <!-- ── Cuerpo ── -->
          <div class="rim-body">

            <!-- Premio -->
            <div class="rim-prize">
              <span v-if="myCtns > 0" class="rim-my-ctns" title="Tus cartones en esta sala">
                <i class="fas fa-ticket-alt" /> {{ myCtns }} {{ myCtns === 1 ? 'cartón' : 'cartones' }}
              </span>
              <span class="rim-prize__lbl">Premio acumulado</span>
              <div class="rim-prize__val">
                <span class="rim-prize__num">{{ prize }}</span>
                <span class="rim-prize__sym">{{ currencySym }}</span>
              </div>
              <span v-if="myCtns > 0" class="rim-participating">
                <i class="fas fa-check-circle" /> Ya estás participando en esta sala
              </span>
            </div>

            <!-- Progreso de cartones mínimos para iniciar (realtime) -->
            <div
              v-if="showCardsProgress"
              class="rim-cards-progress"
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
            <div class="rim-stats">
              <div class="rim-stat">
                <span class="rim-stat__val">{{ room.total_players }}</span>
                <span class="rim-stat__lbl">Jugadores</span>
              </div>
              <div class="rim-stat">
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
            <div class="rim-actions">
              <button
                class="rim-btn rim-btn--lobby"
                :disabled="!isLobbyAllowed"
                :class="{ 'rim-btn--disabled': !isLobbyAllowed }"
                @click="emit('go-lobby')"
              >
                <i class="fas fa-sign-in-alt" /> Ir al Lobby
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
  border: 1px solid rgba(100,140,255,.22);
  border-radius: 20px; overflow: hidden;
  box-shadow: 0 24px 64px rgba(0,0,0,.85), 0 0 0 1px rgba(80,120,255,.08) inset;
  position: relative;
}
/* glow superior */
.rim-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 160px;
  background: radial-gradient(ellipse 80% 55% at 50% 0%, rgba(123,79,255,.28), transparent 70%);
  pointer-events: none; z-index: 0;
}

/* ── Banner ── */
.rim-banner {
  position: relative; height: 140px; overflow: hidden;
}
.rim-banner__img {
  width: 100%; height: 100%; object-fit: cover; display: block;
}
.rim-banner__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(4,8,26,.4) 0%, rgba(4,8,26,.88) 100%);
}

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

/* Timer */
.rim-timer {
  position: absolute; bottom: 32px; left: 50%; transform: translateX(-50%);
  background: rgba(6,12,36,.88); border: 1px solid rgba(100,140,255,.3);
  backdrop-filter: blur(6px); color: #c8d8ff;
  border-radius: 30px; padding: 4px 14px;
  font-family: 'Anton', sans-serif; font-size: 15px; letter-spacing: 1.5px;
  display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
}
.rim-timer i { font-size: 11px; opacity: .65; }
.rim-timer--live { border-color: rgba(255,60,60,.5); color: #ff7070; animation: rimLivePulse 1.8s infinite; }
@keyframes rimLivePulse { 0%,100%{box-shadow:0 0 0 0 rgba(255,60,60,0)} 50%{box-shadow:0 0 0 5px rgba(255,60,60,.15)} }

.rim-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #ff4d4d; box-shadow: 0 0 5px #ff4d4d; flex-shrink: 0;
  animation: rimBlink 1s infinite;
}
@keyframes rimBlink { 0%,100%{opacity:1} 50%{opacity:.2} }

/* Chips pie del banner */
.rim-banner__foot {
  position: absolute; bottom: 0; left: 0; right: 0; z-index: 2;
  display: flex; gap: 6px; padding: 7px 12px;
}
.rim-chip {
  padding: 2px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .3px;
  display: inline-flex; align-items: center; gap: 4px;
}
.rim-chip--type   { background: rgba(123,79,255,.2);  border: 1px solid rgba(123,79,255,.38);  color: #b490ff; }
.rim-chip--mode   { background: rgba(0,158,253,.15);  border: 1px solid rgba(0,158,253,.32);   color: #7cd4ff; }
.rim-chip--rounds { background: rgba(42,245,152,.14); border: 1px solid rgba(42,245,152,.32);  color: #7be8b8; }
.rim-chip--rounds i { font-size: 9px; opacity: .85; }

/* ── Body ── */
.rim-body {
  padding: 14px 16px 16px;
  position: relative; z-index: 1;
  display: flex; flex-direction: column; gap: 12px;
}

/* Premio */
.rim-prize { text-align: center; position: relative; }
.rim-prize__lbl {
  font-size: 9px; color: #5578bb;
  text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 3px;
}
/* Badge de cartones propios — esquina superior derecha del bloque de premio */
.rim-my-ctns {
  position: absolute; right: 0; top: -2px;
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 800; letter-spacing: .3px;
  background: rgba(42,245,152,.14); border: 1px solid rgba(42,245,152,.34);
  color: #7be8b8; white-space: nowrap;
}
.rim-my-ctns i { font-size: 9px; opacity: .85; }

.rim-participating {
  display: inline-flex; align-items: center; gap: 5px;
  margin-top: 6px; padding: 3px 11px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .3px;
  background: rgba(42,245,152,.1); border: 1px solid rgba(42,245,152,.26);
  color: #7be8b8;
}
.rim-participating i { font-size: 10px; color: #2af598; }

.rim-prize__val { display: flex; align-items: baseline; justify-content: center; gap: 6px; }
.rim-prize__num {
  font-family: 'Anton', sans-serif; font-size: 38px; line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 3px 10px rgba(255,180,0,.4));
}
.rim-prize__sym { font-size: 14px; font-weight: 800; color: #ffd060; -webkit-text-fill-color: #ffd060; }

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
  animation: rimCpReadyPulse 1.6s ease-in-out infinite;
}
@keyframes rimCpReadyPulse {
  0%,100% { box-shadow: 0 0 8px rgba(42,245,152,.5); }
  50%     { box-shadow: 0 0 16px rgba(42,245,152,.85); }
}

/* Stats */
.rim-stats { display: flex; gap: 8px; }
.rim-stat {
  flex: 1;
  background: rgba(255,255,255,.04); border: 1px solid rgba(100,140,255,.1);
  border-radius: 11px; padding: 9px 6px; text-align: center;
}
.rim-stat__val {
  font-family: 'Anton', sans-serif; font-size: 18px; color: #dce8ff;
  display: block; margin-bottom: 2px; line-height: 1;
}
.rim-stat__val small { font-family: inherit; font-size: 9px; color: #4a6090; }
.rim-stat__lbl { font-size: 8px; color: #3d5280; text-transform: uppercase; letter-spacing: .6px; }

/* Acciones */
.rim-actions { display: flex; gap: 8px; }
.rim-btn {
  flex: 1; padding: 12px 8px; border: none; border-radius: 12px;
  font-size: 12px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  transition: filter .2s, transform .15s, box-shadow .2s;
  position: relative; overflow: hidden;
}
.rim-btn::after {
  content: ''; position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.14), transparent);
  transform: skewX(-18deg); transition: left .45s ease;
}
.rim-btn:hover::after { left: 130%; }

.rim-btn--lobby {
  background: linear-gradient(90deg, #7b4fff 0%, #009efd 100%);
  color: #fff; box-shadow: 0 4px 18px rgba(123,79,255,.38);
}
.rim-btn--lobby:hover { filter: brightness(1.1); transform: translateY(-1px); }

.rim-btn--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #031a10; box-shadow: 0 4px 18px rgba(42,245,152,.28);
}
.rim-btn--buy:hover { filter: brightness(1.08); transform: translateY(-1px); }

/* UX-01: botón lobby deshabilitado */
.rim-btn--disabled {
  opacity: .38; cursor: not-allowed;
  filter: saturate(.2) brightness(.7);
  pointer-events: none;
}

/* Transición */
.rim-fade-enter-active, .rim-fade-leave-active { transition: opacity .2s ease, transform .2s ease; }
.rim-fade-enter-from, .rim-fade-leave-to { opacity: 0; transform: scale(.96) translateY(6px); }
</style>
