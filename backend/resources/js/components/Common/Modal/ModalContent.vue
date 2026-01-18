<template>
  <div :class="containerClasses">
    <!-- Icono principal -->
    <ModalElement
      v-if="iconName || customIcon"
      type="icon"
      :icon-name="iconName"
      :custom-icon="customIcon"
      :variant="iconVariant"
      :size="iconSize as any"
      :animated="animated"
      :glow="iconGlow"
      class="flex-shrink-0"
    />

    <!-- Contenido principal -->
    <div :class="contentClasses">
      <!-- Título/Header -->
      <div
        v-if="title || $slots.title"
        :class="titleClasses"
      >
        <slot name="title">
          <h3 :class="titleTextClasses">
            {{ title }}
          </h3>
        </slot>
      </div>

      <!-- Subtítulo/Descripción -->
      <div
        v-if="subtitle || description || $slots.subtitle || $slots.description"
        :class="subtitleClasses"
      >
        <slot name="subtitle">
          <p :class="subtitleTextClasses">
            {{ subtitle || description }}
          </p>
        </slot>
      </div>

      <!-- Contenido personalizado -->
      <div
        v-if="$slots.default"
        :class="defaultSlotClasses"
      >
        <slot />
      </div>

      <!-- Información adicional -->
      <div
        v-if="info || $slots.info"
        :class="infoClasses"
      >
        <slot name="info">
          <span :class="infoTextClasses">{{ info }}</span>
        </slot>
      </div>

      <!-- Status/Estado -->
      <div
        v-if="status || showStatus"
        :class="statusClasses"
      >
        <div :class="statusIndicatorClasses" />
        <span
          v-if="status"
          :class="statusTextClasses"
        >{{ status }}</span>
        <slot name="status" />
      </div>

      <!-- Botones de acción -->
      <div
        v-if="actions.length > 0 || $slots.actions"
        :class="actionsClasses"
      >
        <slot name="actions">
          <ModalElement
            v-for="action in actions"
            :key="action.key || action.label"
            type="action"
            :variant="action.variant"
            :size="action.size as any"
            :label="action.label"
            :icon-name="action.iconName"
            :custom-icon="action.customIcon"
            :disabled="action.disabled"
            :loading="action.loading"
            :animated="animated"
            :ripple="ripple"
            @click="handleActionClick(action)"
          />
        </slot>
      </div>

      <!-- Progress bar -->
      <ModalProgress
        v-if="showProgress"
        :value="progressValue"
        :variant="progressVariant as any"
        :size="progressSize as any"
        :animated="animated"
        :indeterminate="progressIndeterminate"
        :label="progressLabel"
        :show-text="showProgressText"
        :position="position as any"
      />
    </div>

    <!-- Badge/Contador -->
    <div
      v-if="badge"
      :class="badgeClasses"
    >
      {{ badge }}
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview ModalContent - Componente unificado para contenido modal
 * @description Reemplaza ModalHeaderText, ModalFooterButton, ModalFooterAction,
 * ModalFooterInfo y ModalFooterStatusBar con un solo componente versátil
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-17
 */

import { computed, type Component } from 'vue'
import ModalElement from './ModalElement.vue'
import ModalProgress from './ModalProgress.vue'
import type { IconType } from '~/components/shared/media/Icon.vue'

type ElementSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
type ElementVariant = 'default' | 'primary' | 'secondary' | 'ghost' | 'danger' | 'success' | 'warning' | 'gaming' | 'premium' | 'minimal'
type ProgressVariant = 'default' | 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'gaming'
type ProgressSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
type ProgressPosition = 'header' | 'footer' | 'body'
type ContentPosition = 'header' | 'footer' | 'body'
type ContentLayout = 'horizontal' | 'vertical' | 'centered'
type ContentVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'gaming' | 'premium'

interface ActionItem {
  key?: string
  label: string
  iconName?: IconType
  customIcon?: Component
  variant?: ElementVariant
  size?: ElementSize
  disabled?: boolean
  loading?: boolean
  onClick?: () => void
}

interface Props {
  // Posición y layout
  /** Posición en el modal */
  position?: ContentPosition
  /** Layout del contenido */
  layout?: ContentLayout
  /** Variante visual */
  variant?: ContentVariant

  // Contenido principal
  /** Título principal */
  title?: string
  /** Subtítulo o descripción */
  subtitle?: string
  /** Descripción (alias de subtitle) */
  description?: string
  /** Información adicional */
  info?: string
  /** Estado/status */
  status?: string
  /** Badge/contador */
  badge?: string | number

  // Icono
  /** Nombre del icono */
  iconName?: IconType
  /** Icono personalizado */
  customIcon?: Component
  /** Variante del icono */
  iconVariant?: 'default' | 'primary' | 'secondary' | 'ghost' | 'danger' | 'success' | 'warning' | 'gaming' | 'premium' | 'minimal'
  /** Tamaño del icono */
  iconSize?: ElementSize | string
  /** Glow en el icono */
  iconGlow?: boolean

  // Progreso
  /** Mostrar barra de progreso */
  showProgress?: boolean
  /** Valor del progreso */
  progressValue?: number
  /** Variante del progreso */
  progressVariant?: ProgressVariant
  /** Tamaño del progreso */
  progressSize?: ProgressSize
  /** Progreso indeterminado */
  progressIndeterminate?: boolean
  /** Label del progreso */
  progressLabel?: string
  /** Mostrar texto del progreso */
  showProgressText?: boolean

  // Estado
  /** Mostrar indicador de estado */
  showStatus?: boolean

  // Acciones
  /** Array de acciones */
  actions?: ActionItem[]

  // Comportamiento
  /** Animaciones habilitadas */
  animated?: boolean
  /** Efecto ripple en botones */
  ripple?: boolean
  /** Contenido centrado */
  centered?: boolean
  /** Espaciado compacto */
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  position: 'body',
  layout: 'vertical',
  variant: 'default',
  iconVariant: 'default',
  iconSize: 'md',
  iconGlow: false,
  showProgress: false,
  progressValue: 0,
  progressVariant: 'primary',
  progressSize: 'md',
  progressIndeterminate: false,
  showProgressText: true,
  showStatus: false,
  actions: () => [],
  animated: true,
  ripple: true,
  centered: false,
  compact: false,
})

const emit = defineEmits<{
  actionClick: [action: ActionItem]
}>()

// Computadas principales
const containerClasses = computed(() => [
  'flex w-full',
  {
    // Layout
    'flex-row items-start gap-3': props.layout === 'horizontal',
    'flex-col gap-2': props.layout === 'vertical',
    'flex-col items-center text-center gap-3': props.layout === 'centered',

    // Posición específica
    'items-center': props.position === 'header' && props.layout === 'horizontal',

    // Espaciado
    'gap-1': props.compact,
    'p-2': props.position === 'footer' && props.compact,
    'p-4': props.position === 'footer' && !props.compact,

    // Centrado
    'justify-center text-center': props.centered,
  },
])

const contentClasses = computed(() => [
  'flex-1 min-w-0',
  {
    'space-y-1': props.layout === 'vertical' && props.compact,
    'space-y-2': props.layout === 'vertical' && !props.compact,
    'text-center': props.layout === 'centered' || props.centered,
  },
])

const titleClasses = computed(() => [
  'flex items-center',
  {
    'justify-center': props.layout === 'centered' || props.centered,
    'mb-1': props.subtitle || props.description,
  },
])

const titleTextClasses = computed(() => [
  'font-semibold leading-tight',
  {
    // Tamaños por posición
    'text-lg': props.position === 'header',
    'text-base': props.position === 'body',
    'text-sm': props.position === 'footer',

    // Colores por posición
    'text-white': props.position === 'header',
    'text-gray-900 dark:text-white': props.position !== 'header',

    // Variantes especiales
    'text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400':
      props.variant === 'gaming' && props.position === 'header',
    'text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-amber-400':
      props.variant === 'premium' && props.position === 'header',
  },
])

const subtitleClasses = computed(() => [
  {
    'mt-1': props.title,
    'text-center': props.layout === 'centered' || props.centered,
  },
])

const subtitleTextClasses = computed(() => [
  'leading-relaxed',
  {
    // Tamaños por posición
    'text-sm': props.position === 'header',
    'text-base': props.position === 'body',
    'text-xs': props.position === 'footer',

    // Colores por posición
    'text-white/80': props.position === 'header',
    'text-gray-600 dark:text-gray-300': props.position !== 'header',
  },
])

const defaultSlotClasses = computed(() => [
  {
    'mt-2': props.title || props.subtitle,
    'text-center': props.layout === 'centered' || props.centered,
  },
])

const infoClasses = computed(() => [
  'mt-2',
  {
    'text-center': props.layout === 'centered' || props.centered,
  },
])

const infoTextClasses = computed(() => [
  'text-xs',
  {
    'text-white/70': props.position === 'header',
    'text-gray-500 dark:text-gray-400': props.position !== 'header',
  },
])

const statusClasses = computed(() => [
  'flex items-center gap-2 mt-2',
  {
    'justify-center': props.layout === 'centered' || props.centered,
  },
])

const statusIndicatorClasses = computed(() => [
  'w-2 h-2 rounded-full',
  {
    'bg-emerald-500': props.variant === 'success',
    'bg-amber-500': props.variant === 'warning',
    'bg-red-500': props.variant === 'danger',
    'bg-primary-500': props.variant === 'primary',
    'bg-purple-500 animate-pulse': props.variant === 'gaming',
    'bg-yellow-500 animate-pulse': props.variant === 'premium',
    'bg-gray-400': props.variant === 'default',
  },
])

const statusTextClasses = computed(() => [
  'text-xs font-medium',
  {
    'text-white/90': props.position === 'header',
    'text-gray-600 dark:text-gray-300': props.position !== 'header',
  },
])

const actionsClasses = computed(() => [
  'flex gap-2 mt-3',
  {
    'justify-center': props.layout === 'centered' || props.centered,
    'justify-end': props.position === 'footer' && !props.centered,
    'flex-wrap': props.actions.length > 3,
  },
])

const badgeClasses = computed(() => [
  'absolute -top-1 -right-1 min-w-[1.25rem] h-5',
  'flex items-center justify-center',
  'text-xs font-bold text-white',
  'rounded-full border-2 border-current shadow-sm',
  {
    'bg-red-500': props.variant === 'danger' || !props.variant,
    'bg-emerald-500': props.variant === 'success',
    'bg-amber-500': props.variant === 'warning',
    'bg-primary-500': props.variant === 'primary',
    'bg-gradient-to-r from-purple-500 to-pink-500': props.variant === 'gaming',
    'bg-gradient-to-r from-yellow-400 to-amber-500': props.variant === 'premium',
  },
])

// Métodos
const handleActionClick = (action: ActionItem) => {
  if (action.onClick) {
    action.onClick()
  }
  emit('actionClick', action)
}
</script>

<style scoped>
/* Gradientes para variantes gaming y premium */
.gaming-gradient {
  background: linear-gradient(135deg, rgba(168, 85, 247, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
}

.premium-gradient {
  background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%);
}

/* Animaciones especiales */
@keyframes gaming-pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.7;
  }
}

@keyframes premium-shimmer {
  0% {
    transform: translateX(-100%);
  }

  100% {
    transform: translateX(100%);
  }
}

.gaming-pulse {
  animation: gaming-pulse 2s ease-in-out infinite;
}

.premium-shimmer {
  position: relative;
  overflow: hidden;
}

.premium-shimmer::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  animation: premium-shimmer 2s infinite;
}

/* Responsive design */
@media (max-width: 640px) {
  .flex-row {
    flex-direction: column;
  }

  .gap-3 {
    gap: 0.5rem;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .text-transparent {
    color: currentColor !important;
    background: none !important;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .animate-pulse {
    animation: none !important;
  }
}
</style>
