<script setup lang="ts">
  import { computed } from 'vue'

  type AlertType = 'info' | 'success' | 'warning' | 'error'

  const props = defineProps<{
    type?: AlertType
  }>()

  const alertClasses = computed(() => {
    switch (props.type) {
      case 'success':
        return {
          wrapper: 'border-green-200 bg-green-50 text-green-800',
          icon: 'text-green-500'
        }
      case 'warning':
        return {
          wrapper: 'border-yellow-200 bg-yellow-50 text-yellow-800',
          icon: 'text-yellow-500'
        }
      case 'error':
        return {
          wrapper: 'border-red-200 bg-red-50 text-red-800',
          icon: 'text-red-500'
        }
      default:
        return {
          wrapper: 'border-blue-200 bg-blue-50 text-blue-800',
          icon: 'text-blue-500'
        }
    }
  })
</script>

<template>
  <div class="mb-4 rounded-lg border p-4" :class="alertClasses.wrapper" role="alert">
    <div class="flex items-start gap-2">
      <!-- Icon -->
      <svg
        class="mt-0.5 h-5 w-5"
        :class="alertClasses.icon"
        fill="currentColor"
        viewBox="0 0 20 20"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-8-4a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v5a1 1 0 102 0V9z"
          clip-rule="evenodd"
        />
      </svg>

      <!-- Content -->
      <div class="text-sm leading-relaxed">
        <slot />
      </div>
    </div>
  </div>
</template>
