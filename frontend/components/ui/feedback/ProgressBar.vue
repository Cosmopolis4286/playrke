<template>
  <div
    class="progress-bar-container"
    :class="containerClass"
  >
    <div
      class="progress-bar-track"
      :style="{ backgroundColor: trackColor }"
    >
      <div
        class="progress-bar-fill"
        :class="fillClass"
        :style="fillStyle"
      >
        <div
          v-if="showLabel"
          class="progress-bar-label"
        >
          {{ displayValue }}
        </div>
      </div>
    </div>
    <div
      v-if="showPercentage"
      class="progress-bar-percentage"
    >
      {{ Math.round(value) }}%
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  value: number
  max?: number
  min?: number
  color?: 'primary' | 'success' | 'warning' | 'danger' | 'info'
  size?: 'small' | 'medium' | 'large'
  showLabel?: boolean
  showPercentage?: boolean
  label?: string
  trackColor?: string
  animated?: boolean
  striped?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  max: 100,
  min: 0,
  color: 'primary',
  size: 'medium',
  showLabel: false,
  showPercentage: false,
  trackColor: '#e5e7eb',
  animated: false,
  striped: false,
})

const normalizedValue = computed(() => {
  const { value, min, max } = props
  return Math.min(Math.max(((value - min) / (max - min)) * 100, 0), 100)
})

const displayValue = computed(() => props.label || `${Math.round(normalizedValue.value)}%`)

const containerClass = computed(() => [
  `progress-bar--${props.size}`,
  {
    'progress-bar--animated': props.animated,
    'progress-bar--striped': props.striped,
  },
])

const fillClass = computed(() => [
  `progress-bar-fill--${props.color}`,
  {
    'progress-bar-fill--animated': props.animated,
    'progress-bar-fill--striped': props.striped,
  },
])

const fillStyle = computed(() => ({
  width: `${normalizedValue.value}%`,
}))
</script>

<style scoped>
.progress-bar-container {
  @apply w-full;
}

.progress-bar-track {
  @apply w-full rounded-full overflow-hidden;
  min-height: 8px;
}

.progress-bar-fill {
  @apply h-full transition-all duration-300 ease-out relative;
  min-width: 0;
}

/* Sizes */
.progress-bar--small .progress-bar-track {
  min-height: 4px;
}

.progress-bar--medium .progress-bar-track {
  min-height: 8px;
}

.progress-bar--large .progress-bar-track {
  min-height: 12px;
}

/* Colors */
.progress-bar-fill--primary {
  @apply bg-blue-500;
}

.progress-bar-fill--success {
  @apply bg-green-500;
}

.progress-bar-fill--warning {
  @apply bg-yellow-500;
}

.progress-bar-fill--danger {
  @apply bg-red-500;
}

.progress-bar-fill--info {
  @apply bg-cyan-500;
}

/* Label */
.progress-bar-label {
  @apply absolute inset-0 flex items-center justify-center text-xs font-medium text-white;
}

/* Percentage */
.progress-bar-percentage {
  @apply text-sm text-gray-600 mt-1;
}

/* Animated */
.progress-bar-fill--animated {
  animation: progress-animation 2s ease-in-out infinite;
}

/* Striped */
.progress-bar-fill--striped {
  background-image: linear-gradient(
    45deg,
    rgba(255, 255, 255, 0.15) 25%,
    transparent 25%,
    transparent 50%,
    rgba(255, 255, 255, 0.15) 50%,
    rgba(255, 255, 255, 0.15) 75%,
    transparent 75%,
    transparent
  );
  background-size: 1rem 1rem;
}

.progress-bar-fill--striped.progress-bar-fill--animated {
  animation: progress-stripes 1s linear infinite;
}

@keyframes progress-animation {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
  100% {
    opacity: 1;
  }
}

@keyframes progress-stripes {
  0% {
    background-position: 1rem 0;
  }
  100% {
    background-position: 0 0;
  }
}
</style>
