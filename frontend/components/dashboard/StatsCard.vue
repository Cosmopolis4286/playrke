<template>
  <Card variant="elevated" hover class="group">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-500 mb-1">{{ title }}</p>
        <p class="text-2xl font-bold text-gray-900 mb-2">{{ value }}</p>
        
        <!-- Change Indicator -->
        <div v-if="change" class="flex items-center gap-1">
          <component
            :is="change.positive ? 'ArrowUpIcon' : 'ArrowDownIcon'"
            :class="[
              'w-4 h-4',
              change.positive ? 'text-green-500' : 'text-red-500'
            ]"
          />
          <span
            :class="[
              'text-sm font-medium',
              change.positive ? 'text-green-600' : 'text-red-600'
            ]"
          >
            {{ change.value }}
          </span>
          <span class="text-sm text-gray-400">vs mes anterior</span>
        </div>
      </div>
      
      <!-- Icon -->
      <div 
        :class="[
          'w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110',
          getColorClasses(color).bg
        ]"
      >
        <component :is="getIcon(icon)" :class="['w-6 h-6', getColorClasses(color).text]" />
      </div>
    </div>
    
    <!-- Progress Bar (optional) -->
    <div v-if="progress" class="mt-4">
      <div class="flex justify-between text-sm text-gray-500 mb-2">
        <span>Progreso</span>
        <span>{{ progress.value }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          :class="[
            'h-2 rounded-full transition-all duration-500',
            getColorClasses(color).progress
          ]"
          :style="{ width: `${progress.value}%` }"
        />
      </div>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { ArrowUpIcon, ArrowDownIcon, CurrencyDollarIcon, TrophyIcon, ClockIcon } from '@heroicons/vue/24/outline'
import Card from '../ui/Card.vue'

interface Props {
  title: string
  value: string
  change?: {
    value: string
    positive: boolean
  }
  progress?: {
    value: number
  }
  icon?: string
  color?: 'blue' | 'green' | 'purple' | 'orange' | 'red'
}

const props = withDefaults(defineProps<Props>(), {
  color: 'blue'
})

const getIcon = (iconName?: string) => {
  const icons: Record<string, any> = {
    CurrencyDollarIcon,
    TrophyIcon,
    ClockIcon
  }
  
  return icons[iconName || 'CurrencyDollarIcon'] || CurrencyDollarIcon
}

const getColorClasses = (color: string) => {
  const colors = {
    blue: {
      bg: 'bg-blue-100 group-hover:bg-blue-200',
      text: 'text-blue-600',
      progress: 'bg-gradient-to-r from-blue-500 to-blue-600'
    },
    green: {
      bg: 'bg-green-100 group-hover:bg-green-200',
      text: 'text-green-600',
      progress: 'bg-gradient-to-r from-green-500 to-green-600'
    },
    purple: {
      bg: 'bg-purple-100 group-hover:bg-purple-200',
      text: 'text-purple-600',
      progress: 'bg-gradient-to-r from-purple-500 to-purple-600'
    },
    orange: {
      bg: 'bg-orange-100 group-hover:bg-orange-200',
      text: 'text-orange-600',
      progress: 'bg-gradient-to-r from-orange-500 to-orange-600'
    },
    red: {
      bg: 'bg-red-100 group-hover:bg-red-200',
      text: 'text-red-600',
      progress: 'bg-gradient-to-r from-red-500 to-red-600'
    }
  }
  
  return colors[color as keyof typeof colors] || colors.blue
}
</script>