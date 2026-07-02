<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

interface Props {
  prizePoolType: string
  currencySym: string
  prizeFormatted: string
  prizeFlash: boolean
  hasTime: boolean
  cd: { time: { days: number; hours: number; minutes: number; seconds: number } } | null
  isFinished: boolean
  livePlayers: number
  totalWinners: number
  playersFlash: boolean
  cardCount: number
  rounds?: number
  round?: number
  liveDrawnLength: number
  totalChips: number
  bingoPattern?: string
  muted: boolean
  isLocal: boolean
  devResetting: boolean
}

const props = withDefaults(defineProps<Props>(), {
  rounds: 0,
  round: 1,
  bingoPattern: '',
})

const emit = defineEmits<{
  toggleMute: []
  openPlayers: []
  devReset: []
}>()

function pad(n: number) { return String(n).padStart(2, '0') }

const drawPct = computed(() =>
  props.totalChips > 0 ? Math.round((props.liveDrawnLength / props.totalChips) * 100) : 0
)
</script>

<template>
  <div class="gh-root">
    <!-- Accent line top -->
    <div class="gh-accent" />

    <!-- ══ Main row ══ -->
    <div class="gh-main">

      <!-- Prize -->
      <div class="gh-prize" :class="{ 'gh-prize--flash': prizeFlash }">
        <span class="gh-prize__type">
          <i class="fas fa-trophy" />
          {{ prizePoolType }}
        </span>
        <div class="gh-prize__amount">
          <span class="gh-prize__sym">{{ currencySym }}</span>
          <span class="gh-prize__val">{{ prizeFormatted }}</span>
        </div>
      </div>

      <div class="gh-divider" />

      <!-- Status badge -->
      <div v-if="isFinished" class="gh-badge gh-badge--done">
        <i class="fas fa-flag-checkered" /> FINALIZADA
      </div>
      <div v-else-if="hasTime && cd" class="gh-countdown">
        <span class="gh-countdown__label"><i class="fas fa-clock" /> Inicia en</span>
        <div class="gh-timer">
          <template v-if="cd.time.days > 0">
            <div class="gh-tu"><b>{{ cd.time.days }}</b><small>D</small></div>
            <span class="gh-tsep">:</span>
          </template>
          <template v-if="cd.time.hours > 0">
            <div class="gh-tu"><b>{{ cd.time.hours }}</b><small>H</small></div>
            <span class="gh-tsep">:</span>
          </template>
          <div class="gh-tu"><b>{{ pad(cd.time.minutes) }}</b><small>M</small></div>
          <span class="gh-tsep">:</span>
          <div class="gh-tu"><b>{{ pad(cd.time.seconds) }}</b><small>S</small></div>
        </div>
      </div>
      <div v-else class="gh-badge gh-badge--live">
        <span class="gh-badge__dot" /> EN VIVO
      </div>

      <div class="gh-spacer" />

      <!-- Actions -->
      <div class="gh-actions">
        <Link v-if="isFinished" href="/bingo/game-rooms" class="gh-btn gh-btn--rooms">
          <i class="fas fa-door-open" /> Volver
        </Link>
        <button
          class="gh-btn gh-btn--icon"
          :class="{ 'gh-btn--muted': muted }"
          :title="muted ? 'Activar sonido' : 'Silenciar'"
          @click="emit('toggleMute')"
        >
          <i :class="muted ? 'fas fa-volume-mute' : 'fas fa-volume-up'" />
        </button>
      </div>

    </div>

    <!-- ── separator ── -->
    <div class="gh-sep" />

    <!-- ══ Stats row ══ -->
    <div class="gh-stats">

      <!-- Players -->
      <button class="gh-stat gh-stat--btn" :class="{ 'gh-stat--flash': playersFlash }" @click="emit('openPlayers')" title="Ver jugadores y ganadores">
        <div class="gh-stat__icon-wrap">
          <i class="fas fa-users" />
          <i v-if="totalWinners > 0" class="fas fa-trophy gh-stat__badge-icon" />
        </div>
        <div class="gh-stat__body">
          <span class="gh-stat__val">
            {{ livePlayers }}
            <em v-if="totalWinners > 0" class="gh-stat__sub-win">· {{ totalWinners }} <i class="fas fa-trophy" /></em>
          </span>
          <span class="gh-stat__lbl">jugadores</span>
        </div>
      </button>

      <div class="gh-stat-sep" />

      <!-- Cards -->
      <div class="gh-stat">
        <i class="fas fa-ticket-alt gh-stat__solo-icon" />
        <div class="gh-stat__body">
          <span class="gh-stat__val">{{ cardCount }}</span>
          <span class="gh-stat__lbl">cartone{{ cardCount === 1 ? '' : 's' }}</span>
        </div>
      </div>

      <!-- Rounds -->
      <template v-if="rounds">
        <div class="gh-stat-sep" />
        <div class="gh-stat">
          <i class="fas fa-sync-alt gh-stat__solo-icon" />
          <div class="gh-stat__body">
            <span class="gh-stat__val">{{ round }}<em class="gh-stat__sub">/{{ rounds }}</em></span>
            <span class="gh-stat__lbl">ronda</span>
          </div>
        </div>
      </template>

      <!-- Drawn progress -->
      <template v-if="liveDrawnLength > 0">
        <div class="gh-stat-sep" />
        <div class="gh-stat gh-stat--progress">
          <i class="fas fa-random gh-stat__solo-icon" />
          <div class="gh-stat__body">
            <span class="gh-stat__val">
              {{ liveDrawnLength }}<em class="gh-stat__sub">/{{ totalChips }}</em>
            </span>
            <span class="gh-stat__lbl">sorteadas</span>
          </div>
          <div class="gh-pbar">
            <div class="gh-pbar__fill" :style="{ width: `${drawPct}%` }" />
          </div>
        </div>
      </template>

      <!-- Pattern -->
      <template v-if="bingoPattern">
        <div class="gh-stat-sep" />
        <div class="gh-stat">
          <i class="fas fa-th gh-stat__solo-icon" />
          <div class="gh-stat__body">
            <span class="gh-stat__val gh-stat__val--sm">{{ bingoPattern }}</span>
            <span class="gh-stat__lbl">patrón</span>
          </div>
        </div>
      </template>

    </div>

    <!-- ══ DEV ribbon ══ -->
    <div v-if="isLocal" class="gh-dev">
      <span class="gh-dev__label"><i class="fas fa-code" /> DEV</span>
      <button class="gh-dev__btn" :disabled="devResetting" @click="emit('devReset')">
        <span v-if="devResetting" class="gh-dev__spin" />
        <i v-else class="fas fa-play" />
        {{ devResetting ? 'Iniciando…' : 'Iniciar sorteo de prueba' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   ROOT
═══════════════════════════════════════════ */
.gh-root {
  display: flex; flex-direction: column;
  background: linear-gradient(160deg, rgba(10,16,44,0.97) 0%, rgba(5,9,28,0.97) 100%);
  border: 1px solid rgba(80,120,255,0.14);
  border-radius: 16px; overflow: hidden;
  backdrop-filter: blur(20px);
  box-shadow: 0 6px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.04);
}

/* Accent line */
.gh-accent {
  height: 2px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(80,140,255,0.55) 18%,
    rgba(42,245,152,0.75) 50%,
    rgba(123,79,255,0.55) 82%,
    transparent 100%);
}

/* ═══════════════════════════════════════════
   MAIN ROW
═══════════════════════════════════════════ */
.gh-main {
  display: flex; align-items: center;
  padding: 10px 18px; gap: 0; min-height: 60px;
}

/* Prize */
.gh-prize { display: flex; flex-direction: column; gap: 0; flex-shrink: 0; }
.gh-prize--flash { animation: prize-pop 0.6s ease; }
@keyframes prize-pop { 0%,100% { transform: scale(1); } 45% { transform: scale(1.06); } }

.gh-prize__type {
  display: flex; align-items: center; gap: 4px;
  font-size: 8px; font-weight: 700; color: #3a5890;
  text-transform: uppercase; letter-spacing: 1.2px;
}
.gh-prize__type i { color: #f5c518; font-size: 8px; }
.gh-prize__amount { display: flex; align-items: baseline; gap: 3px; }
.gh-prize__sym { font-size: 15px; font-weight: 700; color: #ffc840; }
.gh-prize__val {
  font-family: 'Anton', sans-serif; font-size: 44px; line-height: 1;
  background: linear-gradient(130deg, #ffe566 0%, #ffb800 55%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 10px rgba(255,180,0,0.35)); letter-spacing: -1px;
}

/* Divider */
.gh-divider {
  width: 1px; height: 36px; flex-shrink: 0; margin: 0 16px;
  background: linear-gradient(180deg, transparent, rgba(80,120,255,0.18), transparent);
}
.gh-spacer { flex: 1; }

/* Status badges */
.gh-badge {
  display: flex; align-items: center; gap: 7px;
  padding: 6px 16px; border-radius: 30px;
  font-size: 11px; font-weight: 900; letter-spacing: 2px;
  flex-shrink: 0;
}
.gh-badge--live {
  background: rgba(42,245,152,0.08); border: 1px solid rgba(42,245,152,0.24);
  color: #2af598; box-shadow: 0 0 18px rgba(42,245,152,0.07);
}
.gh-badge__dot {
  width: 7px; height: 7px; border-radius: 50%; background: #2af598;
  animation: dot-pulse 1.4s ease-in-out infinite;
  box-shadow: 0 0 6px rgba(42,245,152,0.8);
}
@keyframes dot-pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(42,245,152,0.7); } 50% { box-shadow: 0 0 0 6px rgba(42,245,152,0); } }
.gh-badge--done {
  background: rgba(100,120,160,0.09); border: 1px solid rgba(100,120,160,0.22);
  color: #7090b8; letter-spacing: 1.5px;
}

/* Countdown */
.gh-countdown { display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
.gh-countdown__label {
  font-size: 8.5px; font-weight: 700; color: #3a5890;
  text-transform: uppercase; letter-spacing: 0.9px;
  display: flex; align-items: center; gap: 3px;
}
.gh-timer { display: flex; align-items: center; gap: 2px; }
.gh-tu {
  display: flex; flex-direction: column; align-items: center;
  background: rgba(80,120,255,0.07); border: 1px solid rgba(80,120,255,0.13);
  border-radius: 6px; padding: 2px 5px; min-width: 28px;
}
.gh-tu b  { font-size: 15px; font-weight: 800; color: #ddeaff; line-height: 1.1; font-family: 'Courier New', monospace; }
.gh-tu small { font-size: 6.5px; color: #4a6aaa; text-transform: uppercase; letter-spacing: 0.3px; }
.gh-tsep { color: #2a3860; font-size: 16px; font-weight: 300; margin: 0 1px; padding-bottom: 8px; }

/* Actions */
.gh-actions { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.gh-btn {
  display: inline-flex; align-items: center; gap: 5px;
  border-radius: 20px; font-size: 11px; font-weight: 700;
  cursor: pointer; transition: all 0.16s ease; border: 1px solid; text-decoration: none;
}
.gh-btn--rooms {
  padding: 6px 14px;
  background: rgba(100,140,255,0.09); border-color: rgba(100,140,255,0.22); color: #7c9dff;
}
.gh-btn--rooms:hover { background: rgba(100,140,255,0.18); color: #b0c8ff; box-shadow: 0 3px 12px rgba(100,140,255,0.18); }
.gh-btn--icon {
  width: 34px; height: 34px; padding: 0; justify-content: center; border-radius: 50%;
  background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.09); color: #6080b0; font-size: 13px;
}
.gh-btn--icon:hover { background: rgba(255,255,255,0.09); color: #a0c0ff; }
.gh-btn--muted { color: #2a3460; border-color: rgba(255,255,255,0.05); }

/* ═══════════════════════════════════════════
   SEPARATOR
═══════════════════════════════════════════ */
.gh-sep { height: 1px; background: rgba(80,120,255,0.08); }

/* ═══════════════════════════════════════════
   STATS ROW
═══════════════════════════════════════════ */
.gh-stats {
  display: flex; align-items: stretch;
  padding: 0 18px; background: rgba(0,0,0,0.1);
  flex-wrap: wrap; min-height: 40px;
}

.gh-stat {
  display: flex; align-items: center; gap: 7px;
  padding: 6px 12px; flex: 1; min-width: 0; position: relative;
}
.gh-stat--btn {
  cursor: pointer; border-radius: 8px;
  transition: background 0.18s ease; background: transparent; border: none; text-align: left;
}
.gh-stat--btn:hover { background: rgba(100,140,255,0.07); }

/* Icon with optional badge overlay */
.gh-stat__icon-wrap { position: relative; width: 22px; height: 22px; flex-shrink: 0; }
.gh-stat__icon-wrap > i:first-child {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #7c9dff;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.15); border-radius: 50%;
}
.gh-stat__badge-icon {
  position: absolute; bottom: -5px; left: 10px;
  width: 13px !important; height: 13px !important;
  font-size: 6.5px !important; color: #ffd700 !important;
  background: rgba(255,200,0,0.16) !important; border-color: rgba(255,200,0,0.3) !important;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
}
.gh-stat__solo-icon {
  width: 22px; height: 22px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #7c9dff;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.15); border-radius: 50%;
}

.gh-stat__body { display: flex; flex-direction: column; gap: 0; min-width: 0; }
.gh-stat__val {
  font-size: 13px; font-weight: 800; color: #c8d8ff; line-height: 1.1;
  font-family: 'Courier New', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  display: flex; align-items: center; gap: 4px;
}
.gh-stat__val--sm { font-size: 11px; font-family: inherit; }
.gh-stat__sub     { font-size: 9px; color: #2a3860; font-weight: 500; font-style: normal; }
.gh-stat__sub-win { font-size: 11px; color: #ffd700; font-family: inherit; font-style: normal; }
.gh-stat__sub-win i { font-size: 9px; }
.gh-stat__lbl { font-size: 7px; font-weight: 700; color: #2a3860; text-transform: uppercase; letter-spacing: 0.5px; }

/* Flash animation on stat */
.gh-stat--flash .gh-stat__icon-wrap > i:first-child,
.gh-stat--flash .gh-stat__solo-icon { background: rgba(42,245,152,0.14); color: #2af598; }
.gh-stat--flash .gh-stat__val { animation: stat-pop 0.5s ease; }
@keyframes stat-pop { 0% { color: #2af598; transform: scale(1); } 40% { transform: scale(1.14); } 100% { color: #c8d8ff; transform: scale(1); } }

/* Progress bar inside stat */
.gh-stat--progress { padding-bottom: 12px; }
.gh-pbar {
  position: absolute; bottom: 4px; left: 42px; right: 12px;
  height: 2px; background: rgba(80,120,255,0.1); border-radius: 2px; overflow: hidden;
}
.gh-pbar__fill {
  height: 100%; border-radius: 2px;
  background: linear-gradient(90deg, #4a7fff, #2af598);
  transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}

/* Stat separators */
.gh-stat-sep {
  width: 1px; align-self: stretch; margin: 6px 0;
  background: linear-gradient(180deg, transparent, rgba(80,120,255,0.12), transparent);
  flex-shrink: 0;
}

/* ═══════════════════════════════════════════
   DEV RIBBON
═══════════════════════════════════════════ */
.gh-dev {
  display: flex; align-items: center; gap: 10px;
  padding: 6px 18px 8px;
  border-top: 1px dashed rgba(255,171,0,0.16);
  background: rgba(255,171,0,0.025);
}
.gh-dev__label {
  font-size: 7.5px; font-weight: 800; letter-spacing: 1.2px;
  color: rgba(255,171,0,0.4); text-transform: uppercase;
  display: flex; align-items: center; gap: 4px; flex-shrink: 0;
}
.gh-dev__btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 14px; border-radius: 8px;
  background: rgba(255,171,0,0.09); border: 1px solid rgba(255,171,0,0.26);
  color: #ffd060; font-size: 11px; font-weight: 700; cursor: pointer;
  transition: background 0.18s, border-color 0.18s, color 0.18s;
}
.gh-dev__btn:hover:not(:disabled) { background: rgba(255,171,0,0.18); border-color: rgba(255,171,0,0.48); color: #ffe090; }
.gh-dev__btn:disabled { opacity: 0.45; cursor: not-allowed; }
.gh-dev__btn i { font-size: 9px; }
.gh-dev__spin {
  width: 10px; height: 10px; border-radius: 50%;
  border: 1.5px solid rgba(255,171,0,0.28); border-top-color: #ffd060;
  animation: dev-spin 0.7s linear infinite;
}
@keyframes dev-spin { to { transform: rotate(360deg) } }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 768px) {
  .gh-main { padding: 10px 14px; }
  .gh-stats { padding: 0 14px; }
  .gh-stat { padding: 6px 8px; }
}
@media (max-width: 560px) {
  .gh-main { padding: 10px 12px; min-height: auto; gap: 0; }
  .gh-divider { display: none; }
  .gh-prize { flex-direction: row; align-items: center; gap: 5px; }
  .gh-prize__type { display: none; }
  .gh-prize__sym { font-size: 12px; }
  .gh-prize__val { font-size: 28px; }
  .gh-badge { padding: 4px 10px; font-size: 10px; letter-spacing: 1px; }
  .gh-countdown__label { font-size: 7.5px; }
  .gh-tu { min-width: 24px; padding: 1px 4px; }
  .gh-tu b { font-size: 13px; }
  /* Stats: grid 2-col on mobile */
  .gh-stats { display: grid; grid-template-columns: 1fr 1fr; padding: 0 12px; }
  .gh-stat-sep { display: none; }
  .gh-stat { flex: none; padding: 6px 8px; border-bottom: 1px solid rgba(80,120,255,0.06); }
  .gh-stat:nth-child(odd) { border-right: 1px solid rgba(80,120,255,0.06); }
  .gh-stat--progress { padding-bottom: 14px; }
  .gh-pbar { left: 34px; right: 8px; }
}
@media (max-width: 380px) {
  .gh-prize__val { font-size: 22px; }
  .gh-main { padding: 8px 10px; }
  .gh-stats { padding: 0 10px; }
  .gh-stat { padding: 5px 6px; }
  .gh-stat__val { font-size: 12px; }
}
</style>
