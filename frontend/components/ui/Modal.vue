<template>
  <!-- Backdrop -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div 
          class="fixed inset-0 bg-black/50 backdrop-blur-sm"
          aria-hidden="true"
        />

        <!-- Modal Container -->
        <div class="relative min-h-full flex items-center justify-center p-4">
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
          >
            <div
              v-if="show"
              :class="modalClasses"
              class="relative bg-white rounded-2xl shadow-2xl overflow-hidden"
              @click.stop
            >
              <!-- Header -->
              <div
                v-if="$slots.header || title || closable"
                class="flex items-center justify-between p-6 border-b border-gray-200/80 bg-gray-50/50"
              >
                <div class="flex-1">
                  <slot name="header">
                    <div v-if="title">
                      <h2 class="text-xl font-semibold text-gray-900">
                        {{ title }}
                      </h2>
                      <p v-if="subtitle" class="mt-1 text-sm text-gray-500">
                        {{ subtitle }}
                      </p>
                    </div>
                  </slot>
                </div>

                <!-- Close Button -->
                <button
                  v-if="closable"
                  type="button"
                  class="ml-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                  @click="close"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Body -->
              <div :class="bodyClasses">
                <slot />
              </div>

              <!-- Footer -->
              <div
                v-if="$slots.footer"
                class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50/50 border-t border-gray-200/80"
              >
                <slot name="footer" />
              </div>

              <!-- Loading Overlay -->
              <div
                v-if="loading"
                class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-10"
              >
                <div class="flex flex-col items-center gap-3">
                  <div class="w-8 h-8 border-3 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                  <p v-if="loadingText" class="text-sm text-gray-600">
                    {{ loadingText }}
                  </p>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, watch, nextTick } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

interface Props {
  show: boolean
  title?: string
  subtitle?: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full'
  closable?: boolean
  closeOnBackdrop?: boolean
  loading?: boolean
  loadingText?: string
  persistent?: boolean
  fullscreen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  closable: true,
  closeOnBackdrop: true,
  loading: false,
  persistent: false,
  fullscreen: false
})

const emit = defineEmits<{
  close: []
  open: []
  'update:show': [value: boolean]
}>()

const modalClasses = computed(() => {
  if (props.fullscreen) {
    return 'w-full h-full max-w-none max-h-none rounded-none'
  }

  const sizes = {
    xs: 'w-full max-w-sm',
    sm: 'w-full max-w-md',
    md: 'w-full max-w-lg',
    lg: 'w-full max-w-2xl',
    xl: 'w-full max-w-4xl',
    full: 'w-full max-w-7xl'
  }

  return [
    sizes[props.size],
    'max-h-[90vh]'
  ].join(' ')
})

const bodyClasses = computed(() => {
  if (props.fullscreen) {
    return 'flex-1 overflow-auto'
  }

  return 'p-6 overflow-y-auto max-h-[60vh]'
})

const close = () => {
  if (props.persistent || props.loading) return
  
  emit('close')
  emit('update:show', false)
}

const handleBackdropClick = () => {
  if (props.closeOnBackdrop) {
    close()
  }
}

// Handle escape key
const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.show && props.closable) {
    close()
  }
}

// Body scroll lock
const lockBodyScroll = () => {
  document.body.style.overflow = 'hidden'
}

const unlockBodyScroll = () => {
  document.body.style.overflow = ''
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    emit('open')
    nextTick(() => {
      lockBodyScroll()
      document.addEventListener('keydown', handleKeyDown)
    })
  } else {
    unlockBodyScroll()
    document.removeEventListener('keydown', handleKeyDown)
  }
}, { immediate: true })

// Cleanup on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
  unlockBodyScroll()
  document.removeEventListener('keydown', handleKeyDown)
})
</script>

<style scoped>
.border-3 {
  border-width: 3px;
}
</style>