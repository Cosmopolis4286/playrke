<script setup lang="ts" name="DashboardActivityFeed">
  import { computed } from 'vue'
  import type { ActivityItem, ActivityType } from '@/js/types/common/dashboard/dashboard'

  defineProps<{
    items: ActivityItem[]
    maxItems?: number
  }>()

  const activityConfig: Record<ActivityType, { icon: string; colorClass: string }> = {
    sale:    { icon: 'fas fa-shopping-cart', colorClass: 'text-success' },
    refund:  { icon: 'fas fa-undo',          colorClass: 'text-warning' },
    login:   { icon: 'fas fa-sign-in-alt',   colorClass: 'text-info'    },
    update:  { icon: 'fas fa-pencil-alt',    colorClass: 'text-primary'  },
    alert:   { icon: 'fas fa-exclamation-triangle', colorClass: 'text-danger' },
    info:    { icon: 'fas fa-info-circle',   colorClass: 'text-info'    },
    success: { icon: 'fas fa-check-circle',  colorClass: 'text-success' },
    danger:  { icon: 'fas fa-times-circle',  colorClass: 'text-danger'  },
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
  <ul class="activity-feed list-unstyled mb-0">
    <template v-if="items.length">
      <li
        v-for="item in (maxItems ? items.slice(0, maxItems) : items)"
        :key="item.id"
        class="activity-feed__item d-flex gap-3 align-items-start py-2"
      >
        <!-- Icono de tipo -->
        <div class="activity-feed__icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-light"
             style="width:36px;height:36px;">
          <i :class="[activityConfig[item.type]?.icon ?? 'fas fa-circle', activityConfig[item.type]?.colorClass ?? 'text-muted', 'small']"></i>
        </div>

        <!-- Contenido -->
        <div class="flex-grow-1 min-w-0">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <p class="mb-0 small fw-semibold text-truncate">{{ item.title }}</p>
            <span class="text-muted" style="font-size:11px;white-space:nowrap;">
              {{ formatRelativeTime(item.timestamp) }}
            </span>
          </div>
          <p v-if="item.description" class="mb-0 text-muted small text-truncate">
            {{ item.description }}
          </p>
          <small v-if="item.actor" class="text-muted">por {{ item.actor }}</small>
        </div>
      </li>
    </template>

    <li v-else class="text-muted small text-center py-4">
      <i class="fas fa-inbox mb-2 d-block fs-4"></i>
      Sin actividad reciente
    </li>
  </ul>
</template>

<style scoped>
.activity-feed__item + .activity-feed__item {
  border-top: 1px solid #f1f3f5;
}
</style>
