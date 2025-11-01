<template>
  <div
    ref="triggerRef"
    class="tooltip-trigger"
    @mouseenter="showTooltip"
    @mouseleave="hideTooltip"
    @focus="showTooltip"
    @blur="hideTooltip"
    @click="toggleTooltip"
  >
    <!-- Trigger Content -->
    <slot name="trigger">
      <button
        type="button"
        class="tooltip-default-trigger"
        :aria-describedby="isVisible ? tooltipId : undefined"
      >
        <Icon
          name="info"
          class="w-4 h-4 text-secondary-400 hover:text-secondary-600"
        />
      </button>
    </slot>

    <!-- Tooltip Content -->
    <Teleport to="body">
      <div
        v-if="isVisible"
        :id="tooltipId"
        ref="tooltipRef"
        :class="tooltipClasses"
        :style="tooltipStyles"
        role="tooltip"
        :aria-hidden="!isVisible"
      >
        <!-- Arrow -->
        <div
          :class="arrowClasses"
          :style="arrowStyles"
        />

        <!-- Content -->
        <div class="tooltip-content">
          <slot name="content">
            <div
              v-if="title"
              class="tooltip-title"
            >
              {{ title }}
            </div>
            <div
              v-if="content"
              class="tooltip-text"
            >
              {{ content }}
            </div>
          </slot>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview Tooltip component - Tooltip informativo
 * @description Tooltip posicionado con arrow y múltiples configuraciones
 */

import { computePosition, flip, shift, arrow, offset } from '@floating-ui/dom'

interface Props {
  /** Contenido del tooltip */
  content?: string
  /** Título del tooltip */
  title?: string
  /** Posición preferida */
  placement?: 'top' | 'bottom' | 'left' | 'right' | 'top-start' | 'top-end' | 'bottom-start' | 'bottom-end'
  /** Trigger del tooltip */
  trigger?: 'hover' | 'click' | 'focus' | 'manual'
  /** Retraso para mostrar (ms) */
  showDelay?: number
  /** Retraso para ocultar (ms) */
  hideDelay?: number
  /** Offset desde el elemento trigger */
  offset?: number
  /** Deshabilitado */
  disabled?: boolean
  /** Tema del tooltip */
  theme?: 'dark' | 'light' | 'primary' | 'success' | 'warning' | 'error'
  /** Tamaño del tooltip */
  size?: 'sm' | 'md' | 'lg'
  /** Ancho máximo */
  maxWidth?: string
  /** Mostrar arrow */
  showArrow?: boolean
}

defineOptions({
  name: 'Tooltip',
})

const props = withDefaults(defineProps<Props>(), {
  placement: 'top',
  trigger: 'hover',
  showDelay: 200,
  hideDelay: 100,
  offset: 8,
  disabled: false,
  theme: 'dark',
  size: 'md',
  maxWidth: '320px',
  showArrow: true,
})

const emit = defineEmits<{
  show: []
  hide: []
  toggle: [visible: boolean]
}>()

// Refs
const triggerRef = ref<HTMLElement>()
const tooltipRef = ref<HTMLElement>()
const arrowRef = ref<HTMLElement>()
const isVisible = ref(false)
const showTimeout = ref<NodeJS.Timeout>()
const hideTimeout = ref<NodeJS.Timeout>()

// Unique ID for accessibility
const tooltipId = computed(() => `tooltip-${Math.random().toString(36).substr(2, 9)}`)

// Position state
const tooltipPosition = reactive({
  x: 0,
  y: 0,
  placement: props.placement as string,
  arrowX: 0,
  arrowY: 0,
})

// Computed styles
const tooltipClasses = computed(() => {
  const classes = ['tooltip-container']

  // Theme classes
  switch (props.theme) {
    case 'light':
      classes.push('bg-white text-secondary-900 border border-secondary-200 shadow-lg')
      break
    case 'primary':
      classes.push('bg-primary-600 text-white')
      break
    case 'success':
      classes.push('bg-success-600 text-white')
      break
    case 'warning':
      classes.push('bg-warning-600 text-white')
      break
    case 'error':
      classes.push('bg-error-600 text-white')
      break
    default:
      classes.push('bg-secondary-900 text-white')
  }

  // Size classes
  switch (props.size) {
    case 'sm':
      classes.push('px-2 py-1 text-xs')
      break
    case 'lg':
      classes.push('px-4 py-3 text-base')
      break
    default:
      classes.push('px-3 py-2 text-sm')
  }

  classes.push('rounded-md shadow-lg z-50 transition-opacity duration-200')

  return classes
})

const tooltipStyles = computed(() => ({
  position: 'absolute' as const,
  left: `${tooltipPosition.x}px`,
  top: `${tooltipPosition.y}px`,
  maxWidth: props.maxWidth,
  opacity: isVisible.value ? '1' : '0',
  pointerEvents: (isVisible.value ? 'auto' : 'none') as 'auto' | 'none',
}))

const arrowClasses = computed(() => {
  if (!props.showArrow) { return [] }

  const classes = ['tooltip-arrow']

  // Theme-based arrow styles
  switch (props.theme) {
    case 'light':
      classes.push('bg-white border-secondary-200')
      break
    case 'primary':
      classes.push('bg-primary-600')
      break
    case 'success':
      classes.push('bg-success-600')
      break
    case 'warning':
      classes.push('bg-warning-600')
      break
    case 'error':
      classes.push('bg-error-600')
      break
    default:
      classes.push('bg-secondary-900')
  }

  // Position-based classes
  if (tooltipPosition.placement.startsWith('top')) {
    classes.push('bottom-[-4px] border-t border-l')
  } else if (tooltipPosition.placement.startsWith('bottom')) {
    classes.push('top-[-4px] border-b border-r')
  } else if (tooltipPosition.placement.startsWith('left')) {
    classes.push('right-[-4px] border-l border-b')
  } else if (tooltipPosition.placement.startsWith('right')) {
    classes.push('left-[-4px] border-r border-t')
  }

  return classes
})

const arrowStyles = computed(() => {
  if (!props.showArrow) { return {} }

  return {
    position: 'absolute' as const,
    left: tooltipPosition.arrowX ? `${tooltipPosition.arrowX}px` : undefined,
    top: tooltipPosition.arrowY ? `${tooltipPosition.arrowY}px` : undefined,
  }
})

// Position calculation
const updatePosition = async () => {
  if (!triggerRef.value || !tooltipRef.value) { return }

  const arrowElement = arrowRef.value

  try {
    const { x, y, placement, middlewareData } = await computePosition(
      triggerRef.value,
      tooltipRef.value,
      {
        placement: props.placement,
        middleware: [
          offset(props.offset),
          flip(),
          shift({ padding: 8 }),
          ...(props.showArrow && arrowElement ? [arrow({ element: arrowElement })] : []),
        ],
      },
    )

    tooltipPosition.x = x
    tooltipPosition.y = y
    tooltipPosition.placement = placement

    // Arrow positioning
    if (props.showArrow && middlewareData.arrow) {
      const { x: arrowX, y: arrowY } = middlewareData.arrow
      tooltipPosition.arrowX = arrowX ?? 0
      tooltipPosition.arrowY = arrowY ?? 0
    }
  } catch (error) {
    console.warn('Error calculating tooltip position:', error)
  }
}

// Show/hide methods
const showTooltip = () => {
  if (props.disabled || props.trigger === 'manual') { return }

  clearTimeout(hideTimeout.value)

  if (props.showDelay > 0) {
    showTimeout.value = setTimeout(() => {
      doShow()
    }, props.showDelay)
  } else {
    doShow()
  }
}

const hideTooltip = () => {
  if (props.disabled || props.trigger === 'manual') { return }

  clearTimeout(showTimeout.value)

  if (props.hideDelay > 0) {
    hideTimeout.value = setTimeout(() => {
      doHide()
    }, props.hideDelay)
  } else {
    doHide()
  }
}

const toggleTooltip = () => {
  if (props.trigger !== 'click') { return }

  if (isVisible.value) {
    hideTooltip()
  } else {
    showTooltip()
  }
}

const doShow = async () => {
  if (isVisible.value) { return }

  isVisible.value = true
  emit('show')
  emit('toggle', true)

  await nextTick()
  await updatePosition()
}

const doHide = () => {
  if (!isVisible.value) { return }

  isVisible.value = false
  emit('hide')
  emit('toggle', false)
}

// Public methods
const show = () => doShow()
const hide = () => doHide()
const toggle = () => {
  if (isVisible.value) { hide() } else { show() }
}

// Click outside handler
const handleClickOutside = (event: Event) => {
  if (props.trigger !== 'click') { return }

  const target = event.target as Node
  if (
    !triggerRef.value?.contains(target)
    && !tooltipRef.value?.contains(target)
  ) {
    hide()
  }
}

// Lifecycle
onMounted(() => {
  if (props.trigger === 'click') {
    document.addEventListener('click', handleClickOutside)
  }
})

onUnmounted(() => {
  clearTimeout(showTimeout.value)
  clearTimeout(hideTimeout.value)
  document.removeEventListener('click', handleClickOutside)
})

// Watch for position updates
watch(isVisible, (visible) => {
  if (visible) {
    nextTick(() => updatePosition())
  }
})

defineExpose({
  show,
  hide,
  toggle,
  isVisible: readonly(isVisible),
})
</script>

<style scoped>
.tooltip-trigger {
  @apply inline-block;
}

.tooltip-default-trigger {
  @apply inline-flex items-center justify-center transition-colors duration-200;
}

.tooltip-container {
  @apply pointer-events-auto;
  word-wrap: break-word;
  z-index: 9999;
}

.tooltip-content {
  @apply relative z-10;
}

.tooltip-title {
  @apply font-semibold mb-1;
}

.tooltip-text {
  @apply leading-relaxed;
}

.tooltip-arrow {
  @apply absolute w-2 h-2 rotate-45;
}

/* Animation classes */
.tooltip-container {
  transform-origin: center;
  transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
}

/* Accessibility improvements */
.tooltip-trigger:focus-visible {
  @apply outline-2 outline-primary-500 outline-offset-2;
}

/* Prevent tooltip from blocking interactions */
.tooltip-container {
  pointer-events: none;
}

.tooltip-content {
  pointer-events: auto;
}
</style>
