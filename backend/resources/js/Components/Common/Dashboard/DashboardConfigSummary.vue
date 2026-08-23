<script setup lang="ts">
import type { CommerceConfiguration } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  configuration: CommerceConfiguration
}>()
</script>

<template>
  <div v-if="configuration.currencies.length || configuration.games.length" class="cs-groups">
    <div v-if="configuration.currencies.length" class="cs-group">
      <p class="cs-group__label">Monedas habilitadas</p>
      <div class="cs-chips">
        <span v-for="c in configuration.currencies" :key="c.code" class="cs-chip">
          {{ c.symbol }} {{ c.code }}
        </span>
      </div>
    </div>
    <div v-if="configuration.games.length" class="cs-group">
      <p class="cs-group__label">Juegos habilitados</p>
      <div class="cs-chips">
        <span v-for="g in configuration.games" :key="g" class="cs-chip">{{ g }}</span>
      </div>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-sliders-h"
    title="Sin configuración disponible"
    description="Las monedas y juegos habilitados aparecerán aquí."
  />
</template>

<style scoped>
.cs-groups {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cs-group__label {
  font-size: 0.73rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ds-text-2);
  margin: 0 0 0.5rem;
}

.cs-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.cs-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--ds-text);
}
</style>
