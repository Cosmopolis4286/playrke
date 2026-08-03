<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  nextRoomUnixDate: number | null
  totalActiveRooms: number
  loading?: boolean
}>()

// ── Countdown local a "próxima sala" ──────────────────────────────────────
// No reutiliza useGameRoomCountdown (indexado por sala) — este es un único
// timestamp arbitrario, así que un setInterval local propio es la solución
// más simple y correcta para este único caso.
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  timer = setInterval(() => { now.value = Date.now() }, 1000)
})
onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const secondsRemaining = computed(() => {
  if (props.nextRoomUnixDate == null) return null
  return Math.max(0, props.nextRoomUnixDate - Math.floor(now.value / 1000))
})

const nextRoomLabel = computed(() => {
  const secs = secondsRemaining.value
  if (secs === null) return '—'
  if (secs <= 0) return '¡Ya!'

  const days = Math.floor(secs / 86400)
  if (days >= 1) {
    const hours = Math.floor((secs % 86400) / 3600)
    return `${days}d ${hours}h`
  }

  const h = Math.floor(secs / 3600)
  const m = Math.floor((secs % 3600) / 60)
  const s = secs % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})
</script>

<template>
  <div class="grk-row" :class="{ 'grk-row--loading': loading }">
    <span class="grk-chip">
      <i class="fas fa-hourglass-half grk-chip__icon" />
      <b class="grk-chip__value">{{ nextRoomLabel }}</b>
      <span class="grk-chip__label">próxima</span>
    </span>

    <span class="grk-sep" />

    <span class="grk-chip">
      <i class="fas fa-door-open grk-chip__icon" />
      <b class="grk-chip__value">{{ totalActiveRooms }}</b>
      <span class="grk-chip__label">activas</span>
    </span>
  </div>
</template>

<style scoped>
.grk-row {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 6px 14px; border-radius: 20px;
  background: linear-gradient(160deg, rgba(123,79,255,.08) 0%, rgba(10,15,36,.55) 100%);
  border: 1px solid rgba(255,255,255,.08);
  transition: opacity .2s;
}
.grk-row--loading { opacity: .5; }

.grk-chip { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
.grk-chip__icon {
  font-size: 12px; color: #2af598;
  filter: drop-shadow(0 0 5px rgba(42,245,152,.35));
}
.grk-chip__value {
  font-family: 'Orbitron', 'Anton', monospace;
  font-size: 12.5px; font-weight: 700; letter-spacing: .2px;
  color: #e0eaff;
}
.grk-chip__label {
  font-size: 8.5px; letter-spacing: .3px; color: #7a96c8;
  text-transform: uppercase;
}
.grk-sep { width: 1px; height: 14px; background: rgba(255,255,255,.1); flex-shrink: 0; }

@media (max-width: 380px) {
  .grk-row { padding: 5px 10px; gap: 7px; }
  .grk-chip__value { font-size: 11px; }
  .grk-chip__label { display: none; }
}
</style>
