<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
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

const emit = defineEmits<{
  (e: 'select', roomId: string): void
  (e: 'close'): void
  (e: 'increment'): void
  (e: 'decrement'): void
  (e: 'set-ctns', value: number): void
  (e: 'purchase'): void
}>()

const isAvailable  = computed(() => props.room.status_code === 'ACTIVE')
const isInProgress = computed(() => props.room.status_code === 'IN_PROGRESS')
const isPublic     = computed(() => props.room.room_type === 'PUBLIC')
const playerCtns   = computed(() => props.room.players_room?.[0]?.ctns ?? 0)
const hasTime      = computed(() => props.countdown && !props.countdown.expired)
const animDelay    = computed(() => `${(props.index ?? 0) * 70}ms`)
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

function handleClick() {
  if (!props.loading) emit('select', props.room.id)
}
</script>

<template>
  <div
    class="rc"
    :class="{
      'rc--active':   isAvailable,
      'rc--live':     isInProgress && playerCtns === 0,
      'rc--enter':    isInProgress && playerCtns > 0,
      'rc--off':      !isAvailable && !isInProgress,
    }"
    :style="{ '--d': animDelay }"
    @click="handleClick"
  >
    <!-- glow ring -->
    <div class="rc__ring" />

    <!-- ── IMAGE ── -->
    <div class="rc__img-wrap">
      <img :src="bingoRoomImg" alt="" class="rc__img" />
      <div class="rc__img-fade" />

      <!-- timer pill -->
      <div class="rc__timer" :class="{ 'rc__timer--live': !hasTime }">
        <template v-if="hasTime && countdown">
          <span v-if="countdown.time.days > 0" class="rc__t-seg">{{ countdown.time.days }}<small>d</small></span>
          <span v-if="countdown.time.days > 0" class="rc__t-sep">:</span>
          <span v-if="countdown.time.hours > 0" class="rc__t-seg">{{ String(countdown.time.hours).padStart(2,'0') }}<small>h</small></span>
          <span v-if="countdown.time.hours > 0" class="rc__t-sep">:</span>
          <span class="rc__t-seg">{{ String(countdown.time.minutes).padStart(2,'0') }}<small>m</small></span>
          <span class="rc__t-sep">:</span>
          <span class="rc__t-seg">{{ String(countdown.time.seconds).padStart(2,'0') }}<small>s</small></span>
        </template>
        <template v-else>
          <span class="rc__dot" />LIVE
        </template>
      </div>

      <!-- type chip -->
      <span class="rc__type" :class="isPublic ? 'rc__type--pub' : 'rc__type--prv'">
        <i :class="isPublic ? 'fas fa-globe-americas' : 'fas fa-lock'" />
        {{ isPublic ? 'Pública' : 'Privada' }}
      </span>

      <!-- my-tickets badge -->
      <span v-if="playerCtns > 0" class="rc__tickets">
        <i class="fas fa-ticket-alt" />{{ playerCtns }}
      </span>
    </div>

    <!-- ── BODY ── -->
    <div class="rc__body">

      <!-- mode row -->
      <div class="rc__mode">
        <i class="fas fa-gamepad" />
        {{ gameModeLabel }}
      </div>

      <!-- prize -->
      <div class="rc__prize">
        <span class="rc__prize-lbl">PREMIO</span>
        <div class="rc__prize-val">
          <span class="rc__prize-num">{{ prizeFormatted }}</span>
          <span class="rc__prize-sym">{{ sym }}</span>
        </div>
      </div>

      <!-- meta row -->
      <div class="rc__meta">
        <span class="rc__meta-item">
          <i class="fas fa-tag" />{{ cardPriceFormatted }} {{ sym }}
        </span>
        <span class="rc__meta-item">
          <i class="fas fa-user-friends" />{{ room.total_players }}
        </span>
      </div>

      <!-- cta -->
      <div class="rc__cta">
        <template v-if="isAvailable">
          <i class="fas fa-eye" /> Ver Sala
        </template>
        <template v-else-if="isInProgress && playerCtns > 0">
          <i class="fas fa-sign-in-alt" /> Entrar
        </template>
        <template v-else-if="isInProgress">
          <i class="fas fa-circle" style="font-size:7px;vertical-align:middle;" /> En Juego
        </template>
        <template v-else>
          No disponible
        </template>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* ─── tokens ─── */
:root {
  --c-bg1:   #0b1220;
  --c-bg2:   #060d1c;
  --c-green: #2af598;
  --c-blue:  #009efd;
  --c-purple:#7b4fff;
  --c-gold:  #ffc336;
  --c-red:   #ff4d6d;
  --c-text:  #cdd9f5;
  --c-muted: #4e6491;
}

/* ─── wrapper / card ─── */
.rc {
  --d: 0ms;
  position: relative;
  width: 220px;
  border-radius: 18px;
  overflow: visible;
  cursor: pointer;
  background: linear-gradient(155deg, #0e1830 0%, #070d1e 100%);
  border: 1px solid rgba(255,255,255,0.07);
  box-shadow: 0 6px 28px rgba(0,0,0,0.55);
  transition: transform .26s cubic-bezier(.22,.68,0,1.18), box-shadow .26s ease;
  animation: rcUp .5s ease both;
  animation-delay: var(--d);
}

@keyframes rcUp {
  from { opacity:0; transform:translateY(22px) scale(.96); }
  to   { opacity:1; transform:translateY(0)    scale(1);   }
}

/* hover por estado */
.rc--active:hover {
  transform: translateY(-8px) scale(1.025);
  box-shadow: 0 20px 50px rgba(42,245,152,.18), 0 0 0 1px rgba(42,245,152,.22);
}
.rc--live:hover   { transform: translateY(-5px); }
.rc--enter {
  border-color: rgba(42,245,152,.45);
  box-shadow: 0 6px 28px rgba(0,0,0,.55), 0 0 18px rgba(42,245,152,.2);
  animation: rcUp .5s ease both, enterPulse 2.8s ease-in-out infinite;
  animation-delay: var(--d), 0s;
}
.rc--off  { opacity:.5; cursor:default; filter:saturate(.4); }

@keyframes enterPulse {
  0%,100% { box-shadow:0 6px 28px rgba(0,0,0,.55), 0 0 18px rgba(42,245,152,.2); }
  50%     { box-shadow:0 8px 34px rgba(0,0,0,.55), 0 0 30px rgba(42,245,152,.38); }
}

/* glow ring on hover */
.rc__ring {
  position:absolute; inset:-1px; border-radius:19px;
  background: linear-gradient(135deg, var(--c-green), var(--c-blue), var(--c-purple));
  opacity:0; z-index:-1; filter:blur(6px);
  transition:opacity .28s;
}
.rc--active:hover .rc__ring { opacity:.45; }

/* ─── image ─── */
.rc__img-wrap {
  position:relative;
  border-radius:18px 18px 0 0;
  overflow:hidden;
  height:130px;
}
.rc__img {
  width:100%; height:100%; object-fit:cover; display:block;
  transition:transform .4s ease;
}
.rc--active:hover .rc__img { transform:scale(1.07); }

.rc__img-fade {
  position:absolute; inset:0;
  background:linear-gradient(180deg,
    rgba(5,10,28,.05) 0%,
    rgba(5,10,28,.68) 100%);
}

/* timer */
.rc__timer {
  position:absolute; top:10px; left:50%; transform:translateX(-50%);
  background:rgba(6,10,26,.88);
  border:1px solid rgba(255,255,255,.13);
  backdrop-filter:blur(8px);
  border-radius:24px; padding:4px 12px;
  display:flex; align-items:center; gap:3px;
  font-size:12px; font-weight:700; color:#c8d8ff;
  letter-spacing:.5px; white-space:nowrap;
  font-family:'Anton',sans-serif;
}
.rc__timer--live { border-color:rgba(255,77,109,.5); color:#ff6b8a; }
.rc__dot {
  width:6px; height:6px; border-radius:50%;
  background:#ff4d6d; box-shadow:0 0 5px #ff4d6d;
  margin-right:4px;
  animation:blink 1s infinite;
}
@keyframes blink { 0%,100%{opacity:1}50%{opacity:.25} }

.rc__t-seg { display:inline-flex; align-items:baseline; gap:1px; }
.rc__t-seg small { font-size:8px; color:#6880aa; margin-left:1px; }
.rc__t-sep { color:#3a4e70; margin:0 1px; }

/* type chip */
.rc__type {
  position:absolute; bottom:10px; left:10px;
  font-size:10px; font-weight:700; letter-spacing:.4px;
  padding:3px 9px; border-radius:20px;
  display:flex; align-items:center; gap:4px;
}
.rc__type--pub {
  background:rgba(42,245,152,.14);
  border:1px solid rgba(42,245,152,.32);
  color:#2af598;
}
.rc__type--prv {
  background:rgba(255,195,54,.12);
  border:1px solid rgba(255,195,54,.3);
  color:#ffc336;
}

/* my tickets */
.rc__tickets {
  position:absolute; bottom:10px; right:10px;
  background:rgba(0,74,204,.85);
  border:1px solid rgba(100,160,255,.3);
  color:#fff; font-size:10px; font-weight:700;
  padding:3px 9px; border-radius:20px;
  display:flex; align-items:center; gap:4px;
}

/* ─── body ─── */
.rc__body {
  padding:13px 14px 0;
  display:flex; flex-direction:column; gap:8px;
}

/* mode */
.rc__mode {
  font-size:10px; color:#4e6491; letter-spacing:.5px;
  text-transform:uppercase; font-weight:600;
  display:flex; align-items:center; gap:5px;
}
.rc__mode i { color:#3a5580; font-size:9px; }

/* prize */
.rc__prize { text-align:center; }
.rc__prize-lbl {
  display:block; font-size:9px; letter-spacing:1.5px;
  color:#4e6491; text-transform:uppercase; margin-bottom:2px;
}
.rc__prize-val {
  display:flex; align-items:baseline;
  justify-content:center; gap:5px;
}
.rc__prize-num {
  font-family:'Anton',sans-serif; font-size:32px; line-height:1;
  background:linear-gradient(135deg,#ffd166,#ff9f1c);
  -webkit-background-clip:text; -webkit-text-fill-color:transparent;
  background-clip:text;
  filter:drop-shadow(0 2px 8px rgba(255,165,0,.35));
}
.rc__prize-sym {
  font-size:13px; font-weight:700;
  color:#ffc336; -webkit-text-fill-color:#ffc336;
}

/* meta */
.rc__meta {
  display:flex; justify-content:space-between;
  padding:8px 2px;
  border-top:1px solid rgba(255,255,255,.05);
}
.rc__meta-item {
  font-size:11px; color:#4e6491;
  display:flex; align-items:center; gap:4px;
}
.rc__meta-item i { font-size:10px; color:#2f4468; }

/* cta */
.rc__cta {
  margin:0 -14px;
  padding:11px 0;
  text-align:center;
  font-size:11px; font-weight:800; letter-spacing:1.4px;
  text-transform:uppercase;
  border-radius:0 0 18px 18px;
  display:flex; align-items:center; justify-content:center; gap:7px;
  position:relative; overflow:hidden;
  transition:filter .2s;
}
/* shimmer sweep */
.rc__cta::after {
  content:'';
  position:absolute; top:0; left:-80%; width:50%; height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.22),transparent);
  transform:skewX(-18deg);
  transition:left .45s ease;
}
.rc--active:hover .rc__cta::after { left:130%; }

.rc--active   .rc__cta {
  background:linear-gradient(90deg,#2af598,#009efd);
  color:#031520;
}
.rc--live     .rc__cta {
  background:linear-gradient(90deg,#ff4d6d,#ff6b2b);
  color:#fff;
}
.rc--enter    .rc__cta {
  background:linear-gradient(90deg,#2af598,#00c9ff);
  color:#021a10;
  font-weight:900;
}
.rc--off      .rc__cta {
  background:rgba(255,255,255,.04);
  color:#2f3f5a;
}
</style>
