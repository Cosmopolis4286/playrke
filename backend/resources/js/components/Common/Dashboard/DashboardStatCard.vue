<script setup lang="ts" name="DashboardStatCard">
  import type { StatCardData } from '@/js/types/common/dashboard/dashboard'

  const props = withDefaults(defineProps<StatCardData>(), {
    variant: 'primary',
    trend: 'neutral',
  })

  const trendIcon = {
    up: 'fas fa-arrow-up',
    down: 'fas fa-arrow-down',
    neutral: 'fas fa-minus',
  } as const

  const trendClass = {
    up: 'text-success',
    down: 'text-danger',
    neutral: 'text-muted',
  } as const
</script>

<template>
  <component
    :is="href ? 'a' : 'div'"
    :href="href"
    class="card stat-card h-100"
    :class="[`border-${variant}`, href ? 'stat-card--link' : '']"
  >
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <!-- Contenido principal -->
        <div class="flex-grow-1">
          <p class="text-muted mb-1 small fw-semibold text-uppercase">{{ label }}</p>
          <h4 class="mb-0 fw-bold">{{ value }}</h4>
          <p v-if="description" class="text-muted small mb-0 mt-1">{{ description }}</p>

          <!-- Tendencia -->
          <div v-if="trend && trend !== 'neutral' && trendValue !== undefined" class="mt-2 d-flex align-items-center gap-1">
            <i :class="[trendIcon[trend], trendClass[trend], 'small']"></i>
            <span :class="[trendClass[trend], 'small fw-semibold']">{{ trendValue }}%</span>
            <span v-if="trendLabel" class="text-muted small">{{ trendLabel }}</span>
          </div>
        </div>

        <!-- Icono -->
        <div
          v-if="icon"
          class="stat-card__icon rounded-3 d-flex align-items-center justify-content-center"
          :class="`bg-${variant} bg-opacity-10 text-${variant}`"
        >
          <i :class="[icon, 'fs-5']"></i>
        </div>
      </div>
    </div>
  </component>
</template>

<style scoped>
.stat-card {
  border-top-width: 3px !important;
  border-right-width: 1px !important;
  border-bottom-width: 1px !important;
  border-left-width: 1px !important;
  transition: box-shadow 0.2s;
}
.stat-card--link {
  text-decoration: none;
  color: inherit;
}
.stat-card--link:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}
.stat-card__icon {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
}
</style>
