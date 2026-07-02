<script setup lang="ts">
import { computed } from 'vue'
import { BingoService } from '@js/Services/bingoService'
import type { WsStatus } from '@js/composables/games/bingo/room/lobby/useLobbyEcho'
import type { LobbyRoom, LobbyLiveState } from './types'

const props = defineProps<{
  room: LobbyRoom
  live: LobbyLiveState
  wsStatus: WsStatus
  countdown: string
  isLive: boolean
  isUrgent: boolean
  prizeFlash: boolean
  rate: number
}>()

const bingoRoomImg = '/img/games/bingo/estelar_fd.png'

const isActiveAndConnected = computed(() =>
  props.wsStatus === 'connected' &&
  props.live.statusCode?.toUpperCase() === 'ACTIVE'
)

const wsLabel = computed(() => {
  if (props.wsStatus === 'connected') {
    return props.live.statusCode?.toUpperCase() === 'ACTIVE' ? 'En vivo' : 'Conectado'
  }
  return ({
    connecting:   'Conectando…',
    disconnected: 'Sin conexión',
    error:        'Error',
  } as Record<string, string>)[props.wsStatus] ?? props.wsStatus
})

const fmt = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatAmount = computed(() => (val: number) => fmt.format(val * props.rate))

const formattedPrize = computed(() => formatAmount.value(props.live.prizePool))

const statusClass = computed(() => {
  const c = (props.live.statusCode || 'desconocido').toLowerCase()
  return `lby-status--${c}`
})

const roomStatusText = computed(() =>
  BingoService.getRoomStatusText(props.live.statusCode)
)
</script>

<template>
  <div class="lby-hero">
    <div class="lby-hero__clip">
      <img :src="bingoRoomImg" alt="Sala" class="lby-hero__img" />
      <div class="lby-hero__overlay" />

      <!-- Nav: chips + WS badge -->
      <div class="lby-hero__nav">
        <div class="lby-hero__chips">
          <span class="lby-chip lby-chip--type">
            <i class="fas fa-globe" /> {{ room.room_type_description ?? 'Pública' }}
          </span>
          <span class="lby-chip lby-chip--mode">
            <i class="fas fa-gamepad" /> {{ room.game_mode_description ?? 'Online' }}
          </span>
          <span v-if="room.rounds" class="lby-chip lby-chip--rounds">
            <i class="fas fa-rotate" /> {{ room.rounds }}R
          </span>
          <span v-if="room.game_variant" class="lby-chip lby-chip--variant">
            {{ room.game_variant }}
          </span>
        </div>

        <div
          v-if="isActiveAndConnected"
          class="lby-ws lby-ws--connected lby-ws--live-active"
        >
          <span class="lby-ws__dot" />
          <span class="lby-ws__label">En vivo</span>
          <span class="lby-ws__sep">·</span>
          <span class="lby-ws__status">Disponible</span>
        </div>
        <div v-else class="lby-ws" :class="`lby-ws--${wsStatus}`">
          <span class="lby-ws__dot" />
          <span class="lby-ws__label">{{ wsLabel }}</span>
        </div>
      </div>

      <!-- Bottom: premio + timer + sub-premios -->
      <div class="lby-hero__bottom">
        <div class="lby-prize" :class="{ 'lby-flash': prizeFlash }">
          <span class="lby-prize__lbl"><i class="fas fa-trophy" /> Premio Acumulado</span>
          <div class="lby-prize__val">
            <span class="lby-prize__num">{{ formattedPrize }}</span>
            <span class="lby-prize__sym">{{ live.currencySymbol }}</span>
          </div>
        </div>

        <div class="lby-hero__right">
          <div
            v-if="countdown && !isLive"
            class="lby-timer"
            :class="{ 'lby-timer--urgent': isUrgent }"
          >
            <i class="fas fa-clock" /> {{ countdown }}
          </div>
          <span
            v-if="!isActiveAndConnected"
            class="lby-status-chip"
            :class="statusClass"
          >{{ roomStatusText }}</span>

          <div v-if="room.full_house_prize || room.serial_prize" class="lby-prize-sub">
            <div v-if="room.full_house_prize" class="lby-prize-sub__item">
              <span class="lby-prize-sub__lbl">Casa Llena</span>
              <span class="lby-prize-sub__val">
                {{ formatAmount.value(Number(room.full_house_prize)) }}
                <em class="lby-prize-sub__sym">{{ live.currencySymbol }}</em>
              </span>
            </div>
            <div v-if="room.serial_prize" class="lby-prize-sub__item">
              <span class="lby-prize-sub__lbl">Serial</span>
              <span class="lby-prize-sub__val">
                {{ formatAmount.value(Number(room.serial_prize)) }}
                <em class="lby-prize-sub__sym">{{ live.currencySymbol }}</em>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.lby-hero {
  position: relative; border-radius: 16px;
  border: 1px solid rgba(255,255,255,.07);
  box-shadow: 0 12px 40px rgba(0,0,0,.6), 0 1px 0 rgba(255,255,255,.05) inset;
  min-height: 170px;
  transition: box-shadow .3s, border-color .3s;
}
.lby-hero:hover {
  border-color: rgba(42,245,152,.18);
  box-shadow: 0 18px 52px rgba(0,0,0,.7), 0 0 32px rgba(42,245,152,.06);
}
.lby-hero__clip {
  position: relative; border-radius: inherit; overflow: hidden;
  min-height: inherit; height: 100%;
  display: flex; flex-direction: column; justify-content: space-between;
}
.lby-hero__img {
  position: absolute; inset: 0;
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .7s ease;
}
.lby-hero:hover .lby-hero__img { transform: scale(1.04); }
.lby-hero__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg,
    rgba(4,8,20,.70) 0%, rgba(4,8,20,.20) 30%,
    rgba(4,8,20,.82) 68%, rgba(4,8,20,.98) 100%);
}

.lby-hero__nav {
  position: relative; z-index: 5;
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  padding: 10px 14px 0;
}
.lby-hero__chips { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }

.lby-chip {
  padding: 2px 9px; border-radius: 5px;
  font-size: 9px; font-weight: 700; letter-spacing: .3px;
  display: inline-flex; align-items: center; gap: 4px;
  white-space: nowrap; backdrop-filter: blur(10px);
}
.lby-chip i { font-size: 8px; }
.lby-chip--type    { background: rgba(40,110,190,.18); border: 1px solid rgba(40,110,190,.35); color: #6aaad8; }
.lby-chip--mode    { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); color: #9fb3da; }
.lby-chip--rounds  { background: rgba(130,60,220,.15); border: 1px solid rgba(130,60,220,.32); color: #a878e8; }
.lby-chip--variant { background: rgba(255,150,0,.1); border: 1px solid rgba(255,150,0,.25); color: #e09020; }

.lby-ws {
  display: inline-flex; align-items: center; gap: 5px; flex-shrink: 0;
  padding: 3px 11px; border-radius: 20px;
  font-size: 10px; font-weight: 700;
  background: rgba(4,8,20,.6); border: 1px solid rgba(255,255,255,.12);
  backdrop-filter: blur(10px);
}
.lby-ws__dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.lby-ws__label { color: #3a5068; }
.lby-ws--connected   .lby-ws__dot   { background: #2af598; box-shadow: 0 0 5px rgba(42,245,152,.7); animation: lbyDotPulse 2s infinite; }
.lby-ws--connected   .lby-ws__label { color: #2af598; }
.lby-ws--connecting  .lby-ws__dot   { background: #ffc336; animation: lbyDotBlink .8s infinite; }
.lby-ws--connecting  .lby-ws__label { color: #ffc336; }
.lby-ws--disconnected .lby-ws__dot, .lby-ws--error .lby-ws__dot { background: #ff4d6d; }
.lby-ws--disconnected .lby-ws__label, .lby-ws--error .lby-ws__label { color: #ff4d6d; }
@keyframes lbyDotPulse { 0%,100%{opacity:1} 50%{opacity:.3} }
@keyframes lbyDotBlink  { 0%,100%{opacity:1} 50%{opacity:.1} }
.lby-ws--live-active {
  background: rgba(4,18,10,.7); border-color: rgba(42,245,152,.38);
  animation: lbyNavBadgePulse 3s ease-in-out infinite;
}
.lby-ws__sep    { color: rgba(255,255,255,.2); font-size: 8px; margin: 0 1px; }
.lby-ws__status { color: #9ecaf0; font-size: 9.5px; font-weight: 600; }
@keyframes lbyNavBadgePulse {
  0%,100% { box-shadow: 0 0 6px rgba(42,245,152,.1); border-color: rgba(42,245,152,.3); }
  50%     { box-shadow: 0 0 14px rgba(42,245,152,.3); border-color: rgba(42,245,152,.55); }
}

.lby-hero__bottom {
  position: relative; z-index: 5;
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 10px; flex-wrap: wrap; padding: 8px 14px 12px;
}
.lby-hero__right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }

.lby-prize { display: flex; flex-direction: column; min-width: 0; }
.lby-prize__lbl {
  font-size: 8.5px; color: #b8901c; text-transform: uppercase; letter-spacing: 1.8px;
  display: inline-flex; align-items: center; gap: 4px; margin-bottom: 2px;
}
.lby-prize__val { display: flex; align-items: baseline; gap: 6px; }
.lby-prize__num {
  font-family: 'Anton', sans-serif;
  font-size: clamp(22px, 3.2vw, 34px); line-height: 1; letter-spacing: .3px;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 12px rgba(255,150,0,.35));
}
.lby-prize__sym { font-size: 14px; font-weight: 800; color: #d09010; }

.lby-prize-sub { display: flex; gap: 14px; }
.lby-prize-sub__item { text-align: right; }
.lby-prize-sub__lbl { display: block; font-size: 7px; color: #3a5068; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 1px; }
.lby-prize-sub__val { font-family: 'Anton', sans-serif; font-size: 12px; color: #b0c4e0; }
.lby-prize-sub__sym { font-style: normal; font-size: 9px; font-weight: 700; color: #6a8090; margin-left: 2px; }

.lby-timer {
  background: rgba(4,8,20,.85); border: 1px solid rgba(255,255,255,.1);
  backdrop-filter: blur(10px); color: #9ecaf0;
  border-radius: 30px; padding: 3px 13px;
  font-family: 'Anton', sans-serif; font-size: 14px; letter-spacing: 1px;
  display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
}
.lby-timer i { font-size: 9px; opacity: .55; }
.lby-timer--urgent { border-color: rgba(255,77,109,.4); color: #ff8aaa; animation: lbyPulseUrgent 1.2s infinite; }
@keyframes lbyPulseUrgent { 0%,100%{box-shadow:none} 50%{box-shadow:0 0 0 4px rgba(255,77,109,.12)} }

.lby-status-chip {
  padding: 3px 12px; border-radius: 20px;
  font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
  white-space: nowrap; backdrop-filter: blur(8px);
}
.lby-status--active      { background: rgba(42,245,152,.1);  border: 1px solid rgba(42,245,152,.3);  color: #2af598; }
.lby-status--in_progress { background: rgba(255,77,109,.1);  border: 1px solid rgba(255,77,109,.3);  color: #ff6e8a; }
.lby-status--pending     { background: rgba(255,195,54,.08); border: 1px solid rgba(255,195,54,.25); color: #ffc336; }
.lby-status--finished    { background: rgba(255,77,109,.06); border: 1px solid rgba(255,77,109,.18); color: #a04060; }
.lby-status--offline,
.lby-status--desconocido { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); color: #3a5068; }

.lby-flash { animation: lbyFlash 1.8s ease-in-out; }
@keyframes lbyFlash { 0%,100%{filter:none} 40%{filter:drop-shadow(0 0 12px rgba(42,245,152,.7)) brightness(1.2)} }

@media (max-width: 640px) {
  .lby-hero__bottom { align-items: flex-start; flex-direction: column; gap: 10px; }
  .lby-hero__right  { align-items: flex-start; }
}
@media (max-width: 480px) {
  .lby-hero__nav    { padding: 10px 12px 0; }
  .lby-hero__bottom { padding: 10px 12px 14px; }
  .lby-prize__num   { font-size: 28px; }
}
</style>
