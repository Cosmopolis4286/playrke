<template>
  <div class="empty-state" :class="sizeClass">
    <div class="empty-state-content">
      <!-- Icon -->
      <div class="empty-state-icon" :class="iconColorClass">
        <slot name="icon">
          <svg
            v-if="icon === 'ticket'"
            class="icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
            />
          </svg>
          <svg
            v-else-if="icon === 'calendar'"
            class="icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <svg
            v-else-if="icon === 'activity'"
            class="icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 10V3L4 14h7v7l9-11h-7z"
            />
          </svg>
          <svg
            v-else-if="icon === 'search'"
            class="icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          <svg
            v-else
            class="icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
            />
          </svg>
        </slot>
      </div>

      <!-- Title -->
      <h3 class="empty-state-title">
        {{ title }}
      </h3>

      <!-- Description -->
      <p v-if="description" class="empty-state-description">
        {{ description }}
      </p>

      <!-- Action Button -->
      <div v-if="$slots.action || actionText" class="empty-state-action">
        <slot name="action">
          <button
            v-if="actionText"
            class="action-button"
            type="button"
            @click.stop="handleAction"
          >
            {{ actionText }}
          </button>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  icon?: 'ticket' | 'calendar' | 'activity' | 'search' | 'box'
  title: string
  description?: string
  actionText?: string
  size?: 'sm' | 'md' | 'lg'
  iconColor?: 'blue' | 'green' | 'purple' | 'gray'
}

const props = withDefaults(defineProps<Props>(), {
  icon: 'box',
  size: 'md',
  iconColor: 'gray',
})

const emit = defineEmits<{
  action: []
}>()

const handleAction = () => {
  console.log('EmptyState: Button clicked!')
  emit('action')
}

const sizeClass = computed(() => {
  const sizes = {
    sm: 'empty-state-sm',
    md: 'empty-state-md',
    lg: 'empty-state-lg',
  }
  return sizes[props.size]
})

const iconColorClass = computed(() => {
  const colors = {
    blue: 'icon-blue',
    green: 'icon-green',
    purple: 'icon-purple',
    gray: 'icon-gray',
  }
  return colors[props.iconColor]
})
</script>

<style scoped>
.empty-state {
  @apply flex items-center justify-center;
  @apply bg-white dark:bg-gray-800;
  @apply rounded-xl border border-gray-200 dark:border-gray-700;
  @apply transition-all duration-200;
}

.empty-state-sm {
  @apply p-6;
}

.empty-state-md {
  @apply p-12;
}

.empty-state-lg {
  @apply p-16;
}

.empty-state-content {
  @apply text-center max-w-md;
  position: relative;
  z-index: 10;
}

.empty-state-icon {
  @apply w-16 h-16 mx-auto mb-4;
  @apply rounded-full;
  @apply flex items-center justify-center;
  @apply transition-transform duration-300;
}

.empty-state:hover .empty-state-icon {
  @apply scale-110;
}

.icon {
  @apply w-8 h-8;
}

.icon-blue {
  @apply bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400;
}

.icon-green {
  @apply bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400;
}

.icon-purple {
  @apply bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400;
}

.icon-gray {
  @apply bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400;
}

.empty-state-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white mb-2;
}

.empty-state-description {
  @apply text-gray-600 dark:text-gray-400 mb-6;
}

.empty-state-action {
  @apply flex justify-center gap-3;
}

.action-button {
  @apply px-6 py-2.5 rounded-lg font-medium;
  @apply bg-primary-600 hover:bg-primary-700;
  @apply text-white;
  @apply transition-colors duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
  @apply cursor-pointer;
  position: relative;
  z-index: 10;
}

.action-button:hover {
  @apply transform scale-105;
}
</style>
