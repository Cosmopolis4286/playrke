<template>
  <!-- Wrapper div para asegurar elemento raíz único -->
  <div v-if="props.name && props.name.trim()" :class="iconClasses" v-bind="$attrs">
    <component
      :is="iconComponent"
      v-if="!isSocialIcon"
      class="w-full h-full"
    />
    <Icon
      v-else
      :icon="socialIconName"
      class="w-full h-full"
    />
  </div>

  <!-- Fallback visual para iconos vacíos en desarrollo -->
  <div
    v-else-if="isDev"
    :class="[iconClasses, 'opacity-30 text-red-500 flex items-center justify-center']"
    title="Icono vacío - revisa el prop 'name'"
    v-bind="$attrs"
  >
    ?
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview SharedIcon component - Wrapper unificado para Heroicons
 * @description Componente para iconos con soporte completo de Heroicons
 */
import { computed } from 'vue'
import * as HeroiconsOutline from '@heroicons/vue/24/outline'
import * as HeroiconsSolid from '@heroicons/vue/24/solid'
import { Icon } from '@iconify/vue'

// Check if we're in development mode
const isDev = import.meta.env.DEV

// Tipos exportables
export type IconSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'
export type IconVariant = 'outline' | 'solid'
export type IconColor = 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'muted' | 'white' | 'current' | 'info' | 'gray'
export type IconAnimation = 'spin' | 'pulse' | 'bounce' | 'float'
export type IconType = string

interface Props {
  /** Nombre del icono sin el sufijo "Icon" */
  name?: string
  /** Tamaño del icono */
  size?: IconSize
  /** Variante del icono */
  variant?: IconVariant
  /** Clases CSS adicionales - acepta string, array o object */
  class?: string | string[] | Record<string, boolean>
  /** Animación del icono - true para auto-detectar, string para tipo específico */
  animated?: boolean | IconAnimation
  /** Color predefinido del icono */
  color?: IconColor
  /** Efecto de pulsación */
  pulse?: boolean
  /** Rotación continua */
  spin?: boolean
}

defineOptions({
  name: 'Icon',
  inheritAttrs: false,
})

const props = withDefaults(defineProps<Props>(), {
  name: '',
  size: 'md',
  variant: 'outline',
  animated: false,
  pulse: false,
  spin: false,
})

// Mapeo de iconos comunes para facilitar el uso
const iconAliases: Record<string, string> = {
  // Navigation
  'menu': 'Bars3',
  'close': 'XMark',
  'back': 'ArrowLeft',
  'arrow-left': 'ArrowLeft',
  'forward': 'ArrowRight',
  'up': 'ArrowUp',
  'down': 'ArrowDown',

  // Actions
  'add': 'Plus',
  'remove': 'Minus',
  'delete': 'Trash',
  'edit': 'PencilSquare',
  'save': 'Check',
  'cancel': 'XMark',
  'search': 'MagnifyingGlass',
  'filter': 'AdjustmentsHorizontal',
  'refresh': 'ArrowPath',
  'download': 'ArrowDownTray',
  'upload': 'ArrowUpTray',
  'user-plus': 'UserPlus',
  'shield-check': 'ShieldCheck',
  'shield-lock': 'LockClosed',
  'device-phone-mobile': 'DevicePhoneMobile',
  'chat-bubble-left-right': 'ChatBubbleLeftRight',
  'chat-bubble-left-ellipsis': 'ChatBubbleLeftEllipsis',
  'chat-bubble-left': 'ChatBubbleLeft',
  'book-open': 'BookOpen',
  'video-camera': 'VideoCamera',
  'arrow-path': 'ArrowPath',
  'arrow-right': 'ArrowRight',
  'arrow-down-tray': 'ArrowDownTray',
  'user-check': 'UserCircle',
  'chart-bar': 'ChartBarSquare',
  'document-check': 'DocumentCheck',
  'handshake': 'HandRaised',
  'building-office': 'BuildingOffice',
  'building-office-2': 'BuildingOffice2',
  'user-group': 'UserGroup',
  'question-mark-circle': 'QuestionMarkCircle',
  'database': 'CircleStack',
  'computer-desktop': 'ComputerDesktop',

  // UI Elements
  'loading': 'ArrowPath',
  'spinner': 'ArrowPath',
  'info': 'InformationCircle',
  'warning': 'ExclamationTriangle',
  'error': 'XCircle',
  'success': 'CheckCircle',
  'help': 'QuestionMarkCircle',
  'settings': 'Cog6Tooth',
  'profile': 'User',
  'logout': 'ArrowRightOnRectangle',
  'login': 'ArrowRightOnRectangle',
  'arrow-right-on-rectangle': 'ArrowRightOnRectangle',

  // Game specific
  'trophy': 'Trophy',
  'star': 'Star',
  'heart': 'Heart',
  'game': 'PuzzlePiece',
  'ticket': 'Ticket',
  'money': 'CurrencyDollar',
  'chart': 'ChartBarSquare',
  'stats': 'ChartPie',

  // Common
  'home': 'Home',
  'dashboard': 'Squares2X2',
  'users': 'Users',
  'calendar': 'Calendar',
  'clock': 'Clock',
  'email': 'Envelope',
  'phone': 'Phone',
  'location': 'MapPin',
  'link': 'Link',
  'external': 'ArrowTopRightOnSquare',
  'copy': 'DocumentDuplicate',
  'share': 'Share',
  'print': 'Printer',
  'file': 'Document',
  'document-text': 'Document', // Mapeo para document-text inexistente
  'folder': 'Folder',
  'image': 'Photo',
  'video': 'VideoCamera',
  'audio': 'SpeakerWave',
  'lock': 'LockClosed',
  'unlock': 'LockOpen',
  'eye': 'Eye',
  'eyeOff': 'EyeSlash',
  'notification': 'Bell',
  'message': 'ChatBubbleLeft',
  'mail': 'Envelope',

  // Missing icons - agregando iconos faltantes
  'currency-dollar': 'CurrencyDollar',
  'arrow-trending-up': 'ArrowUpRight',
  'arrow-trending-down': 'ArrowDownRight',
  'arrow-up-right': 'ArrowUpRight',
  'arrow-down-right': 'ArrowDownRight',
  'lock-closed': 'LockClosed',
  'shield-exclamation': 'ShieldExclamation',
  'credit-card': 'CreditCard',
  'user-circle': 'UserCircle',
  'x-mark': 'XMark',
  'at-symbol': 'AtSymbol',
  'check-circle': 'CheckCircle',
  'building-storefront': 'BuildingStorefront',
  'chevron-left': 'ChevronLeft',
  'chevron-right': 'ChevronRight',
  'chevron-down': 'ChevronDown',
  'chevron-up': 'ChevronUp',
  'gray': 'QuestionMarkCircle', // Fallback para color gray
  'information-circle': 'InformationCircle',
  'exclamation-circle': 'ExclamationCircle',
  'exclamation-triangle': 'ExclamationTriangle',
  'inbox': 'InboxArrowDown',

  // Additional aliases for common icons
  'language': 'Language',
  'sun': 'Sun',
  'moon': 'Moon',
  'bars-3': 'Bars3',
  'map-pin': 'MapPin',

  // Game and activity icons (duplicados eliminados)
  'pencil': 'PencilSquare',
  'trash': 'Trash',
  'hashtag': 'Hashtag',
  'sparkles': 'Sparkles',
  'play': 'Play',
  'bolt': 'Bolt',
  'plus': 'Plus',
  'bars': 'Bars3',
  'command': 'CommandLine',

  // Notification icons
  'bell': 'Bell',
  'bell-slash': 'BellSlash',

  // Fixed missing icons
  'puzzle-piece': 'PuzzlePiece',
  'cog-6-tooth': 'Cog6Tooth',

  // Social media icons (usando Iconify)
  'facebook': 'mdi:facebook',
  'twitter': 'mdi:twitter',
  'instagram': 'mdi:instagram',
  'linkedin': 'mdi:linkedin',
  'youtube': 'mdi:youtube',
  'github': 'mdi:github',
  'tiktok': 'mdi:tiktok',
  'whatsapp': 'mdi:whatsapp',
  'telegram': 'mdi:telegram',

  // Additional common aliases
  'gear': 'Cog6Tooth',
  'cog': 'Cog6Tooth',
  'squares-2x2': 'Squares2X2',
  'code-bracket': 'CodeBracketSquare',
  'paint-brush': 'PaintBrush',
  'cpu-chip': 'CpuChip',
  'globe-alt': 'GlobeAlt',
  'play-circle': 'PlayCircle',

  // Iconos faltantes - mapeos adicionales para prevenir errores
  'cloud-arrow-up': 'CloudArrowUp',
  'folder-open': 'FolderOpen',
  'lightning-bolt': 'Bolt', // lightning-bolt mapea a Bolt que existe
  'circle-stack': 'CircleStack', // Para base de datos

  // Iconos para sales.vue
  'document-chart-bar': 'ChartBarSquare',
  'banknotes': 'Banknotes',
  'calculator': 'Calculator',
  'chart-pie': 'ChartPie',
  'arrow-uturn-left': 'ArrowUturnLeft',

  // Iconos adicionales faltantes
  'arrow-up': 'ArrowUp',
  'arrow-down': 'ArrowDown',
  'chart-line': 'ChartBarSquare', // chart-line no existe, mapear a ChartBarSquare
  'x-circle': 'XCircle',
  'gamepad-2': 'PuzzlePiece', // Usar PuzzlePiece como gamepad alternativo
  'refresh-ccw': 'ArrowPath', // refresh-ccw mapea a ArrowPath
  'clipboard': 'DocumentDuplicate', // clipboard mapea a DocumentDuplicate

  // Iconos faltantes reportados por consola
  'rocket-launch': 'RocketLaunch',
  'check-badge': 'CheckBadge',
  'cube-transparent': 'CubeTransparent',
  'arrow-top-right-on-square': 'ArrowTopRightOnSquare',
  'chart-bar-square': 'ChartBarSquare',
  'face-smile': 'FaceSmile',

  // Iconos adicionales faltantes
  'terminal': 'CommandLine', // terminal mapea a CommandLine
  'command-line': 'CommandLine',

  // Logo y branding
  'logo': 'Trophy', // Logo principal con TrophyIcon (más apropiado para lotería)
  'brand': 'Trophy', // Alternativa para branding
  'lottery': 'Trophy', // Icono específico para lotería

  // Iconos financieros
  'wallet': 'Wallet', // Billetera/cartera
  'bank': 'BuildingLibrary', // Banco/institución financiera

  // Iconos con prefijo heroicons: (para compatibilidad con sintaxis Iconify)
  'heroicons:gift': 'Gift',
  'heroicons:magnifying-glass': 'MagnifyingGlass',
  'heroicons:trophy': 'Trophy',
  'heroicons:ticket': 'Ticket',
  'heroicons:currency-dollar': 'CurrencyDollar',
  'heroicons:users': 'Users',
  'heroicons:chart-bar': 'ChartBarSquare',
  'heroicons:calendar': 'Calendar',
  'heroicons:clock': 'Clock',
  'heroicons:home': 'Home',
  'heroicons:cog-6-tooth': 'Cog6Tooth',
  'heroicons:bell': 'Bell',
  'heroicons:envelope': 'Envelope',
  'heroicons:user': 'User',
  'heroicons:arrow-right': 'ArrowRight',
  'heroicons:arrow-left': 'ArrowLeft',
  'heroicons:x-mark': 'XMark',
  'heroicons:check': 'Check',
  'heroicons:plus': 'Plus',
  'heroicons:minus': 'Minus',
  'heroicons:trash': 'Trash',
  'heroicons:pencil': 'PencilSquare',
  'heroicons:fire': 'Bolt',
  'heroicons:sparkles': 'Sparkles',
  'heroicons:wallet': 'Wallet',
  'heroicons:credit-card': 'CreditCard',
  'heroicons:building-library': 'BuildingLibrary',
  'heroicons:shopping-cart': 'ShoppingCart',
  'heroicons:squares-plus': 'Squares2X2',
  'heroicons:exclamation-triangle': 'ExclamationTriangle',
  'heroicons:exclamation-circle': 'ExclamationCircle',
  'heroicons:x-circle': 'XCircle',
  'heroicons:check-circle': 'CheckCircle',
  'heroicons:information-circle': 'InformationCircle',
  'heroicons:clipboard-document-list': 'ClipboardDocumentList',

  // Iconos específicos de regalos y premios
  'gift': 'Gift',
  'fire': 'Bolt',

  // Missing icons that were causing console warnings
  'receipt-percent': 'ReceiptPercent',
  'magnifying-glass': 'MagnifyingGlass',
  'arrows-up-down': 'ArrowsUpDown',
  'plus-circle': 'PlusCircle',
  'arrow-up-tray': 'ArrowUpTray',
  'pencil-square': 'PencilSquare',
  'squares-plus': 'Squares2X2',
}

// Detectar si es un ícono de Iconify (contiene : en el nombre, excepto heroicons:)
const isIconifyIcon = computed(() => {
  const name = props.name || ''
  // Si tiene : y NO empieza con 'heroicons:', es un icono de Iconify
  return name.includes(':') && !name.startsWith('heroicons:')
})

// Detectar si es un ícono social
const isSocialIcon = computed(() => {
  const socialIcons = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'github', 'tiktok', 'whatsapp', 'telegram']
  return socialIcons.includes(props.name) || isIconifyIcon.value
})

// Nombre del ícono social/Iconify para Iconify
const socialIconName = computed(() => iconAliases[props.name] || props.name)

const iconComponent = computed(() => {
  // Validar si el nombre está vacío o es undefined
  if (!props.name || props.name.trim() === '') {
    // Solo mostrar warnings en desarrollo
    if (isDev) {
      console.warn(`SharedIcon "${props.name}" (Icon) not found in Heroicons ${props.variant}`)
      console.warn('HINT: Check if you are passing an empty string or undefined as the icon name prop')
    }
    // Fallback a un icono de interrogación para nombres vacíos
    return HeroiconsOutline.QuestionMarkCircleIcon
  }

  const iconSet = props.variant === 'solid' ? HeroiconsSolid : HeroiconsOutline

  // Usar alias si existe, sino usar el nombre directo
  const iconBaseName = iconAliases[props.name] || props.name

  // Convertir a formato PascalCase + Icon
  const iconName = `${iconBaseName.charAt(0).toUpperCase() + iconBaseName.slice(1)}Icon`

  const icon = iconSet[iconName as keyof typeof iconSet]

  if (!icon) {
    console.warn(`SharedIcon "${props.name}" (${iconName}) not found in Heroicons ${props.variant}`)
    // Fallback a un icono de interrogación
    return HeroiconsOutline.QuestionMarkCircleIcon
  }

  return icon
})

// Detección automática de animación basada en el nombre del icono
const autoDetectedAnimation = computed(() => {
  const spinIcons = ['spinner', 'loading', 'refresh', 'settings', 'cog', 'gear']
  const pulseIcons = ['heart', 'star', 'notification', 'bell', 'info', 'warning', 'error', 'success']
  const bounceIcons = ['success', 'check', 'checkmark', 'trophy', 'award']
  const floatIcons = ['chevron', 'arrow']

  if (spinIcons.includes(props.name)) { return 'spin' }
  if (pulseIcons.includes(props.name)) { return 'pulse' }
  if (bounceIcons.includes(props.name)) { return 'bounce' }
  if (floatIcons.includes(props.name)) { return 'float' }

  return 'pulse' // Default suave para cualquier icono
})

// Computed para determinar el tipo de animación final
const finalAnimation = computed(() => {
  // Prioridad: spin prop > pulse prop > animated prop > ninguna
  if (props.spin) { return 'spin' }
  if (props.pulse) { return 'pulse' }

  if (props.animated === true) {
    return autoDetectedAnimation.value
  } else if (typeof props.animated === 'string') {
    return props.animated
  }

  return null
})

// Computed para clases de color
const colorClasses = computed(() => {
  if (!props.color) { return '' }

  const colorMap = {
    primary: 'text-primary-600',
    secondary: 'text-gray-600',
    success: 'text-success-600',
    warning: 'text-warning-600',
    error: 'text-error-600',
    muted: 'text-gray-400',
    white: 'text-white',
    current: 'text-current',
    info: 'text-blue-600',
    gray: 'text-gray-500',
  }

  return colorMap[props.color] || ''
})

// Computed para clases de animación
const animationClasses = computed(() => {
  const animation = finalAnimation.value
  if (!animation) { return '' }

  const animationMap = {
    spin: 'animate-spin',
    pulse: 'animate-pulse',
    bounce: 'animate-bounce',
    float: 'animate-float',
  }

  return animationMap[animation] || ''
})

// Computed para manejar clases de forma inteligente (arrays, objects, strings)
const normalizedClasses = computed(() => {
  if (!props.class) { return '' }

  // Si es un string, devolverlo como está
  if (typeof props.class === 'string') {
    return props.class
  }

  // Si es un array, unirlo con espacios
  if (Array.isArray(props.class)) {
    return props.class.filter(Boolean).join(' ')
  }

  // Si es un object, procesar claves con valores truthy
  if (typeof props.class === 'object' && props.class !== null) {
    return Object.entries(props.class)
      .filter(([_, value]) => Boolean(value))
      .map(([key, _]) => key)
      .join(' ')
  }

  return ''
})

const iconClasses = computed(() => {
  const sizeClasses = {
    'xs': 'w-3 h-3', // 12px
    'sm': 'w-4 h-4', // 16px
    'md': 'w-5 h-5', // 20px
    'lg': 'w-6 h-6', // 24px
    'xl': 'w-8 h-8', // 32px
    '2xl': 'w-10 h-10', // 40px
  }

  return [
    sizeClasses[props.size],
    'flex-shrink-0', // Evitar que se comprima
    colorClasses.value,
    animationClasses.value,
    normalizedClasses.value,
  ].filter(Boolean).join(' ')
})
</script>

<style scoped>
/* Animaciones para iconos */

/* Rotación continua - para loaders, refresh, settings */
.animate-spin {
  animation: spin 1s linear infinite;
}

/* Pulsación suave - para notificaciones, estados */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Rebote sutil - para éxito, alertas */
.animate-bounce {
  animation: bounce 1s infinite;
}

/* Flotación suave - para elementos decorativos */
.animate-float {
  animation: float 3s ease-in-out infinite;
}

/* Definición de keyframes */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
    transform: translate3d(0, 0, 0);
  }
  40%, 43% {
    animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
    transform: translate3d(0, -8px, 0);
  }
  70% {
    animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
    transform: translate3d(0, -4px, 0);
  }
  90% {
    transform: translate3d(0, -2px, 0);
  }
}

@keyframes float {
  0% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-3px);
  }
  100% {
    transform: translateY(0px);
  }
}

/* Optimizaciones de rendimiento */
.animate-spin,
.animate-pulse,
.animate-bounce,
.animate-float {
  transform-origin: center;
  backface-visibility: hidden;
  will-change: transform, opacity;
}

/* Soporte para reduce-motion */
@media (prefers-reduced-motion: reduce) {
  .animate-spin,
  .animate-pulse,
  .animate-bounce,
  .animate-float {
    animation: none !important;
  }
}
</style>
