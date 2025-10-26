<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Transacciones</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">
          Gestiona todas las transacciones del sistema
        </p>
      </div>
      <div class="flex gap-3">
        <button
          @click="exportTransactions"
          class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Exportar
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Total</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Pendientes</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.pending }}</p>
          </div>
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Aprobadas</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.approved }}</p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Rechazadas</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ stats.rejected }}</p>
          </div>
          <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-lg">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Volumen</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">
              {{ formatCurrency(stats.volume) }}
            </p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
      <!-- Filters -->
      <div class="p-4 border-b border-slate-200 dark:border-slate-700">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Buscar por ID, usuario..."
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              @input="debouncedSearch"
            />
          </div>
          <select
            v-model="filters.type"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadTransactions"
          >
            <option value="">Todos los tipos</option>
            <option value="deposit">Depósito</option>
            <option value="withdrawal">Retiro</option>
            <option value="transfer">Transferencia</option>
            <option value="purchase">Compra</option>
            <option value="prize">Premio</option>
          </select>
          <select
            v-model="filters.status"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadTransactions"
          >
            <option value="">Todos los estados</option>
            <option value="pending">Pendiente</option>
            <option value="approved">Aprobada</option>
            <option value="rejected">Rechazada</option>
            <option value="completed">Completada</option>
          </select>
          <input
            v-model="filters.dateFrom"
            type="date"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadTransactions"
          />
          <input
            v-model="filters.dateTo"
            type="date"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadTransactions"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Usuario
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Tipo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Monto
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Fecha
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="transaction in transactions"
              :key="transaction.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-mono text-slate-900 dark:text-white">
                  #{{ transaction.id.substring(0, 8) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-slate-900 dark:text-white">
                  {{ transaction.user_name }}
                </div>
                <div class="text-sm text-slate-500 dark:text-slate-400">
                  {{ transaction.user_email }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getTypeColor(transaction.type)
                  ]"
                >
                  {{ getTypeLabel(transaction.type) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-slate-900 dark:text-white">
                  {{ formatCurrency(transaction.amount) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ transaction.currency }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getStatusColor(transaction.status)
                  ]"
                >
                  {{ getStatusLabel(transaction.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                {{ formatDateTime(transaction.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewTransaction(transaction)"
                  class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                >
                  Ver
                </button>
                <button
                  v-if="transaction.status === 'pending'"
                  @click="approveTransaction(transaction)"
                  class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3"
                >
                  Aprobar
                </button>
                <button
                  v-if="transaction.status === 'pending'"
                  @click="rejectTransaction(transaction)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                >
                  Rechazar
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty state -->
        <div
          v-if="transactions.length === 0"
          class="text-center py-12"
        >
          <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <p class="text-slate-600 dark:text-slate-400 text-lg">No se encontraron transacciones</p>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination.total > 0"
        class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between"
      >
        <div class="text-sm text-slate-600 dark:text-slate-400">
          Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }}
        </div>
        <div class="flex gap-2">
          <button
            @click="previousPage"
            :disabled="pagination.currentPage === 1"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              pagination.currentPage === 1
                ? 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-600 cursor-not-allowed'
                : 'bg-blue-600 hover:bg-blue-700 text-white'
            ]"
          >
            Anterior
          </button>
          <button
            @click="nextPage"
            :disabled="pagination.currentPage === pagination.lastPage"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              pagination.currentPage === pagination.lastPage
                ? 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-600 cursor-not-allowed'
                : 'bg-blue-600 hover:bg-blue-700 text-white'
            ]"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- View Modal -->
    <div
      v-if="selectedTransaction"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-xl max-w-2xl w-full">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
              Detalles de Transacción
            </h2>
            <button
              @click="closeModal"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">ID de Transacción</p>
              <p class="text-slate-900 dark:text-white font-mono">{{ selectedTransaction.id }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Usuario</p>
              <p class="text-slate-900 dark:text-white">{{ selectedTransaction.user_name }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Tipo</p>
              <p class="text-slate-900 dark:text-white">{{ getTypeLabel(selectedTransaction.type) }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Estado</p>
              <p class="text-slate-900 dark:text-white">{{ getStatusLabel(selectedTransaction.status) }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Monto</p>
              <p class="text-slate-900 dark:text-white font-semibold">
                {{ formatCurrency(selectedTransaction.amount) }} {{ selectedTransaction.currency }}
              </p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Fecha</p>
              <p class="text-slate-900 dark:text-white">{{ formatDateTime(selectedTransaction.created_at) }}</p>
            </div>
          </div>

          <div v-if="selectedTransaction.description">
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Descripción</p>
            <p class="text-slate-900 dark:text-white">{{ selectedTransaction.description }}</p>
          </div>

          <div v-if="selectedTransaction.metadata" class="bg-slate-100 dark:bg-slate-900 p-4 rounded-lg">
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Metadatos</p>
            <pre class="text-xs text-slate-900 dark:text-white overflow-x-auto">{{ JSON.stringify(selectedTransaction.metadata, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatCurrency, formatDateTime } from '~/layers/shared/utils/formatters'
import { debounce } from '~/layers/shared/utils/helpers'
import { useUIStore } from '~/layers/shared/stores/ui'

definePageMeta({
  middleware: ['auth'],
  layout: 'admin'
})

interface Transaction {
  id: string
  user_name: string
  user_email: string
  type: string
  amount: number
  currency: string
  status: string
  created_at: string
  description?: string
  metadata?: any
}

const { getTransactions, approveTransaction: approveTransactionApi, rejectTransaction: rejectTransactionApi } = useAdminApi()
const uiStore = useUIStore()

// Estados
const transactions = ref<Transaction[]>([])
const selectedTransaction = ref<Transaction | null>(null)

const filters = ref({
  search: '',
  type: '',
  status: '',
  dateFrom: '',
  dateTo: ''
})

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  perPage: 20,
  total: 0,
  from: 0,
  to: 0
})

const stats = ref({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0,
  volume: 0
})

// Computed
const debouncedSearch = debounce(() => {
  loadTransactions()
}, 500)

// Métodos
const loadTransactions = async () => {
  try {
    const response = await getTransactions({
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
      search: filters.value.search,
      type: filters.value.type,
      status: filters.value.status,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    })

    transactions.value = response.data
    pagination.value = {
      currentPage: response.current_page,
      lastPage: response.last_page,
      perPage: response.per_page,
      total: response.total,
      from: response.from,
      to: response.to
    }

    // Update stats
    stats.value = response.stats || {
      total: response.total,
      pending: response.data.filter((t: Transaction) => t.status === 'pending').length,
      approved: response.data.filter((t: Transaction) => t.status === 'approved').length,
      rejected: response.data.filter((t: Transaction) => t.status === 'rejected').length,
      volume: response.data.reduce((sum: number, t: Transaction) => sum + t.amount, 0)
    }
  } catch (error) {
    uiStore.notifyError('Error cargando transacciones')
  }
}

const viewTransaction = (transaction: Transaction) => {
  selectedTransaction.value = transaction
}

const approveTransaction = async (transaction: Transaction) => {
  if (!confirm(`¿Aprobar transacción #${transaction.id.substring(0, 8)}?`)) return

  try {
    await approveTransactionApi(transaction.id)
    uiStore.notifySuccess('Transacción aprobada')
    loadTransactions()
  } catch (error) {
    uiStore.notifyError('Error aprobando transacción')
  }
}

const rejectTransaction = async (transaction: Transaction) => {
  const reason = prompt('¿Razón del rechazo?')
  if (!reason) return

  try {
    await rejectTransactionApi(transaction.id, reason)
    uiStore.notifySuccess('Transacción rechazada')
    loadTransactions()
  } catch (error) {
    uiStore.notifyError('Error rechazando transacción')
  }
}

const exportTransactions = () => {
  uiStore.notifySuccess('Exportando transacciones...')
}

const closeModal = () => {
  selectedTransaction.value = null
}

const getTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    deposit: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    withdrawal: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    transfer: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    purchase: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
    prize: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
  }
  return colors[type] || 'bg-slate-100 text-slate-800'
}

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    deposit: 'Depósito',
    withdrawal: 'Retiro',
    transfer: 'Transferencia',
    purchase: 'Compra',
    prize: 'Premio'
  }
  return labels[type] || type
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
  }
  return colors[status] || 'bg-slate-100 text-slate-800'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Pendiente',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    completed: 'Completada'
  }
  return labels[status] || status
}

const nextPage = () => {
  if (pagination.value.currentPage < pagination.value.lastPage) {
    pagination.value.currentPage++
    loadTransactions()
  }
}

const previousPage = () => {
  if (pagination.value.currentPage > 1) {
    pagination.value.currentPage--
    loadTransactions()
  }
}

// Cargar al montar
onMounted(() => {
  loadTransactions()
})
</script>
