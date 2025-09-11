<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[9999] space-y-2">
      <TransitionGroup
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-full scale-95"
        enter-to-class="opacity-100 translate-x-0 scale-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-x-0 scale-100"
        leave-to-class="opacity-0 translate-x-full scale-95"
        move-class="transition-transform duration-200"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'flex items-start max-w-sm w-full p-4 rounded-xl shadow-lg backdrop-blur-sm border',
            'transform-gpu will-change-transform',
            getToastClasses(toast.type)
          ]"
          @click="removeToast(toast.id)"
        >
          <!-- Icon -->
          <div class="flex-shrink-0">
            <component 
              :is="getToastIcon(toast.type)" 
              :class="['w-6 h-6', getIconClasses(toast.type)]"
            />
          </div>
          
          <!-- Content -->
          <div class="ml-3 flex-1">
            <h4 
              v-if="toast.title" 
              :class="['text-sm font-medium', getTitleClasses(toast.type)]"
            >
              {{ toast.title }}
            </h4>
            <p :class="['text-sm', getMessageClasses(toast.type)]">
              {{ toast.message }}
            </p>
          </div>
          
          <!-- Close Button -->
          <button
            v-if="toast.closable !== false"
            @click.stop="removeToast(toast.id)"
            :class="[
              'ml-4 flex-shrink-0 p-1 rounded-lg transition-colors',
              getCloseButtonClasses(toast.type)
            ]"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
          
          <!-- Progress Bar -->
          <div
            v-if="toast.duration"
            class="absolute bottom-0 left-0 h-1 bg-current opacity-30 rounded-b-xl transition-all duration-100 ease-linear"
            :style="{ width: `${toast.progress}%` }"
          />
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { XMarkIcon, CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

interface Toast {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title?: string
  message: string
  duration?: number
  closable?: boolean
  progress?: number
}

const toasts = ref<Toast[]>([])

const addToast = (toast: Omit<Toast, 'id'>) => {
  const id = Math.random().toString(36).substr(2, 9)
  const newToast: Toast = {
    id,
    closable: true,
    duration: 5000,
    progress: 100,
    ...toast
  }
  
  toasts.value.push(newToast)
  
  if (newToast.duration) {
    // Progress animation
    let progress = 100
    const interval = setInterval(() => {
      progress -= 100 / (newToast.duration! / 100)
      newToast.progress = Math.max(0, progress)
      
      if (progress <= 0) {
        clearInterval(interval)
        removeToast(id)
      }
    }, 100)
  }
}

const removeToast = (id: string) => {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    toasts.value.splice(index, 1)
  }
}

const getToastIcon = (type: string) => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[type as keyof typeof icons]
}

const getToastClasses = (type: string) => {
  const classes = {
    success: 'bg-green-50/90 border-green-200 text-green-900',
    error: 'bg-red-50/90 border-red-200 text-red-900',
    warning: 'bg-yellow-50/90 border-yellow-200 text-yellow-900',
    info: 'bg-blue-50/90 border-blue-200 text-blue-900'
  }
  return classes[type as keyof typeof classes]
}

const getIconClasses = (type: string) => {
  const classes = {
    success: 'text-green-500',
    error: 'text-red-500',
    warning: 'text-yellow-500',
    info: 'text-blue-500'
  }
  return classes[type as keyof typeof classes]
}

const getTitleClasses = (type: string) => {
  const classes = {
    success: 'text-green-900',
    error: 'text-red-900',
    warning: 'text-yellow-900',
    info: 'text-blue-900'
  }
  return classes[type as keyof typeof classes]
}

const getMessageClasses = (type: string) => {
  const classes = {
    success: 'text-green-700',
    error: 'text-red-700',
    warning: 'text-yellow-700',
    info: 'text-blue-700'
  }
  return classes[type as keyof typeof classes]
}

const getCloseButtonClasses = (type: string) => {
  const classes = {
    success: 'hover:bg-green-100 text-green-500',
    error: 'hover:bg-red-100 text-red-500',
    warning: 'hover:bg-yellow-100 text-yellow-500',
    info: 'hover:bg-blue-100 text-blue-500'
  }
  return classes[type as keyof typeof classes]
}

// Global toast methods
const toast = {
  success: (message: string, title?: string, options?: Partial<Toast>) =>
    addToast({ type: 'success', message, title, ...options }),
  error: (message: string, title?: string, options?: Partial<Toast>) =>
    addToast({ type: 'error', message, title, ...options }),
  warning: (message: string, title?: string, options?: Partial<Toast>) =>
    addToast({ type: 'warning', message, title, ...options }),
  info: (message: string, title?: string, options?: Partial<Toast>) =>
    addToast({ type: 'info', message, title, ...options })
}

// Provide toast methods globally
import { provide } from 'vue'
provide('toast', toast)

// Export for external use
defineExpose({
  addToast,
  removeToast,
  toast
})
</script>

<style scoped>
.transform-gpu {
  transform: translateZ(0);
}
</style>