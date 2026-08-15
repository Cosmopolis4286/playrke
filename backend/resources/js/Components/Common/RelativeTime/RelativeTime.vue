<script setup lang="ts" name="RelativeTime">
  /**
   * Muestra una fecha en formato relativo ("hace 5 min") y, al hover/click,
   * un popover con la fecha y hora completas en formato local.
   */
  import { computed } from 'vue'

  const props = withDefaults(defineProps<{
    /** Fecha en formato ISO 8601 (o cualquier string parseable por Date). */
    value: string | null
    /** Ícono FontAwesome mostrado antes del texto relativo. */
    icon?: string
  }>(), {
    icon: 'fas fa-clock',
  })

  const relativeLabel = computed(() => formatRelative(props.value))

  const fullLabel = computed(() => {
    if (!props.value) return 'Sin fecha registrada'
    const date = new Date(props.value)
    if (isNaN(date.getTime())) return 'Sin fecha registrada'

    const datePart = date.toLocaleDateString('es-VE', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
    const timePart = date.toLocaleTimeString('es-VE', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    })
    return `${datePart}, ${timePart}`
  })

  function formatRelative(value: string | null): string {
    if (!value) return '—'
    const date = new Date(value)
    if (isNaN(date.getTime())) return '—'

    const diffSeconds = Math.floor((Date.now() - date.getTime()) / 1000)
    if (diffSeconds < 5) return 'Justo ahora'
    if (diffSeconds < 60) return `Hace ${diffSeconds} seg`

    const diffMinutes = Math.floor(diffSeconds / 60)
    if (diffMinutes < 60) return `Hace ${diffMinutes} min`

    const diffHours = Math.floor(diffMinutes / 60)
    if (diffHours < 24) return `Hace ${diffHours} h`

    const diffDays = Math.floor(diffHours / 24)
    if (diffDays < 30) return `Hace ${diffDays} d`

    const diffMonths = Math.floor(diffDays / 30)
    if (diffMonths < 12) return `Hace ${diffMonths} ${diffMonths === 1 ? 'mes' : 'meses'}`

    const diffYears = Math.floor(diffMonths / 12)
    return `Hace ${diffYears} ${diffYears === 1 ? 'año' : 'años'}`
  }
</script>

<template>
  <VDropdown placement="top" :distance="8" :triggers="['hover', 'click', 'focus']">
    <span class="relative-time" tabindex="0">
      <i :class="icon" class="relative-time-icon"></i>{{ relativeLabel }}
    </span>
    <template #popper>
      <div class="relative-time-popover">{{ fullLabel }}</div>
    </template>
  </VDropdown>
</template>

<style scoped>
.relative-time {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  cursor: default;
  color: var(--ds-text-2);
  font-size: 0.85rem;
  white-space: nowrap;
}
.relative-time-icon {
  font-size: 0.75rem;
  opacity: 0.7;
}
.relative-time-popover {
  padding: 0.35rem 0.15rem;
  font-size: 0.8rem;
  font-weight: 600;
  white-space: nowrap;
}
</style>
