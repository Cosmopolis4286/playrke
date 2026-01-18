<template>
  <component
    :is="elementTag"
    :type="buttonType"
    :disabled="disabled || loading"
    :class="elementClasses"
    :aria-label="computedAriaLabel"
    :title="tooltip"
    v-bind="elementAttrs"
    @click="handleClick"
    @mouseenter="handleHover(true)"
    @mouseleave="handleHover(false)"
    @focus="handleFocus(true)"
    @blur="handleFocus(false)"
  >
    <!-- Loading Spinner -->
    <Icon
      v-if="loading"
      name="spinner"
      :size="iconSize"
      spinning
      :aria-hidden="false"
      :aria-label="loadingText"
    />

    <!-- SharedIcon Content -->
    <Icon
      v-else-if="iconName"
      :name="iconName"
      :variant="iconVariant"
      :size="iconSize"
      :color="iconColor as any"
      :spinning="spinning"
      :pulse="pulse"
    />

    <!-- Custom SharedIcon -->
    <component
      :is="customIcon"
      v-else-if="customIcon"
      :class="iconClasses"
      :aria-hidden="true"
    />

    <!-- Label Text -->
    <span
      v-if="label && !iconOnly"
      :class="labelClasses"
    >
      {{ label }}
    </span>

    <!-- Badge/Counter -->
    <span
      v-if="badge"
      :class="badgeClasses"
    >
      {{ badge }}
    </span>

    <!-- Ripple Effect -->
    <div
      v-if="showRipple"
      :class="rippleClasses"
    />

    <!-- Glow Effect -->
    <div
      v-if="showGlow"
      :class="glowClasses"
    />
  </component>
</template>

<script setup lang="ts">
/**
 * @fileoverview ModalElement - Componente unificado para elementos modales dinámicos
 * @description Reemplaza ModalHeaderIcon, ModalHeaderAction y ModalCloseButton
 * con un solo componente reutilizable y configurable
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-17
 */

import { computed, ref, type Component } from 'vue'
import type { IconType, IconVariant, IconSize } from '~/components/shared/media/Icon.vue'

type ElementType = 'icon' | 'action' | 'close'
type ElementVariant = 'default' | 'primary' | 'secondary' | 'ghost' | 'danger' | 'success' | 'warning' | 'gaming' | 'premium' | 'minimal'
type ElementSize = 'sm' | 'md' | 'lg' | 'xl'

interface Props {
  /** Tipo de elemento modal */
  type?: ElementType
  /** Variante visual */
  variant?: ElementVariant
  /** Tamaño del elemento */
  size?: ElementSize

  // Contenido
  /** Nombre del icono usando SharedIcon */
  iconName?: IconType
  /** Variante del icono (outline/solid) */
  iconVariant?: IconVariant
  /** Color del icono */
  iconColor?: string
  /** Icono personalizado */
  customIcon?: Component
  /** Texto del label */
  label?: string
  /** Tooltip del elemento */
  tooltip?: string
  /** Badge/contador */
  badge?: string | number

  // Comportamiento
  /** Tipo de botón */
  buttonType?: 'button' | 'submit' | 'reset'
  /** Elemento deshabilitado */
  disabled?: boolean
  /** Estado de carga */
  loading?: boolean
  /** Texto de carga */
  loadingText?: string
  /** Solo mostrar icono */
  iconOnly?: boolean
  /** Ancho completo */
  fullWidth?: boolean
  /** Posicionamiento para close button */
  position?: 'top-right' | 'inline'

  // Efectos
  /** Animaciones habilitadas */
  animated?: boolean
  /** Efecto ripple */
  ripple?: boolean
  /** Efecto glow */
  glow?: boolean
  /** Icono girando */
  spinning?: boolean
  /** Efecto pulse */
  pulse?: boolean

  // Accesibilidad
  /** Etiqueta ARIA personalizada */
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'action',
  variant: 'default',
  size: 'md',
  iconVariant: 'outline',
  buttonType: 'button',
  disabled: false,
  loading: false,
  loadingText: 'Cargando...',
  iconOnly: false,
  fullWidth: false,
  position: 'inline',
  animated: true,
  ripple: true,
  glow: false,
  spinning: false,
  pulse: false,
})

const emit = defineEmits<{
  click: [event: MouseEvent]
  close: []
}>()

// Estado reactivo
const isHovered = ref(false)
const isFocused = ref(false)
const showRipple = ref(false)

// Computadas dinámicas
const elementTag = computed(() => props.type === 'icon' ? 'div' : 'button')

const buttonType = computed(() => elementTag.value === 'button' ? props.buttonType : undefined)

const iconSize = computed((): IconSize => {
  const sizeMap: Record<ElementSize, IconSize> = {
    sm: 'xs',
    md: 'sm',
    lg: 'md',
    xl: 'lg',
  }
  return sizeMap[props.size]
})

const computedAriaLabel = computed(() => {
  if (props.ariaLabel) { return props.ariaLabel }
  if (props.tooltip) { return props.tooltip }
  if (props.label) { return props.label }

  const typeLabels: Record<ElementType, string> = {
    icon: props.iconName || 'Icono',
    action: 'Acción',
    close: 'Cerrar',
  }

  return typeLabels[props.type]
})

const showGlow = computed(() => props.glow && (isHovered.value || isFocused.value))

const elementAttrs = computed(() => {
  const attrs: Record<string, any> = {}

  if (elementTag.value === 'button') {
    attrs.type = buttonType.value
  }

  return attrs
})

// Clases computadas principales
const elementClasses = computed(() => {
  const baseClasses = [
    'relative inline-flex items-center justify-center',
    'transition-all duration-200 ease-out',
    'overflow-hidden',
  ]

  // Clases específicas por tipo
  if (props.type === 'icon') {
    baseClasses.push(
      ...getIconClasses(),
      ...getSizeClasses(),
      ...getVariantClasses(),
      ...getAnimationClasses(),
    )
  } else {
    baseClasses.push(
      'font-medium',
      'focus:outline-none focus:ring-2 focus:ring-offset-2',
      ...getButtonClasses(),
      ...getSizeClasses(),
      ...getVariantClasses(),
      ...getAnimationClasses(),
      ...getStateClasses(),
    )
  }

  return baseClasses.filter(Boolean)
})

const getIconClasses = () => {
  if (props.type !== 'icon') { return [] }

  return [
    'rounded-full',
    'backdrop-blur-sm',
  ]
}

const getButtonClasses = () => {
  const classes = []

  // Forma del botón
  if (props.iconOnly) {
    classes.push('rounded-full')
  } else {
    classes.push('rounded-md', 'gap-2')
  }

  // Posicionamiento para close button
  if (props.type === 'close' && props.position === 'top-right') {
    classes.push('absolute top-2 right-2 z-20')
  }

  // Ancho completo
  if (props.fullWidth) {
    classes.push('w-full')
  }

  return classes
}

const getSizeClasses = () => {
  if (props.iconOnly || props.type === 'icon') {
    const sizeMap: Record<ElementSize, string> = {
      sm: 'w-6 h-6 p-1',
      md: 'w-8 h-8 p-1.5',
      lg: 'w-10 h-10 p-2',
      xl: 'w-12 h-12 p-2.5',
    }
    return [sizeMap[props.size]]
  } else {
    const sizeMap: Record<ElementSize, string> = {
      sm: 'px-2 py-1 text-xs',
      md: 'px-3 py-1.5 text-sm',
      lg: 'px-4 py-2 text-base',
      xl: 'px-5 py-2.5 text-lg',
    }
    return [sizeMap[props.size]]
  }
}

const getVariantClasses = () => {
  const classes = []

  // Solo aplicar estilos de variante si no está deshabilitado
  if (props.disabled) {
    classes.push('opacity-50 cursor-not-allowed')
    return classes
  }

  if (props.loading) {
    classes.push('cursor-wait')
    return classes
  }

  const variantMap: Record<ElementVariant, string[]> = {
    default: ['bg-white/20 text-white border border-white/30 hover:bg-white/30 focus:ring-white/50 backdrop-blur-sm'],
    primary: ['bg-primary-600 text-white border border-primary-600 hover:bg-primary-700 focus:ring-primary-500'],
    secondary: ['bg-gray-200 text-gray-900 border border-gray-300 hover:bg-gray-300 focus:ring-gray-500'],
    ghost: ['bg-transparent text-white hover:bg-white/20 focus:ring-white/50'],
    danger: ['bg-red-500/20 text-red-100 border border-red-400/30 hover:bg-red-500/30 focus:ring-red-500'],
    success: ['bg-emerald-600 text-white border border-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'],
    warning: ['bg-amber-600 text-white border border-amber-600 hover:bg-amber-700 focus:ring-amber-500'],
    gaming: ['bg-gradient-to-br from-purple-500/30 to-pink-500/30 text-white border border-purple-400/40 hover:from-purple-500/40 hover:to-pink-500/40 focus:ring-purple-500'],
    premium: ['bg-gradient-to-br from-yellow-400/30 to-amber-500/30 text-yellow-100 border border-yellow-400/40 hover:from-yellow-400/40 hover:to-amber-500/40 focus:ring-yellow-500'],
    minimal: ['bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:ring-gray-500'],
  }

  classes.push(...variantMap[props.variant])

  return classes
}

const getAnimationClasses = () => {
  if (!props.animated || props.disabled || props.loading) { return [] }

  const classes = ['transform']

  if (isHovered.value) {
    classes.push('scale-110')

    if (props.variant === 'gaming') {
      classes.push('rotate-12')
    }
  }

  if (props.glow) {
    classes.push('hover:shadow-lg')
  }

  return classes
}

const getStateClasses = () => {
  const classes = []

  if (isFocused.value) {
    classes.push('ring-2 ring-offset-2')

    const focusRingMap: Record<ElementVariant, string> = {
      default: 'ring-white/50',
      primary: 'ring-primary-500',
      secondary: 'ring-gray-500',
      ghost: 'ring-white/50',
      danger: 'ring-red-500',
      success: 'ring-emerald-500',
      warning: 'ring-amber-500',
      gaming: 'ring-purple-500',
      premium: 'ring-yellow-500',
      minimal: 'ring-gray-500',
    }

    classes.push(focusRingMap[props.variant])
  }

  return classes
}

// Clases adicionales
const iconClasses = computed(() => [
  'transition-all duration-200',
  {
    'w-3 h-3': props.size === 'sm',
    'w-4 h-4': props.size === 'md',
    'w-5 h-5': props.size === 'lg',
    'w-6 h-6': props.size === 'xl',
  },
  {
    'transform rotate-12': isHovered.value && props.animated && props.variant === 'gaming',
    'transform scale-110': isHovered.value && props.animated && props.variant !== 'gaming',
  },
])

const labelClasses = computed(() => [
  'truncate',
  {
    'sr-only': props.iconOnly,
  },
])

const badgeClasses = computed(() => [
  'absolute -top-1 -right-1 min-w-[1.25rem] h-5',
  'flex items-center justify-center',
  'text-xs font-bold text-white bg-red-500',
  'rounded-full border-2 border-current',
  'transition-all duration-200',
  {
    'transform scale-110': isHovered.value && props.animated,
  },
])

const rippleClasses = computed(() => [
  'absolute inset-0 bg-white/20 rounded-full',
  'animate-ping pointer-events-none',
  {
    'opacity-0': !showRipple.value,
  },
])

const glowClasses = computed(() => {
  const glowMap: Record<ElementVariant, string> = {
    default: 'shadow-lg shadow-white/20',
    primary: 'shadow-lg shadow-primary-500/30',
    secondary: 'shadow-lg shadow-gray-500/30',
    ghost: 'shadow-lg shadow-white/20',
    danger: 'shadow-lg shadow-red-500/30',
    success: 'shadow-lg shadow-emerald-500/30',
    warning: 'shadow-lg shadow-amber-500/30',
    gaming: 'shadow-lg shadow-purple-500/40',
    premium: 'shadow-lg shadow-yellow-500/40',
    minimal: 'shadow-lg shadow-gray-500/20',
  }

  return [
    'absolute inset-0 rounded-full transition-all duration-300',
    glowMap[props.variant],
  ]
})

// Métodos
const handleClick = (event: MouseEvent) => {
  if (props.disabled || props.loading) { return }

  // Efecto ripple
  if (props.ripple && elementTag.value === 'button') {
    showRipple.value = true
    setTimeout(() => {
      showRipple.value = false
    }, 300)
  }

  if (props.type === 'close') {
    emit('close')
  } else {
    emit('click', event)
  }
}

const handleHover = (hover: boolean) => {
  if (!props.disabled && !props.loading) {
    isHovered.value = hover
  }
}

const handleFocus = (focused: boolean) => {
  isFocused.value = focused
}
</script>

<style scoped>
/* Soporte para reduced motion */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }

  .animate-ping {
    animation: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  button {
    border-width: 2px;
  }
}

/* Gaming effects */
@keyframes gaming-glow {
  0%, 100% {
    box-shadow: 0 0 20px rgba(168, 85, 247, 0.4);
  }
  50% {
    box-shadow: 0 0 30px rgba(168, 85, 247, 0.6);
  }
}

.gaming-glow {
  animation: gaming-glow 2s ease-in-out infinite;
}

/* Premium shimmer */
@keyframes premium-shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.premium-shimmer {
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  background-size: 200% 100%;
  animation: premium-shimmer 2s infinite;
}
</style>
