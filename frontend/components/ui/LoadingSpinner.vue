<template>
  <div :class="containerClasses">
    <!-- Spinner -->
    <div
      v-if="variant === 'spinner'"
      :class="spinnerClasses"
    >
      <div :class="spinnerInnerClasses"></div>
    </div>
    
    <!-- Dots -->
    <div
      v-else-if="variant === 'dots'"
      class="flex space-x-2"
    >
      <div
        v-for="i in 3"
        :key="i"
        :class="[
          'rounded-full animate-bounce',
          getSizeClasses().dot,
          getColorClasses().dot
        ]"
        :style="{ animationDelay: `${(i - 1) * 0.2}s` }"
      />
    </div>
    
    <!-- Pulse -->
    <div
      v-else-if="variant === 'pulse'"
      :class="[
        'rounded-full animate-ping',
        getSizeClasses().pulse,
        getColorClasses().pulse
      ]"
    />
    
    <!-- Bars -->
    <div
      v-else-if="variant === 'bars'"
      class="flex items-end space-x-1"
    >
      <div
        v-for="i in 4"
        :key="i"
        :class="[
          'animate-pulse rounded-full',
          getSizeClasses().bar,
          getColorClasses().bar
        ]"
        :style="{ animationDelay: `${(i - 1) * 0.15}s`, animationDuration: '1s' }"
      />
    </div>
    
    <!-- Ring -->
    <div
      v-else-if="variant === 'ring'"
      :class="ringClasses"
    >
      <div></div>
      <div></div>
      <div></div>
      <div></div>
    </div>
    
    <!-- Text -->
    <p
      v-if="text"
      :class="[
        'mt-3 text-center font-medium animate-pulse',
        getSizeClasses().text,
        getColorClasses().text
      ]"
    >
      {{ text }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'spinner' | 'dots' | 'pulse' | 'bars' | 'ring'
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  color?: 'primary' | 'secondary' | 'white' | 'gray'
  text?: string
  fullscreen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'spinner',
  size: 'md',
  color: 'primary',
  fullscreen: false
})

const containerClasses = computed(() => {
  const base = ['flex flex-col items-center justify-center']
  
  if (props.fullscreen) {
    base.push('fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50')
  }
  
  return base
})

const spinnerClasses = computed(() => {
  return [
    'animate-spin rounded-full border-solid',
    getSizeClasses().spinner,
    getColorClasses().spinner
  ]
})

const spinnerInnerClasses = computed(() => {
  return [
    'rounded-full',
    getSizeClasses().spinnerInner,
    getColorClasses().spinnerInner
  ]
})

const ringClasses = computed(() => {
  return [
    'relative inline-flex',
    getSizeClasses().ring
  ]
})

const getSizeClasses = () => {
  const sizes = {
    xs: {
      spinner: 'w-4 h-4 border-2',
      spinnerInner: 'w-2 h-2',
      dot: 'w-1 h-1',
      pulse: 'w-3 h-3',
      bar: 'w-1 h-2',
      ring: 'w-4 h-4',
      text: 'text-xs'
    },
    sm: {
      spinner: 'w-6 h-6 border-2',
      spinnerInner: 'w-3 h-3',
      dot: 'w-1.5 h-1.5',
      pulse: 'w-4 h-4',
      bar: 'w-1 h-3',
      ring: 'w-6 h-6',
      text: 'text-sm'
    },
    md: {
      spinner: 'w-8 h-8 border-3',
      spinnerInner: 'w-4 h-4',
      dot: 'w-2 h-2',
      pulse: 'w-6 h-6',
      bar: 'w-1.5 h-4',
      ring: 'w-8 h-8',
      text: 'text-base'
    },
    lg: {
      spinner: 'w-12 h-12 border-4',
      spinnerInner: 'w-6 h-6',
      dot: 'w-3 h-3',
      pulse: 'w-8 h-8',
      bar: 'w-2 h-6',
      ring: 'w-12 h-12',
      text: 'text-lg'
    },
    xl: {
      spinner: 'w-16 h-16 border-4',
      spinnerInner: 'w-8 h-8',
      dot: 'w-4 h-4',
      pulse: 'w-12 h-12',
      bar: 'w-2 h-8',
      ring: 'w-16 h-16',
      text: 'text-xl'
    }
  }
  
  return sizes[props.size]
}

const getColorClasses = () => {
  const colors = {
    primary: {
      spinner: 'border-blue-200 border-t-blue-600',
      spinnerInner: 'bg-blue-600',
      dot: 'bg-blue-600',
      pulse: 'bg-blue-600',
      bar: 'bg-blue-600',
      text: 'text-blue-600'
    },
    secondary: {
      spinner: 'border-gray-200 border-t-gray-600',
      spinnerInner: 'bg-gray-600',
      dot: 'bg-gray-600',
      pulse: 'bg-gray-600',
      bar: 'bg-gray-600',
      text: 'text-gray-600'
    },
    white: {
      spinner: 'border-white border-opacity-30 border-t-white',
      spinnerInner: 'bg-white',
      dot: 'bg-white',
      pulse: 'bg-white',
      bar: 'bg-white',
      text: 'text-white'
    },
    gray: {
      spinner: 'border-gray-300 border-t-gray-700',
      spinnerInner: 'bg-gray-700',
      dot: 'bg-gray-700',
      pulse: 'bg-gray-700',
      bar: 'bg-gray-700',
      text: 'text-gray-700'
    }
  }
  
  return colors[props.color]
}
</script>

<style scoped>
@keyframes pulse-bars {
  0%, 40%, 100% {
    transform: scaleY(0.4);
  }
  20% {
    transform: scaleY(1);
  }
}

.animate-pulse-bars {
  animation: pulse-bars 1.2s infinite ease-in-out;
}

/* Ring loader styles */
.ring-loader > div {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: ring-spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
}

.ring-loader > div:nth-child(1) {
  animation-delay: -0.45s;
}

.ring-loader > div:nth-child(2) {
  animation-delay: -0.3s;
}

.ring-loader > div:nth-child(3) {
  animation-delay: -0.15s;
}

@keyframes ring-spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.border-3 {
  border-width: 3px;
}
</style>