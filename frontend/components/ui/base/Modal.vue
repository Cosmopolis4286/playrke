<template>
  <Teleport to="body">
    <Transition
      name="modal-backdrop"
      appear
    >
      <div
        v-if="modelValue"
        :class="overlayClasses"
        :aria-hidden="!modelValue"
        @click="handleBackdropClick"
        @keydown="handleKeydown"
      >
        <!-- Enhanced Backdrop -->
        <div :class="backdropClasses">
          <!-- Loading Overlay Integration -->
          <div
            v-if="loading"
            class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-50 rounded-2xl"
            role="status"
            aria-live="polite"
          >
            <div class="flex flex-col items-center gap-3">
              <SharedIcon
                name="spinner"
                :size="loadingSize"
                color="primary"
                spinning
                :aria-hidden="false"
                :aria-label="loadingText"
              />
              <span class="text-sm text-gray-600 font-medium">{{ loadingText }}</span>
            </div>
          </div>
        </div>

        <!-- Focus Trap Container -->
        <div
          ref="focusTrapRef"
          class="contents"
          @keydown.tab="handleTabKey"
        >
          <!-- Modal Container -->
          <Transition
            name="modal-content"
            appear
            @enter="onEnter"
            @leave="onLeave"
          >
            <div
              v-if="modelValue"
              ref="modalRef"
              :class="modalClasses"
              :aria-modal="true"
              :aria-labelledby="headerId"
              :aria-describedby="bodyId"
              role="dialog"
              @click.stop
            >
              <!-- Glassmorphism Background -->
              <div :class="glassmorphismClasses" />

              <!-- Border Glow Effect -->
              <div
                v-if="showGlow"
                :class="glowClasses"
              />

              <!-- Modal Content -->
              <div class="relative z-10 flex flex-col h-full">
                <!-- Header Section -->
                <ModalHeader
                  v-if="showHeader"
                  :id="headerId"
                  :title="title"
                  :subtitle="subtitle"
                  :icon="icon"
                  :variant="headerVariant"
                  :closable="closable"
                  :loading="loading"
                  @close="handleClose"
                >
                  <template
                    v-if="$slots.header"
                    #custom
                  >
                    <slot name="header" />
                  </template>
                </ModalHeader>

                <!-- Body Section -->
                <div
                  :id="bodyId"
                  :class="bodyClasses"
                >
                  <!-- Content Wrapper -->
                  <div :class="contentWrapperClasses">
                    <slot />
                  </div>

                  <!-- Scroll Fade Effects -->
                  <div
                    v-if="showScrollFade"
                    :class="scrollFadeTopClasses"
                  />
                  <div
                    v-if="showScrollFade"
                    :class="scrollFadeBottomClasses"
                  />
                </div>

                <!-- Footer Section -->
                <ModalFooter
                  v-if="showFooter"
                  :show-default-buttons="showDefaultButtons"
                  :show-cancel-button="showCancelButton"
                  :show-confirm-button="showConfirmButton"
                  :cancel-text="cancelText"
                  :confirm-text="confirmText"
                  :confirm-variant="confirmVariant"
                  :confirm-disabled="confirmDisabled"
                  :cancel-disabled="cancelDisabled"
                  :loading="loading"
                  :variant="footerVariant"
                  @confirm="handleConfirm"
                  @cancel="handleCancel"
                >
                  <template
                    v-if="$slots.footer"
                    #custom
                  >
                    <slot name="footer" />
                  </template>
                </ModalFooter>
              </div>

              <!-- Loading Overlay ya integrado en backdrop -->

              <!-- Resize Handle -->
              <div
                v-if="resizable"
                :class="resizeHandleClasses"
                @mousedown="handleResizeStart"
              />
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, nextTick, onMounted, onUnmounted, watch, useId, useSlots } from 'vue'
import ModalHeader from './ModalHeader.vue'
import ModalFooter from './ModalFooter.vue'
import type { PropType } from 'vue'

defineOptions({ name: 'Modal' })

// Types
interface ModalSize {
  width: string
  height?: string
  maxWidth?: string
  maxHeight?: string
}

interface FocusableElement extends HTMLElement {
  focus(): void
}

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  variant: {
    type: String as PropType<'default' | 'premium' | 'gaming' | 'minimal'>,
    default: 'default',
  },
  size: {
    type: String as PropType<'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | 'full' | 'auto'>,
    default: 'md',
  },
  position: {
    type: String as PropType<'center' | 'top' | 'bottom'>,
    default: 'center',
  },
  closable: {
    type: Boolean,
    default: true,
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true,
  },
  closeOnEscape: {
    type: Boolean,
    default: true,
  },
  persistent: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  loadingText: {
    type: String,
    default: 'Procesando...',
  },
  loadingVariant: {
    type: String as PropType<'default' | 'premium' | 'minimal'>,
    default: 'default',
  },
  showLoadingProgress: {
    type: Boolean,
    default: false,
  },
  loadingProgress: {
    type: Number,
    default: 0,
  },
  loadingSize: {
    type: String as PropType<'xs' | 'sm' | 'md' | 'lg' | 'xl'>,
    default: 'lg',
  },
  showDefaultButtons: {
    type: Boolean,
    default: false,
  },
  showCancelButton: {
    type: Boolean,
    default: true,
  },
  showConfirmButton: {
    type: Boolean,
    default: true,
  },
  cancelText: {
    type: String,
    default: 'Cancelar',
  },
  confirmText: {
    type: String,
    default: 'Confirmar',
  },
  confirmVariant: {
    type: String as PropType<'primary' | 'success' | 'danger' | 'warning'>,
    default: 'primary',
  },
  confirmDisabled: {
    type: Boolean,
    default: false,
  },
  cancelDisabled: {
    type: Boolean,
    default: false,
  },
  bodyPadding: {
    type: String as PropType<'none' | 'sm' | 'md' | 'lg' | 'xl'>,
    default: 'md',
  },
  headerVariant: {
    type: String as PropType<'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'default' | 'minimal' | 'gaming' | 'premium'>,
    default: 'default',
  },
  footerVariant: {
    type: String as PropType<'default' | 'primary' | 'secondary' | 'minimal' | 'gaming' | 'premium'>,
    default: 'default',
  },
  showGlow: {
    type: Boolean,
    default: true,
  },
  showScrollFade: {
    type: Boolean,
    default: true,
  },
  resizable: {
    type: Boolean,
    default: false,
  },
  draggable: {
    type: Boolean,
    default: false,
  },
  zIndex: {
    type: Number,
    default: 50,
  },
  backdropBlur: {
    type: String as PropType<'none' | 'sm' | 'md' | 'lg' | 'xl'>,
    default: 'md',
  },
  transitionDuration: {
    type: Number,
    default: 300,
  },
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'close': []
  'confirm': []
  'cancel': []
  'opened': []
  'closed': []
  'backdrop-click': []
  'escape-key': []
}>()

// State
const modalRef = ref<HTMLElement | null>(null)
const focusTrapRef = ref<HTMLElement | null>(null)
const previousActiveElement = ref<Element | null>(null)

// IDs for accessibility
const headerId = useId()
const bodyId = useId()

// Slots
const slots = useSlots()

// Computed - Display Logic
const showHeader = computed(() => !!(props.title || props.subtitle || props.icon || slots.header))

const showFooter = computed(() => props.showDefaultButtons || slots.footer)

// Computed - Styles
const overlayClasses = computed(() => [
  'fixed inset-0 flex transition-all duration-300',
  `z-${props.zIndex}`,
  getPositionClasses(),
  {
    'p-4': props.size !== 'full',
    'p-0': props.size === 'full',
  },
].join(' '))

const backdropClasses = computed(() => [
  'absolute inset-0 transition-all duration-300',
  getBackdropClasses(),
].join(' '))

const modalClasses = computed(() => [
  'relative w-full transition-all duration-300 transform-gpu',
  'bg-white/95 backdrop-blur-xl shadow-2xl',
  'border border-white/20',
  getSizeClasses(),
  getVariantClasses(),
  {
    'max-h-[90vh]': props.size !== 'full',
    'h-screen w-screen': props.size === 'full',
    'overflow-hidden': true,
    'cursor-move': props.draggable,
    'resize': props.resizable,
  },
].join(' '))

const glassmorphismClasses = computed(() => [
  'absolute inset-0 transition-opacity duration-300',
  getGlassmorphismClasses(),
].join(' '))

const glowClasses = computed(() => [
  'absolute inset-0 rounded-2xl transition-opacity duration-500',
  'bg-gradient-to-r from-blue-400/20 via-purple-400/10 to-blue-400/20',
  'blur-xl opacity-75',
].join(' '))

const bodyClasses = computed(() => [
  'relative flex-1 overflow-auto',
  'custom-scrollbar',
].join(' '))

const contentWrapperClasses = computed(() => [
  'relative z-10',
  getPaddingClasses(),
].join(' '))

const scrollFadeTopClasses = computed(() => [
  'absolute top-0 left-0 right-0 h-4 z-20 pointer-events-none',
  'bg-gradient-to-b from-white to-transparent',
].join(' '))

const scrollFadeBottomClasses = computed(() => [
  'absolute bottom-0 left-0 right-0 h-4 z-20 pointer-events-none',
  'bg-gradient-to-t from-white to-transparent',
].join(' '))

const resizeHandleClasses = computed(() => [
  'absolute bottom-0 right-0 w-4 h-4 cursor-se-resize',
  'bg-gradient-to-tl from-gray-400 to-transparent',
  'hover:from-gray-600 transition-colors duration-200',
].join(' '))

// Utility Functions
const getPositionClasses = (): string => {
  switch (props.position) {
    case 'top': return 'items-start justify-center pt-16'
    case 'bottom': return 'items-end justify-center pb-16'
    default: return 'items-center justify-center'
  }
}

const getBackdropClasses = (): string => {
  const blurClasses = {
    none: 'bg-black/50',
    sm: 'bg-black/30 backdrop-blur-sm',
    md: 'bg-black/40 backdrop-blur-md',
    lg: 'bg-black/30 backdrop-blur-lg',
    xl: 'bg-black/20 backdrop-blur-xl',
  }
  return blurClasses[props.backdropBlur]
}

const getSizeClasses = (): string => {
  const sizeClasses = {
    'xs': 'max-w-xs',
    'sm': 'max-w-sm',
    'md': 'max-w-md',
    'lg': 'max-w-lg',
    'xl': 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    'full': 'w-full h-full max-w-none max-h-none',
    'auto': 'max-w-fit max-h-fit',
  }
  return sizeClasses[props.size]
}

const getVariantClasses = (): string => {
  switch (props.variant) {
    case 'premium':
      return 'rounded-3xl border-2 border-gradient-to-r from-blue-400 to-purple-400'
    case 'gaming':
      return 'rounded-2xl border-2 border-blue-500/50 shadow-blue-500/25'
    case 'minimal':
      return 'rounded-xl border border-gray-200 shadow-lg'
    default:
      return 'rounded-2xl border border-white/20 shadow-2xl'
  }
}

const getGlassmorphismClasses = (): string => {
  switch (props.variant) {
    case 'premium':
      return 'bg-gradient-to-br from-white/90 via-white/95 to-blue-50/90'
    case 'gaming':
      return 'bg-gradient-to-br from-gray-900/95 via-blue-900/90 to-purple-900/95'
    case 'minimal':
      return 'bg-white/98'
    default:
      return 'bg-gradient-to-br from-white/95 via-white/98 to-gray-50/95'
  }
}

const getPaddingClasses = (): string => {
  const paddingClasses = {
    none: 'p-0',
    sm: 'p-3',
    md: 'p-6',
    lg: 'p-8',
    xl: 'p-10',
  }
  return paddingClasses[props.bodyPadding]
}

// Focus Management
const getFocusableElements = (): FocusableElement[] => {
  if (!modalRef.value) { return [] }

  const selector = [
    'button:not([disabled])',
    'input:not([disabled])',
    'textarea:not([disabled])',
    'select:not([disabled])',
    'a[href]',
    '[tabindex]:not([tabindex="-1"])',
  ].join(', ')

  return Array.from(modalRef.value.querySelectorAll(selector)) as FocusableElement[]
}

const trapFocus = (event: KeyboardEvent) => {
  const focusableElements = getFocusableElements()
  if (focusableElements.length === 0) { return }

  const firstElement = focusableElements[0]
  const lastElement = focusableElements[focusableElements.length - 1]

  if (!firstElement || !lastElement) { return }

  if (event.shiftKey) {
    if (document.activeElement === firstElement) {
      event.preventDefault()
      lastElement.focus()
    }
  } else {
    if (document.activeElement === lastElement) {
      event.preventDefault()
      firstElement.focus()
    }
  }
}

// Event Handlers
const handleClose = () => {
  if (props.persistent || !props.closable) { return }
  emit('update:modelValue', false)
  emit('close')
}

const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
  if (!props.persistent) {
    handleClose()
  }
}

const handleBackdropClick = () => {
  emit('backdrop-click')
  if (props.closeOnBackdrop && props.closable && !props.persistent) {
    handleClose()
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    emit('escape-key')
    if (props.closeOnEscape && props.closable && !props.persistent) {
      handleClose()
    }
  }
}

const handleTabKey = (event: KeyboardEvent) => {
  if (event.key === 'Tab') {
    trapFocus(event)
  }
}

const handleResizeStart = (event: MouseEvent) => {
  if (!props.resizable) { return }
  event.preventDefault()
  // Resize logic would go here
}

// Transition Handlers
const onEnter = async (el: Element) => {
  await nextTick()

  // Store previous active element
  previousActiveElement.value = document.activeElement

  // Focus first focusable element
  const focusableElements = getFocusableElements()
  if (focusableElements.length > 0 && focusableElements[0]) {
    focusableElements[0].focus()
  }

  emit('opened')
}

const onLeave = () => {
  // Restore focus to previous element
  if (previousActiveElement.value && 'focus' in previousActiveElement.value) {
    (previousActiveElement.value as FocusableElement).focus()
  }

  emit('closed')
}

// Body Scroll Management
const lockBodyScroll = () => {
  document.body.style.overflow = 'hidden'
  document.body.style.paddingRight = `${getScrollbarWidth()}px`
}

const unlockBodyScroll = () => {
  document.body.style.overflow = ''
  document.body.style.paddingRight = ''
}

const getScrollbarWidth = (): number => {
  const outer = document.createElement('div')
  outer.style.visibility = 'hidden'
  outer.style.overflow = 'scroll'
  document.body.appendChild(outer)

  const inner = document.createElement('div')
  outer.appendChild(inner)

  const scrollbarWidth = outer.offsetWidth - inner.offsetWidth

  outer.parentNode?.removeChild(outer)

  return scrollbarWidth
}

// Lifecycle
onMounted(() => {
  if (props.modelValue) {
    lockBodyScroll()
  }
})

onUnmounted(() => {
  unlockBodyScroll()
})

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    lockBodyScroll()
  } else {
    unlockBodyScroll()
  }
})

// Expose methods for parent components
defineExpose({
  close: handleClose,
  focus: () => {
    const focusableElements = getFocusableElements()
    if (focusableElements.length > 0 && focusableElements[0]) {
      focusableElements[0].focus()
    }
  },
})
</script>

<style scoped>
/* Modal Backdrop Animations */
.modal-backdrop-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-backdrop-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 1, 1);
}

.modal-backdrop-enter-from {
  opacity: 0;
}

.modal-backdrop-leave-to {
  opacity: 0;
}

/* Modal Content Animations */
.modal-content-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-content-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 1, 1);
}

.modal-content-enter-from {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.modal-content-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

/* Custom Scrollbar */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.4);
}

.custom-scrollbar::-webkit-scrollbar-thumb:active {
  background: rgba(0, 0, 0, 0.6);
}

/* GPU Acceleration */
.modal-backdrop-enter-active,
.modal-backdrop-leave-active,
.modal-content-enter-active,
.modal-content-leave-active {
  will-change: transform, opacity;
  transform: translateZ(0);
}

/* Gaming Variant Specific Styles */
.gaming-modal {
  background: linear-gradient(135deg,
    rgba(17, 24, 39, 0.95) 0%,
    rgba(30, 64, 175, 0.90) 50%,
    rgba(67, 56, 202, 0.95) 100%);
  color: white;
}

/* Premium Variant Glow Effect */
.premium-glow {
  box-shadow:
    0 0 50px rgba(59, 130, 246, 0.15),
    0 0 100px rgba(147, 51, 234, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

/* Responsive Adjustments */
@media (max-width: 640px) {
  .modal-content-enter-from {
    transform: translateY(-10px) scale(0.98);
  }

  .modal-content-leave-to {
    transform: translateY(10px) scale(0.98);
  }
}

@media (prefers-reduced-motion: reduce) {
  .modal-backdrop-enter-active,
  .modal-backdrop-leave-active,
  .modal-content-enter-active,
  .modal-content-leave-active {
    transition: none;
    animation: none;
  }

  .modal-content-enter-from,
  .modal-content-leave-to {
    transform: none;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.8);
  }

  .modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
  }
}
</style>
