<template>
  <div :class="['progress-bar-wrapper', `size-${size}`]">
    <div class="progress-bar-track">
      <div
        :class="['progress-bar-fill', `color-${color}`, { animated }]"
        :style="{ width: `${percentage}%` }"
        role="progressbar"
        :aria-valuenow="value"
        :aria-valuemin="0"
        :aria-valuemax="max"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  value?: number
  max?: number
  color?: 'primary' | 'success' | 'warning' | 'danger'
  size?: 'small' | 'medium' | 'large'
  animated?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  value: 0,
  max: 100,
  color: 'primary',
  size: 'medium',
  animated: false,
})

const percentage = computed(() =>
  props.max > 0 ? Math.min(100, Math.max(0, (props.value / props.max) * 100)) : 0
)
</script>

<style scoped>
.progress-bar-track {
  width: 100%;
  background-color: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
}

.size-small .progress-bar-track { height: 4px; }
.size-medium .progress-bar-track { height: 8px; }
.size-large .progress-bar-track { height: 12px; }

.progress-bar-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.4s ease;
}

.color-primary { background: linear-gradient(90deg, #667eea, #764ba2); }
.color-success { background-color: #10b981; }
.color-warning { background-color: #f59e0b; }
.color-danger  { background-color: #ef4444; }

.animated {
  background-size: 200% 100%;
  animation: progress-shimmer 1.5s infinite linear;
}

@keyframes progress-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
