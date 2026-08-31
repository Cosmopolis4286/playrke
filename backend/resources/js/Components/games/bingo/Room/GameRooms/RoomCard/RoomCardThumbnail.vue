<script setup lang="ts">
import { computed } from 'vue'
import { hideAllPoppers } from 'floating-vue'
import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import type { TimerLevel } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'
import { BINGO_ROOM_IMG, formatCountdown, shortRoomCode as formatShortRoomCode } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'

const props = defineProps<{
  isAvailable: boolean
  isInProgress: boolean
  hasTime: boolean
  timerLevel: TimerLevel
  countdown: RoomCountdown | undefined
  uuidCode?: string | null
  roomType?: string | null
}>()

const emit = defineEmits<{ (e: 'show-top-buyers'): void }>()

const shortRoomCode = computed(() => formatShortRoomCode(props.uuidCode))

/** El tooltip flotante (z-index 10000) queda por encima del backdrop del
 * modal (9999) porque abrir el modal no dispara un mouseleave real sobre
 * este botón — el mouse no se mueve, solo aparece contenido encima. Cerrar
 * todos los poppers activos antes de emitir evita el tooltip "fantasma"
 * superpuesto al modal recién abierto. */
function openTopBuyers(): void {
  hideAllPoppers()
  emit('show-top-buyers')
}
</script>

<template>
  <div class="rc__thumb">
    <div class="rc__stars" />
    <img :src="BINGO_ROOM_IMG" alt="" class="rc__img" />
    <div class="rc__img-veil" />
    <div class="rc__side-accent" />

    <!-- Fila superior: código a la izquierda, estado + candado (solo ícono,
         sin texto "Privada") agrupados a la derecha — evita el badge de
         texto ancho que antes competía por espacio con el badge de estado
         centrado. -->
    <div class="rc__top-row">
      <span v-if="shortRoomCode" class="rc__room-code">
        <i class="fas fa-hashtag" />{{ shortRoomCode }}
      </span>
      <span v-else />

      <div class="rc__top-row-right">
        <!-- Un solo badge: fusiona estado + countdown cuando hay tiempo activo,
             para no apilar dos pastillas (arriba/abajo) y ganar altura.
             Semáforo: verde (>10min) → amarillo (2-10min) → rojo (<2min). -->
        <div
          v-if="hasTime"
          v-tooltip="'Tiempo restante para el inicio del sorteo'"
          class="rc__status-badge rc__status-badge--timer"
          :class="`rc__status-badge--${timerLevel}`"
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

        <span
          v-if="roomType === 'PRIVATE'"
          v-tooltip="'Sala privada: requiere código de acceso'"
          class="rc__private-icon"
        >
          <i class="fas fa-lock" />
        </span>

        <!-- Ranking de compradores — a la derecha del badge de estado/timer,
             mismo patrón circular que el candado de sala privada.
             stop-propagation evita disparar la navegación de compra de la card. -->
        <button
          type="button"
          class="rc__rank-icon"
          v-tooltip="'Ver el ranking de compradores de esta sala'"
          @click.stop="openTopBuyers"
        >
          <i class="fas fa-ranking-star" />
        </button>
      </div>
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

/* Fila superior: código a la izquierda, estado + candado agrupados a la
   derecha — reemplaza el badge de estado centrado (colisionaba con el
   badge "Privada" de texto ancho) por un layout de fila con espacio
   dedicado a cada elemento, sin solapamientos posibles. */
.rc__top-row {
  position: absolute; top: 6px; left: 8px; right: 8px; z-index: 3;
  display: flex; align-items: center; justify-content: space-between; gap: 6px;
}

.rc__top-row-right {
  display: flex; align-items: center; gap: 5px; flex-shrink: 0;
}

.rc__room-code {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 8px; font-weight: 600; letter-spacing: .4px;
  color: rgba(255,255,255,.38);
  text-shadow: 0 1px 3px rgba(0,0,0,.6);
  flex-shrink: 0;
}
.rc__room-code i { font-size: 6.5px; opacity: .7; }

/* Ícono "Privada" — solo candado circular, sin texto (menos ancho que el
   badge de texto anterior, tono ámbar consistente con los modales de
   acceso privado). */
.rc__private-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0;
  background: rgba(42,32,6,.75); border: 1px solid rgba(255,194,0,.4);
  color: #ffc200; font-size: 7px;
}

/* Ícono "Ver Top de Compras" — mismo patrón circular que .rc__private-icon,
   tono dorado (mismo acento que el bloque de premio) para distinguirlo del
   ámbar de privacidad. Ligeramente más grande que el candado: es un botón
   real (clickeable), no solo un indicador informativo. */
.rc__rank-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;
  background: rgba(42,32,6,.75); border: 1px solid rgba(255,215,90,.4);
  color: #ffd75a; font-size: 8px;
  cursor: pointer;
  transition: background .2s ease, border-color .2s ease, transform .2s ease;
}
.rc__rank-icon:hover {
  background: rgba(255,187,32,.18);
  border-color: rgba(255,215,90,.65);
  transform: scale(1.1);
}

/* badge único — ya no centrado absoluto, vive en el flujo de .rc__top-row-right;
   fusiona estado + countdown para no apilar dos pastillas */
.rc__status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 9.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  white-space: nowrap; backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0,0,0,.4);
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

/* Badge del timer — mismo alto/padding/tamaño de texto que el badge de
   estado (antes tenía padding y font-size mayores, así que ambos badges se
   veían con distinta altura al alternar entre salas con/sin countdown
   activo dentro de la misma grilla). Semáforo por tiempo restante vía las
   clases --ok/--warning/--urgent de abajo. */
.rc__status-badge--timer i.far { font-size: 8.5px; }
.rc__status-badge--timer b { font-size: 9.5px; letter-spacing: .3px; }

/* Verde: sorteo tranquilo, >10 minutos */
.rc__status-badge--ok.rc__status-badge--timer {
  animation: timerGlowOk 2.6s ease-in-out infinite;
}
@keyframes timerGlowOk {
  0%, 100% { box-shadow: 0 2px 10px rgba(0,0,0,.4); }
  50%      { box-shadow: 0 2px 10px rgba(0,0,0,.4), 0 0 12px rgba(42,245,152,.22); }
}

/* Amarillo: 2-10 minutos restantes */
.rc__status-badge--warning {
  color: #ffcf4d;
  background: rgba(42,32,6,.75);
  border-color: rgba(255,207,77,.4);
  animation: timerGlowWarning 1.8s ease-in-out infinite;
}
.rc__status-badge--warning b { color: #ffcf4d; text-shadow: 0 0 8px rgba(255,207,77,.4); }
@keyframes timerGlowWarning {
  0%, 100% { box-shadow: 0 2px 10px rgba(0,0,0,.4); }
  50%      { box-shadow: 0 2px 14px rgba(255,207,77,.35); }
}

/* Rojo: <2 minutos restantes — máxima urgencia */
.rc__status-badge--urgent {
  color: #ff5a5a;
  background: rgba(42,8,8,.75);
  border-color: rgba(255,90,90,.45);
  animation: urgentPulse 1.1s ease-in-out infinite;
}
.rc__status-badge--urgent b { color: #ff5a5a; text-shadow: 0 0 8px rgba(255,90,90,.5); }
@keyframes urgentPulse {
  0%, 100% { box-shadow: 0 2px 10px rgba(0,0,0,.4); }
  50%      { box-shadow: 0 2px 18px rgba(255,90,90,.5); }
}
</style>
