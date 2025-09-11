<template>
  <div class="relative overflow-hidden bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-4 hover:shadow-md transition-all duration-200 group">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
      <div class="absolute inset-0 bg-gradient-to-br from-yellow-400 to-orange-500"></div>
    </div>
    
    <div class="relative">
      <div class="flex items-start justify-between">
        <!-- Content -->
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-2">
            <GiftIcon class="w-5 h-5 text-yellow-600" />
            <span class="text-xs font-medium text-yellow-700 bg-yellow-100 px-2 py-1 rounded-full">
              {{ getPromotionLabel(promotion.type) }}
            </span>
          </div>
          
          <h4 class="font-semibold text-gray-900 mb-1">{{ promotion.title }}</h4>
          <p class="text-sm text-gray-600 mb-3">{{ promotion.description }}</p>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-500">
              <ClockIcon class="w-4 h-4" />
              <span>Expira en {{ promotion.expires }}</span>
            </div>
            
            <Button
              size="sm"
              variant="accent"
              class="group-hover:scale-105 transition-transform"
            >
              Reclamar
            </Button>
          </div>
        </div>
        
        <!-- Icon -->
        <div class="ml-4">
          <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
            <component :is="getPromotionIcon(promotion.type)" class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>
      
      <!-- Progress Bar (if applicable) -->
      <div v-if="promotion.progress" class="mt-4">
        <div class="flex justify-between text-xs text-gray-500 mb-2">
          <span>Progreso</span>
          <span>{{ promotion.progress.current }}/{{ promotion.progress.total }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-gradient-to-r from-yellow-400 to-orange-500 h-2 rounded-full transition-all duration-500"
            :style="{ width: `${(promotion.progress.current / promotion.progress.total) * 100}%` }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { GiftIcon, ClockIcon, CurrencyDollarIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'

interface Promotion {
  id: string
  title: string
  description: string
  expires: string
  type: 'bonus' | 'cashback' | 'tournament' | 'loyalty'
  progress?: {
    current: number
    total: number
  }
}

interface Props {
  promotion: Promotion
}

defineProps<Props>()

const getPromotionIcon = (type: string) => {
  const icons = {
    bonus: GiftIcon,
    cashback: ArrowPathIcon,
    tournament: CurrencyDollarIcon,
    loyalty: GiftIcon
  }
  return icons[type as keyof typeof icons] || GiftIcon
}

const getPromotionLabel = (type: string) => {
  const labels = {
    bonus: 'BONO',
    cashback: 'CASHBACK',
    tournament: 'TORNEO',
    loyalty: 'FIDELIDAD'
  }
  return labels[type as keyof typeof labels] || 'PROMOCIÓN'
}
</script>