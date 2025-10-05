<template>
  <div>
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Reportes y Estadísticas</h1>
      <p class="text-slate-600 dark:text-slate-400 mt-1">
        Análisis detallado del sistema y métricas de rendimiento
      </p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Tipo de Reporte
          </label>
          <select
            v-model="filters.reportType"
            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadReport"
          >
            <option value="revenue">Ingresos</option>
            <option value="players">Jugadores</option>
            <option value="games">Juegos</option>
            <option value="transactions">Transacciones</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Período
          </label>
          <select
            v-model="filters.period"
            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadReport"
          >
            <option value="today">Hoy</option>
            <option value="week">Esta Semana</option>
            <option value="month">Este Mes</option>
            <option value="year">Este Año</option>
            <option value="custom">Personalizado</option>
          </select>
        </div>

        <div v-if="filters.period === 'custom'">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Fecha Inicio
          </label>
          <input
            v-model="filters.startDate"
            type="date"
            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadReport"
          />
        </div>

        <div v-if="filters.period === 'custom'">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Fecha Fin
          </label>
          <input
            v-model="filters.endDate"
            type="date"
            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadReport"
          />
        </div>

        <div class="flex items-end">
          <button
            @click="exportReport"
            class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exportar
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium opacity-90">Ingresos Totales</h3>
          <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-3xl font-bold mb-1">{{ formatCurrency(reportData.totalRevenue) }}</p>
        <p class="text-sm opacity-75">
          <span :class="reportData.revenueChange >= 0 ? 'text-green-300' : 'text-red-300'">
            {{ reportData.revenueChange >= 0 ? '+' : '' }}{{ reportData.revenueChange }}%
          </span>
          vs período anterior
        </p>
      </div>

      <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium opacity-90">Jugadores Activos</h3>
          <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <p class="text-3xl font-bold mb-1">{{ formatNumber(reportData.activePlayers) }}</p>
        <p class="text-sm opacity-75">
          <span :class="reportData.playersChange >= 0 ? 'text-green-300' : 'text-red-300'">
            {{ reportData.playersChange >= 0 ? '+' : '' }}{{ reportData.playersChange }}%
          </span>
          vs período anterior
        </p>
      </div>

      <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium opacity-90">Juegos Jugados</h3>
          <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        </div>
        <p class="text-3xl font-bold mb-1">{{ formatNumber(reportData.totalGames) }}</p>
        <p class="text-sm opacity-75">
          <span :class="reportData.gamesChange >= 0 ? 'text-green-300' : 'text-red-300'">
            {{ reportData.gamesChange >= 0 ? '+' : '' }}{{ reportData.gamesChange }}%
          </span>
          vs período anterior
        </p>
      </div>

      <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium opacity-90">Transacciones</h3>
          <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
        </div>
        <p class="text-3xl font-bold mb-1">{{ formatNumber(reportData.totalTransactions) }}</p>
        <p class="text-sm opacity-75">
          Volumen: {{ formatCurrency(reportData.transactionVolume) }}
        </p>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Revenue Chart -->
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
          Ingresos por Día
        </h3>
        <div class="h-64 flex items-end justify-between gap-2">
          <div
            v-for="(value, index) in reportData.revenueChart"
            :key="index"
            class="flex-1 bg-blue-500 rounded-t hover:bg-blue-600 transition-colors cursor-pointer relative group"
            :style="{ height: `${(value / Math.max(...reportData.revenueChart)) * 100}%` }"
          >
            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
              {{ formatCurrency(value) }}
            </div>
          </div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-slate-500 dark:text-slate-400">
          <span v-for="day in reportData.chartLabels" :key="day">{{ day }}</span>
        </div>
      </div>

      <!-- Players Chart -->
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
          Jugadores Activos por Día
        </h3>
        <div class="h-64 flex items-end justify-between gap-2">
          <div
            v-for="(value, index) in reportData.playersChart"
            :key="index"
            class="flex-1 bg-green-500 rounded-t hover:bg-green-600 transition-colors cursor-pointer relative group"
            :style="{ height: `${(value / Math.max(...reportData.playersChart)) * 100}%` }"
          >
            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
              {{ value }}
            </div>
          </div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-slate-500 dark:text-slate-400">
          <span v-for="day in reportData.chartLabels" :key="day">{{ day }}</span>
        </div>
      </div>
    </div>

    <!-- Top Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Players -->
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
            Top Jugadores
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Jugador</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Juegos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Ganado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr
                v-for="(player, index) in reportData.topPlayers"
                :key="player.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/50"
              >
                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">{{ index + 1 }}</td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ player.name }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">{{ player.games }}</td>
                <td class="px-6 py-4 text-sm text-green-600 dark:text-green-400 font-semibold">
                  {{ formatCurrency(player.winnings) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Rooms -->
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
            Salas Más Populares
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Sala</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Juegos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Ingresos</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr
                v-for="(room, index) in reportData.topRooms"
                :key="room.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/50"
              >
                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">{{ index + 1 }}</td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ room.name }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">{{ room.games }}</td>
                <td class="px-6 py-4 text-sm text-blue-600 dark:text-blue-400 font-semibold">
                  {{ formatCurrency(room.revenue) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatCurrency, formatNumber } from '~/layers/shared/utils/formatters'

definePageMeta({
  middleware: ['auth'],
  layout: 'admin'
})

const { getReports } = useAdminApi()
const uiStore = useUIStore()

// Estados
const filters = ref({
  reportType: 'revenue',
  period: 'month',
  startDate: '',
  endDate: ''
})

const reportData = ref({
  totalRevenue: 125430,
  revenueChange: 12.5,
  activePlayers: 3456,
  playersChange: 8.3,
  totalGames: 12890,
  gamesChange: 15.7,
  totalTransactions: 8934,
  transactionVolume: 234500,
  revenueChart: [12000, 15000, 13500, 18000, 16500, 21000, 19500],
  playersChart: [320, 450, 380, 520, 490, 610, 580],
  chartLabels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
  topPlayers: [
    { id: '1', name: 'Juan Pérez', games: 145, winnings: 12500 },
    { id: '2', name: 'María García', games: 132, winnings: 10800 },
    { id: '3', name: 'Carlos López', games: 128, winnings: 9500 },
    { id: '4', name: 'Ana Martínez', games: 115, winnings: 8900 },
    { id: '5', name: 'Pedro Rodríguez', games: 98, winnings: 7600 }
  ],
  topRooms: [
    { id: '1', name: 'Sala VIP Premium', games: 456, revenue: 45600 },
    { id: '2', name: 'Sala Rápida', games: 789, revenue: 39450 },
    { id: '3', name: 'Sala Clásica', games: 567, revenue: 28350 },
    { id: '4', name: 'Mega Jackpot', games: 234, revenue: 23400 },
    { id: '5', name: 'Sala Nocturna', games: 345, revenue: 17250 }
  ]
})

// Métodos
const loadReport = async () => {
  try {
    const response = await getReports({
      type: filters.value.reportType,
      period: filters.value.period,
      start_date: filters.value.startDate,
      end_date: filters.value.endDate
    })

    // Actualizar con datos reales del backend
    // reportData.value = response
    console.log('Report loaded:', response)
  } catch (error) {
    uiStore.notifyError('Error cargando reporte')
  }
}

const exportReport = () => {
  // Implementar exportación a CSV/Excel
  uiStore.notifySuccess('Exportando reporte...')

  // Simular descarga
  const csv = `Tipo,Valor\nIngresos,${reportData.value.totalRevenue}\nJugadores,${reportData.value.activePlayers}\nJuegos,${reportData.value.totalGames}`
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `reporte-${filters.value.reportType}-${Date.now()}.csv`
  link.click()
}

// Cargar al montar
onMounted(() => {
  loadReport()
})
</script>
