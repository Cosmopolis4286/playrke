<script setup lang="ts" name="DashboardActivityFeed">
  import { computed } from 'vue'
  import type { ActivityItem, ActivityType } from '@/js/types/common/dashboard/dashboard'

  defineProps<{
    items: ActivityItem[]
    maxItems?: number
    columns?: 1 | 2
  }>()

  const activityConfig: Record<ActivityType, { icon: string; colorClass: string; bgClass: string }> = {
    sale:    { icon: 'fas fa-shopping-cart', colorClass: 'text-success', bgClass: 'bg-success-subtle' },
    refund:  { icon: 'fas fa-undo',          colorClass: 'text-warning', bgClass: 'bg-warning-subtle' },
    login:   { icon: 'fas fa-sign-in-alt',   colorClass: 'text-info',    bgClass: 'bg-info-subtle' },
    update:  { icon: 'fas fa-pencil-alt',    colorClass: 'text-primary',  bgClass: 'bg-primary-subtle' },
    alert:   { icon: 'fas fa-exclamation-triangle', colorClass: 'text-danger', bgClass: 'bg-danger-subtle' },
    info:    { icon: 'fas fa-ticket-alt',    colorClass: 'text-info',    bgClass: 'bg-info-subtle' },
    success: { icon: 'fas fa-check-circle',  colorClass: 'text-success', bgClass: 'bg-success-subtle' },
    danger:  { icon: 'fas fa-times-circle',  colorClass: 'text-danger',  bgClass: 'bg-danger-subtle' },
    bingo:   { icon: 'fas fa-th',            colorClass: 'text-primary',  bgClass: 'bg-primary-subtle' },
  }

  const statusConfig: Record<string, { label: string; class: string }> = {
    active:    { label: 'Activo', class: 'badge bg-success' },
    winner:    { label: 'Ganador', class: 'badge bg-warning text-dark' },
    cancelled: { label: 'Cancelado', class: 'badge bg-danger' },
    pending:   { label: 'Pendiente', class: 'badge bg-info' },
    played:    { label: 'Jugado', class: 'badge bg-secondary' },
  }

  function formatRelativeTime(iso: string): string {
    const diff = Date.now() - new Date(iso).getTime()
    const minutes = Math.floor(diff / 60000)
    if (minutes < 1)  return 'Ahora mismo'
    if (minutes < 60) return `Hace ${minutes} min`
    const hours = Math.floor(minutes / 60)
    if (hours < 24)   return `Hace ${hours} h`
    const days = Math.floor(hours / 24)
    return `Hace ${days} d`
  }
</script>

<template>
  <ul class="activity-feed list-unstyled mb-0"
      :class="{ 'row g-3': columns === 2 }">
    <template v-if="items.length">
      <li
        v-for="item in (maxItems ? items.slice(0, maxItems) : items)"
        :key="item.id"
        class="activity-feed__item d-flex gap-3 align-items-start p-3 rounded-3"
        :class="{ 'col-md-6': columns === 2 }"
      >
        <!-- Icono de tipo -->
        <div class="activity-feed__icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
             :class="activityConfig[item.type]?.bgClass ?? 'bg-light'"
             style="width:44px;height:44px;">
          <i :class="[activityConfig[item.type]?.icon ?? 'fas fa-circle', activityConfig[item.type]?.colorClass ?? 'text-muted']"></i>
        </div>

        <!-- Contenido -->
        <div class="flex-grow-1 min-w-0">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
            <p class="mb-0 fw-semibold text-truncate">{{ item.title }}</p>
            <span class="text-muted small" style="white-space:nowrap;">
              {{ formatRelativeTime(item.timestamp) }}
            </span>
          </div>
          <p v-if="item.description" class="mb-1 text-muted small">
            {{ item.description }}
          </p>
          <div class="d-flex align-items-center justify-content-between">
            <small v-if="item.actor" class="text-muted">
              <i class="fas fa-hashtag small me-1"></i>{{ item.actor }}
            </small>
            <span v-if="item.meta?.status" :class="statusConfig[item.meta.status]?.class ?? 'badge bg-secondary'">
              {{ statusConfig[item.meta.status]?.label ?? item.meta.status }}
            </span>
          </div>
          <div v-if="item.meta?.price" class="mt-1">
            <span class="badge bg-light text-dark border">
              <i class="fas fa-coins text-warning me-1"></i>
              {{ item.meta.price }} {{ item.meta.currency }}
            </span>
          </div>
        </div>
      </li>
    </template>

    <li v-else class="col-12 text-muted small text-center py-4">
      <i class="fas fa-inbox mb-2 d-block fs-4"></i>
      Sin actividad reciente
    </li>
  </ul>
</template>

<style scoped>
.activity-feed__item {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  color: var(--ds-text);
  transition: box-shadow 0.2s ease, transform 0.1s ease;
}
.activity-feed__item:hover {
  box-shadow: var(--ds-card-hover-shadow);
  transform: translateY(-1px);
}
:deep(.text-muted) { color: var(--ds-text-2) !important; }
@media (max-width: 767px) {
  .activity-feed__item {
    width: 100% !important;
  }
}
</style>
