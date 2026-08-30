<template>
  <div class="missions-layout">

    <div v-if="loading" class="missions-loading">
      <i class="fas fa-spinner fa-spin"></i> Cargando misiones...
    </div>

    <div v-else-if="missions.length" class="mission-list">
      <div v-for="m in missions" :key="m.code" class="mission-card" :class="{ 'mission-card--completed': m.completed }">
        <div class="mission-card__icon">
          <i class="fas" :class="m.completed ? 'fa-check' : 'fa-bullseye'"></i>
        </div>
        <div class="mission-card__body">
          <div class="mission-card__header">
            <span class="mission-card__name">{{ m.name }}</span>
            <span class="mission-card__xp">+{{ m.xp_reward }} XP</span>
          </div>
          <p v-if="m.description" class="mission-card__desc">{{ m.description }}</p>
          <div class="mission-card__progress-bar">
            <div class="mission-card__progress-fill" :style="{ width: Math.min(100, (m.progress / m.target) * 100) + '%' }"></div>
          </div>
          <span class="mission-card__progress-label">{{ m.progress }} / {{ m.target }}</span>
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon"><i class="fas fa-bullseye"></i></div>
      <p>Sin misiones activas hoy</p>
      <small>Vuelve mañana para nuevas misiones</small>
    </div>

  </div>
</template>

<script setup lang="ts">
import type { ProfileDailyMission } from '@js/composables/useProfile'

defineProps<{
    missions: ProfileDailyMission[]
    loading?: boolean
}>()
</script>

<style scoped>
.missions-layout { display: flex; flex-direction: column; gap: 0.75rem; }

.missions-loading { display: flex; align-items: center; gap: 0.5rem; padding: 1.5rem 0; color: var(--ds-text-2); font-size: 0.88rem; }

.mission-list { display: flex; flex-direction: column; gap: 0.75rem; }

.mission-card {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  padding: 1rem 1.1rem;
  border-radius: 14px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface);
}
.mission-card--completed {
  border-color: rgba(52, 211, 153, 0.4);
  background: rgba(52, 211, 153, 0.06);
}

.mission-card__icon {
  width: 38px; height: 38px; flex-shrink: 0;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
  font-size: 0.9rem;
}
.mission-card--completed .mission-card__icon { background: rgba(52, 211, 153, 0.2); color: #34d399; }

.mission-card__body { flex: 1; min-width: 0; }
.mission-card__header { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.2rem; }
.mission-card__name { font-weight: 600; color: var(--ds-text); font-size: 0.92rem; }
.mission-card__xp { font-size: 0.75rem; font-weight: 700; color: #f59e0b; white-space: nowrap; }
.mission-card__desc { font-size: 0.8rem; color: var(--ds-text-2); margin: 0 0 0.6rem; }

.mission-card__progress-bar { height: 8px; border-radius: 99px; background: var(--ds-border); overflow: hidden; margin-bottom: 0.3rem; }
.mission-card__progress-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #667eea, #764ba2); transition: width 0.4s ease; }
.mission-card--completed .mission-card__progress-fill { background: linear-gradient(90deg, #11998e, #34d399); }
.mission-card__progress-label { font-size: 0.72rem; color: var(--ds-text-2); }

.empty-state { text-align: center; padding: 2rem 0; color: var(--ds-text-muted); }
.empty-icon { width: 56px; height: 56px; border-radius: 50%; background: var(--ds-surface-2); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 0.75rem; }
.empty-state p { font-size: 0.88rem; margin: 0 0 0.2rem; color: var(--ds-text-2); }
.empty-state small { font-size: 0.76rem; }
</style>
