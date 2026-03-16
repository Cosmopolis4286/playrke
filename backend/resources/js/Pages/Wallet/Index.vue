<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useWallet } from '@js/composables/wallet/useWallet'
import type { WalletPageProps, WalletMovementType } from '@js/types/wallet'

const props = defineProps<WalletPageProps>()

const {
  wallets,
  activeWallet,
  movements,
  movementsMeta,
  movementsLoading,
  modalOpen,
  operation,
  amount,
  loading,
  errorMsg,
  successMsg,
  canWithdraw,
  formattedBalance,
  openModal,
  closeModal,
  selectWallet,
  loadMoreMovements,
  submit,
} = useWallet(props.wallets)

// Estado para animaciones y microinteracciones
const isAnimating = ref(false)
const quickAmounts = [10, 25, 50, 100, 200, 500]
const selectedQuickAmount = ref<number | null>(null)

// ─── helpers visuales mejorados para movimientos ───────────────────────────────
const movementMeta: Record<WalletMovementType, { 
  icon: string; 
  label: string; 
  colorClass: string; 
  bgClass: string; 
  sign: '+' | '-';
  gradient: string;
  description: string;
}> = {
  DEPOSIT:   { 
    icon: 'fas fa-arrow-down',  
    label: 'Depósito',         
    colorClass: 'text-emerald-600', 
    bgClass: 'bg-emerald-50', 
    sign: '+',
    gradient: 'from-emerald-400 to-green-500',
    description: 'Fondos añadidos a tu cuenta'
  },
  PRIZE:     { 
    icon: 'fas fa-trophy',      
    label: 'Premio ganado',     
    colorClass: 'text-amber-600',   
    bgClass: 'bg-amber-50',   
    sign: '+',
    gradient: 'from-amber-400 to-yellow-500',
    description: 'Premio por juego ganado'
  },
  PURCHASE:  { 
    icon: 'fas fa-ticket-alt',  
    label: 'Compra de cartón',  
    colorClass: 'text-rose-600',    
    bgClass: 'bg-rose-50',    
    sign: '-',
    gradient: 'from-rose-400 to-pink-500',
    description: 'Cartón de bingo adquirido'
  },
  WITHDRAWAL:{ 
    icon: 'fas fa-arrow-up',    
    label: 'Retiro',            
    colorClass: 'text-blue-600',    
    bgClass: 'bg-blue-50',    
    sign: '-',
    gradient: 'from-blue-400 to-indigo-500',
    description: 'Fondos retirados de tu cuenta'
  },
}

// Formato de fecha mejorado
function formatDate(iso: string): string {
  const date = new Date(iso)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)
  
  if (diffInHours < 1) {
    return 'Hace unos minutos'
  } else if (diffInHours < 24) {
    return `Hoy, ${date.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' })}`
  } else if (diffInHours < 48) {
    return `Ayer, ${date.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' })}`
  } else {
    return new Intl.DateTimeFormat('es-VE', { 
      day: '2-digit', 
      month: 'short', 
      hour: '2-digit', 
      minute: '2-digit' 
    }).format(date)
  }
}

const hasMoreMovements = () =>
  movementsMeta.value !== null &&
  movementsMeta.value.current_page < movementsMeta.value.last_page

// Estadísticas calculadas
const totalDeposits = computed(() => 
  movements.value
    .filter(m => m.type === 'DEPOSIT')
    .reduce((sum, m) => sum + m.amount, 0)
)

const totalWithdrawals = computed(() => 
  movements.value
    .filter(m => m.type === 'WITHDRAWAL')
    .reduce((sum, m) => sum + m.amount, 0)
)

const totalPrizes = computed(() => 
  movements.value
    .filter(m => m.type === 'PRIZE')
    .reduce((sum, m) => sum + m.amount, 0)
)

// Manejo de montos rápidos
function selectQuickAmount(value: number) {
  selectedQuickAmount.value = value
  amount.value = value.toString()
  isAnimating.value = true
  setTimeout(() => isAnimating.value = false, 300)
}

function clearQuickAmount() {
  selectedQuickAmount.value = null
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <div class="mx-auto px-4 py-6" style="max-width: 1200px">

      <!-- ── Encabezado mejorado ─────────────────────────────────────────── -->
      <header class="mb-8">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
              <i class="fas fa-wallet text-white text-lg"></i>
            </div>
            <div>
              <h1 class="text-3xl font-bold text-gray-900 mb-1">Mi Billetera</h1>
              <p class="text-gray-600">Gestiona tus fondos y revisa tu actividad</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Última actualización</p>
            <p class="text-sm font-medium text-gray-700">{{ formatDate(new Date().toISOString()) }}</p>
          </div>
        </div>
      </header>

      <!-- ── Sin billeteras ─────────────────────────────────────── -->
      <div v-if="!wallets.length" class="flex items-center justify-center min-h-[400px]">
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 py-20 px-8 text-center max-w-md">
          <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-wallet text-3xl text-gray-400"></i>
          </div>
          <h2 class="text-xl font-bold text-gray-800 mb-3">Sin billeteras activas</h2>
          <p class="text-gray-600 mb-6">Contacta al soporte para activar tu billetera y comenzar a gestionar tus fondos.</p>
          <button class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all transform hover:scale-105">
            <i class="fas fa-headset mr-2"></i>
            Contactar soporte
          </button>
        </div>
      </div>

      <template v-else>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Columna izquierda: Tarjeta principal y acciones -->
          <div class="lg:col-span-2 space-y-6">
            <!-- ── Selector de billetera (si hay varias) ──────────── -->
            <div v-if="wallets.length > 1" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Selecciona tu billetera</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="wallet in wallets"
                  :key="wallet.id"
                  type="button"
                  class="px-4 py-2 rounded-xl text-sm font-medium border transition-all transform hover:scale-105"
                  :class="activeWallet?.id === wallet.id
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white border-transparent shadow-lg'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300 hover:shadow-md'"
                  @click="selectWallet(wallet)"
                >
                  <i class="fas fa-coins mr-2"></i>
                  {{ wallet.currency.symbol }} {{ wallet.currency.iso_code }}
                </button>
              </div>
            </div>

            <!-- ── Tarjeta de saldo mejorada ───────────────────────────────── -->
            <div
              class="rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden"
              style="background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%)"
            >
              <!-- Decoración de fondo animada -->
              <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full opacity-20 bg-white animate-pulse"></div>
              <div class="absolute -bottom-16 -left-8 w-64 h-64 rounded-full opacity-20 bg-white animate-pulse" style="animation-delay: 1s"></div>
              <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 rounded-full opacity-10 bg-white animate-ping" style="animation-duration: 3s"></div>

              <div class="relative z-10">
                <div class="flex items-start justify-between mb-6">
                  <div class="flex-1">
                    <p class="text-sm font-medium opacity-80 mb-2 uppercase tracking-wider">Saldo disponible</p>
                    <p class="text-5xl font-black mb-2">
                      {{ activeWallet?.currency.symbol }}&nbsp;{{ formattedBalance }}
                    </p>
                    <p class="text-lg opacity-90 font-medium">{{ activeWallet?.currency.name }}</p>
                  </div>
                  <div class="w-16 h-16 rounded-2xl bg-white/25 backdrop-blur-sm flex items-center justify-center shadow-lg border border-white/20">
                    <i class="fas fa-coins text-2xl"></i>
                  </div>
                </div>

                <!-- Status badge mejorado -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <span
                      class="inline-flex items-center gap-2 text-sm font-bold px-4 py-2 rounded-full backdrop-blur-sm"
                      :class="{
                        'bg-emerald-400/30 text-emerald-100 border border-emerald-400/50': activeWallet?.status === 'ACTIVE',
                        'bg-yellow-400/30 text-yellow-100 border border-yellow-400/50':   activeWallet?.status === 'INACTIVE',
                        'bg-red-400/30 text-red-100 border border-red-400/50':         activeWallet?.status === 'BLOCKED',
                      }"
                    >
                      <span
                        class="w-2 h-2 rounded-full animate-pulse"
                        :class="{
                          'bg-emerald-300': activeWallet?.status === 'ACTIVE',
                          'bg-yellow-300':  activeWallet?.status === 'INACTIVE',
                          'bg-red-300':     activeWallet?.status === 'BLOCKED',
                        }"
                      ></span>
                      {{ activeWallet?.status === 'ACTIVE' ? 'Activa' :
                         activeWallet?.status === 'INACTIVE' ? 'Inactiva' : 'Bloqueada' }}
                    </span>
                  </div>
                  <div class="text-right">
                    <p class="text-xs opacity-70">ID: {{ activeWallet?.id.slice(-8) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Acciones mejoradas ───────────────────────────────────────── -->
            <div class="grid grid-cols-2 gap-4">
              <button
                type="button"
                class="group relative overflow-hidden bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white rounded-2xl p-6 font-bold shadow-lg transition-all transform hover:scale-105 hover:shadow-2xl"
                @click="openModal('deposit')"
              >
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-green-700 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10 flex flex-col items-center gap-3">
                  <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-arrow-down text-xl"></i>
                  </div>
                  <span class="text-lg">Depositar</span>
                  <span class="text-xs opacity-80">Añadir fondos</span>
                </div>
              </button>
              <button
                type="button"
                class="group relative overflow-hidden rounded-2xl p-6 font-bold shadow-lg transition-all transform hover:scale-105 hover:shadow-2xl"
                :class="canWithdraw
                  ? 'bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                :disabled="!canWithdraw"
                @click="openModal('withdraw')"
              >
                <div 
                  v-if="canWithdraw"
                  class="absolute inset-0 bg-gradient-to-r from-amber-600 to-orange-700 opacity-0 group-hover:opacity-100 transition-opacity"
                ></div>
                <div class="relative z-10 flex flex-col items-center gap-3">
                  <div class="w-12 h-12 rounded-full flex items-center justify-center backdrop-blur-sm" :class="canWithdraw ? 'bg-white/20' : 'bg-gray-200'">
                    <i class="fas fa-arrow-up text-xl"></i>
                  </div>
                  <span class="text-lg">Retirar</span>
                  <span class="text-xs" :class="canWithdraw ? 'opacity-80' : 'opacity-50'">
                    {{ canWithdraw ? 'Retirar fondos' : 'Sin saldo' }}
                  </span>
                </div>
              </button>
            </div>
          </div>

          <!-- Columna derecha: Estadísticas y resumen -->
          <div class="space-y-6">
            <!-- ── Estadísticas rápidas ───────────────────────────────── -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-line text-indigo-500"></i>
                Resumen de actividad
              </h3>
              <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-xl">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center">
                      <i class="fas fa-arrow-down text-white"></i>
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-gray-800">Depósitos</p>
                      <p class="text-xs text-gray-500">Total recibido</p>
                    </div>
                  </div>
                  <p class="text-lg font-bold text-emerald-600">
                    {{ activeWallet?.currency.symbol }} {{ 
                      new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                        maximumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                      }).format(totalDeposits)
                    }}
                  </p>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-amber-50 rounded-xl">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center">
                      <i class="fas fa-trophy text-white"></i>
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-gray-800">Premios</p>
                      <p class="text-xs text-gray-500">Ganancias</p>
                    </div>
                  </div>
                  <p class="text-lg font-bold text-amber-600">
                    {{ activeWallet?.currency.symbol }} {{ 
                      new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                        maximumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                      }).format(totalPrizes)
                    }}
                  </p>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-xl">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                      <i class="fas fa-arrow-up text-white"></i>
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-gray-800">Retiros</p>
                      <p class="text-xs text-gray-500">Total retirado</p>
                    </div>
                  </div>
                  <p class="text-lg font-bold text-blue-600">
                    {{ activeWallet?.currency.symbol }} {{ 
                      new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                        maximumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                      }).format(totalWithdrawals)
                    }}
                  </p>
                </div>
              </div>
            </div>

            <!-- ── Tipos de movimiento mejorados ─────────────────────── -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-500"></i>
                Tipos de movimiento
              </h3>
              <div class="space-y-3">
                <div
                  v-for="(meta, key) in movementMeta"
                  :key="key"
                  class="flex items-center gap-4 p-3 rounded-xl transition-all hover:shadow-md"
                  :class="meta.bgClass"
                >
                  <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-white shadow-sm">
                    <i :class="[meta.icon, meta.colorClass, 'text-lg']"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-bold text-gray-800">{{ meta.label }}</p>
                    <p class="text-xs text-gray-600">{{ meta.description }}</p>
                  </div>
                  <div class="text-right">
                    <span 
                      class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold"
                      :class="meta.sign === '+' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"
                    >
                      {{ meta.sign }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- ── Historial de movimientos mejorado ───────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-blue-50">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                  <i class="fas fa-history text-indigo-600"></i>
                </div>
                Movimientos recientes
              </h3>
              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 bg-white px-3 py-1 rounded-full border border-gray-200">
                  {{ movements.length }} movimientos
                </span>
              </div>
            </div>
          </div>

          <!-- Estado de carga mejorado -->
          <div v-if="movementsLoading && !movements.length" class="py-16 flex flex-col items-center justify-center">
            <div class="relative">
              <div class="w-16 h-16 border-4 border-indigo-200 rounded-full animate-spin border-t-indigo-600"></div>
              <div class="absolute inset-0 flex items-center justify-center">
                <i class="fas fa-coins text-indigo-600 text-lg"></i>
              </div>
            </div>
            <p class="mt-4 text-gray-600 font-medium">Cargando movimientos...</p>
          </div>

          <!-- Sin movimientos mejorado -->
          <div v-else-if="!movements.length" class="py-16 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
              <i class="fas fa-receipt text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Sin movimientos</h3>
            <p class="text-gray-500 mb-1">Aún no hay movimientos registrados en esta billetera.</p>
            <p class="text-sm text-gray-400">Los depósitos, retiros, premios y compras aparecerán aquí.</p>
          </div>

          <!-- Lista de movimientos mejorada -->
          <template v-else>
            <ul class="divide-y divide-gray-100">
              <li
                v-for="(mov, index) in movements"
                :key="mov.id"
                class="group flex items-center gap-4 px-6 py-4 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-200"
                :class="{ 'border-l-4 border-indigo-500': index === 0 }"
              >
                <!-- Icono mejorado -->
                <div
                  class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm border border-white/50"
                  :class="movementMeta[mov.type].bgClass"
                >
                  <i :class="[movementMeta[mov.type].icon, movementMeta[mov.type].colorClass, 'text-lg']"></i>
                </div>

                <!-- Descripción mejorada -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <p class="text-sm font-bold text-gray-800 truncate">{{ movementMeta[mov.type].label }}</p>
                    <span 
                      v-if="index === 0"
                      class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full font-semibold"
                    >
                      Más reciente
                    </span>
                  </div>
                  <p class="text-xs text-gray-500">
                    {{ mov.description ?? movementMeta[mov.type].description }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                    <i class="fas fa-clock"></i>
                    {{ formatDate(mov.created_at) }}
                  </p>
                </div>

                <!-- Monto mejorado -->
                <div class="text-right flex-shrink-0">
                  <p
                    class="text-lg font-bold"
                    :class="movementMeta[mov.type].sign === '+' ? 'text-emerald-600' : 'text-rose-500'"
                  >
                    {{ movementMeta[mov.type].sign }}{{ activeWallet?.currency.symbol }}&nbsp;{{
                      new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                        maximumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                      }).format(mov.amount)
                    }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1">
                    Saldo: {{ activeWallet?.currency.symbol }}&nbsp;{{
                      new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                        maximumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                      }).format(mov.balance_after)
                    }}
                  </p>
                </div>
              </li>
            </ul>

            <!-- Cargar más mejorado -->
            <div v-if="hasMoreMovements() || movementsLoading" class="px-6 py-5 border-t border-gray-100 text-center bg-gradient-to-r from-gray-50 to-blue-50">
              <button
                type="button"
                class="group inline-flex items-center gap-2 px-6 py-3 bg-white border border-indigo-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-300 font-medium rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-md"
                :disabled="movementsLoading"
                @click="loadMoreMovements"
              >
                <span v-if="movementsLoading" class="flex items-center gap-2">
                  <div class="w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                  Cargando más movimientos...
                </span>
                <span v-else class="flex items-center gap-2">
                  Ver más movimientos
                  <i class="fas fa-arrow-down group-hover:translate-y-0.5 transition-transform"></i>
                </span>
              </button>
            </div>
          </template>
        </div>

      </template>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL DEPÓSITO / RETIRO MEJORADO
         ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="modalOpen"
          class="fixed inset-0 z-[200] flex items-center justify-center p-4"
          @click.self="closeModal"
        >
          <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="closeModal" />

          <Transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
          >
            <div
              v-if="modalOpen"
              class="relative bg-white rounded-3xl shadow-2xl w-full overflow-hidden border border-gray-100"
              style="max-width: 480px"
              @click.stop
            >
              <!-- Header mejorado -->
              <div
                class="relative px-6 py-6"
                :class="operation === 'deposit'
                  ? 'bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100 border-b border-emerald-200'
                  : 'bg-gradient-to-br from-amber-50 via-yellow-50 to-amber-100 border-b border-amber-200'"
              >
                <!-- Decoración de fondo -->
                <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-20" 
                  :class="operation === 'deposit' ? 'bg-emerald-400' : 'bg-amber-400'">
                </div>
                
                <div class="relative z-10 flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <div
                      class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg"
                      :class="operation === 'deposit' ? 'bg-gradient-to-br from-emerald-500 to-green-600' : 'bg-gradient-to-br from-amber-500 to-orange-600'"
                    >
                      <i
                        class="text-white text-xl"
                        :class="operation === 'deposit' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"
                      />
                    </div>
                    <div>
                      <h2 class="text-xl font-bold text-gray-900 mb-1">
                        {{ operation === 'deposit' ? 'Depositar fondos' : 'Retirar fondos' }}
                      </h2>
                      <p class="text-sm text-gray-600">Billetera {{ activeWallet?.currency.iso_code }}</p>
                    </div>
                  </div>
                  <button
                    type="button"
                    class="w-10 h-10 flex items-center justify-center hover:bg-white/70 rounded-xl transition-all text-gray-400 hover:text-gray-600 hover:scale-110"
                    @click="closeModal"
                  >
                    <i class="fas fa-times text-lg" />
                  </button>
                </div>
              </div>

              <!-- Body mejorado -->
              <div class="p-6 space-y-5">

                <!-- Saldo actual mejorado -->
                <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-2xl px-5 py-4 border border-gray-200">
                  <div class="flex justify-between items-center">
                    <div>
                      <p class="text-sm text-gray-600 mb-1">Saldo actual</p>
                      <p class="text-2xl font-bold text-gray-900">
                        {{ activeWallet?.currency.symbol }} {{ formattedBalance }}
                      </p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                      <i class="fas fa-wallet text-indigo-600"></i>
                    </div>
                  </div>
                </div>

                <!-- Monto con montos rápidos -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-3">
                    Monto a {{ operation === 'deposit' ? 'depositar' : 'retirar' }}
                  </label>
                  <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-lg select-none">
                      {{ activeWallet?.currency.symbol }}
                    </span>
                    <input
                      v-model="amount"
                      type="number"
                      min="0.01"
                      step="0.01"
                      placeholder="0.00"
                      class="w-full border-2 border-gray-200 rounded-2xl pl-12 pr-4 py-4 text-lg font-semibold focus:outline-none focus:ring-2 focus:border-transparent transition-all"
                      :class="operation === 'deposit' ? 'focus:ring-emerald-300 focus:border-emerald-400' : 'focus:ring-amber-300 focus:border-amber-400'"
                      :disabled="loading"
                      @keyup.enter="submit"
                      @input="clearQuickAmount"
                    />
                  </div>
                  
                  <!-- Montos rápidos -->
                  <div class="mt-4">
                    <p class="text-xs text-gray-500 mb-2 font-medium">Montos rápidos:</p>
                    <div class="grid grid-cols-3 gap-2">
                      <button
                        v-for="quickAmount in quickAmounts"
                        :key="quickAmount"
                        type="button"
                        class="px-3 py-2 text-sm font-medium rounded-xl border transition-all transform hover:scale-105"
                        :class="selectedQuickAmount === quickAmount
                          ? 'bg-indigo-500 text-white border-indigo-500 shadow-md'
                          : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50'"
                        @click="selectQuickAmount(quickAmount)"
                      >
                        {{ activeWallet?.currency.symbol }} {{ quickAmount }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Mensajes mejorados -->
                <Transition
                  enter-active-class="duration-300 ease-out"
                  enter-from-class="opacity-0 -translate-y-2"
                  enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="duration-200 ease-in"
                  leave-from-class="opacity-100 translate-y-0"
                  leave-to-class="opacity-0 -translate-y-2"
                >
                  <div
                    v-if="errorMsg"
                    class="flex items-start gap-3 bg-red-50 border-2 border-red-200 rounded-2xl px-4 py-4 text-sm text-red-700"
                  >
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="flex-1">
                      <p class="font-semibold">Error</p>
                      <p class="text-red-600">{{ errorMsg }}</p>
                    </div>
                  </div>
                </Transition>

                <Transition
                  enter-active-class="duration-300 ease-out"
                  enter-from-class="opacity-0 -translate-y-2"
                  enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="duration-200 ease-in"
                  leave-from-class="opacity-100 translate-y-0"
                  leave-to-class="opacity-0 -translate-y-2"
                >
                  <div
                    v-if="successMsg"
                    class="flex items-start gap-3 bg-emerald-50 border-2 border-emerald-200 rounded-2xl px-4 py-4 text-sm text-emerald-700"
                  >
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-check-circle text-emerald-600"></i>
                    </div>
                    <div class="flex-1">
                      <p class="font-semibold">Éxito</p>
                      <p class="text-emerald-600">{{ successMsg }}</p>
                    </div>
                  </div>
                </Transition>

              </div>

              <!-- Footer mejorado -->
              <div class="px-6 pb-6 flex gap-3">
                <button
                  type="button"
                  class="flex-1 py-3.5 border-2 border-gray-200 rounded-2xl text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all hover:border-gray-300 hover:shadow-md"
                  :disabled="loading"
                  @click="closeModal"
                >
                  <i class="fas fa-times mr-2"></i>
                  Cancelar
                </button>
                <button
                  type="button"
                  class="flex-1 py-3.5 rounded-2xl text-sm font-bold text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-lg active:scale-95"
                  :class="operation === 'deposit' ? 'bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700' : 'bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700'"
                  :disabled="loading"
                  @click="submit"
                >
                  <span v-if="loading" class="flex items-center justify-center gap-2">
                    <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    Procesando...
                  </span>
                  <span v-else class="flex items-center justify-center gap-2">
                    <i :class="operation === 'deposit' ? 'fas fa-check' : 'fas fa-arrow-up'"></i>
                    {{ operation === 'deposit' ? 'Confirmar depósito' : 'Confirmar retiro' }}
                  </span>
                </button>
              </div>

            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>
