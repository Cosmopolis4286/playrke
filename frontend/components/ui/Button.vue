<template>
  <component
    :is="tag"
    :class="buttonClasses"
    :disabled="disabled || loading"
    v-bind="$attrs"
    @click="handleClick"
  >
    <div class="flex items-center justify-center gap-2">
      <!-- Loading Spinner -->
      <div
        v-if="loading"
        class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"
      />
      
      <!-- Icon Left -->
      <component
        v-if="iconLeft && !loading"
        :is="iconLeft"
        :class="iconClasses"
      />
      
      <!-- Content -->
      <span v-if="$slots.default" :class="{ 'opacity-0': loading }">
        <slot />
      </span>
      
      <!-- Icon Right -->
      <component
        v-if="iconRight && !loading"
        :is="iconRight"
        :class="iconClasses"
      />
    </div>
    
    <!-- Ripple Effect -->
    <span
      v-if="ripple"
      ref="rippleElement"
      class="absolute inset-0 overflow-hidden rounded-[inherit]"
    >
      <span
        v-for="(rippleEffect, index) in rippleEffects"
        :key="index"
        class="absolute rounded-full bg-white/20 pointer-events-none animate-ping"
        :style="rippleEffect.style"
      />
    </span>
  </component>
</template>

<script setup lang="ts">
import { computed, ref, type Component } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'accent' | 'outline' | 'ghost' | 'danger'
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  tag?: string | Component
  disabled?: boolean
  loading?: boolean
  block?: boolean
  rounded?: boolean
  iconLeft?: Component
  iconRight?: Component
  ripple?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  tag: 'button',
  disabled: false,
  loading: false,
  block: false,
  rounded: false,
  ripple: true
})

const emit = defineEmits(['click'])

const rippleElement = ref<HTMLElement>()
const rippleEffects = ref<Array<{ style: string }>>([])

const buttonClasses = computed(() => {
  const base = [
    'relative inline-flex items-center justify-center font-medium transition-all duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-2',
    'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
    'overflow-hidden'
  ]

  // Tamaños
  const sizes = {
    xs: 'px-2.5 py-1.5 text-xs min-h-[28px]',
    sm: 'px-3 py-2 text-sm min-h-[32px]',
    md: 'px-4 py-2.5 text-sm min-h-[40px]',
    lg: 'px-6 py-3 text-base min-h-[44px]',
    xl: 'px-8 py-4 text-lg min-h-[52px]'
  }

  // Variantes
  const variants = {
    primary: [
      'bg-gradient-to-r from-blue-500 to-purple-600',
      'hover:from-blue-600 hover:to-purple-700',
      'active:from-blue-700 active:to-purple-800',
      'text-white shadow-lg hover:shadow-xl',
      'focus:ring-blue-500',
      'hover:-translate-y-0.5 active:translate-y-0'
    ],
    secondary: [
      'bg-gradient-to-r from-gray-100 to-gray-200',
      'hover:from-gray-200 hover:to-gray-300',
      'active:from-gray-300 active:to-gray-400',
      'text-gray-700 shadow-sm hover:shadow-md',
      'focus:ring-gray-500'
    ],
    accent: [
      'bg-gradient-to-r from-orange-500 to-pink-500',
      'hover:from-orange-600 hover:to-pink-600',
      'active:from-orange-700 active:to-pink-700',
      'text-white shadow-lg hover:shadow-xl',
      'focus:ring-orange-500',
      'hover:-translate-y-0.5 active:translate-y-0'
    ],
    outline: [
      'border-2 border-current bg-transparent',
      'text-blue-600 hover:bg-blue-50 active:bg-blue-100',
      'focus:ring-blue-500'
    ],
    ghost: [
      'bg-transparent text-gray-600',
      'hover:bg-gray-100 active:bg-gray-200',
      'focus:ring-gray-500'
    ],
    danger: [
      'bg-gradient-to-r from-red-500 to-red-600',
      'hover:from-red-600 hover:to-red-700',
      'active:from-red-700 active:to-red-800',
      'text-white shadow-lg hover:shadow-xl',
      'focus:ring-red-500',
      'hover:-translate-y-0.5 active:translate-y-0'
    ]
  }

  const rounded = props.rounded ? 'rounded-full' : 'rounded-lg'
  const block = props.block ? 'w-full' : ''

  return [
    ...base,
    sizes[props.size],
    ...variants[props.variant],
    rounded,
    block
  ].filter(Boolean).join(' ')
})

const iconClasses = computed(() => {
  const sizes = {
    xs: 'w-3 h-3',
    sm: 'w-4 h-4',
    md: 'w-4 h-4',
    lg: 'w-5 h-5',
    xl: 'w-6 h-6'
  }
  
  return sizes[props.size]
})

const handleClick = (event: MouseEvent) => {
  if (props.disabled || props.loading) return
  
  // Efecto Ripple
  if (props.ripple && rippleElement.value) {
    createRipple(event)
  }
  
  emit('click', event)
}

const createRipple = (event: MouseEvent) => {
  if (!rippleElement.value) return
  
  const rect = rippleElement.value.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top
  const size = Math.max(rect.width, rect.height) * 2
  
  const rippleStyle = {
    style: `
      left: ${x - size / 2}px;
      top: ${y - size / 2}px;
      width: ${size}px;
      height: ${size}px;
    `
  }
  
  rippleEffects.value.push(rippleStyle)
  
  setTimeout(() => {
    rippleEffects.value.shift()
  }, 600)
}
</script>

<style scoped>
@keyframes ripple {
  0% {
    transform: scale(0);
    opacity: 0.6;
  }
  100% {
    transform: scale(1);
    opacity: 0;
  }
}

.animate-ping {
  animation: ripple 0.6s ease-out;
}
</style>