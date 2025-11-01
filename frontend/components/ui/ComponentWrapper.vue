<template>
  <div class="component-wrapper">
    <!-- Simplified wrapper - no lazy loading for now -->
    <div
      v-if="isLoading"
      class="loading"
    >
      <LoadingSpinner />
      <p v-if="loadingText">
        {{ loadingText }}
      </p>
    </div>

    <div
      v-else-if="error"
      class="error"
    >
      <p>Error: {{ error.message }}</p>
      <button
        v-if="canRetry"
        @click="retry"
      >
        Retry
      </button>
    </div>

    <div
      v-else-if="component"
      class="content"
    >
      <component
        :is="component"
        v-bind="componentProps"
        v-on="componentEvents"
      />
    </div>

    <div
      v-else
      class="placeholder"
    >
      <p>{{ placeholderText }}</p>
      <button
        v-if="showLoadButton"
        @click="load"
      >
        {{ loadButtonText }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview ComponentWrapper - Simplified version without complex lazy loading
 * @description Basic wrapper that handles component loading states
 */

interface Props {
  loader?: () => Promise<any>
  componentProps?: Record<string, any>
  componentEvents?: Record<string, any>
  loadingText?: string
  placeholderText?: string
  loadButtonText?: string
  showLoadButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  componentProps: () => ({}),
  componentEvents: () => ({}),
  loadingText: 'Loading...',
  placeholderText: 'Click to load',
  loadButtonText: 'Load',
  showLoadButton: false,
})

const component = ref(null)
const isLoading = ref(false)
const error = ref<Error | null>(null)
const canRetry = ref(true)

const load = async () => {
  if (!props.loader) { return }

  isLoading.value = true
  error.value = null

  try {
    const result = await props.loader()
    component.value = result.default || result
  } catch (e) {
    error.value = e as Error
  } finally {
    isLoading.value = false
  }
}

const retry = () => {
  load()
}

// Auto load if loader is provided
if (props.loader) {
  load()
}
</script>

<style scoped>
.component-wrapper {
  @apply w-full;
}

.loading {
  @apply flex flex-col items-center justify-center p-4 text-secondary-600;
}

.error {
  @apply flex flex-col items-center justify-center p-4 text-red-600 bg-red-50 rounded-lg;
}

.placeholder {
  @apply flex flex-col items-center justify-center p-4 text-secondary-500 bg-secondary-50 rounded-lg;
}
</style>
