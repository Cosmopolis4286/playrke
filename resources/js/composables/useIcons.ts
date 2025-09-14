// Icon library management composable
import { computed } from 'vue'

// Icon mapping for different contexts
export interface IconConfig {
  name: string
  type: 'tabler' | 'lucide' | 'heroicons' | 'fontawesome'
  size?: string | number
  color?: string
  stroke?: string | number
  fill?: 'none' | 'currentColor' | string
}

// Predefined icon sets for auth forms
export const authIcons = {
  // User & Authentication
  user: { name: 'ti ti-user', type: 'tabler' as const },
  userPlus: { name: 'ti ti-user-plus', type: 'tabler' as const },
  userCheck: { name: 'ti ti-user-check', type: 'tabler' as const },
  userX: { name: 'ti ti-user-x', type: 'tabler' as const },

  // Email & Communication
  mail: { name: 'ti ti-mail', type: 'tabler' as const },
  mailOpen: { name: 'ti ti-mail-opened', type: 'tabler' as const },
  mailCheck: { name: 'ti ti-mail-check', type: 'tabler' as const },
  at: { name: 'ti ti-at', type: 'tabler' as const },

  // Security & Password
  lock: { name: 'ti ti-lock', type: 'tabler' as const },
  lockOpen: { name: 'ti ti-lock-open', type: 'tabler' as const },
  key: { name: 'ti ti-key', type: 'tabler' as const },
  shield: { name: 'ti ti-shield', type: 'tabler' as const },
  shieldCheck: { name: 'ti ti-shield-check', type: 'tabler' as const },
  fingerprint: { name: 'ti ti-fingerprint', type: 'tabler' as const },

  // Visibility & Password Toggle
  eye: { name: 'ti ti-eye', type: 'tabler' as const },
  eyeOff: { name: 'ti ti-eye-off', type: 'tabler' as const },
  eyeCheck: { name: 'ti ti-eye-check', type: 'tabler' as const },

  // Actions & Navigation
  login: { name: 'ti ti-login', type: 'tabler' as const },
  logout: { name: 'ti ti-logout', type: 'tabler' as const },
  arrowRight: { name: 'ti ti-arrow-right', type: 'tabler' as const },
  arrowLeft: { name: 'ti ti-arrow-left', type: 'tabler' as const },
  chevronDown: { name: 'ti ti-chevron-down', type: 'tabler' as const },
  chevronUp: { name: 'ti ti-chevron-up', type: 'tabler' as const },

  // Status & Feedback
  check: { name: 'ti ti-check', type: 'tabler' as const },
  checkCircle: { name: 'ti ti-circle-check', type: 'tabler' as const },
  x: { name: 'ti ti-x', type: 'tabler' as const },
  xCircle: { name: 'ti ti-circle-x', type: 'tabler' as const },
  alert: { name: 'ti ti-alert-triangle', type: 'tabler' as const },
  alertCircle: { name: 'ti ti-alert-circle', type: 'tabler' as const },
  info: { name: 'ti ti-info-circle', type: 'tabler' as const },

  // Loading & Progress
  loader: { name: 'ti ti-loader-2', type: 'tabler' as const },
  refresh: { name: 'ti ti-refresh', type: 'tabler' as const },
  spinner: { name: 'ti ti-loader', type: 'tabler' as const },

  // Location & Geography
  world: { name: 'ti ti-world', type: 'tabler' as const },
  mapPin: { name: 'ti ti-map-pin', type: 'tabler' as const },
  flag: { name: 'ti ti-flag', type: 'tabler' as const },

  // Social & External
  facebook: { name: 'ti ti-brand-facebook', type: 'tabler' as const },
  instagram: { name: 'ti ti-brand-instagram', type: 'tabler' as const },
  telegram: { name: 'ti ti-brand-telegram', type: 'tabler' as const },
  google: { name: 'ti ti-brand-google', type: 'tabler' as const },
  github: { name: 'ti ti-brand-github', type: 'tabler' as const },

  // Interface & UI
  search: { name: 'ti ti-search', type: 'tabler' as const },
  filter: { name: 'ti ti-filter', type: 'tabler' as const },
  settings: { name: 'ti ti-settings', type: 'tabler' as const },
  menu: { name: 'ti ti-menu-2', type: 'tabler' as const },
  close: { name: 'ti ti-x', type: 'tabler' as const },

  // Help & Information
  help: { name: 'ti ti-help-circle', type: 'tabler' as const },
  question: { name: 'ti ti-question-mark', type: 'tabler' as const },
  lightbulb: { name: 'ti ti-bulb', type: 'tabler' as const },
  book: { name: 'ti ti-book', type: 'tabler' as const },

  // Legal & Documents
  file: { name: 'ti ti-file-text', type: 'tabler' as const },
  fileCheck: { name: 'ti ti-file-check', type: 'tabler' as const },
  certificate: { name: 'ti ti-certificate', type: 'tabler' as const },
  scale: { name: 'ti ti-scale', type: 'tabler' as const },

  // Gaming & PlayRKE Specific
  gamepad: { name: 'ti ti-device-gamepad-2', type: 'tabler' as const },
  trophy: { name: 'ti ti-trophy', type: 'tabler' as const },
  star: { name: 'ti ti-star', type: 'tabler' as const },
  starFilled: { name: 'ti ti-star-filled', type: 'tabler' as const },
  coin: { name: 'ti ti-coin', type: 'tabler' as const },
  diamond: { name: 'ti ti-diamond', type: 'tabler' as const },
}

// Toast/Notification specific icons
export const toastIcons = {
  success: { name: 'ti ti-circle-check-filled', type: 'tabler' as const, color: '#10b981' },
  error: { name: 'ti ti-circle-x-filled', type: 'tabler' as const, color: '#ef4444' },
  warning: { name: 'ti ti-alert-triangle-filled', type: 'tabler' as const, color: '#f59e0b' },
  info: { name: 'ti ti-info-circle-filled', type: 'tabler' as const, color: '#3b82f6' },
  loading: { name: 'ti ti-loader-2', type: 'tabler' as const, color: '#6366f1' },
}

// Form validation icons
export const validationIcons = {
  valid: { name: 'ti ti-circle-check', type: 'tabler' as const, color: '#10b981' },
  invalid: { name: 'ti ti-circle-x', type: 'tabler' as const, color: '#ef4444' },
  warning: { name: 'ti ti-alert-triangle', type: 'tabler' as const, color: '#f59e0b' },
  loading: { name: 'ti ti-loader-2', type: 'tabler' as const, color: '#6366f1' },
}

export function useIcons() {
  // Get icon configuration by name
  const getIcon = (iconName: keyof typeof authIcons): IconConfig => {
    return authIcons[iconName] || authIcons.help
  }

  // Get toast icon
  const getToastIcon = (type: keyof typeof toastIcons): IconConfig => {
    return toastIcons[type] || toastIcons.info
  }

  // Get validation icon
  const getValidationIcon = (state: keyof typeof validationIcons): IconConfig => {
    return validationIcons[state] || validationIcons.invalid
  }

  // Build icon class string
  const buildIconClass = (config: IconConfig, additionalClasses = ''): string => {
    const baseClasses = [config.name]

    if (additionalClasses) {
      baseClasses.push(additionalClasses)
    }

    return baseClasses.join(' ')
  }

  // Build icon style object
  const buildIconStyle = (config: IconConfig): Record<string, string> => {
    const style: Record<string, string> = {}

    if (config.color) {
      style.color = config.color
    }

    if (config.size) {
      const size = typeof config.size === 'number' ? `${config.size}px` : config.size
      style.width = size
      style.height = size
    }

    return style
  }

  // Create animated icon class
  const getAnimatedIconClass = (iconName: keyof typeof authIcons, animation: string = 'pulse'): string => {
    const config = getIcon(iconName)
    return `${config.name} animate-${animation}`
  }

  // Get contextual icon based on form field type
  const getFieldIcon = (fieldType: string): IconConfig => {
    const fieldIconMap: Record<string, keyof typeof authIcons> = {
      email: 'mail',
      username: 'user',
      password: 'lock',
      'confirm-password': 'lockOpen',
      country: 'world',
      phone: 'at',
      name: 'user',
      search: 'search',
    }

    const iconKey = fieldIconMap[fieldType] || 'help'
    return getIcon(iconKey)
  }

  // Get social icon
  const getSocialIcon = (platform: string): IconConfig => {
    const socialMap: Record<string, keyof typeof authIcons> = {
      facebook: 'facebook',
      instagram: 'instagram',
      telegram: 'telegram',
      google: 'google',
      github: 'github',
    }

    const iconKey = socialMap[platform.toLowerCase()] || 'world'
    return getIcon(iconKey)
  }

  return {
    // Core functions
    getIcon,
    getToastIcon,
    getValidationIcon,
    buildIconClass,
    buildIconStyle,
    getAnimatedIconClass,

    // Contextual functions
    getFieldIcon,
    getSocialIcon,

    // Icon sets
    authIcons,
    toastIcons,
    validationIcons,
  }
}

// Icon size utilities
export const iconSizes = {
  xs: '12px',
  sm: '16px',
  md: '20px',
  lg: '24px',
  xl: '32px',
  '2xl': '48px',
} as const

// Icon animation classes (to be used with Tailwind)
export const iconAnimations = {
  spin: 'animate-spin',
  pulse: 'animate-pulse',
  bounce: 'animate-bounce',
  ping: 'animate-ping',
  wiggle: 'animate-wiggle', // Custom animation
  shake: 'animate-shake',   // Custom animation
} as const