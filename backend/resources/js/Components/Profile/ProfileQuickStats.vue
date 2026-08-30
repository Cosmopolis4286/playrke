<template>
  <div class="quick-stats">
    <div class="stats-row">
      <div v-for="stat in quickStats" :key="stat.label" class="stat-item">
        <span class="stat-value">{{ stat.value }}</span>
        <span class="stat-label">{{ stat.label }}</span>
      </div>
    </div>

    <!-- Progreso hacia el siguiente nivel — solo se muestra si el backend
         trae el dato (level_progress viene de UserLevelService, puede
         faltar si el usuario aún no tiene fila en user_levels). -->
    <div v-if="showProgress" class="level-progress" :title="`${xpCurrent} / ${xpNeeded} XP para el nivel ${level + 1}`">
      <div class="level-progress__track">
        <div class="level-progress__fill" :style="{ width: `${progressPercent}%` }" />
      </div>
      <span class="level-progress__label">{{ progressPercent }}% al nivel {{ level + 1 }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    stats: Record<string, any>
    missionsPending?: number
    missionsTotal?: number
}>()

const level = computed<number>(() => props.stats.level ?? 1)
const progressPercent = computed<number>(() => Math.min(100, Math.max(0, Math.round(props.stats.level_progress ?? 0))))
const xpCurrent = computed<number>(() => props.stats.experience_points ?? 0)
const xpNeeded  = computed<number>(() => props.stats.xp_to_next_level ?? 0)

// Sin dato real de progreso (usuario sin fila en user_levels todavía) no se
// muestra una barra en 0% engañosa — se omite el widget entero.
const showProgress = computed(() => typeof props.stats.level_progress === 'number' && xpNeeded.value > 0)

const quickStats = computed(() => {
    const items = [
        { label: 'Partidas',  value: props.stats.games_played       ?? 0 },
        { label: 'Victorias', value: props.stats.games_won          ?? 0 },
        { label: 'Nivel',     value: level.value },
        { label: 'Logros',    value: props.stats.achievements_count ?? 0 },
    ]

    const streak = props.stats.current_streak ?? 0
    items.push({ label: 'Racha', value: streak > 0 ? `🔥 ${streak}` : '0' })

    if (props.missionsTotal) {
        const done = props.missionsTotal - (props.missionsPending ?? 0)
        items.push({ label: 'Misiones', value: `${done}/${props.missionsTotal}` })
    }

    return items
})
</script>

<style scoped>
.quick-stats { display: flex; flex-direction: column; gap: 10px; width: 100%; }

.stats-row { display: flex; gap: 1.4rem; flex-wrap: wrap; justify-content: center; }
.stat-item { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.stat-value {
    color: #fff; font-size: 1.35rem; font-weight: 800; line-height: 1;
    text-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
.stat-label { color: rgba(255,255,255,0.6); font-size: 0.63rem; text-transform: uppercase; letter-spacing: 0.07em; }

.level-progress { display: flex; flex-direction: column; gap: 4px; max-width: 260px; margin: 0 auto; width: 100%; }
.level-progress__track {
    height: 6px; border-radius: 99px; background: rgba(255,255,255,0.15);
    overflow: hidden;
}
.level-progress__fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, #f59e0b, #f97316);
    transition: width 0.4s ease;
}
.level-progress__label {
    font-size: 0.62rem; color: rgba(255,255,255,0.55); text-align: center;
}

/* Modo apilado (header en 1 columna, ver ProfileHeader.vue): alinear a la
   izquierda para leer de arriba hacia abajo junto con identidad/acciones,
   en vez de centrar un bloque suelto en medio de dos columnas alineadas
   a la izquierda. */
@media (max-width: 1180px) {
    .stats-row { justify-content: flex-start; }
    .level-progress { margin: 0; }
    .level-progress__label { text-align: left; }
}

@media (max-width: 600px) {
    .stats-row { gap: 1rem; }
    .stat-value { font-size: 1.15rem; }
}
</style>
