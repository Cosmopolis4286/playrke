<template>
  <BaseCard
    class="
      'stat-card relative overflow-hidden transition-all duration-300',
      { 'hover:shadow-lg hover:-translate-y-1': !loading }
    "
  >
    <!-- Loading State -->
    <div
      v-if="loading"
      class="p-6"
    >
      <div class="animate-pulse space-y-4">
        <div class="flex items-center justify-between">
          <div class="h-4 bg-secondary-200 rounded w-24" />
          <div class="h-4 w-4 bg-secondary-200 rounded" />
        </div>
        <div class="space-y-2">
          <div class="h-8 bg-secondary-200 rounded w-32" />
          <div class="h-3 bg-secondary-200 rounded w-40" />
        </div>
        <div class="h-3 bg-secondary-200 rounded w-20" />
      </div>
    </div>

    <!-- Content -->
    <div
      v-else
      class="p-6"
    >
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-medium text-secondary-600">
          {{ title }}
        </h3>
        <div :class="iconContainerClasses">
          <SharedIcon
            :name="getIconName(icon)"
            class="w-4 h-4"
          />
        </div>
      </div>

      <!-- Main Value -->
      <div class="space-y-2">
        <div class="text-2xl font-bold text-secondary-900">
          {{ value }}
        </div>

        <!-- Subtitle -->
        <div
          v-if="$slots.subtitle"
          class="text-sm text-secondary-500"
        >
          <slot name="subtitle" />
        </div>
      </div>

      <!-- Change Indicator -->
      <div
        v-if="change !== undefined"
        class="flex items-center mt-4"
      >
        <div :class="changeContainerClasses">
          <SharedIcon
            :name="changeSharedIcon"
            class="w-3 h-3 mr-1"
          />
          <span class="text-xs font-medium">
            {{ formatChange(change) }}
          </span>
        </div>
        <span
          v-if="period"
          class="text-xs text-secondary-500 ml-2"
        >
          vs {{ period }}
        </span>
      </div>

      <!-- Progress Bar (optional) -->
      <div
        v-if="progress !== undefined"
        class="mt-4"
      >
        <div class="flex items-center justify-between text-xs text-secondary-600 mb-1">
          <span>Progreso</span>
          <span>{{ Math.round(validateProgress(progress)) }}%</span>
        </div>
        <div class="w-full bg-secondary-200 rounded-full h-2">
          <div
            :class="progressBarClasses"
            :style="{ width: `${validateProgress(progress)}%` }"
          />
        </div>
      </div>

      <!-- Action Button (optional) -->
      <div
        v-if="actionText"
        class="mt-4"
      >
        <button
          :class="actionButtonClasses"
          @click="$emit('action')"
        >
          {{ actionText }}
          <SharedIcon
            name="arrow-right"
            class="w-3 h-3 ml-1"
          />
        </button>
      </div>
    </div>

    <!-- Accent Border -->
    <div :class="accentBorderClasses" />
  </BaseCard>
</template>

<script setup lang="ts">
/**
 * @fileoverview StatCard component - Tarjeta de estadística
 * @description Componente para mostrar KPIs y métricas con indicadores visuales
 * @version 2.0.0
 * @since 2025-09-25
 *
 * Features:
 * - Soporte para múltiples tipos de métricas
 * - Indicadores visuales de cambio/tendencia
 * - Estados de carga con skeleton
 * - Barras de progreso opcionales
 * - Botones de acción integrados
 * - Themes de color configurables
 * - Responsive design
 * - Accesibilidad mejorada
 */

/**
 * Tipo para los colores disponibles en el theme
 */
type ColorVariant = 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'info'

/**
 * Tipo para las tendencias de cambio
 */
type TrendVariant = 'up' | 'down' | 'neutral'

/**
 * Props del componente StatCard
 */
interface Props {
  /** Título de la estadística */
  title: string
  /** Valor principal a mostrar */
  value: string | number
  /** Porcentaje de cambio respecto al período anterior */
  change?: number
  /** Tendencia del cambio (determina color e ícono) */
  trend?: TrendVariant
  /** Ícono principal (nombre genérico que se mapea a Heroicons) */
  icon: string
  /** Color theme de la tarjeta */
  color?: ColorVariant
  /** Estado de carga (muestra skeleton) */
  loading?: boolean
  /** Período de comparación (ej: "mes anterior") */
  period?: string
  /** Progreso como porcentaje (0-100) */
  progress?: number
  /** Texto del botón de acción opcional */
  actionText?: string
}

interface Emits {
  /** Evento de acción */
  action: []
}

defineOptions({
  name: 'StatCard',
})

const props = withDefaults(defineProps<Props>(), {
  color: 'primary',
  loading: false,
  trend: 'neutral',
})

defineEmits<Emits>()

// Constantes para mapeo de iconos
const ICON_MAP = {
  'currency': 'currency-dollar',
  'users': 'users',
  'ticket': 'ticket',
  'tickets': 'ticket',
  'chart': 'chart-bar',
  'conversion': 'arrow-up-right',
  'sales': 'shopping-cart',
  'orders': 'shopping-bag',
  'revenue': 'banknotes',
  'growth': 'chart-bar',
  'target': 'target',
  'trending-up': 'arrow-up-right',
  'trending-down': 'arrow-down-right',
  'arrow-trending-up': 'arrow-up-right',
  'arrow-trending-down': 'arrow-down-right',
  'performance': 'chart-bar-square',
  'analytics': 'presentation-chart-line',
  'dashboard': 'squares-2x2',
  'commerce': 'building-storefront',
  'store': 'building-storefront',
  'game': 'puzzle-piece',
  'trophy': 'trophy',
  'clock': 'clock',
  'calendar': 'calendar',
  'notification': 'bell',
  'settings': 'cog-6-tooth',
  'user': 'user',
  'profile': 'user-circle',
  'email': 'envelope',
  'phone': 'phone',
  'location': 'map-pin',
  'search': 'magnifying-glass',
  'filter': 'funnel',
  'download': 'arrow-down-tray',
  'upload': 'arrow-up-tray',
  'share': 'share',
  'like': 'heart',
  'star': 'star',
  'flag': 'flag',
  'tag': 'tag',
  'folder': 'folder',
  'lightbulb': 'sparkles',
  'light-bulb': 'sparkles',
  'tips': 'sparkles',
  'insights': 'sparkles',
  'file': 'document',
  'image': 'photo',
  'video': 'video-camera',
  'music': 'musical-note',
  'lock': 'lock-closed',
  'unlock': 'lock-open',
  'shield': 'shield-check',
  'warning': 'exclamation-triangle',
  'error': 'x-circle',
  'success': 'check-circle',
  'info': 'information-circle',
} as const

// Funciones helper
/**
 * Mapea un nombre de ícono genérico a un ícono específico de Heroicons
 * @param iconName - Nombre genérico del ícono
 * @returns Nombre del ícono de Heroicons o fallback
 */
const getIconName = (iconName: string): string => ICON_MAP[iconName as keyof typeof ICON_MAP] || iconName || 'question-mark-circle'

/**
 * Valida que un valor de progreso esté en el rango correcto
 * @param progress - Valor de progreso
 * @returns Progreso validado entre 0 y 100
 */
const validateProgress = (progress: number): number => Math.max(0, Math.min(100, progress))

// Computed properties
const iconContainerClasses = computed(() => {
  const baseClasses = 'flex items-center justify-center w-8 h-8 rounded-lg'

  switch (props.color) {
    case 'success':
      return `${baseClasses} bg-success-100 text-success-600`
    case 'warning':
      return `${baseClasses} bg-warning-100 text-warning-600`
    case 'error':
      return `${baseClasses} bg-error-100 text-error-600`
    case 'info':
      return `${baseClasses} bg-info-100 text-info-600`
    case 'secondary':
      return `${baseClasses} bg-secondary-100 text-secondary-600`
    default:
      return `${baseClasses} bg-primary-100 text-primary-600`
  }
})

const changeSharedIcon = computed(() => {
  if (props.trend === 'up') { return 'arrow-up-right' }
  if (props.trend === 'down') { return 'arrow-down-right' }
  return 'minus'
})

const changeContainerClasses = computed(() => {
  const baseClasses = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium'

  if (props.trend === 'up') {
    return `${baseClasses} bg-success-100 text-success-700`
  } else if (props.trend === 'down') {
    return `${baseClasses} bg-error-100 text-error-700`
  } else {
    return `${baseClasses} bg-secondary-100 text-secondary-700`
  }
})

const progressBarClasses = computed(() => {
  const baseClasses = 'h-2 rounded-full transition-all duration-500 ease-out'

  switch (props.color) {
    case 'success':
      return `${baseClasses} bg-success-500`
    case 'warning':
      return `${baseClasses} bg-warning-500`
    case 'error':
      return `${baseClasses} bg-error-500`
    case 'info':
      return `${baseClasses} bg-info-500`
    case 'secondary':
      return `${baseClasses} bg-secondary-500`
    default:
      return `${baseClasses} bg-primary-500`
  }
})

const actionButtonClasses = computed(() => {
  const baseClasses = 'inline-flex items-center text-xs font-medium hover:underline transition-colors duration-200'

  switch (props.color) {
    case 'success':
      return `${baseClasses} text-success-600 hover:text-success-700`
    case 'warning':
      return `${baseClasses} text-warning-600 hover:text-warning-700`
    case 'error':
      return `${baseClasses} text-error-600 hover:text-error-700`
    case 'info':
      return `${baseClasses} text-info-600 hover:text-info-700`
    case 'secondary':
      return `${baseClasses} text-secondary-600 hover:text-secondary-700`
    default:
      return `${baseClasses} text-primary-600 hover:text-primary-700`
  }
})

const accentBorderClasses = computed(() => {
  const baseClasses = 'absolute top-0 left-0 w-full h-1'

  switch (props.color) {
    case 'success':
      return `${baseClasses} bg-success-500`
    case 'warning':
      return `${baseClasses} bg-warning-500`
    case 'error':
      return `${baseClasses} bg-error-500`
    case 'info':
      return `${baseClasses} bg-info-500`
    case 'secondary':
      return `${baseClasses} bg-secondary-500`
    default:
      return `${baseClasses} bg-primary-500`
  }
})

// Methods
/**
 * Formatea el porcentaje de cambio con validación
 * @param change - Porcentaje de cambio
 * @returns String formateado con signo y símbolo de porcentaje
 */
const formatChange = (change: number): string => {
  // Validar que el cambio sea un número válido
  if (typeof change !== 'number' || !isFinite(change)) {
    return '0.0%'
  }

  // Limitar a valores razonables (-999.9% a 999.9%)
  const limitedChange = Math.max(-999.9, Math.min(999.9, change))

  const sign = limitedChange > 0 ? '+' : ''
  return `${sign}${limitedChange.toFixed(1)}%`
}

/**
 * Determina si el componente debe mostrar estado de error
 * @returns true si hay errores de validación
 */
const hasValidationErrors = computed(() => !props.title || (props.icon && !getIconName(props.icon)))

/**
 * Obtiene clases CSS adicionales basadas en el estado del componente
 * @returns string con clases CSS condicionales
 */
const componentStateClasses = computed(() => {
  const classes = []

  if (hasValidationErrors.value) {
    classes.push('border-error-300')
  }

  if (!props.loading && props.change !== undefined) {
    classes.push('has-change-indicator')
  }

  return classes.join(' ')
})
</script>

<style scoped>
.stat-card {
  @apply cursor-default;
}

/* Hover effects */
.stat-card:hover .progress-bar {
  @apply shadow-sm;
}

/* Loading animation */
.stat-card .animate-pulse > div {
  animation-duration: 1.5s;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .stat-card {
    @apply text-sm;
  }

  .stat-card .text-2xl {
    @apply text-xl;
  }
}

/* Focus states for accessibility */
.stat-card button:focus {
  @apply outline-none ring-2 ring-offset-2;
}

.stat-card button:focus.text-primary-600 {
  @apply ring-primary-500;
}

.stat-card button:focus.text-success-600 {
  @apply ring-success-500;
}

.stat-card button:focus.text-warning-600 {
  @apply ring-warning-500;
}

.stat-card button:focus.text-error-600 {
  @apply ring-error-500;
}

.stat-card button:focus.text-info-600 {
  @apply ring-info-500;
}

.stat-card button:focus.text-secondary-600 {
  @apply ring-secondary-500;
}
</style>
