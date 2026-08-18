<script setup lang="ts" name="DashboardSection">
  import type { DashboardSectionProps } from '@/js/types/common/dashboard/dashboard'

  withDefaults(defineProps<DashboardSectionProps>(), {
    bordered: true,
    loading: false,
    skeletonVariant: 'lines',
  })
</script>

<template>
  <div class="card h-100" :class="[bordered ? '' : 'border-0 shadow-none', $props.class]">
    <!-- Header opcional -->
    <div v-if="title || $slots.header" class="card-header bg-transparent border-bottom d-flex align-items-center gap-2">
      <slot name="header">
        <i v-if="icon" :class="[icon, 'text-muted']"></i>
        <div>
          <h6 class="mb-0 fw-semibold">{{ title }}</h6>
          <small v-if="subtitle" class="text-muted">{{ subtitle }}</small>
        </div>
      </slot>
      <!-- Acciones opcionales en el header -->
      <div v-if="$slots.actions" class="ms-auto">
        <slot name="actions" />
      </div>
    </div>

    <!-- Body -->
    <div class="card-body">
      <!-- Skeleton mientras carga — la forma sigue al tipo de contenido real
           de la sección (ver DashboardSkeletonVariant) para que el salto a
           datos reales se sienta continuo en vez de un placeholder genérico. -->
      <template v-if="loading">
        <div v-if="skeletonVariant === 'list'" class="sk-list">
          <div v-for="i in 4" :key="i" class="sk-list__row">
            <div class="skeleton-block sk-list__icon"></div>
            <div class="sk-list__main">
              <div class="skeleton-block" style="height: 12px; width: 55%; border-radius: 4px;"></div>
              <div class="skeleton-block sk-list__bar"></div>
            </div>
            <div class="skeleton-block" style="height: 18px; width: 32px; border-radius: 10px;"></div>
          </div>
        </div>

        <div v-else-if="skeletonVariant === 'chart'" class="sk-chart">
          <div
            v-for="i in 10"
            :key="i"
            class="skeleton-block sk-chart__bar"
            :style="{ height: (30 + (i % 4) * 15) + '%' }"
          ></div>
        </div>

        <div v-else-if="skeletonVariant === 'stat'" class="sk-stat">
          <div class="skeleton-block" style="height: 44px; width: 44px; border-radius: 50%; margin: 0 auto 0.5rem;"></div>
          <div class="skeleton-block" style="height: 28px; width: 45%; border-radius: 4px; margin: 0 auto 0.4rem;"></div>
          <div class="skeleton-block" style="height: 12px; width: 65%; border-radius: 4px; margin: 0 auto;"></div>
        </div>

        <template v-else>
          <div class="skeleton-block mb-2" style="height: 16px; width: 60%; border-radius: 4px;"></div>
          <div class="skeleton-block mb-2" style="height: 16px; width: 80%; border-radius: 4px;"></div>
          <div class="skeleton-block" style="height: 16px; width: 40%; border-radius: 4px;"></div>
        </template>
      </template>

      <slot v-else />
    </div>

    <!-- Footer opcional -->
    <div v-if="$slots.footer" class="card-footer bg-transparent border-top">
      <slot name="footer" />
    </div>
  </div>
</template>

<style scoped>
.skeleton-block {
  background: linear-gradient(90deg, var(--ds-skeleton-a) 25%, var(--ds-skeleton-b) 50%, var(--ds-skeleton-a) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Skeleton 'list' — filas tipo EntityList/ActivityFeed */
.sk-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.sk-list__row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
}
.sk-list__icon {
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 6px;
  flex-shrink: 0;
}
.sk-list__main {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.sk-list__bar {
  height: 4px;
  width: 100%;
  border-radius: 2px;
}

/* Skeleton 'chart' — barras tipo TrendChart */
.sk-chart {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  height: 220px;
  padding-top: 1rem;
}
.sk-chart__bar {
  flex: 1;
  border-radius: 4px 4px 0 0;
}

/* Skeleton 'stat' — bloque centrado tipo CommissionPanel */
.sk-stat {
  padding: 1.5rem 1rem;
  text-align: center;
}

/* Root card element — scoped applies directly (no :deep needed) */
.card {
  background: var(--ds-surface) !important;
  border-color: var(--ds-border) !important;
  color: var(--ds-text);
}
.card-header {
  background: var(--ds-surface-2) !important;
  border-color: var(--ds-border) !important;
  color: var(--ds-text);
}
.card-body { color: var(--ds-text); }
.card-footer {
  background: var(--ds-surface-2) !important;
  border-color: var(--ds-border) !important;
}
</style>
