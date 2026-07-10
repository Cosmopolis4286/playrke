<script setup lang="ts" name="DashboardSection">
  import type { DashboardSectionProps } from '@/js/types/common/dashboard/dashboard'

  withDefaults(defineProps<DashboardSectionProps>(), {
    bordered: true,
    loading: false,
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
      <!-- Skeleton mientras carga -->
      <template v-if="loading">
        <div class="skeleton-block mb-2" style="height: 16px; width: 60%; border-radius: 4px;"></div>
        <div class="skeleton-block mb-2" style="height: 16px; width: 80%; border-radius: 4px;"></div>
        <div class="skeleton-block" style="height: 16px; width: 40%; border-radius: 4px;"></div>
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
  background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 50%, #e9ecef 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
