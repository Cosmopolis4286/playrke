<template>
  <button
    :class="buttonClasses"
    :disabled="disabled || loading"
    :type="type"
    :aria-label="ariaLabel"
    :aria-describedby="ariaDescribedby"
    v-bind="$attrs"
    @click="handleClick"
  >
    <!-- Loading Spinner -->
    <div
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center"
    >
      <div
        class="animate-spin rounded-full border-2 border-transparent border-t-current w-4 h-4"
        aria-hidden="true"
      />
    </div>

    <!-- Left SharedIcon -->
    <component
      :is="leftIcon"
      v-if="leftIcon && !loading"
      :class="['flex-shrink-0', iconSizeClass, { 'mr-2': hasContent }]"
      aria-hidden="true"
    />

    <!-- Slot SharedIcon (legacy support) -->
    <slot
      v-if="!loading && !leftIcon"
      name="icon"
    />

    <!-- Text Content -->
    <span
      v-if="!loading && hasContent"
      :class="{ 'ml-2': $slots.icon && !leftIcon }"
    >
      <slot />
    </span>

    <!-- Right SharedIcon -->
    <component
      :is="rightIcon"
      v-if="rightIcon && !loading"
      :class="['flex-shrink-0', iconSizeClass, { 'ml-2': hasContent }]"
      aria-hidden="true"
    />
  </button>
</template>

<script setup lang="ts">
/**
 * @fileoverview Button component - Sistema unificado de botones
 * @description Botón optimizado con paleta azul profesional y accesibilidad completa
 */
import { computed, useSlots } from 'vue'
import type { Component } from 'vue'

interface Props {
  /** Variante visual del botón */
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'success' | 'warning' | 'info' | 'outline'
  /** Tamaño del botón */
  size?: 'sm' | 'md' | 'lg' | 'xl'
  /** Estado deshabilitado */
  disabled?: boolean
  /** Estado de carga */
  loading?: boolean
  /** Tipo de botón HTML */
  type?: 'button' | 'submit' | 'reset'
  /** Icono a la izquierda */
  leftIcon?: Component
  /** Icono a la derecha */
  rightIcon?: Component
  /** Aria label para accesibilidad */
  ariaLabel?: string
  /** Aria describedby para accesibilidad */
  ariaDescribedby?: string
}

interface Emits {
  /** Evento de click */
  click: [event: Event]
}

defineOptions({
  name: 'Button',
  inheritAttrs: false,
})

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
})

const emit = defineEmits<Emits>()
const slots = useSlots()

// Computed properties
const hasContent = computed(() => Boolean(slots.default))

const iconSizeClass = computed(() => {
  const sizes = {
    sm: 'w-3 h-3',
    md: 'w-4 h-4',
    lg: 'w-5 h-5',
    xl: 'w-6 h-6',
  }
  return sizes[props.size]
})

// Event handlers
const handleClick = (event: Event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}

// Button classes - versión simplificada sin logs
const buttonClasses = computed(() => {
  // Base classes - usando clases que están en el safelist
  const baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200'

  // Size classes
  const sizeClasses = {
    xl: 'px-8 py-4 text-lg',
    lg: 'px-6 py-3 text-base',
    md: 'px-4 py-2 text-sm',
    sm: 'px-3 py-1.5 text-xs',
  }

  // Variant classes
  const variantClasses = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700',
    secondary: 'bg-white text-blue-700 border border-blue-300 hover:bg-blue-50',
    ghost: 'bg-transparent text-blue-600 hover:bg-blue-50',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    success: 'bg-green-600 text-white hover:bg-green-700',
    warning: 'bg-yellow-600 text-white hover:bg-yellow-700',
    info: 'bg-cyan-600 text-white hover:bg-cyan-700',
    outline: 'bg-transparent border border-gray-300 text-gray-700 hover:bg-gray-50',
  }

  const sizeClass = sizeClasses[props.size] || sizeClasses.md
  const variantClass = variantClasses[props.variant] || 'bg-gray-600 text-white hover:bg-gray-700'
  const disabledClass = (props.disabled || props.loading) ? 'opacity-50 cursor-not-allowed' : ''

  return [baseClasses, sizeClass, variantClass, disabledClass].filter(Boolean).join(' ')
})
</script>
