<script setup lang="ts">
import { computed } from 'vue'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@/js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import type { BingoGameRoomDetail } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

const props = defineProps<{
  room: BingoGameRoom
  exchangeRate: number
  currencySym: string
  countdown: RoomCountdown | undefined
  isPurchasing: boolean
  roomDetail: BingoGameRoomDetail | null
  numCtns: number
  totalAmount: number
  prizePoolLabel: string
  loading: boolean
  index?: number
}>()

const emit = defineEmits<{ (e: 'select', roomId: string): void }>()

const isAvailable  = computed(() => props.room.status_code === 'ACTIVE')
const isInProgress = computed(() => props.room.status_code === 'IN_PROGRESS')
const isPublic     = computed(() => props.room.room_type === 'PUBLIC')
const playerCtns   = computed(() => props.room.players_room?.[0]?.ctns ?? 0)
const hasTime      = computed(() => props.countdown && !props.countdown.expired)
const animDelay    = computed(() => `${(props.index ?? 0) * 80}ms`)
const bingoRoomImg = '/img/games/bingo/estelar_fd.png'

const prizeFormatted = computed(() =>
  (props.room.prize_pool * props.exchangeRate).toFixed(2)
)
const cardPriceFormatted = computed(() => {
  if (props.room.card_price != null) return props.room.card_price.toFixed(2)
  return ((props.room.config?.card_price_usd ?? 0) * props.exchangeRate).toFixed(2)
})
const sym = computed(() => props.room.currency_symbol ?? props.currencySym)

const gameModeLabel = computed(() => {
  const m: Record<string, string> = { ONLINE: 'Online', OFFLINE: 'Presencial', MIXED: 'Mixto' }
  return m[props.room.game_mode ?? ''] ?? props.room.game_mode ?? ''
})

const timerStr = computed(() => {
  if (!hasTime.value || !props.countdown) return null
  const { days, hours, minutes, seconds } = props.countdown.time
  const ss = String(seconds).padStart(2, '0')
  const mm = String(minutes).padStart(2, '0')
  if (days > 0) return `${days}d ${String(hours).padStart(2,'0')}h ${mm}m`
  if (hours > 0) return `${String(hours).padStart(2,'0')}:${mm}:${ss}`
  return `${mm}:${ss}`
})

function handleClick() {
  if (!props.loading) emit('select', props.room.id)
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
    @click="handleClick"
  >
    <div class="rc__glow" />
    <div class="rc__accent" />
    <div class="rc__shimmer" />

    <div class="rc__inner">

      <!-- ── Thumbnail ── -->
      <div class="rc__thumb">
        <img :src="bingoRoomImg" alt="" class="rc__img" />
        <div class="rc__img-veil" />
      </div>

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- LÍNEA 1: nombre + modo · badge estado · timer -->
        <div class="rc__top">
          <div class="rc__title-block">
            <span class="rc__name">Bingo {{ isPublic ? 'Público' : 'Privado' }}</span>
            <span class="rc__dot-sep">·</span>
            <span class="rc__mode">
              <i class="fas fa-gamepad" />
              {{ gameModeLabel }}
            </span>
          </div>

          <div class="rc__top-right">
            <!-- Badge estado -->
            <span class="rc__badge"
              :class="isAvailable ? 'rc__badge--ok' : isInProgress ? 'rc__badge--live' : 'rc__badge--off'"
            >
              <span class="rc__badge-dot" />
              {{ isAvailable ? 'Disponible' : isInProgress ? 'En Juego' : 'Cerrada' }}
            </span>

            <!-- Timer -->
            <div class="rc__timer" :class="{ 'rc__timer--live': !hasTime }">
              <template v-if="hasTime && timerStr">
                <i class="far fa-clock" style="font-size:8px;opacity:.5;" />
                <span class="rc__timer-val">{{ timerStr }}</span>
              </template>
              <template v-else>
                <span class="rc__blink" />
                <span class="rc__live-txt">LIVE</span>
              </template>
            </div>
          </div>
        </div>

        <!-- LÍNEA 2: premio grande -->
        <div class="rc__prize-row">
          <i class="fas fa-trophy rc__trophy" />
          <span class="rc__prize-num">{{ prizeFormatted }}</span>
          <span class="rc__prize-sym">{{ sym }}</span>
        </div>

        <!-- LÍNEA 3: badges de stats -->
        <div class="rc__pills">
          <span class="rc__pill">
            <i class="fas fa-ticket-alt" />
            {{ cardPriceFormatted }} {{ sym }}
          </span>
          <span class="rc__pill">
            <i class="fas fa-users" />
            {{ room.total_players }} jug.
          </span>
          <span v-if="playerCtns > 0" class="rc__pill rc__pill--mine">
            <i class="fas fa-layer-group" />
            {{ playerCtns }} míos
          </span>
          <span class="rc__pill rc__pill--type">
            <i class="fas" :class="isPublic ? 'fa-globe' : 'fa-lock'" />
            {{ isPublic ? 'Público' : 'Privado' }}
          </span>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Orbitron:wght@700;900&display=swap');

/* ══════════════════════════════
   BASE
══════════════════════════════ */
.rc {
  --d: 0ms;
  --accent: #2af598;
  --glow-clr: rgba(42,245,152,.1);

  position: relative;
  width: 100%;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(135deg, #0e1d3c 0%, #070e20 100%);
  border: 1px solid rgba(255,255,255,.07);
  box-shadow: 0 4px 24px rgba(0,0,0,.5), 0 1px 0 rgba(255,255,255,.04) inset;
  transition: transform .22s cubic-bezier(.22,.68,0,1.2), box-shadow .22s ease, border-color .22s ease;
  animation: rcIn .4s ease both;
  animation-delay: var(--d);
}
@keyframes rcIn {
  from { opacity:0; transform:translateY(8px) scale(.985); }
  to   { opacity:1; transform:translateY(0)   scale(1);    }
}

.rc--live   { --accent: #ff4d6d; --glow-clr: rgba(255,77,109,.1); }
.rc--active:hover {
  transform: translateY(-3px) scale(1.004);
  box-shadow: 0 12px 40px rgba(0,0,0,.6), 0 0 0 1px rgba(42,245,152,.2), 0 0 20px rgba(42,245,152,.08);
  border-color: rgba(42,245,152,.18);
}
.rc--off { opacity:.28; cursor:default; filter:saturate(.12) brightness(.75); }

/* glow fondo */
.rc__glow {
  position:absolute; inset:0; z-index:0; pointer-events:none;
  background: radial-gradient(ellipse 90% 70% at 20% 110%, var(--glow-clr), transparent 65%);
}

/* shimmer */
.rc__shimmer {
  position:absolute; inset:0; z-index:1; pointer-events:none;
  background: linear-gradient(108deg, transparent 35%, rgba(255,255,255,.022) 50%, transparent 65%);
  transform: translateX(-100%);
  transition: transform 0s;
}
.rc--active:hover .rc__shimmer { transform:translateX(100%); transition:transform .5s ease; }

/* acento top */
.rc__accent {
  position:relative; z-index:2;
  height: 2px; width:100%;
}
.rc--active .rc__accent { background:linear-gradient(90deg,#2af598,#009efd 55%,transparent); box-shadow:0 0 10px rgba(42,245,152,.3); }
.rc--live   .rc__accent { background:linear-gradient(90deg,#ff4d6d,#ff8c42 55%,transparent); box-shadow:0 0 10px rgba(255,77,109,.3); }
.rc--off    .rc__accent { background:rgba(255,255,255,.04); }

/* ── Inner ── */
.rc__inner {
  position:relative; z-index:2;
  display:flex; align-items:stretch;
}

/* ══════════════════════════════
   THUMBNAIL — más delgado
══════════════════════════════ */
.rc__thumb {
  position:relative;
  flex: 0 0 78px;
  overflow:hidden;
}
.rc__img {
  width:100%; height:100%;
  object-fit:cover; display:block;
  transition: transform .45s ease;
}
.rc--active:hover .rc__img { transform:scale(1.07); }
.rc__img-veil {
  position:absolute; inset:0;
  background:linear-gradient(90deg, transparent 0%, rgba(7,14,32,.55) 55%, rgba(7,14,32,.95) 100%);
}

/* ══════════════════════════════
   BODY
══════════════════════════════ */
.rc__body {
  flex:1; min-width:0;
  padding: 9px 12px 9px 9px;
  display:flex; flex-direction:column;
  gap: 5px;
}

/* ── Línea 1: título + derecha ── */
.rc__top {
  display:flex; align-items:center;
  justify-content:space-between;
  gap:8px;
}

.rc__title-block {
  display:flex; align-items:center; gap:5px;
  min-width:0; overflow:hidden;
}
.rc__name {
  font-family:'Rajdhani',sans-serif;
  font-size:13px; font-weight:700;
  color:#cddeff; white-space:nowrap;
  letter-spacing:.3px;
}
.rc__dot-sep { color:#1e3050; font-size:10px; flex-shrink:0; }
.rc__mode {
  font-size:9px; color:#2e4468;
  display:flex; align-items:center; gap:3px;
  white-space:nowrap; flex-shrink:0;
}
.rc__mode i { font-size:8px; }

.rc__top-right {
  display:flex; align-items:center; gap:6px; flex-shrink:0;
}

/* Badge estado */
.rc__badge {
  display:inline-flex; align-items:center; gap:4px;
  padding: 2px 7px; border-radius:20px;
  font-size:7.5px; font-weight:700; letter-spacing:.5px;
  text-transform:uppercase; white-space:nowrap;
}
.rc__badge-dot {
  width:4px; height:4px; border-radius:50%;
  background:currentColor; flex-shrink:0;
}
.rc__badge--ok   { background:rgba(42,245,152,.1);  border:1px solid rgba(42,245,152,.28); color:#2af598; }
.rc__badge--live { background:rgba(255,77,109,.1);  border:1px solid rgba(255,77,109,.28); color:#ff6e8a; }
.rc__badge--off  { background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:#253648; }
.rc__badge--ok   .rc__badge-dot { animation:dp 2s infinite; }
.rc__badge--live .rc__badge-dot { animation:dp 1.1s infinite; }
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.1} }

/* Timer */
.rc__timer {
  display:inline-flex; align-items:center; gap:4px;
  color:#7aacdc; white-space:nowrap;
}
.rc__timer-val {
  font-family:'Orbitron',monospace;
  font-size:11px; font-weight:700; letter-spacing:.4px;
  color:#9ec8f0;
}
.rc__timer--live { color:#ff6e8a; }
.rc__live-txt {
  font-family:'Orbitron',monospace;
  font-size:9px; font-weight:700; letter-spacing:1px; color:#ff6e8a;
}
.rc__blink {
  width:5px; height:5px; border-radius:50%;
  background:currentColor; display:inline-block;
  animation:bk 1s infinite;
}
@keyframes bk { 0%,100%{opacity:1} 50%{opacity:.1} }

/* ── Línea 2: premio ── */
.rc__prize-row {
  display:flex; align-items:baseline; gap:5px;
}
.rc__trophy {
  font-size:9px; color:#c8a030;
  filter:drop-shadow(0 0 4px rgba(200,160,48,.35));
  flex-shrink:0;
  align-self:center;
  margin-bottom:1px;
}
.rc__prize-num {
  font-family:'Orbitron','Anton',sans-serif;
  font-size:22px; font-weight:900; line-height:1;
  background:linear-gradient(135deg, #ffe566 0%, #ffb820 55%, #ff8c00 100%);
  -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
  filter:drop-shadow(0 2px 6px rgba(255,155,0,.25));
  letter-spacing:-.5px;
}
.rc__prize-sym {
  font-family:'Rajdhani',sans-serif;
  font-size:12px; font-weight:700;
  color:#e0a020; -webkit-text-fill-color:#e0a020;
}

/* ── Línea 3: pills / badges ── */
.rc__pills {
  display:flex; flex-wrap:wrap; gap:4px; align-items:center;
}

.rc__pill {
  display:inline-flex; align-items:center; gap:4px;
  padding: 2px 7px; border-radius:6px;
  font-size:9px; font-weight:600;
  color:#4a6a90;
  background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.06);
  white-space:nowrap;
  letter-spacing:.1px;
}
.rc__pill i { font-size:7.5px; color:#2e4a6a; }

.rc__pill--mine {
  color:#9060d8;
  background:rgba(120,70,220,.1);
  border-color:rgba(120,70,220,.2);
}
.rc__pill--mine i { color:#7040b8; }

.rc__pill--type {
  color:#2a4860;
  background:rgba(40,80,130,.08);
  border-color:rgba(40,80,130,.15);
  margin-left:auto;
}
.rc__pill--type i { color:#1e3a58; }
</style>
