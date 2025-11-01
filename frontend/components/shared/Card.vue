<template>
  <component
    :is="as"
    class="shared-card"
    :class="[
      variantClasses,
      sizeClasses,
      hoverClasses,
      { 'cursor-pointer': clickable },
    ]"
    v-bind="componentProps"
  >
    <!-- Header Slot -->
    <div
      v-if="$slots.header || title"
      class="card-header"
      :class="{ 'border-b border-gray-200 dark:border-gray-700': !noDivider }"
    >
      <slot name="header">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div
              v-if="icon || iconGradient"
              class="card-icon"
              :class="iconGradient ? `bg-gradient-to-br ${iconGradient}` : 'bg-gray-100 dark:bg-gray-800'"
            >
              <SharedIcon
                v-if="icon"
                :name="icon"
                class="w-5 h-5"
                :class="iconColor || 'text-gray-600 dark:text-gray-400'"
              />
            </div>
            <div>
              <h3
                v-if="title"
                class="card-title"
                :class="titleSize"
              >
                {{ title }}
              </h3>
              <p
                v-if="subtitle"
                class="card-subtitle"
              >
                {{ subtitle }}
              </p>
            </div>
          </div>
          <slot name="header-actions" />
        </div>
      </slot>
    </div>

    <!-- Body Slot -->
    <div
      class="card-body"
      :class="paddingClasses"
    >
      <slot />
    </div>

    <!-- Footer Slot -->
    <div
      v-if="$slots.footer"
      class="card-footer"
      :class="{ 'border-t border-gray-200 dark:border-gray-700': !noDivider }"
    >
      <slot name="footer" />
    </div>
  </component>
</template>

<script setup lang="ts">
interface Props {
  as?: string
  variant?: 'default' | 'outlined' | 'elevated' | 'flat' | 'gradient'
  size?: 'sm' | 'md' | 'lg'
  title?: string
  subtitle?: string
  icon?: string
  iconColor?: string
  iconGradient?: string
  clickable?: boolean
  hover?: boolean
  to?: string
  href?: string
  noDivider?: boolean
  noPadding?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
  variant: 'default',
  size: 'md',
  title: undefined,
  subtitle: undefined,
  icon: undefined,
  iconColor: undefined,
  iconGradient: undefined,
  clickable: false,
  hover: true,
  to: undefined,
  href: undefined,
  noDivider: false,
  noPadding: false,
})

// Computed classes
const variantClasses = computed(() => {
  const variants = {
    default: 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm',
    outlined: 'bg-transparent border-2 border-gray-300 dark:border-gray-700',
    elevated: 'bg-white dark:bg-gray-900 shadow-lg shadow-gray-200/50 dark:shadow-gray-950/50 border border-gray-100 dark:border-gray-800',
    flat: 'bg-gray-50 dark:bg-gray-800/50',
    gradient: 'bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 border border-gray-200 dark:border-gray-800 shadow-sm',
  }
  return variants[props.variant]
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'rounded-lg',
    md: 'rounded-xl',
    lg: 'rounded-2xl',
  }
  return sizes[props.size]
})

const hoverClasses = computed(() => {
  if (!props.hover) return ''
  return 'transition-all duration-300 hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-gray-950/50 hover:-translate-y-1'
})

const paddingClasses = computed(() => {
  if (props.noPadding) return ''
  const paddings = {
    sm: 'p-3',
    md: 'p-4',
    lg: 'p-6',
  }
  return paddings[props.size]
})

const titleSize = computed(() => {
  const sizes = {
    sm: 'text-sm font-semibold',
    md: 'text-base font-bold',
    lg: 'text-lg font-bold',
  }
  return sizes[props.size]
})

const componentProps = computed(() => {
  if (props.to) {
    return { to: props.to }
  }
  if (props.href) {
    return { href: props.href, target: '_blank', rel: 'noopener noreferrer' }
  }
  return {}
})
</script>

<style scoped>
.shared-card {
  @apply relative overflow-hidden;
}

.card-header {
  @apply px-4 py-3 md:px-5 md:py-4;
}

.card-icon {
  @apply w-9 h-9 md:w-10 md:h-10 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm;
}

.card-title {
  @apply text-gray-900 dark:text-white;
}

.card-subtitle {
  @apply text-xs md:text-sm text-gray-600 dark:text-gray-400 mt-0.5;
}

.card-body {
  @apply relative z-10;
}

.card-footer {
  @apply px-4 py-3 md:px-5 md:py-4 bg-gray-50/50 dark:bg-gray-800/50;
}
</style>
