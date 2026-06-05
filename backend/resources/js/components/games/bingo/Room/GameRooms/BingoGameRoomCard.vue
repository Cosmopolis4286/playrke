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
const animDelay    = computed(() => `${(props.index ?? 0) * 90}ms`)
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

// Countdown siempre muestra MM:SS, con horas/días si aplica
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
    <!-- Acento top -->
    <div class="rc__accent" />

    <div class="rc__inner">

      <!-- ── Thumbnail ── -->
      <div class="rc__thumb">
        <img :src="bingoRoomImg" alt="" class="rc__img" />
        <div class="rc__img-veil" />
        <span class="rc__dot"
          :class="isAvailable ? 'rc__dot--ok' : isInProgress ? 'rc__dot--live' : 'rc__dot--dim'"
        />
      </div>

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- Fila A: título + modo | estado + timer -->
        <div class="rc__row rc__row--a">
          <div>
            <p class="rc__name">Bingo {{ isPublic ? 'Público' : 'Privado' }}</p>
            <p class="rc__mode"><i class="fas fa-gamepad" /> {{ gameModeLabel }}</p>
          </div>
          <div class="rc__col-right">
            <span class="rc__badge"
              :class="isAvailable?'rc__badge--ok':isInProgress?'rc__badge--live':'rc__badge--off'"
            >
              <span v-if="isInProgress" class="rc__blink" />
              {{ isAvailable ? 'Disponible' : isInProgress ? 'En Juego' : 'Cerrada' }}
            </span>
            <div class="rc__timer" :class="{'rc__timer--live':!hasTime}">
              <template v-if="hasTime && timerStr">
                <i class="far fa-clock" style="font-family:'Font Awesome 6 Free';font-size:9px;opacity:.55;" />
                <span class="rc__timer-val">{{ timerStr }}</span>
              </template>
              <template v-else>
                <span class="rc__blink" /> LIVE
              </template>
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="rc__sep" />

        <!-- Fila B: premio | stats -->
        <div class="rc__row rc__row--b">

          <!-- Premio -->
          <div class="rc__prize">
            <span class="rc__prize-lbl">PREMIO</span>
            <div class="rc__prize-val">
              <span class="rc__prize-num">{{ prizeFormatted }}</span>
              <span class="rc__prize-sym">{{ sym }}</span>
            </div>
          </div>

          <!-- Stats -->
          <div class="rc__stats">
            <span class="rc__stat">
              <i class="fas fa-tag" />
              <span>{{ cardPriceFormatted }} {{ sym }}</span>
              <em>c/cartón</em>
            </span>
            <span class="rc__stat">
              <i class="fas fa-users" />
              <span>{{ room.total_players }}</span>
              <em>jugadores</em>
            </span>
            <span v-if="playerCtns > 0" class="rc__stat rc__stat--ctns">
              <i class="fas fa-ticket-alt" />
              <span>{{ playerCtns }}</span>
              <em>mis ctns</em>
            </span>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ════════════════════════════════
   CARD HORIZONTAL — full width, responsive
════════════════════════════════ */
.rc {
  --d: 0ms;
  position: relative;
  width: 100%;
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(145deg, #101e3e 0%, #08102a 100%);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 4px 24px rgba(0,0,0,0.5);
  transition: transform .22s cubic-bezier(.22,.68,0,1.2),
              box-shadow .22s ease, border-color .22s ease;
  animation: rcIn .42s ease both;
  animation-delay: var(--d);
}
@keyframes rcIn {
  from { opacity:0; transform:translateY(8px); }
  to   { opacity:1; transform:translateY(0);   }
}
.rc--active:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 36px rgba(42,245,152,.12), 0 0 0 1px rgba(42,245,152,.22);
  border-color: rgba(42,245,152,.2);
}
.rc--off { opacity:.32; cursor:default; filter:saturate(.18); }

/* ── Acento top ── */
.rc__accent { height: 2px; width: 100%; flex-shrink: 0; }
.rc--active .rc__accent { background: linear-gradient(90deg,#2af598 0%,#009efd 45%,transparent 90%); }
.rc--live   .rc__accent { background: linear-gradient(90deg,#ff4d6d 0%,#ff8c42 45%,transparent 90%); }
.rc--off    .rc__accent { background: rgba(255,255,255,.04); }

/* ── Inner flex ── */
.rc__inner { display: flex; align-items: stretch; min-height: 90px; }

/* ── Thumbnail ── */
.rc__thumb {
  position: relative;
  flex: 0 0 90px;
  overflow: hidden;
}
.rc__img {
  width: 100%; height: 100%;
  object-fit: cover; display: block;
  transition: transform .4s ease;
}
.rc--active:hover .rc__img { transform: scale(1.06); }
.rc__img-veil {
  position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent 30%, rgba(8,16,42,.88) 100%);
}

/* status dot */
.rc__dot {
  position: absolute; bottom: 8px; left: 8px;
  width: 7px; height: 7px; border-radius: 50%;
  border: 1.5px solid rgba(8,16,42,.9);
}
.rc__dot--ok   { background:#2af598; box-shadow:0 0 6px #2af598; animation:dp 2s infinite; }
.rc__dot--live { background:#ff4d6d; box-shadow:0 0 6px #ff4d6d; animation:dp 1.1s infinite; }
.rc__dot--dim  { background:#1a2d4a; }
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.2} }

/* ── Body ── */
.rc__body {
  flex: 1;
  min-width: 0;
  padding: 10px 12px 10px 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 6px;
}

/* ── Fila A: nombre + modo | badge + timer ── */
.rc__row { display:flex; align-items:flex-start; justify-content:space-between; gap:6px; }

.rc__name {
  font-size: 12px; font-weight: 700;
  color: #ccdaff; margin: 0 0 2px; line-height: 1.1;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__mode {
  font-size: 9.5px; color: #3e5878; margin: 0;
  display: flex; align-items: center; gap: 3px;
}
.rc__mode i { font-size: 8px; }

.rc__col-right {
  display: flex; flex-direction: column;
  align-items: flex-end; gap: 4px;
  flex-shrink: 0;
}

/* badge estado */
.rc__badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 2px 7px; border-radius: 8px;
  font-size: 8.5px; font-weight: 700; letter-spacing: .5px;
  text-transform: uppercase; white-space: nowrap;
}
.rc__badge--ok   { background:rgba(42,245,152,.13); border:1px solid rgba(42,245,152,.28); color:#2af598; }
.rc__badge--live { background:rgba(255,77,109,.11);  border:1px solid rgba(255,77,109,.26); color:#ff6e8a; }
.rc__badge--off  { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); color:#2e4260; }

/* timer */
.rc__timer {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 12px; color: #90b0d8; white-space: nowrap;
}
.rc__timer-val { font-family:'Anton',sans-serif; letter-spacing:.4px; }
.rc__timer--live { color: #ff6e8a; font-size: 10px; }

.rc__blink {
  display: inline-block; width: 5px; height: 5px; border-radius: 50%;
  background: currentColor; animation: bk 1s infinite; vertical-align: middle;
}
@keyframes bk { 0%,100%{opacity:1} 50%{opacity:.1} }

/* ── Separador ── */
.rc__sep {
  height: 1px;
  background: linear-gradient(90deg, rgba(80,120,200,.15), transparent);
}

/* ── Fila B: premio + stats ── */
.rc__row--b { align-items: center; flex-wrap: nowrap; }

.rc__prize { display: flex; flex-direction: column; gap: 1px; flex-shrink: 0; }
.rc__prize-lbl {
  font-size: 7.5px; color: #2e4260;
  text-transform: uppercase; letter-spacing: 1.1px;
}
.rc__prize-val { display: flex; align-items: baseline; gap: 3px; }
.rc__prize-num {
  font-family: 'Anton', sans-serif; font-size: 22px; line-height: 1;
  background: linear-gradient(135deg,#ffd166,#ff9f1c);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 2px 5px rgba(255,155,0,.26));
}
.rc__prize-sym {
  font-size: 10px; font-weight: 700;
  color: #ffc336; -webkit-text-fill-color: #ffc336;
}

/* stats */
.rc__stats {
  display: flex; flex-direction: column; gap: 3px;
  margin-left: auto; padding-left: 8px;
  align-items: flex-end;
}
.rc__stat {
  display: flex; align-items: center; gap: 4px;
  font-size: 9.5px;
}
.rc__stat i    { font-size: 8px; color: #243654; width: 9px; text-align: center; }
.rc__stat span { color: #607090; font-weight: 600; }
.rc__stat em   { font-size: 7.5px; color: #2e4260; font-style: normal; }
.rc__stat--ctns i    { color: #6040b0; }
.rc__stat--ctns span { color: #9070d8; }
</style>
