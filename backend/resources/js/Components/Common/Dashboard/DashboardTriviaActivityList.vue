<script setup lang="ts">
import type { TriviaActivityRow } from '@/js/types/roles/player/dashboard/dashboard-player'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  rows: TriviaActivityRow[]
}>()

const statusLabel: Record<string, string> = {
  registered: 'Registrado',
  confirmed: 'Confirmado',
  playing: 'Jugando',
  eliminated: 'Eliminado',
  winner: 'Ganador',
  abandoned: 'Abandonó',
}
</script>

<template>
  <div v-if="rows.length" class="tal-list">
    <div v-for="row in rows" :key="row.id" class="tal-item">
      <div class="tal-item__icon"><i class="fas fa-brain"></i></div>
      <div class="tal-item__main">
        <span class="tal-item__title">
          {{ statusLabel[row.status] ?? row.status }}
          <template v-if="row.final_rank">· Puesto #{{ row.final_rank }}</template>
        </span>
        <span class="tal-item__sub">
          {{ row.correct_answers ?? 0 }} correctas · {{ row.wrong_answers ?? 0 }} incorrectas
        </span>
      </div>
      <span v-if="row.prize_won > 0" class="tal-prize">$ {{ row.prize_won.toFixed(2) }}</span>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-brain"
    title="Sin actividad en Trivia todavía"
    description="Tus partidas de Trivia aparecerán aquí."
  />
</template>

<style scoped>
.tal-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.tal-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
}

.tal-item__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(124, 58, 237, 0.15);
  color: #a78bfa;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 0.85rem;
}

.tal-item__main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.tal-item__title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ds-text);
}

.tal-item__sub {
  font-size: 0.75rem;
  color: var(--ds-text-2);
}

.tal-prize {
  font-size: 0.8rem;
  font-weight: 700;
  color: #4ade80;
  white-space: nowrap;
}
</style>
