<template>
  <div class="level-progress-card bg-white rounded-lg shadow-md p-6 max-w-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Progreso de Nivel</h3>
      <div class="level-badge bg-gradient-to-r from-blue-500 to-purple-600 text-white px-3 py-1 rounded-full text-sm font-bold">
        Nivel {{ level }}
      </div>
    </div>
    
    <div class="xp-info mb-4">
      <div class="current-xp text-2xl font-bold text-gray-900">
        {{ currentXp }} XP
      </div>
      <div class="xp-needed text-sm text-gray-600">
        {{ xpNeeded }} XP para el siguiente nivel
      </div>
    </div>
    
    <div class="progress-section mb-4">
      <ProgressBar
        :value="progressPercentage"
        :max="100"
        color="primary"
        size="medium"
        :animated="isAnimating"
        class="mb-2"
      />
      
      <div class="flex justify-between text-xs text-gray-500">
        <span>{{ currentXp }} XP</span>
        <span>{{ xpToNextLevel }} XP</span>
      </div>
    </div>
    
    <div v-if="showDetails" class="level-details border-t pt-4">
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-gray-500">Total XP</div>
          <div class="font-semibold text-gray-800">{{ totalXp }}</div>
        </div>
        <div>
          <div class="text-gray-500">Siguiente nivel</div>
          <div class="font-semibold text-gray-800">{{ nextLevel }}</div>
        </div>
      </div>
    </div>
    
    <div v-if="leveledUp" class="level-up-animation">
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">¡Felicidades!</strong>
        <span class="block sm:inline"> Has subido al nivel {{ level }}.</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import ProgressBar from '../ui/feedback/ProgressBar.vue'

interface Props {
  level?: number
  currentXp?: number
  totalXp?: number
  xpToNextLevel?: number
  showDetails?: boolean
  leveledUp?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  level: 1,
  currentXp: 0,
  totalXp: 0,
  xpToNextLevel: 1000,
  showDetails: false,
  leveledUp: false,
})

const isAnimating = ref(false)
const previousLevel = ref(props.level)

const progressPercentage = computed(() => {
  if (props.xpToNextLevel === 0) return 100
  return Math.min(100, (props.currentXp / props.xpToNextLevel) * 100)
})

const xpNeeded = computed(() => {
  return Math.max(0, props.xpToNextLevel - props.currentXp)
})

const nextLevel = computed(() => props.level + 1)

watch(() => props.level, (newLevel, oldLevel) => {
  if (newLevel > oldLevel) {
    isAnimating.value = true
    setTimeout(() => {
      isAnimating.value = false
    }, 2000)
  }
})

const triggerLevelUpAnimation = () => {
  isAnimating.value = true
  setTimeout(() => {
    isAnimating.value = false
  }, 2000)
}

defineExpose({
  triggerLevelUpAnimation
})
</script>

<style scoped>
.level-progress-card {
  transition: all 0.3s ease;
}

.level-progress-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.level-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
  }
}

.level-up-animation {
  animation: slideInUp 0.5s ease-out;
}

@keyframes slideInUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.current-xp {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
</style>
