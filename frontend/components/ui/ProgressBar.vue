<template>
  <div>
    <!-- Label -->
    <div v-if="label || showValue" class="flex justify-between items-center mb-2">
      <span v-if="label" class="text-sm font-medium text-gray-700">
        {{ label }}
      </span>
      <span v-if="showValue" class="text-sm text-gray-500">
        {{ displayValue }}
      </span>
    </div>

    <!-- Progress Track -->
    <div
      :class="[
        'relative overflow-hidden rounded-full bg-gray-200 transition-all duration-300',
        getHeightClass()
      ]"
    >
      <!-- Progress Fill -->
      <div
        :class="[
          'h-full transition-all duration-700 ease-out rounded-full',
          getColorClasses(),
          animated ? 'animate-pulse' : ''
        ]"
        :style="{ 
          width: `${animatedValue}%`,
          transform: striped ? 'translateX(0)' : 'none'
        }"
      >
        <!-- Stripes -->
        <div
          v-if="striped"
          class="absolute inset-0 bg-gradient-to-r from-transparent via-white via-transparent opacity-20 animate-slide"
          style="background-size: 20px 20px;"
        />
        
        <!-- Glow Effect -->
        <div
          v-if="glowing"
          class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-50 blur-sm"
          :style="{ width: '30%', left: `${animatedValue * 0.7}%` }"
        />
      </div>

      <!-- Indeterminate Animation -->
      <div
        v-if="indeterminate"
        class="absolute top-0 left-0 h-full w-full bg-gradient-to-r from-transparent via-current to-transparent opacity-70 animate-slide-infinite"
        :class="getColorClasses()"
      />

      <!-- Text Overlay -->
      <div
        v-if="showPercentage && !indeterminate"
        class="absolute inset-0 flex items-center justify-center text-xs font-medium"
        :class="animatedValue > 50 ? 'text-white' : 'text-gray-700'"
      >
        {{ Math.round(animatedValue) }}%
      </div>
    </div>

    <!-- Steps (for stepped progress) -->
    <div v-if="steps && steps > 1" class="flex justify-between mt-2">
      <div
        v-for="step in steps"
        :key="step"
        class="flex flex-col items-center"
      >
        <div
          :class="[
            'w-3 h-3 rounded-full border-2 transition-all duration-300',
            getCurrentStep() >= step
              ? 'bg-current border-current ' + getColorClasses()
              : 'bg-white border-gray-300'
          ]"
        />
        <span
          v-if="stepLabels && stepLabels[step - 1]"
          class="text-xs mt-1 text-gray-500"
        >
          {{ stepLabels[step - 1] }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface Props {
  value: number
  max?: number
  min?: number
  label?: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  color?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger'
  striped?: boolean
  animated?: boolean
  glowing?: boolean
  indeterminate?: boolean
  showValue?: boolean
  showPercentage?: boolean
  steps?: number
  stepLabels?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  max: 100,
  min: 0,
  size: 'md',
  color: 'primary',
  striped: false,
  animated: false,
  glowing: false,
  indeterminate: false,
  showValue: false,
  showPercentage: false
})

const animatedValue = ref(0)

// Computed values
const percentage = computed(() => {
  const range = props.max - props.min
  return Math.min(100, Math.max(0, ((props.value - props.min) / range) * 100))
})

const displayValue = computed(() => {
  if (props.indeterminate) return 'Cargando...'
  return props.showPercentage ? `${Math.round(percentage.value)}%` : `${props.value}/${props.max}`
})

const getCurrentStep = () => {
  if (!props.steps) return 0
  return Math.ceil((percentage.value / 100) * props.steps)
}

const getHeightClass = () => {
  const heights = {
    xs: 'h-1',
    sm: 'h-2',
    md: 'h-3',
    lg: 'h-4',
    xl: 'h-6'
  }
  return heights[props.size]
}

const getColorClasses = () => {
  const colors = {
    primary: 'bg-gradient-to-r from-blue-500 to-purple-600',
    secondary: 'bg-gradient-to-r from-gray-400 to-gray-600',
    success: 'bg-gradient-to-r from-green-500 to-green-600',
    warning: 'bg-gradient-to-r from-yellow-500 to-orange-500',
    danger: 'bg-gradient-to-r from-red-500 to-red-600'
  }
  return colors[props.color]
}

// Animate progress on mount and value change
const animateProgress = () => {
  const targetValue = props.indeterminate ? 100 : percentage.value
  const duration = 1000
  const startTime = Date.now()
  const startValue = animatedValue.value

  const animate = () => {
    const elapsed = Date.now() - startTime
    const progress = Math.min(elapsed / duration, 1)
    
    // Easing function
    const eased = 1 - Math.pow(1 - progress, 3)
    animatedValue.value = startValue + (targetValue - startValue) * eased

    if (progress < 1) {
      requestAnimationFrame(animate)
    }
  }

  requestAnimationFrame(animate)
}

// Watch for value changes
watch(() => props.value, animateProgress)
watch(() => props.indeterminate, animateProgress)

onMounted(() => {
  if (!props.indeterminate) {
    animateProgress()
  }
})
</script>

<style scoped>
@keyframes slide {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

@keyframes slide-infinite {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(200%);
  }
}

.animate-slide {
  animation: slide 2s ease-in-out infinite;
}

.animate-slide-infinite {
  animation: slide-infinite 1.5s ease-in-out infinite;
}

/* Striped pattern */
.bg-striped {
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
  background-size: 20px 20px;
}

.bg-striped.animate-slide {
  animation: slide-stripes 1s linear infinite;
}

@keyframes slide-stripes {
  0% {
    background-position: 0 0;
  }
  100% {
    background-position: 20px 0;
  }
}
</style>