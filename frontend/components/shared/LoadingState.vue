<template>
  <div class="loading-state" :class="sizeClass">
    <div class="loading-content">
      <!-- Spinner -->
      <div class="spinner" :class="spinnerSizeClass">
        <div class="spinner-circle" />
      </div>

      <!-- Text -->
      <p v-if="text" class="loading-text">
        {{ text }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  text?: string
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
})

const sizeClass = computed(() => {
  const sizes = {
    sm: 'loading-sm',
    md: 'loading-md',
    lg: 'loading-lg',
  }
  return sizes[props.size]
})

const spinnerSizeClass = computed(() => {
  const sizes = {
    sm: 'spinner-sm',
    md: 'spinner-md',
    lg: 'spinner-lg',
  }
  return sizes[props.size]
})
</script>

<style scoped>
.loading-state {
  @apply flex items-center justify-center;
}

.loading-sm {
  @apply p-4;
}

.loading-md {
  @apply p-12;
}

.loading-lg {
  @apply p-16;
}

.loading-content {
  @apply text-center;
}

.spinner {
  @apply mx-auto mb-4;
  @apply relative;
}

.spinner-sm {
  @apply w-8 h-8;
}

.spinner-md {
  @apply w-12 h-12;
}

.spinner-lg {
  @apply w-16 h-16;
}

.spinner-circle {
  @apply w-full h-full;
  @apply border-4 border-gray-200 dark:border-gray-700;
  @apply border-t-primary-600;
  @apply rounded-full;
  @apply animate-spin;
}

.loading-text {
  @apply text-gray-600 dark:text-gray-400;
  @apply font-medium;
}
</style>
