<template>
  <div :class="cardClasses" v-bind="$attrs">
    <!-- Header -->
    <div 
      v-if="$slots.header || title || subtitle" 
      class="flex items-start justify-between p-6 border-b border-gray-200/50"
    >
      <div class="flex-1">
        <div v-if="$slots.header">
          <slot name="header" />
        </div>
        <div v-else>
          <h3 v-if="title" class="text-lg font-semibold text-gray-900">
            {{ title }}
          </h3>
          <p v-if="subtitle" class="mt-1 text-sm text-gray-500">
            {{ subtitle }}
          </p>
        </div>
      </div>
      
      <!-- Header Actions -->
      <div v-if="$slots.actions" class="flex items-center gap-2 ml-4">
        <slot name="actions" />
      </div>
    </div>

    <!-- Content -->
    <div :class="contentClasses">
      <slot />
    </div>

    <!-- Footer -->
    <div 
      v-if="$slots.footer" 
      class="flex items-center justify-between px-6 py-4 bg-gray-50/50 border-t border-gray-200/50 rounded-b-xl"
    >
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'default' | 'elevated' | 'outlined' | 'glass' | 'gradient'
  size?: 'sm' | 'md' | 'lg'
  title?: string
  subtitle?: string
  padding?: boolean
  hover?: boolean
  clickable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md',
  padding: true,
  hover: false,
  clickable: false
})

const cardClasses = computed(() => {
  const base = [
    'bg-white rounded-xl transition-all duration-300',
    'border border-gray-200/50'
  ]

  // Variantes
  const variants = {
    default: ['shadow-sm hover:shadow-md'],
    elevated: ['shadow-lg hover:shadow-xl'],
    outlined: ['border-2 border-gray-200 shadow-none'],
    glass: [
      'bg-white/80 backdrop-blur-sm border-white/20',
      'shadow-lg hover:shadow-xl'
    ],
    gradient: [
      'bg-gradient-to-br from-white to-gray-50',
      'shadow-lg hover:shadow-xl',
      'border-0'
    ]
  }

  // Hover effects
  const hoverEffects = props.hover || props.clickable ? [
    'hover:-translate-y-1',
    'hover:shadow-xl'
  ] : []

  // Clickable
  const clickableStyles = props.clickable ? [
    'cursor-pointer',
    'active:scale-[0.98]',
    'select-none'
  ] : []

  return [
    ...base,
    ...variants[props.variant],
    ...hoverEffects,
    ...clickableStyles
  ].filter(Boolean).join(' ')
})

const contentClasses = computed(() => {
  if (!props.padding) return ''
  
  const sizes = {
    sm: 'p-4',
    md: 'p-6',
    lg: 'p-8'
  }
  
  return sizes[props.size]
})
</script>