<script setup lang="ts">
import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { BINGO_ROOM_IMG, formatCountdown } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'

defineProps<{
  isAvailable: boolean
  isInProgress: boolean
  hasTime: boolean
  isUrgent: boolean
  countdown: RoomCountdown | undefined
}>()
</script>

<template>
  <div class="rc__thumb">
    <div class="rc__stars" />
    <img :src="BINGO_ROOM_IMG" alt="" class="rc__img" />
    <div class="rc__img-veil" />
    <div class="rc__side-accent" />

    <!-- Un solo badge: fusiona estado + countdown cuando hay tiempo activo,
         para no apilar dos pastillas (arriba/abajo) y ganar altura. -->
    <div
      v-if="hasTime"
      v-tooltip="'Tiempo restante para el inicio del sorteo'"
      class="rc__status-badge rc__status-badge--ok"
      :class="{ 'rc__status-badge--urgent': isUrgent }"
    >
      <i class="far fa-clock" /><b>{{ formatCountdown(countdown) }}</b>
    </div>
    <div
      v-else
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
  </div>
</template>

<style scoped>
/* ══════════════════════════════════
   THUMBNAIL — banner limpio: un solo glow + imagen sutil
══════════════════════════════════ */
.rc__thumb {
  position: relative;
  width: 100%;
  height: 60px;
  flex: 0 0 auto;
  overflow: hidden;
  background:
    radial-gradient(ellipse 120% 90% at 30% 0%, rgba(123,79,255,.16) 0%, transparent 60%),
    linear-gradient(160deg, #10163a 0%, #0a0f24 60%, #060a18 100%);
}

/* campo de estrellas — una sola capa discreta, respiración lenta */
.rc__stars {
  position: absolute; inset: 0; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at 12% 30%, rgba(255,255,255,.7), transparent 100%),
    radial-gradient(1px 1px at 32% 68%, rgba(190,210,255,.6), transparent 100%),
    radial-gradient(1.5px 1.5px at 58% 22%, rgba(210,185,255,.65), transparent 100%),
    radial-gradient(1px 1px at 75% 55%, rgba(255,255,255,.55), transparent 100%),
    radial-gradient(1.5px 1.5px at 91% 35%, rgba(200,255,220,.55), transparent 100%),
    radial-gradient(1px 1px at 20% 82%, rgba(255,240,180,.5), transparent 100%);
  animation: twinkle 6s ease-in-out infinite;
}
@keyframes twinkle { 0%,100%{opacity:.6} 50%{opacity:1} }

.rc__img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover; object-position: center 22%; display: block;
  transform: scale(1.5);
  transition: transform .6s cubic-bezier(.22,.68,0,1);
  opacity: .4;
  mix-blend-mode: screen;
}

.rc__img-veil {
  position: absolute; inset: 0;
  background:
    linear-gradient(180deg,
      rgba(7,12,26,.3) 0%,
      rgba(7,12,26,.45) 55%,
      rgba(7,12,26,.95) 100%);
}

/* acento inferior — línea fina y estática, sin escaneo: más contenida */
.rc__side-accent {
  position: absolute; left: 0; right: 0; bottom: 0; height: 1px;
  background: linear-gradient(90deg, transparent 0%, var(--accent) 50%, transparent 100%);
  opacity: .5;
}

/* badge único — top centrado; fusiona estado + countdown para no apilar dos pastillas */
.rc__status-badge {
  position: absolute; top: 7px; left: 50%; transform: translateX(-50%);
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 9.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  white-space: nowrap; backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0,0,0,.4); z-index: 3;
  transition: box-shadow .3s ease;
}
.rc__status-dot {
  width: 5px; height: 5px; border-radius: 50%;
  background: currentColor; flex-shrink: 0;
  box-shadow: 0 0 5px currentColor;
}
.rc__status-badge--ok   { background: rgba(8,28,18,.75); border: 1px solid rgba(42,245,152,.35); color: #2af598; }
.rc__status-badge--live { background: rgba(28,8,16,.75); border: 1px solid rgba(255,77,109,.35); color: #ff6e8a; }
.rc__status-badge--off  { background: rgba(8,12,24,.75); border: 1px solid rgba(255,255,255,.1); color: #3a5068; }
.rc__status-badge--ok   .rc__status-dot { animation: dp 2.4s ease-in-out infinite; }
.rc__status-badge--live .rc__status-dot { animation: dp 1.2s ease-in-out infinite; }
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.25} }

.rc__status-badge b {
  font-family: 'Orbitron', 'Anton', monospace;
  font-size: 9.5px; font-weight: 700; letter-spacing: .3px;
}
.rc__status-badge i.far { font-size: 8.5px; opacity: .7; }

/* variante urgente del badge fusionado: <1 minuto para el sorteo */
.rc__status-badge--urgent {
  color: #ffb020;
  background: rgba(42,24,6,.72);
  border-color: rgba(255,176,32,.4);
  animation: urgentPulse 1.6s ease-in-out infinite;
}
.rc__status-badge--urgent b { color: #ffb020; text-shadow: 0 0 8px rgba(255,176,32,.45); }
@keyframes urgentPulse {
  0%, 100% { box-shadow: 0 2px 10px rgba(0,0,0,.4); }
  50%      { box-shadow: 0 2px 16px rgba(255,176,32,.4); }
}
</style>
