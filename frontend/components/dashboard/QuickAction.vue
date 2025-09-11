<template>
  <a
    :href="action.href"
    class="group flex flex-col items-center p-6 bg-white border border-gray-200 rounded-xl hover:border-gray-300 hover:shadow-md transition-all duration-200 hover:-translate-y-1"
  >
    <!-- Icon -->
    <div
      :class="[
        'w-12 h-12 rounded-xl flex items-center justify-center mb-3 transition-all duration-200 group-hover:scale-110',
        getColorClasses(action.color).bg,
        'group-hover:shadow-lg'
      ]"
    >
      <component :is="getIcon(action.icon)" :class="['w-6 h-6', getColorClasses(action.color).text]" />
    </div>
    
    <!-- Label -->
    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 text-center">
      {{ action.name }}
    </span>
    
    <!-- Badge -->
    <span
      v-if="action.badge"
      class="mt-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full"
    >
      {{ action.badge }}
    </span>
  </a>
</template>

<script setup lang="ts">
import { 
  PlusIcon,
  ChartBarIcon,
  QuestionMarkCircleIcon,
  CurrencyDollarIcon,
  GameController2Icon
} from '@heroicons/vue/24/outline'

interface QuickAction {
  name: string
  icon: string
  href: string
  color: 'blue' | 'green' | 'purple' | 'orange' | 'red'
  badge?: string
}

interface Props {
  action: QuickAction
}

defineProps<Props>()

const getIcon = (iconName: string) => {
  const icons: Record<string, any> = {
    PlusIcon,
    ChartBarIcon,
    QuestionMarkCircleIcon,
    CurrencyDollarIcon,
    GameController2Icon,
    GamepadIcon: GameController2Icon
  }
  
  return icons[iconName] || PlusIcon
}

const getColorClasses = (color: string) => {
  const colors = {
    blue: {
      bg: 'bg-blue-100',
      text: 'text-blue-600'
    },
    green: {
      bg: 'bg-green-100',
      text: 'text-green-600'
    },
    purple: {
      bg: 'bg-purple-100',
      text: 'text-purple-600'
    },
    orange: {
      bg: 'bg-orange-100',
      text: 'text-orange-600'
    },
    red: {
      bg: 'bg-red-100',
      text: 'text-red-600'
    }
  }
  
  return colors[color as keyof typeof colors] || colors.blue
}
</script>