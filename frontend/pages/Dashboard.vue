<template>
  <AppLayout page-title="Dashboard" page-subtitle="Resumen de tu actividad en PlayRKE">
    <div class="space-y-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatsCard
          v-for="stat in stats"
          :key="stat.id"
          :title="stat.title"
          :value="stat.value"
          :change="stat.change"
          :icon="stat.icon"
          :color="stat.color"
          class="animate-slide-up"
          :style="{ animationDelay: `${stat.id * 100}ms` }"
        />
      </div>

      <!-- Quick Actions -->
      <Card variant="glass" class="animate-slide-up" style="animation-delay: 400ms">
        <template #header>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
              <BoltIcon class="w-6 h-6 text-white" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">Acciones Rápidas</h2>
              <p class="text-sm text-gray-500">Accede rápidamente a tus funciones favoritas</p>
            </div>
          </div>
        </template>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <QuickAction
            v-for="action in quickActions"
            :key="action.name"
            :action="action"
          />
        </div>
      </Card>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Games -->
        <Card variant="elevated" class="lg:col-span-2 animate-slide-up" style="animation-delay: 500ms">
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-teal-600 rounded-xl flex items-center justify-center">
                  <TrophyIcon class="w-6 h-6 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Juegos Recientes</h3>
                  <p class="text-sm text-gray-500">Tu actividad de gaming</p>
                </div>
              </div>
              <Button variant="outline" size="sm">
                Ver Todo
              </Button>
            </div>
          </template>

          <div class="space-y-4">
            <GameItem
              v-for="game in recentGames"
              :key="game.id"
              :game="game"
            />
          </div>
        </Card>

        <!-- Account Summary -->
        <Card variant="gradient" class="animate-slide-up" style="animation-delay: 600ms">
          <template #header>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                <CreditCardIcon class="w-6 h-6 text-white" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-white">Mi Cuenta</h3>
                <p class="text-sm text-white/80">Estado financiero</p>
              </div>
            </div>
          </template>

          <div class="space-y-6">
            <!-- Balance -->
            <div class="text-center">
              <p class="text-sm text-white/80 mb-2">Saldo Disponible</p>
              <p class="text-3xl font-bold text-white mb-4">
                ${{ formatNumber(user?.balance || 0) }}
              </p>
              <div class="flex gap-2">
                <Button variant="secondary" size="sm" class="flex-1">
                  Depositar
                </Button>
                <Button variant="outline" size="sm" class="flex-1 text-white border-white hover:bg-white hover:text-gray-900">
                  Retirar
                </Button>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/20">
              <div class="text-center">
                <p class="text-sm text-white/80">Ganado Hoy</p>
                <p class="text-lg font-bold text-green-300">
                  +${{ formatNumber(dailyWinnings) }}
                </p>
              </div>
              <div class="text-center">
                <p class="text-sm text-white/80">Jugado Hoy</p>
                <p class="text-lg font-bold text-white">
                  ${{ formatNumber(dailyPlayed) }}
                </p>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Activity Chart -->
      <Card variant="elevated" class="animate-slide-up" style="animation-delay: 700ms">
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                <ChartBarIcon class="w-6 h-6 text-white" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Actividad de los Últimos 7 Días</h3>
                <p class="text-sm text-gray-500">Ganancias y pérdidas por día</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="ghost" size="sm">7D</Button>
              <Button variant="outline" size="sm">30D</Button>
              <Button variant="ghost" size="sm">90D</Button>
            </div>
          </div>
        </template>

        <ActivityChart :data="chartData" />
      </Card>

      <!-- Notifications & Updates -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Active Promotions -->
        <Card variant="glass" class="animate-slide-up" style="animation-delay: 800ms">
          <template #header>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center">
                <GiftIcon class="w-6 h-6 text-white" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Promociones Activas</h3>
                <p class="text-sm text-gray-500">Ofertas especiales para ti</p>
              </div>
            </div>
          </template>

          <div class="space-y-4">
            <PromotionItem
              v-for="promotion in promotions"
              :key="promotion.id"
              :promotion="promotion"
            />
          </div>
        </Card>

        <!-- Recent Transactions -->
        <Card variant="outlined" class="animate-slide-up" style="animation-delay: 900ms">
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                  <ArrowsRightLeftIcon class="w-6 h-6 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Transacciones</h3>
                  <p class="text-sm text-gray-500">Últimos movimientos</p>
                </div>
              </div>
              <Button variant="outline" size="sm">
                Ver Todo
              </Button>
            </div>
          </template>

          <div class="space-y-3">
            <TransactionItem
              v-for="transaction in recentTransactions"
              :key="transaction.id"
              :transaction="transaction"
            />
          </div>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
  BoltIcon,
  TrophyIcon,
  CreditCardIcon,
  ChartBarIcon,
  GiftIcon,
  ArrowsRightLeftIcon
} from '@heroicons/vue/24/outline'

// Components
import AppLayout from '../layouts/AppLayout.vue'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import StatsCard from '../components/dashboard/StatsCard.vue'
import QuickAction from '../components/dashboard/QuickAction.vue'
import GameItem from '../components/dashboard/GameItem.vue'
import ActivityChart from '../components/dashboard/ActivityChart.vue'
import PromotionItem from '../components/dashboard/PromotionItem.vue'
import TransactionItem from '../components/dashboard/TransactionItem.vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

// Mock data
const stats = [
  {
    id: 1,
    title: 'Balance Total',
    value: '$125,450',
    change: { value: '+12.5%', positive: true },
    icon: 'CurrencyDollarIcon',
    color: 'blue'
  },
  {
    id: 2,
    title: 'Ganado Hoy',
    value: '$2,350',
    change: { value: '+18.2%', positive: true },
    icon: 'TrophyIcon',
    color: 'green'
  },
  {
    id: 3,
    title: 'Juegos Jugados',
    value: '47',
    change: { value: '+3', positive: true },
    icon: 'GamepadIcon',
    color: 'purple'
  },
  {
    id: 4,
    title: 'Tiempo de Juego',
    value: '5.4h',
    change: { value: '-0.5h', positive: false },
    icon: 'ClockIcon',
    color: 'orange'
  }
]

const quickActions = [
  { name: 'Jugar Bingo', icon: 'GamepadIcon', href: '/games/bingo', color: 'blue' },
  { name: 'Depositar', icon: 'PlusIcon', href: '/deposit', color: 'green' },
  { name: 'Ver Historial', icon: 'ChartBarIcon', href: '/history', color: 'purple' },
  { name: 'Soporte', icon: 'QuestionMarkCircleIcon', href: '/support', color: 'orange' }
]

const recentGames = ref([
  {
    id: '1',
    name: 'Bingo Premium',
    result: 'win',
    amount: 2500,
    time: '2 min ago',
    type: 'bingo'
  },
  {
    id: '2',
    name: 'Casino Royale',
    result: 'loss',
    amount: 1200,
    time: '15 min ago',
    type: 'casino'
  },
  {
    id: '3',
    name: 'Super Bingo',
    result: 'win',
    amount: 850,
    time: '1 hour ago',
    type: 'bingo'
  }
])

const promotions = ref([
  {
    id: '1',
    title: 'Bono de Bienvenida',
    description: 'Recibe 100% extra en tu primer depósito',
    expires: '2 días',
    type: 'bonus'
  },
  {
    id: '2',
    title: 'Cashback Semanal',
    description: '10% de cashback en todas tus pérdidas',
    expires: '5 días',
    type: 'cashback'
  }
])

const recentTransactions = ref([
  {
    id: '1',
    type: 'deposit',
    amount: 50000,
    status: 'completed',
    time: '10:30 AM',
    method: 'Tarjeta'
  },
  {
    id: '2',
    type: 'win',
    amount: 2500,
    status: 'completed',
    time: '09:15 AM',
    method: 'Bingo'
  },
  {
    id: '3',
    type: 'withdraw',
    amount: 25000,
    status: 'pending',
    time: 'Yesterday',
    method: 'Banco'
  }
])

const dailyWinnings = ref(2350)
const dailyPlayed = ref(5200)

const chartData = ref([
  { day: 'Lun', wins: 1200, losses: 800 },
  { day: 'Mar', wins: 2100, losses: 1200 },
  { day: 'Mié', wins: 800, losses: 1500 },
  { day: 'Jue', wins: 2800, losses: 900 },
  { day: 'Vie', wins: 1900, losses: 1100 },
  { day: 'Sáb', wins: 3200, losses: 1400 },
  { day: 'Dom', wins: 2350, losses: 1800 }
])

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('es-CO').format(num)
}
</script>

<style scoped>
@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slide-up {
  animation: slide-up 0.6s ease-out forwards;
  opacity: 0;
}
</style>