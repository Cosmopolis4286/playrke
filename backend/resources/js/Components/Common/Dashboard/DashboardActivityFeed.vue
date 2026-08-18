<script setup lang="ts" name="DashboardActivityFeed">
  import { computed, ref, watch } from 'vue'
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import type { ActivityItem, ActivityType } from '@/js/types/common/dashboard/dashboard'

  const props = withDefaults(defineProps<{
    items: ActivityItem[]
    maxItems?: number
    columns?: 1 | 2
    /** Altura máxima con scroll interno propio (px). Sin valor: sin límite (comportamiento previo). */
    maxHeight?: number
    /** Muestra la barra de filtros por tipo cuando hay más de un tipo presente. */
    filterable?: boolean
    /** Indicador "live" — punto pulsante junto al contador de nuevos. */
    isLive?: boolean
  }>(), {
    filterable: false,
    isLive: false,
  })

  const activityConfig: Record<ActivityType, { icon: string; colorClass: string; bgClass: string; label: string }> = {
    sale:    { icon: 'fas fa-shopping-cart', colorClass: 'text-success', bgClass: 'bg-success-subtle', label: 'Ventas' },
    refund:  { icon: 'fas fa-undo',          colorClass: 'text-warning', bgClass: 'bg-warning-subtle', label: 'Reembolsos' },
    login:   { icon: 'fas fa-sign-in-alt',   colorClass: 'text-info',    bgClass: 'bg-info-subtle',    label: 'Accesos' },
    update:  { icon: 'fas fa-pencil-alt',    colorClass: 'text-primary',  bgClass: 'bg-primary-subtle', label: 'Actualizaciones' },
    alert:   { icon: 'fas fa-exclamation-triangle', colorClass: 'text-danger', bgClass: 'bg-danger-subtle', label: 'Alertas' },
    info:    { icon: 'fas fa-ticket-alt',    colorClass: 'text-info',    bgClass: 'bg-info-subtle',    label: 'Info' },
    success: { icon: 'fas fa-check-circle',  colorClass: 'text-success', bgClass: 'bg-success-subtle', label: 'Aprobados' },
    danger:  { icon: 'fas fa-times-circle',  colorClass: 'text-danger',  bgClass: 'bg-danger-subtle',  label: 'Rechazados' },
    bingo:   { icon: 'fas fa-th',            colorClass: 'text-primary',  bgClass: 'bg-primary-subtle', label: 'Bingo' },
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

  // --------------------------------------------------------------------------
  // Filtro por tipo
  // --------------------------------------------------------------------------

  const activeFilter = ref<ActivityType | null>(null)

  const presentTypes = computed<ActivityType[]>(() => {
    const seen = new Set<ActivityType>()
    props.items.forEach((item) => seen.add(item.type))
    return Array.from(seen)
  })

  const visibleItems = computed(() => {
    const filtered = activeFilter.value
      ? props.items.filter((item) => item.type === activeFilter.value)
      : props.items
    return props.maxItems ? filtered.slice(0, props.maxItems) : filtered
  })

  // --------------------------------------------------------------------------
  // Contador de items nuevos no vistos (se limpia al hacer scroll o click)
  // --------------------------------------------------------------------------

  const seenCount = ref(props.items.length)
  const unseenCount = computed(() => Math.max(0, props.items.length - seenCount.value))

  watch(() => props.items.length, (len, prevLen) => {
    if (len < prevLen) seenCount.value = len // se reemplazó/filtró la lista, no "nuevos"
  })

  function markAllSeen() {
    seenCount.value = props.items.length
  }
</script>

<template>
  <div class="activity-feed-wrap">
    <!-- Filtros por tipo -->
    <div v-if="filterable && presentTypes.length > 1" class="af-filters">
      <button
        type="button"
        class="af-filter-chip"
        :class="{ 'af-filter-chip--active': activeFilter === null }"
        @click="activeFilter = null"
      >
        Todo
      </button>
      <button
        v-for="type in presentTypes"
        :key="type"
        type="button"
        class="af-filter-chip"
        :class="[{ 'af-filter-chip--active': activeFilter === type }, activityConfig[type]?.colorClass]"
        @click="activeFilter = type"
      >
        <i :class="activityConfig[type]?.icon"></i>
        {{ activityConfig[type]?.label ?? type }}
      </button>
    </div>

    <!-- Aviso de nuevos items no vistos -->
    <button
      v-if="unseenCount > 0"
      type="button"
      class="af-unseen-banner"
      @click="markAllSeen"
    >
      <span class="af-unseen-dot" v-if="isLive"></span>
      {{ unseenCount }} {{ unseenCount === 1 ? 'evento nuevo' : 'eventos nuevos' }}
      <i class="fas fa-arrow-up ms-1"></i>
    </button>

    <ul
      class="activity-feed list-unstyled mb-0"
      :class="{ 'row g-3': columns === 2, 'activity-feed--scroll': maxHeight }"
      :style="maxHeight ? { maxHeight: `${maxHeight}px` } : undefined"
      @scroll="markAllSeen"
    >
      <template v-if="visibleItems.length">
        <li
          v-for="item in visibleItems"
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

      <li v-else class="col-12">
        <DashboardEmptyState
          compact
          icon="fas fa-inbox"
          :title="activeFilter ? 'Sin actividad de este tipo' : 'Sin actividad reciente'"
          :description="activeFilter ? 'Prueba con otro filtro o vuelve a \'Todo\'.' : 'Las últimas acciones aparecerán aquí en cuanto ocurran.'"
        />
      </li>
    </ul>
  </div>
</template>

<style scoped>
.activity-feed-wrap { position: relative; }

/* Filtros */
.af-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 0.75rem;
}
.af-filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 0.3rem 0.65rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 999px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.af-filter-chip:hover { background: var(--ds-surface); }
.af-filter-chip--active {
  background: #667eea;
  border-color: #667eea;
  color: #fff !important;
}

/* Banner de no vistos */
.af-unseen-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  margin-bottom: 0.6rem;
  padding: 0.4rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 8px;
  border: 1px dashed #667eea;
  background: rgba(102, 126, 234, 0.08);
  color: #667eea;
  cursor: pointer;
}
.af-unseen-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #22c55e; box-shadow: 0 0 5px #22c55e;
  animation: afUnseenPulse 1.8s ease-in-out infinite;
  display: inline-block;
}
@keyframes afUnseenPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.8); }
}

/* Scroll interno propio */
.activity-feed--scroll {
  overflow-y: auto;
  padding-right: 4px;
}

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
