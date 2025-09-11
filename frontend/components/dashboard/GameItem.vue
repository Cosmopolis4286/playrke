<template>
  <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
    <!-- Game Info -->
    <div class="flex items-center gap-4">
      <div
        :class="[
          'w-12 h-12 rounded-lg flex items-center justify-center',
          getGameStyle(game.type).bg
        ]"
      >
        <component :is="getGameIcon(game.type)" :class="['w-6 h-6', getGameStyle(game.type).text]" />
      </div>
      
      <div>
        <h4 class="font-medium text-gray-900">{{ game.name }}</h4>
        <p class="text-sm text-gray-500">{{ game.time }}</p>
      </div>
    </div>
    
    <!-- Result -->
    <div class="text-right">
      <div class="flex items-center gap-2">
        <span
          :class="[
            'text-sm font-medium px-2 py-1 rounded-full',
            game.result === 'win'
              ? 'bg-green-100 text-green-700'
              : 'bg-red-100 text-red-700'
          ]"
        >
          {{ game.result === 'win' ? 'Ganó' : 'Perdió' }}
        </span>
      </div>
      <p
        :class="[
          'text-lg font-bold mt-1',
          game.result === 'win' ? 'text-green-600' : 'text-red-600'
        ]"
      >
        {{ game.result === 'win' ? '+' : '-' }}${{ formatNumber(game.amount) }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { TrophyIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline'

interface Game {
  id: string
  name: string
  result: 'win' | 'loss'
  amount: number
  time: string
  type: 'bingo' | 'casino' | 'sports'
}

interface Props {
  game: Game
}

defineProps<Props>()

const getGameIcon = (type: string) => {
  const icons = {
    bingo: TrophyIcon,
    casino: CurrencyDollarIcon,
    sports: TrophyIcon
  }
  return icons[type as keyof typeof icons] || TrophyIcon
}

const getGameStyle = (type: string) => {
  const styles = {
    bingo: {
      bg: 'bg-blue-100',
      text: 'text-blue-600'
    },
    casino: {
      bg: 'bg-purple-100',
      text: 'text-purple-600'
    },
    sports: {
      bg: 'bg-green-100',
      text: 'text-green-600'
    }
  }
  return styles[type as keyof typeof styles] || styles.bingo
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('es-CO').format(num)
}
</script>