<template>
  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
    <!-- Transaction Info -->
    <div class="flex items-center gap-3">
      <div
        :class="[
          'w-10 h-10 rounded-lg flex items-center justify-center',
          getTransactionStyle(transaction.type).bg
        ]"
      >
        <component 
          :is="getTransactionIcon(transaction.type)" 
          :class="['w-5 h-5', getTransactionStyle(transaction.type).text]" 
        />
      </div>
      
      <div>
        <h4 class="text-sm font-medium text-gray-900">
          {{ getTransactionTitle(transaction.type) }}
        </h4>
        <div class="flex items-center gap-2 text-xs text-gray-500">
          <span>{{ transaction.method }}</span>
          <span>•</span>
          <span>{{ transaction.time }}</span>
        </div>
      </div>
    </div>
    
    <!-- Amount & Status -->
    <div class="text-right">
      <div class="flex items-center gap-2 justify-end mb-1">
        <span
          :class="[
            'text-sm font-medium',
            transaction.type === 'deposit' || transaction.type === 'win'
              ? 'text-green-600'
              : 'text-red-600'
          ]"
        >
          {{ getAmountPrefix(transaction.type) }}${{ formatNumber(transaction.amount) }}
        </span>
      </div>
      
      <!-- Status Badge -->
      <span
        :class="[
          'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
          getStatusStyle(transaction.status)
        ]"
      >
        <div
          :class="[
            'w-1.5 h-1.5 rounded-full mr-1.5',
            getStatusDotColor(transaction.status)
          ]"
        />
        {{ getStatusText(transaction.status) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { 
  ArrowDownIcon, 
  ArrowUpIcon, 
  TrophyIcon,
  CreditCardIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

interface Transaction {
  id: string
  type: 'deposit' | 'withdraw' | 'win' | 'loss'
  amount: number
  status: 'completed' | 'pending' | 'failed'
  time: string
  method: string
}

interface Props {
  transaction: Transaction
}

defineProps<Props>()

const getTransactionIcon = (type: string) => {
  const icons = {
    deposit: ArrowDownIcon,
    withdraw: ArrowUpIcon,
    win: TrophyIcon,
    loss: CreditCardIcon
  }
  return icons[type as keyof typeof icons] || CreditCardIcon
}

const getTransactionStyle = (type: string) => {
  const styles = {
    deposit: {
      bg: 'bg-green-100',
      text: 'text-green-600'
    },
    withdraw: {
      bg: 'bg-blue-100',
      text: 'text-blue-600'
    },
    win: {
      bg: 'bg-yellow-100',
      text: 'text-yellow-600'
    },
    loss: {
      bg: 'bg-red-100',
      text: 'text-red-600'
    }
  }
  return styles[type as keyof typeof styles] || styles.deposit
}

const getTransactionTitle = (type: string) => {
  const titles = {
    deposit: 'Depósito',
    withdraw: 'Retiro',
    win: 'Ganancia',
    loss: 'Pérdida'
  }
  return titles[type as keyof typeof titles] || 'Transacción'
}

const getAmountPrefix = (type: string) => {
  return ['deposit', 'win'].includes(type) ? '+' : '-'
}

const getStatusStyle = (status: string) => {
  const styles = {
    completed: 'bg-green-100 text-green-700',
    pending: 'bg-yellow-100 text-yellow-700',
    failed: 'bg-red-100 text-red-700'
  }
  return styles[status as keyof typeof styles] || styles.pending
}

const getStatusDotColor = (status: string) => {
  const colors = {
    completed: 'bg-green-500',
    pending: 'bg-yellow-500',
    failed: 'bg-red-500'
  }
  return colors[status as keyof typeof colors] || colors.pending
}

const getStatusText = (status: string) => {
  const texts = {
    completed: 'Completado',
    pending: 'Pendiente',
    failed: 'Fallido'
  }
  return texts[status as keyof typeof texts] || 'Desconocido'
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('es-CO').format(num)
}
</script>