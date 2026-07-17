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

    <!-- Timer solo cuando hay countdown activo: en curso (isInProgress) el
         status-badge ya muestra "EN JUEGO" con punto parpadeante — mostrar
         un segundo badge "LIVE" aquí sería redundante. -->
    <div
      v-if="hasTime"
      v-tooltip="'Tiempo restante para el inicio del sorteo'"
      class="rc__timer-badge"
      :class="{ 'rc__timer-badge--urgent': isUrgent }"
    >
      <i class="far fa-clock" /><b>{{ formatCountdown(countdown) }}</b>
    </div>

    <div class="rc__orbit-dot" />
  </div>
</template>

<style scoped>
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
</style>
