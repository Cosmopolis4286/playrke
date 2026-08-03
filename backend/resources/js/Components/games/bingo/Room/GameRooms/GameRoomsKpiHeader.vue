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

// Máximo 3 unidades de tiempo, siempre las 3 más significativas para la
// magnitud restante: ≥1 día → días/horas/minutos; si no, horas/minutos/segundos.
// Antes cortaba a solo "Xd Xh" (2 unidades) cuando había días, perdiendo
// precisión frente al caso <1 día que sí mostraba segundos.
const nextRoomLabel = computed(() => {
  const secs = secondsRemaining.value
  if (secs === null) return '—'
  if (secs <= 0) return '¡Ya!'

  const days = Math.floor(secs / 86400)
  if (days >= 1) {
    const hours = Math.floor((secs % 86400) / 3600)
    const minutes = Math.floor((secs % 3600) / 60)
    return `${days}d ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m`
  }

  const h = Math.floor(secs / 3600)
  const m = Math.floor((secs % 3600) / 60)
  const s = secs % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})
</script>

<template>
  <div class="grk-row inline-flex items-center gap-2.5" :class="{ 'grk-row--loading': loading }">
    <span class="grk-chip inline-flex items-center gap-1.5 whitespace-nowrap">
      <i class="fas fa-hourglass-half grk-chip__icon" />
      <b class="grk-chip__value">{{ nextRoomLabel }}</b>
      <span class="grk-chip__label">próxima</span>
    </span>

    <span class="grk-sep" />

    <span class="grk-chip inline-flex items-center gap-1.5 whitespace-nowrap">
      <i class="fas fa-door-open grk-chip__icon" />
      <b class="grk-chip__value">{{ totalActiveRooms }}</b>
      <span class="grk-chip__label">activas</span>
    </span>
  </div>
</template>

<style scoped>
.grk-row {
  padding: 5px 12px; border-radius: 18px;
  background: linear-gradient(160deg, rgba(123,79,255,.08) 0%, rgba(10,15,36,.55) 100%);
  border: 1px solid rgba(255,255,255,.08);
  transition: opacity .2s;
}
.grk-row--loading { opacity: .5; }
.grk-chip__icon {
  font-size: 10.5px; color: #2af598;
  filter: drop-shadow(0 0 5px rgba(42,245,152,.35));
}
.grk-chip__value {
  font-family: 'Orbitron', 'Anton', monospace;
  font-size: 11px; font-weight: 700; letter-spacing: .2px;
  color: #e0eaff;
}
.grk-chip__label {
  font-size: 7.5px; letter-spacing: .3px; color: #7a96c8;
  text-transform: uppercase;
}
.grk-sep { width: 1px; height: 12px; background: rgba(255,255,255,.1); flex-shrink: 0; }

@media (max-width: 380px) {
  .grk-row { padding: 4px 9px; gap: 6px; }
  .grk-chip__value { font-size: 10px; }
  .grk-chip__label { display: none; }
}
</style>
