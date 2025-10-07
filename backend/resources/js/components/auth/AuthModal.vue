<template>
  <teleport to="body">
    <transition
      :name="transitionName"
      @enter="onEnter"
      @leave="onLeave"
    >
      <div
        v-if="visible"
        class="auth__modal-overlay"
        :class="overlayClasses"
        @click="handleOverlayClick"
        @keydown.escape="handleEscape"
        role="dialog"
        :aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="contentId"
      >
        <div
          ref="modalRef"
          class="auth__modal"
          :class="modalClasses"
          @click.stop
          @keydown="handleKeydown"
        >
          <!-- Header -->
          <div v-if="$slots.header || title" class="auth__modal-header">
            <slot name="header">
              <h3 :id="titleId" class="auth__modal-title">
                <i v-if="icon" :class="icon" class="mr-2"></i>
                {{ title }}
              </h3>
              <button
                v-if="closable"
                type="button"
                @click="handleClose"
                class="auth__modal-close"
                :aria-label="closeLabel"
              >
                <i class="fas fa-times"></i>
              </button>
            </slot>
          </div>

          <!-- Body -->
          <div :id="contentId" class="auth__modal-content" :class="contentClasses">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="auth__modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

// Types
interface Props {
  visible: boolean
  title?: string
  icon?: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full'
  variant?: 'default' | 'danger' | 'success' | 'warning' | 'info'
  closable?: boolean
  closeOnOverlay?: boolean
  closeOnEscape?: boolean
  closeLabel?: string
  transitionName?: string
  persistent?: boolean
  scrollable?: boolean
  centered?: boolean
  backdrop?: boolean
  zIndex?: number
}

// Props
const props = withDefaults(defineProps<Props>(), {
  title: '',
  icon: '',
  size: 'md',
  variant: 'default',
  closable: true,
  closeOnOverlay: true,
  closeOnEscape: true,
  closeLabel: 'Cerrar',
  transitionName: 'modal',
  persistent: false,
  scrollable: true,
  centered: true,
  backdrop: true,
  zIndex: 50,
})

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean]
  'close': []
  'opened': []
  'closed': []
  'before-close': []
}>()

// State
const modalRef = ref<HTMLElement>()
const focusableElements = ref<NodeListOf<HTMLElement> | null>(null)
const previousActiveElement = ref<HTMLElement | null>(null)
const currentFocusIndex = ref(0)

// Computed
const titleId = computed(() => `modal-title-${Math.random().toString(36).substr(2, 9)}`)
const contentId = computed(() => `modal-content-${Math.random().toString(36).substr(2, 9)}`)

const overlayClasses = computed(() => {
  const classes = []
  if (!props.backdrop) classes.push('auth__modal-overlay--no-backdrop')
  if (props.centered) classes.push('auth__modal-overlay--centered')
  return classes
})

const modalClasses = computed(() => {
  const classes = []

  // Size classes
  classes.push(`auth__modal--${props.size}`)

  // Variant classes
  if (props.variant !== 'default') {
    classes.push(`auth__modal--${props.variant}`)
  }

  // Modifier classes
  if (props.scrollable) classes.push('auth__modal--scrollable')
  if (props.persistent) classes.push('auth__modal--persistent')

  return classes
})

const contentClasses = computed(() => {
  const classes = []
  if (props.scrollable) classes.push('auth__modal-content--scrollable')
  return classes
})

// Methods
const handleClose = () => {
  if (props.persistent) return

  emit('before-close')
  emit('update:visible', false)
  emit('close')
}

const handleOverlayClick = () => {
  if (props.closeOnOverlay && !props.persistent) {
    handleClose()
  }
}

const handleEscape = () => {
  if (props.closeOnEscape && !props.persistent) {
    handleClose()
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (!props.visible) return

  switch (event.key) {
    case 'Escape':
      handleEscape()
      break
    case 'Tab':
      handleTabNavigation(event)
      break
  }
}

const handleTabNavigation = (event: KeyboardEvent) => {
  if (!focusableElements.value) return

  const elements = Array.from(focusableElements.value)
  const currentIndex = elements.indexOf(document.activeElement as HTMLElement)

  if (event.shiftKey) {
    // Shift + Tab (backwards)
    const prevIndex = currentIndex <= 0 ? elements.length - 1 : currentIndex - 1
    elements[prevIndex]?.focus()
  } else {
    // Tab (forwards)
    const nextIndex = currentIndex >= elements.length - 1 ? 0 : currentIndex + 1
    elements[nextIndex]?.focus()
  }

  event.preventDefault()
}

const setupFocusTrap = () => {
  if (!modalRef.value) return

  // Store the previously focused element
  previousActiveElement.value = document.activeElement as HTMLElement

  // Find all focusable elements within the modal
  focusableElements.value = modalRef.value.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )

  // Focus the first focusable element
  if (focusableElements.value.length > 0) {
    focusableElements.value[0].focus()
  }
}

const removeFocusTrap = () => {
  // Return focus to the previously focused element
  if (previousActiveElement.value) {
    previousActiveElement.value.focus()
  }

  focusableElements.value = null
  previousActiveElement.value = null
}

const lockBodyScroll = () => {
  document.body.style.overflow = 'hidden'
  document.body.style.paddingRight = getScrollbarWidth() + 'px'
}

const unlockBodyScroll = () => {
  document.body.style.overflow = ''
  document.body.style.paddingRight = ''
}

const getScrollbarWidth = (): number => {
  const scrollDiv = document.createElement('div')
  scrollDiv.style.cssText = 'width: 100px; height: 100px; overflow: scroll; position: absolute; top: -9999px;'
  document.body.appendChild(scrollDiv)
  const scrollbarWidth = scrollDiv.offsetWidth - scrollDiv.clientWidth
  document.body.removeChild(scrollDiv)
  return scrollbarWidth
}

// Transition hooks
const onEnter = () => {
  nextTick(() => {
    setupFocusTrap()
    lockBodyScroll()
    emit('opened')
  })
}

const onLeave = () => {
  removeFocusTrap()
  unlockBodyScroll()
  emit('closed')
}

// Watchers
watch(() => props.visible, (newValue) => {
  if (newValue) {
    nextTick(() => {
      setupFocusTrap()
    })
  }
})

// Lifecycle
onMounted(() => {
  if (props.visible) {
    setupFocusTrap()
    lockBodyScroll()
  }
})

onUnmounted(() => {
  unlockBodyScroll()
  removeFocusTrap()
})
</script>

<style scoped>
/* Modal Overlay */
.auth__modal-overlay {
  @apply fixed inset-0 flex items-center justify-center p-4;
  z-index: v-bind('props.zIndex');
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(8px);
}

.auth__modal-overlay--no-backdrop {
  background: transparent;
  backdrop-filter: none;
}

.auth__modal-overlay--centered {
  @apply items-center;
}

/* Modal Container */
.auth__modal {
  @apply bg-gray-800 rounded-2xl shadow-2xl w-full overflow-hidden;
  border: 1px solid var(--color-border-secondary);
  max-height: 90vh;
}

/* Size variants */
.auth__modal--xs {
  @apply max-w-xs;
}

.auth__modal--sm {
  @apply max-w-sm;
}

.auth__modal--md {
  @apply max-w-md;
}

.auth__modal--lg {
  @apply max-w-lg;
}

.auth__modal--xl {
  @apply max-w-2xl;
}

.auth__modal--full {
  @apply max-w-full h-full m-0 rounded-none;
  max-height: 100vh;
}

/* Variant styles */
.auth__modal--danger {
  border-color: var(--color-border-error);
}

.auth__modal--danger .auth__modal-header {
  @apply bg-red-600/10 border-red-600/20;
}

.auth__modal--success {
  border-color: var(--color-border-success);
}

.auth__modal--success .auth__modal-header {
  @apply bg-green-600/10 border-green-600/20;
}

.auth__modal--warning {
  border-color: #f59e0b;
}

.auth__modal--warning .auth__modal-header {
  @apply bg-yellow-600/10 border-yellow-600/20;
}

.auth__modal--info {
  border-color: var(--color-primary);
}

.auth__modal--info .auth__modal-header {
  @apply bg-blue-600/10 border-blue-600/20;
}

/* Scrollable modifier */
.auth__modal--scrollable {
  @apply flex flex-col;
}

.auth__modal--scrollable .auth__modal-content--scrollable {
  @apply flex-1 overflow-y-auto;
}

/* Persistent modifier */
.auth__modal--persistent {
  animation: shake 0.5s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

/* Modal Header */
.auth__modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-600;
}

.auth__modal-title {
  @apply text-lg font-semibold text-white flex items-center;
}

.auth__modal-close {
  @apply p-2 rounded-full hover:bg-gray-700 transition-colors;
  color: var(--color-text-muted);
}

.auth__modal-close:hover {
  color: var(--color-text-primary);
}

/* Modal Content */
.auth__modal-content {
  @apply p-6 text-gray-300 text-sm leading-relaxed;
}

.auth__modal-content--scrollable {
  @apply overflow-y-auto;
  max-height: 60vh;
}

/* Modal Footer */
.auth__modal-footer {
  @apply flex items-center justify-end space-x-3 p-6 border-t border-gray-600 bg-gray-750;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease-out;
}

.modal-enter-from {
  opacity: 0;
  transform: scale(0.8) translateY(-20px);
}

.modal-leave-to {
  opacity: 0;
  transform: scale(1.1) translateY(20px);
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide transitions */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease-out;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(100%);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(-100%);
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-out;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-100%);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(100%);
}

/* Scale transitions */
.scale-enter-active,
.scale-leave-active {
  transition: all 0.2s ease-out;
}

.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.5);
}

/* Responsive */
@media (max-width: 640px) {
  .auth__modal-overlay {
    @apply p-2;
  }

  .auth__modal {
    @apply mx-2;
    max-width: calc(100vw - 1rem);
  }

  .auth__modal-header,
  .auth__modal-content,
  .auth__modal-footer {
    @apply p-4;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .auth__modal {
    @apply border-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .modal-enter-active,
  .modal-leave-active,
  .fade-enter-active,
  .fade-leave-active,
  .slide-up-enter-active,
  .slide-up-leave-active,
  .slide-down-enter-active,
  .slide-down-leave-active,
  .scale-enter-active,
  .scale-leave-active,
  .auth__modal--persistent {
    transition: none;
    animation: none;
  }
}
</style>