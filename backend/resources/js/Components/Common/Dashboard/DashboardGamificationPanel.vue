<script setup lang="ts">
import type { UserLevelInfo, AchievementStats } from '@/js/composables/roles/player/dashboard/useGamification'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  levelInfo: UserLevelInfo | null
  achievementStats: AchievementStats | null
  loading: boolean
  error: string | null
}>()
</script>

<template>
  <div v-if="loading && !levelInfo" class="gp-state">
    <span class="gp-spinner"></span><span>Cargando tu progreso...</span>
  </div>
  <div v-else-if="error" class="gp-state gp-state--error">
    <i class="fas fa-exclamation-triangle"></i> {{ error }}
  </div>
  <div v-else-if="levelInfo" class="gp-panel">
    <div class="gp-level">
      <div class="gp-level__badge">{{ levelInfo.level }}</div>
      <div class="gp-level__info">
        <span class="gp-level__label">Nivel {{ levelInfo.level }}</span>
        <div class="gp-level__bar">
          <div class="gp-level__bar-fill" :style="{ width: levelInfo.progress_percentage + '%' }"></div>
        </div>
        <span class="gp-level__xp">{{ levelInfo.current_xp }} / {{ levelInfo.current_xp + levelInfo.xp_needed_for_next_level }} XP</span>
      </div>
    </div>

    <div v-if="achievementStats" class="gp-achievements">
      <div class="gp-stat">
        <span class="gp-stat__value">{{ achievementStats.unlocked_count ?? 0 }}</span>
        <span class="gp-stat__label">Logros desbloqueados</span>
      </div>
      <div class="gp-stat">
        <span class="gp-stat__value">{{ achievementStats.total_achievements ?? 0 }}</span>
        <span class="gp-stat__label">Total disponibles</span>
      </div>
      <div v-if="(achievementStats.unseen_count ?? 0) > 0" class="gp-stat gp-stat--accent">
        <span class="gp-stat__value">{{ achievementStats.unseen_count }}</span>
        <span class="gp-stat__label">Nuevos sin ver</span>
      </div>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-star"
    title="Sin progreso todavía"
    description="Tu nivel y logros aparecerán aquí en cuanto empieces a jugar."
  />
</template>

<style scoped>
.gp-panel {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.gp-level {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.gp-level__badge {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 800;
  flex-shrink: 0;
}

.gp-level__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.gp-level__label {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--ds-text);
}

.gp-level__bar {
  height: 8px;
  border-radius: 4px;
  background: rgba(127, 127, 127, 0.18);
  overflow: hidden;
}

.gp-level__bar-fill {
  height: 100%;
  border-radius: 4px;
  background: linear-gradient(90deg, #f59e0b, #d97706);
  transition: width 0.4s ease-out;
}

.gp-level__xp {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  font-variant-numeric: tabular-nums;
}

.gp-achievements {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 0.75rem;
}

.gp-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  padding: 0.75rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
  text-align: center;
}

.gp-stat--accent { background: rgba(245, 158, 11, 0.12); }

.gp-stat__value {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--ds-text);
}
.gp-stat--accent .gp-stat__value { color: #f59e0b; }

.gp-stat__label {
  font-size: 0.68rem;
  color: var(--ds-text-2);
}

.gp-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--ds-text-2);
  font-size: 0.875rem;
}
.gp-state--error { color: #ef4444; }

.gp-spinner {
  width: 20px;
  height: 20px;
  border: 3px solid var(--ds-border-2);
  border-top-color: #f59e0b;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
