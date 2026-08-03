<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  prizePoolType: string
  currencySym: string
  prizeFormatted: string
  prizeFlash: boolean
  hasTime: boolean
  cd: { time: { days: number; hours: number; minutes: number; seconds: number } } | null
  isFinished: boolean
}

const props = defineProps<Props>()

function pad(n: number) { return String(n).padStart(2, '0') }

const countdownUrgency = computed(() => {
  if (!props.cd) return 'normal'
  const { days, hours, minutes, seconds } = props.cd.time
  const totalSeconds = days * 86400 + hours * 3600 + minutes * 60 + seconds
  if (totalSeconds <= 60)  return 'urgent'
  if (totalSeconds <= 300) return 'warning'
  return 'normal'
})
</script>

<template>
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
  <div v-else-if="hasTime && cd" class="gh-countdown" :class="`gh-countdown--${countdownUrgency}`">
    <span class="gh-countdown__label"><i class="fas fa-clock" /> Inicia en</span>
    <!-- Máximo 3 unidades de tiempo, siempre las más significativas — con
         días presentes se omiten los segundos (D:H:M), igual criterio que
         GameRoomsKpiHeader.vue y useLobbyCountdown.ts. Antes mostraba D:H:M:S
         (4 bloques) cuando había días Y horas a la vez. -->
    <div class="gh-timer" :class="`gh-timer--${countdownUrgency}`">
      <template v-if="cd.time.days > 0">
        <div class="gh-tu"><b>{{ cd.time.days }}</b><small>D</small></div>
        <span class="gh-tsep">:</span>
        <div class="gh-tu"><b>{{ pad(cd.time.hours) }}</b><small>H</small></div>
        <span class="gh-tsep">:</span>
        <div class="gh-tu"><b>{{ pad(cd.time.minutes) }}</b><small>M</small></div>
      </template>
      <template v-else>
        <template v-if="cd.time.hours > 0">
          <div class="gh-tu"><b>{{ cd.time.hours }}</b><small>H</small></div>
          <span class="gh-tsep">:</span>
        </template>
        <div class="gh-tu"><b>{{ pad(cd.time.minutes) }}</b><small>M</small></div>
        <span class="gh-tsep">:</span>
        <div class="gh-tu"><b>{{ pad(cd.time.seconds) }}</b><small>S</small></div>
      </template>
    </div>
  </div>
  <div v-else class="gh-badge gh-badge--live">
    <span class="gh-badge__dot" /> EN VIVO
  </div>
</template>

<style scoped>
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

/* ── Countdown urgency states ── */
/* warning: <5 min → naranja */
.gh-countdown--warning .gh-countdown__label { color: #c07820; }
.gh-countdown--warning .gh-countdown__label i { color: #e09030; }
.gh-timer--warning .gh-tu {
  background: rgba(255,160,0,0.09);
  border-color: rgba(255,160,0,0.24);
}
.gh-timer--warning .gh-tu b { color: #ffcf70; }
.gh-timer--warning .gh-tu small { color: #a07030; }
.gh-timer--warning .gh-tsep { color: #7a5010; }

/* urgent: ≤60 s → rojo pulsante */
.gh-countdown--urgent .gh-countdown__label { color: #b03030; }
.gh-countdown--urgent .gh-countdown__label i { color: #e05050; }
.gh-timer--urgent .gh-tu {
  background: rgba(255,60,60,0.1);
  border-color: rgba(255,80,80,0.32);
  animation: cd-urgent-border 0.7s ease-in-out infinite alternate;
}
.gh-timer--urgent .gh-tu b {
  color: #ff8080;
  animation: cd-urgent-shake 0.5s ease-in-out infinite;
}
.gh-timer--urgent .gh-tu small { color: #903030; }
.gh-timer--urgent .gh-tsep { color: #602020; animation: cd-urgent-sep 0.7s ease-in-out infinite alternate; }
@keyframes cd-urgent-border {
  from { box-shadow: 0 0 0 0 rgba(255,60,60,0.0); border-color: rgba(255,80,80,0.25); }
  to   { box-shadow: 0 0 10px 1px rgba(255,60,60,0.3); border-color: rgba(255,80,80,0.55); }
}
@keyframes cd-urgent-shake {
  0%,100% { transform: translateX(0); }
  25%      { transform: translateX(-1px); }
  75%      { transform: translateX(1px); }
}
@keyframes cd-urgent-sep {
  from { color: #602020; }
  to   { color: #c04040; }
}

/* Responsive */
@media (max-width: 560px) {
  .gh-divider { display: none; }
  .gh-prize { flex-direction: row; align-items: center; gap: 5px; }
  .gh-prize__type { display: none; }
  .gh-prize__sym { font-size: 12px; }
  .gh-prize__val { font-size: 28px; }
  .gh-badge { padding: 4px 10px; font-size: 10px; letter-spacing: 1px; }
  .gh-countdown__label { font-size: 7.5px; }
  .gh-tu { min-width: 24px; padding: 1px 4px; }
  .gh-tu b { font-size: 13px; }
}
@media (max-width: 380px) {
  .gh-prize__val { font-size: 22px; }
}
</style>
