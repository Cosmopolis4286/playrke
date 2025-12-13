<script setup lang="ts">
/**
 * SharedIcon - Alias component for Icon.vue
 *
 * This component serves as an alias to maintain backward compatibility
 * with existing code that references 'SharedIcon'.
 *
 * The actual implementation is in './media/Icon.vue'
 * This allows Nuxt auto-import to resolve 'SharedIcon' correctly.
 */
import Icon from './media/Icon.vue'
import type { IconSize, IconVariant, IconColor, IconAnimation } from './media/Icon.vue'

// Re-export types for convenience
export type { IconSize, IconVariant, IconColor, IconAnimation }

// Re-export component with same name and options
defineOptions({
  name: 'SharedIcon',
  inheritAttrs: true,
})

// Forward all props to the Icon component
interface Props {
  name?: string
  size?: IconSize
  variant?: IconVariant
  class?: string | string[] | Record<string, boolean>
  animated?: boolean | IconAnimation
  color?: IconColor
  pulse?: boolean
  spin?: boolean
}

const props = defineProps<Props>()
</script>

<template>
  <Icon v-bind="props" :class="props.class">
    <slot />
  </Icon>
</template>