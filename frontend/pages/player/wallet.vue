<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <!-- Header -->
    <header class="bg-black/30 backdrop-blur-md border-b border-white/10">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <h1 class="text-2xl font-bold text-white">Mi Billetera</h1>
          <NuxtLink to="/player/lobby" class="text-white hover:text-gray-300 transition">
            <Icon name="lucide:arrow-left" class="h-6 w-6 inline mr-2" />
            Volver al Lobby
          </NuxtLink>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna izquierda: Balances -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Billeteras -->
          <div
            v-for="wallet in wallets"
            :key="wallet.id"
            :class="[
              'bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 cursor-pointer transition-all',
              selectedWallet?.id === wallet.id ? 'ring-2 ring-blue-500' : 'hover:bg-white/15'
            ]"
            @click="selectedWallet = wallet"
          >
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="text-white font-semibold">{{ wallet.currency }}</h3>
                <p class="text-gray-300 text-sm">{{ getCurrencyName(wallet.currency) }}</p>
              </div>
              <div
                :class="[
                  'px-3 py-1 rounded-full text-xs font-medium',
                  wallet.status === 'active' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'
                ]"
              >
                {{ wallet.status }}
              </div>
            </div>

            <div class="mb-4">
              <p class="text-gray-400 text-sm mb-1">Balance disponible</p>
              <p class="text-3xl font-bold text-white">
                ${{ formatNumber(wallet.balance) }}
                <span class="text-lg text-gray-300">{{ wallet.currency }}</span>
              </p>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <button
                class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition"
                @click.stop="openDepositModal(wallet)"
              >
                <Icon name="lucide:plus" class="inline h-4 w-4 mr-1" />
                Depositar
              </button>
              <button
                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition"
                @click.stop="openWithdrawalModal(wallet)"
              >
                <Icon name="lucide:arrow-up" class="inline h-4 w-4 mr-1" />
                Retirar
              </button>
            </div>
          </div>

          <!-- Botón agregar billetera -->
          <button
            class="w-full p-6 bg-white/5 backdrop-blur-lg rounded-2xl border-2 border-dashed border-white/20 hover:border-white/40 text-white transition"
          >
            <Icon name="lucide:plus-circle" class="h-8 w-8 mx-auto mb-2" />
            <span class="text-sm">Agregar Billetera</span>
          </button>
        </div>

        <!-- Columna derecha: Transacciones -->
        <div class="lg:col-span-2">
          <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <!-- Header de transacciones -->
            <div class="p-6 border-b border-white/10">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-white">Historial de Transacciones</h2>
                <div class="flex items-center space-x-2">
                  <select
                    v-model="filterType"
                    class="px-3 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Todos los tipos</option>
                    <option value="deposit">Depósitos</option>
                    <option value="withdrawal">Retiros</option>
                    <option value="purchase">Compras</option>
                    <option value="prize">Premios</option>
                    <option value="refund">Reembolsos</option>
                  </select>

                  <select
                    v-model="filterStatus"
                    class="px-3 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Todos los estados</option>
                    <option value="pending">Pendiente</option>
                    <option value="completed">Completado</option>
                    <option value="failed">Fallido</option>
                    <option value="cancelled">Cancelado</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Lista de transacciones -->
            <div class="divide-y divide-white/10">
              <div v-if="isLoadingTransactions" class="p-12 text-center">
                <Icon name="lucide:loader-2" class="h-8 w-8 animate-spin mx-auto text-white mb-2" />
                <p class="text-gray-300">Cargando transacciones...</p>
              </div>

              <div v-else-if="filteredTransactions.length === 0" class="p-12 text-center">
                <Icon name="lucide:inbox" class="h-12 w-12 mx-auto text-gray-400 mb-2" />
                <p class="text-gray-300">No hay transacciones</p>
              </div>

              <div
                v-else
                v-for="transaction in filteredTransactions"
                :key="transaction.id"
                class="p-4 hover:bg-white/5 transition"
              >
                <div class="flex justify-between items-start">
                  <div class="flex items-start space-x-3">
                    <div
                      :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center',
                        getTransactionIconClass(transaction.type)
                      ]"
                    >
                      <Icon :name="getTransactionIcon(transaction.type)" class="h-5 w-5" />
                    </div>

                    <div>
                      <p class="text-white font-medium">{{ getTransactionTitle(transaction.type) }}</p>
                      <p class="text-gray-400 text-sm">{{ transaction.description }}</p>
                      <p class="text-gray-500 text-xs mt-1">{{ formatDate(transaction.created_at) }}</p>
                    </div>
                  </div>

                  <div class="text-right">
                    <p
                      :class="[
                        'text-lg font-bold',
                        transaction.type === 'deposit' || transaction.type === 'prize' ? 'text-green-400' : 'text-red-400'
                      ]"
                    >
                      {{ transaction.type === 'deposit' || transaction.type === 'prize' ? '+' : '-' }}
                      ${{ formatNumber(transaction.amount) }}
                    </p>
                    <span
                      :class="[
                        'inline-block px-2 py-1 rounded-full text-xs font-medium mt-1',
                        getStatusClass(transaction.status)
                      ]"
                    >
                      {{ getStatusText(transaction.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Paginación -->
            <div v-if="totalPages > 1" class="p-4 border-t border-white/10">
              <div class="flex justify-center space-x-2">
                <button
                  :disabled="currentPage === 1"
                  class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed transition"
                  @click="currentPage--"
                >
                  Anterior
                </button>
                <span class="px-4 py-1 text-white">
                  Página {{ currentPage }} de {{ totalPages }}
                </span>
                <button
                  :disabled="currentPage === totalPages"
                  class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed transition"
                  @click="currentPage++"
                >
                  Siguiente
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal de depósito -->
    <UiBaseModal
      v-model="showDepositModal"
      title="Depositar Fondos"
      size="md"
      @confirm="submitDeposit"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Billetera
          </label>
          <input
            :value="`${depositForm.currency} - ${getCurrencyName(depositForm.currency)}`"
            type="text"
            disabled
            class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Monto
          </label>
          <input
            v-model.number="depositForm.amount"
            type="number"
            min="0"
            step="0.01"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Método de pago
          </label>
          <select
            v-model="depositForm.payment_method"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Seleccionar método</option>
            <option value="bank_transfer">Transferencia bancaria</option>
            <option value="crypto">Criptomoneda</option>
            <option value="cash">Efectivo</option>
          </select>
        </div>

        <div class="p-4 bg-blue-50 rounded-lg">
          <p class="text-sm text-gray-600">
            Una vez enviada la solicitud, recibirás instrucciones para completar el depósito.
          </p>
        </div>
      </div>
    </UiBaseModal>

    <!-- Modal de retiro -->
    <UiBaseModal
      v-model="showWithdrawalModal"
      title="Retirar Fondos"
      size="md"
      @confirm="submitWithdrawal"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Balance disponible
          </label>
          <input
            :value="`$${formatNumber(withdrawalForm.available)} ${withdrawalForm.currency}`"
            type="text"
            disabled
            class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Monto a retirar
          </label>
          <input
            v-model.number="withdrawalForm.amount"
            type="number"
            min="0"
            :max="withdrawalForm.available"
            step="0.01"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Cuenta de destino
          </label>
          <input
            v-model="withdrawalForm.destination_account"
            type="text"
            placeholder="Número de cuenta o dirección"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div class="p-4 bg-yellow-50 rounded-lg">
          <p class="text-sm text-gray-600">
            Los retiros pueden tardar de 24 a 48 horas en procesarse.
          </p>
        </div>
      </div>
    </UiBaseModal>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de billetera del jugador
 *
 * Gestión de billeteras, depósitos, retiros y
 * historial de transacciones.
 */
import { formatRelativeTime } from '#shared/utils/formatters'

definePageMeta({
  middleware: ['auth'],
  layout: 'player'
})

const { getWallets, getTransactions, requestDeposit, requestWithdrawal } = useWalletApi()

// Estado
const wallets = ref<any[]>([])
const selectedWallet = ref<any>(null)
const transactions = ref<any[]>([])
const isLoadingTransactions = ref(false)
const showDepositModal = ref(false)
const showWithdrawalModal = ref(false)

// Filtros
const filterType = ref('')
const filterStatus = ref('')
const currentPage = ref(1)
const perPage = 10

// Formularios
const depositForm = ref({
  wallet_id: '',
  currency: '',
  amount: 0,
  payment_method: ''
})

const withdrawalForm = ref({
  wallet_id: '',
  currency: '',
  amount: 0,
  available: 0,
  destination_account: ''
})

// Computed
const filteredTransactions = computed(() => {
  let filtered = transactions.value

  if (filterType.value) {
    filtered = filtered.filter(t => t.type === filterType.value)
  }

  if (filterStatus.value) {
    filtered = filtered.filter(t => t.status === filterStatus.value)
  }

  return filtered
})

const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / perPage))

// Métodos
const loadWallets = async () => {
  try {
    const response = await getWallets()
    wallets.value = response.wallets || []
    if (wallets.value.length > 0 && !selectedWallet.value) {
      selectedWallet.value = wallets.value[0]
    }
  } catch (error) {
    console.error('Error cargando billeteras:', error)
  }
}

const loadTransactions = async () => {
  if (!selectedWallet.value) return

  isLoadingTransactions.value = true
  try {
    const response = await getTransactions(selectedWallet.value.id)
    transactions.value = response.transactions || []
  } catch (error) {
    console.error('Error cargando transacciones:', error)
  } finally {
    isLoadingTransactions.value = false
  }
}

const openDepositModal = (wallet: any) => {
  depositForm.value = {
    wallet_id: wallet.id,
    currency: wallet.currency,
    amount: 0,
    payment_method: ''
  }
  showDepositModal.value = true
}

const openWithdrawalModal = (wallet: any) => {
  withdrawalForm.value = {
    wallet_id: wallet.id,
    currency: wallet.currency,
    amount: 0,
    available: wallet.balance,
    destination_account: ''
  }
  showWithdrawalModal.value = true
}

const submitDeposit = async () => {
  try {
    await requestDeposit(depositForm.value)
    showDepositModal.value = false
    alert('Solicitud de depósito enviada exitosamente')
    await loadWallets()
    await loadTransactions()
  } catch (error) {
    console.error('Error solicitando depósito:', error)
    alert('Error al solicitar depósito')
  }
}

const submitWithdrawal = async () => {
  try {
    await requestWithdrawal(withdrawalForm.value)
    showWithdrawalModal.value = false
    alert('Solicitud de retiro enviada exitosamente')
    await loadWallets()
    await loadTransactions()
  } catch (error) {
    console.error('Error solicitando retiro:', error)
    alert('Error al solicitar retiro')
  }
}

// Helpers
const getCurrencyName = (currency: string) => {
  const names: Record<string, string> = {
    USD: 'Dólar estadounidense',
    VES: 'Bolívar venezolano',
    BRL: 'Real brasileño',
    USDT: 'Tether'
  }
  return names[currency] || currency
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num)
}

const formatDate = (date: string) => {
  return formatRelativeTime(date)
}

const getTransactionIcon = (type: string) => {
  const icons: Record<string, string> = {
    deposit: 'lucide:arrow-down',
    withdrawal: 'lucide:arrow-up',
    purchase: 'lucide:shopping-cart',
    prize: 'lucide:trophy',
    refund: 'lucide:refresh-cw'
  }
  return icons[type] || 'lucide:circle'
}

const getTransactionIconClass = (type: string) => {
  const classes: Record<string, string> = {
    deposit: 'bg-green-500/20 text-green-400',
    withdrawal: 'bg-blue-500/20 text-blue-400',
    purchase: 'bg-purple-500/20 text-purple-400',
    prize: 'bg-yellow-500/20 text-yellow-400',
    refund: 'bg-gray-500/20 text-gray-400'
  }
  return classes[type] || 'bg-gray-500/20 text-gray-400'
}

const getTransactionTitle = (type: string) => {
  const titles: Record<string, string> = {
    deposit: 'Depósito',
    withdrawal: 'Retiro',
    purchase: 'Compra',
    prize: 'Premio',
    refund: 'Reembolso'
  }
  return titles[type] || type
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-500/20 text-yellow-300',
    completed: 'bg-green-500/20 text-green-300',
    failed: 'bg-red-500/20 text-red-300',
    cancelled: 'bg-gray-500/20 text-gray-300'
  }
  return classes[status] || 'bg-gray-500/20 text-gray-300'
}

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: 'Pendiente',
    completed: 'Completado',
    failed: 'Fallido',
    cancelled: 'Cancelado'
  }
  return texts[status] || status
}

// Watchers
watch(selectedWallet, () => {
  if (selectedWallet.value) {
    loadTransactions()
  }
})

// Lifecycle
onMounted(() => {
  loadWallets()
})

// SEO
useHead({
  title: 'Mi Billetera - PlayRKE'
})
</script>
