<template>
  <component
    :is="iconComponent"
    :class="iconClasses"
    :aria-hidden="ariaHidden"
    :aria-label="ariaLabel"
    v-bind="iconProps"
  />
</template>

<script setup lang="ts">
/**
 * @fileoverview SharedIcon - Sistema unificado de iconos con Heroicons
 * @description Componente centralizado para todos los iconos del sistema con lazy loading
 * y soporte completo para Heroicons outline/solid + Lucide como fallback
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-17
 */

import { computed, defineAsyncComponent, type Component } from 'vue'
// useResponsive auto-imported by Nuxt

// Tipos de iconos soportados
export type IconType = 'info' | 'success' | 'error' | 'warning' | 'spinner' | 'heart' | 'user' | 'settings'
  | 'bell' | 'check' | 'x' | 'plus' | 'minus' | 'arrow-left' | 'arrow-right'
  | 'chevron-down' | 'chevron-up' | 'home' | 'game' | 'stats' | 'admin' | 'logout'
  | 'exclamation-triangle' | 'question-mark-circle' | 'save'
  | 'play' | 'trophy' | 'clock' | 'profile' | 'ticket' | 'credit-card' | 'help'
  | 'chat-bubble-left-right' | 'close' | 'back' | 'forward' | 'add' | 'remove'
  | 'delete' | 'edit' | 'search' | 'eye' | 'eyeOff' | 'lock' | 'file' | 'folder'
  | 'chevron-left' | 'chevron-right' | 'menu' | 'volume-on' | 'volume-off'

type IconVariant = 'outline' | 'solid'
type IconSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'

interface Props {
  /** Tipo de icono a mostrar */
  name: IconType
  /** Variante del icono (outline o solid) */
  variant?: IconVariant
  /** Tamaño del icono */
  size?: IconSize
  /** Color personalizado (cualquier clase CSS) */
  color?: string
  /** Deshabilitar para lectores de pantalla */
  ariaHidden?: boolean
  /** Etiqueta accesible */
  ariaLabel?: string
  /** Spinning animation para loaders */
  spinning?: boolean
  /** Pulse effect para notificaciones */
  pulse?: boolean
  /** Props adicionales para el icono */
  iconProps?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'outline',
  size: 'md',
  color: 'currentColor',
  ariaHidden: true,
  spinning: false,
  pulse: false,
  iconProps: () => ({}),
})

// Composables
const { viewport } = useResponsive()

// Mapeo dinámico de iconos con lazy loading
const iconMap: Record<IconType, Record<IconVariant, () => Promise<any>>> = {
  'info': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.InformationCircleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.InformationCircleIcon),
  },
  'success': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.CheckCircleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.CheckCircleIcon),
  },
  'error': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.XCircleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.XCircleIcon),
  },
  'warning': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ExclamationTriangleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ExclamationTriangleIcon),
  },
  'spinner': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ArrowPathIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ArrowPathIcon),
  },
  'heart': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.HeartIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.HeartIcon),
  },
  'user': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.UserIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.UserIcon),
  },
  'settings': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.CogIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.CogIcon),
  },
  'bell': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.BellIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.BellIcon),
  },
  'check': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.CheckIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.CheckIcon),
  },
  'x': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.XMarkIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.XMarkIcon),
  },
  'plus': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.PlusIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.PlusIcon),
  },
  'minus': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.MinusIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.MinusIcon),
  },
  'arrow-left': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ArrowLeftIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ArrowLeftIcon),
  },
  'arrow-right': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ArrowRightIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ArrowRightIcon),
  },
  'chevron-down': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ChevronDownIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ChevronDownIcon),
  },
  'chevron-up': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ChevronUpIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ChevronUpIcon),
  },
  'home': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.HomeIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.HomeIcon),
  },
  'game': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.PlayIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.PlayIcon),
  },
  'stats': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ChartBarIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ChartBarIcon),
  },
  'admin': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.UserGroupIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.UserGroupIcon),
  },
  'logout': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ArrowRightOnRectangleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ArrowRightOnRectangleIcon),
  },
  'exclamation-triangle': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ExclamationTriangleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ExclamationTriangleIcon),
  },
  'question-mark-circle': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.QuestionMarkCircleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.QuestionMarkCircleIcon),
  },
  'save': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.CheckIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.CheckIcon),
  },
  'play': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.PlayIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.PlayIcon),
  },
  'trophy': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.TrophyIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.TrophyIcon),
  },
  'clock': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ClockIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ClockIcon),
  },
  'profile': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.UserIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.UserIcon),
  },
  'ticket': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.TicketIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.TicketIcon),
  },
  'credit-card': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.CreditCardIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.CreditCardIcon),
  },
  'help': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.QuestionMarkCircleIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.QuestionMarkCircleIcon),
  },
  'chat-bubble-left-right': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ChatBubbleLeftRightIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ChatBubbleLeftRightIcon),
  },
  'close': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.XMarkIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.XMarkIcon),
  },
  'back': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ArrowLeftIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ArrowLeftIcon),
  },
  'forward': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ArrowRightIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ArrowRightIcon),
  },
  'add': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.PlusIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.PlusIcon),
  },
  'remove': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.MinusIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.MinusIcon),
  },
  'delete': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.TrashIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.TrashIcon),
  },
  'edit': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.PencilSquareIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.PencilSquareIcon),
  },
  'search': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.MagnifyingGlassIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.MagnifyingGlassIcon),
  },
  'eye': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.EyeIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.EyeIcon),
  },
  'eyeOff': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.EyeSlashIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.EyeSlashIcon),
  },
  'lock': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.LockClosedIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.LockClosedIcon),
  },
  'file': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.DocumentIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.DocumentIcon),
  },
  'folder': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.FolderIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.FolderIcon),
  },
  'chevron-left': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ChevronLeftIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ChevronLeftIcon),
  },
  'chevron-right': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.ChevronRightIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.ChevronRightIcon),
  },
  'menu': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.Bars3Icon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.Bars3Icon),
  },
  'volume-on': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.SpeakerWaveIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.SpeakerWaveIcon),
  },
  'volume-off': {
    outline: () => import('@heroicons/vue/24/outline').then(m => m.SpeakerXMarkIcon),
    solid: () => import('@heroicons/vue/24/solid').then(m => m.SpeakerXMarkIcon),
  },
}

// Componente lazy con memoización
const iconComponent = computed((): Component => {
  const iconLoader = iconMap[props.name]?.[props.variant]

  if (!iconLoader) {
    // Fallback a icono de interrogación si no existe
    return defineAsyncComponent(() =>
      import('@heroicons/vue/24/outline').then(m => m.QuestionMarkCircleIcon),
    )
  }

  return defineAsyncComponent({
    loader: iconLoader,
    delay: 0,
    timeout: 3000,
    errorComponent: defineAsyncComponent(() =>
      import('@heroicons/vue/24/outline').then(m => m.QuestionMarkCircleIcon),
    ),
    loadingComponent: defineAsyncComponent(() =>
      import('@heroicons/vue/24/outline').then(m => m.ArrowPathIcon),
    ),
  })
})

// Clases computadas optimizadas
const iconClasses = computed(() => {
  // Mapeo responsive de tamaños
  const sizeMap: Record<IconSize, string> = {
    'xs': viewport.isMobile ? 'w-3 h-3' : 'w-4 h-4',
    'sm': viewport.isMobile ? 'w-4 h-4' : 'w-5 h-5',
    'md': viewport.isMobile ? 'w-5 h-5' : 'w-6 h-6',
    'lg': viewport.isMobile ? 'w-6 h-6' : 'w-8 h-8',
    'xl': viewport.isMobile ? 'w-8 h-8' : 'w-10 h-10',
    '2xl': viewport.isMobile ? 'w-10 h-10' : 'w-12 h-12',
  }

  // Colores semánticos
  const colorMap: Record<string, string> = {
    info: 'text-primary-600',
    success: 'text-emerald-600',
    error: 'text-red-600',
    warning: 'text-amber-600',
    primary: 'text-primary-600',
    secondary: 'text-sky-600',
    muted: 'text-gray-500',
    white: 'text-white',
  }

  const baseClasses = [
    sizeMap[props.size],
    'transition-all duration-200',
    'flex-shrink-0',
  ]

  // Color dinámico
  if (props.color && props.color !== 'currentColor') {
    if (props.color && props.color in colorMap) {
      baseClasses.push(colorMap[props.color]!)
    } else if (props.color) {
      baseClasses.push(props.color)
    }
  }

  // Efectos especiales
  if (props.spinning) {
    baseClasses.push('animate-spin')
  }

  if (props.pulse) {
    baseClasses.push('animate-pulse')
  }

  // Hover effects
  baseClasses.push('hover:scale-110')

  return baseClasses.join(' ')
})

// Props computados para pasar al icono
const computedIconProps = computed(() => ({
  ...props.iconProps,
  role: props.ariaHidden ? undefined : 'img',
}))

// Label accesible automático
const ariaLabel = computed(() => {
  if (props.ariaLabel) { return props.ariaLabel }

  const labels: Record<IconType, string> = {
    'info': 'Información',
    'success': 'Éxito',
    'error': 'Error',
    'warning': 'Advertencia',
    'spinner': 'Cargando',
    'heart': 'Favorito',
    'user': 'Usuario',
    'settings': 'Configuración',
    'bell': 'Notificaciones',
    'check': 'Confirmar',
    'x': 'Cerrar',
    'plus': 'Agregar',
    'minus': 'Quitar',
    'arrow-left': 'Anterior',
    'arrow-right': 'Siguiente',
    'chevron-down': 'Expandir',
    'chevron-up': 'Colapsar',
    'home': 'Inicio',
    'game': 'Juego',
    'stats': 'Estadísticas',
    'admin': 'Administración',
    'logout': 'Cerrar sesión',
    'exclamation-triangle': 'Advertencia',
    'question-mark-circle': 'Ayuda',
    'save': 'Guardar',
    'play': 'Reproducir',
    'trophy': 'Trofeo',
    'clock': 'Reloj',
    'profile': 'Perfil',
    'ticket': 'Boleto',
    'credit-card': 'Tarjeta de crédito',
    'help': 'Ayuda',
    'chat-bubble-left-right': 'Conversación',
    'close': 'Cerrar',
    'back': 'Atrás',
    'forward': 'Adelante',
    'add': 'Agregar',
    'remove': 'Quitar',
    'delete': 'Eliminar',
    'edit': 'Editar',
    'search': 'Buscar',
    'eye': 'Ver',
    'eyeOff': 'Ocultar',
    'lock': 'Bloquear',
    'file': 'Archivo',
    'folder': 'Carpeta',
    'chevron-left': 'Izquierda',
    'chevron-right': 'Derecha',
    'menu': 'Menú',
    'volume-on': 'Sonido activado',
    'volume-off': 'Sonido desactivado',
  }

  return labels[props.name] || props.name
})
</script>

<style scoped>
/* Optimizaciones de performance */
.icon-container {
  contain: layout style;
  will-change: transform;
}

/* Soporte para reduced motion */
@media (prefers-reduced-motion: reduce) {
  .transition-all {
    transition: none;
  }

  .animate-spin,
  .animate-pulse {
    animation: none;
  }

  .hover\:scale-110:hover {
    transform: none;
  }
}

/* Optimización para dark mode futuro */
@media (prefers-color-scheme: dark) {
  .text-gray-500 {
    color: rgb(156 163 175);
  }
}
</style>
