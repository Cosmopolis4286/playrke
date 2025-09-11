<template>
  <div class="h-64">
    <div class="flex items-end justify-between h-full p-4 space-x-2">
      <div
        v-for="(item, index) in data"
        :key="index"
        class="flex flex-col items-center flex-1 h-full"
      >
        <!-- Chart Bars Container -->
        <div class="flex flex-col items-center justify-end h-full w-full max-w-[40px] space-y-1">
          <!-- Wins Bar -->
          <div
            class="w-full bg-gradient-to-t from-green-500 to-green-400 rounded-t-lg transition-all duration-1000 hover:shadow-lg relative group"
            :style="{
              height: `${(item.wins / maxValue) * 70}%`,
              minHeight: '4px',
              animationDelay: `${index * 100}ms`
            }"
          >
            <!-- Tooltip -->
            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
              Ganó: ${{ formatNumber(item.wins) }}
            </div>
          </div>
          
          <!-- Losses Bar -->
          <div
            class="w-full bg-gradient-to-t from-red-500 to-red-400 rounded-b-lg transition-all duration-1000 hover:shadow-lg relative group"
            :style="{
              height: `${(item.losses / maxValue) * 70}%`,
              minHeight: '4px',
              animationDelay: `${index * 100 + 50}ms`
            }"
          >
            <!-- Tooltip -->
            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
              Perdió: ${{ formatNumber(item.losses) }}
            </div>
          </div>
        </div>
        
        <!-- Day Label -->
        <span class="text-xs text-gray-600 mt-2 font-medium">{{ item.day }}</span>
      </div>
    </div>
    
    <!-- Legend -->
    <div class="flex justify-center items-center gap-6 mt-4 pt-4 border-t border-gray-200">
      <div class="flex items-center gap-2">
        <div class="w-3 h-3 bg-gradient-to-r from-green-500 to-green-400 rounded"></div>
        <span class="text-sm text-gray-600">Ganancias</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-3 h-3 bg-gradient-to-r from-red-500 to-red-400 rounded"></div>
        <span class="text-sm text-gray-600">Pérdidas</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface ChartData {
  day: string
  wins: number
  losses: number
}

interface Props {
  data: ChartData[]
}

const props = defineProps<Props>()

const maxValue = computed(() => {
  const allValues = props.data.flatMap(item => [item.wins, item.losses])
  return Math.max(...allValues)
})

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('es-CO').format(num)
}
</script>

<style scoped>
@keyframes slideUp {
  from {
    height: 0;
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-bar {
  animation: slideUp 0.8s ease-out forwards;
}
</style>