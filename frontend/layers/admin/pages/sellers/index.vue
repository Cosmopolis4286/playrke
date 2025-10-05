<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Vendedores</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">
          Gestiona los vendedores y sus comisiones
        </p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Nuevo Vendedor
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Total Vendedores</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Activos</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.active }}</p>
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
            <p class="text-sm text-slate-600 dark:text-slate-400">Salas Asignadas</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ stats.rooms }}</p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Ventas (30d)</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">$12.4K</p>
          </div>
          <div class="p-3 bg-orange-100 dark:bg-orange-900/20 rounded-lg">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="filters.search"
              type="text"
              placeholder="Buscar por nombre o email..."
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              @input="debouncedSearch"
            />
          </div>
          <select
            v-model="filters.status"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadSellers"
          >
            <option value="">Todos los estados</option>
            <option value="active">Activos</option>
            <option value="inactive">Inactivos</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Vendedor
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Email
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Salas
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Comisión
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="seller in sellers"
              :key="seller.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <img
                    :src="seller.avatar || '/images/default-avatar.png'"
                    :alt="seller.name"
                    class="w-10 h-10 rounded-full"
                  />
                  <div class="ml-4">
                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                      {{ seller.name }}
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                      @{{ seller.username }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white">{{ seller.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white">
                  {{ seller.rooms_count }} salas
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white">
                  {{ seller.commission }}%
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    seller.status === 'active'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                      : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                  ]"
                >
                  {{ seller.status === 'active' ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewSeller(seller)"
                  class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                >
                  Ver
                </button>
                <button
                  @click="editSeller(seller)"
                  class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3"
                >
                  Editar
                </button>
                <button
                  @click="deleteSeller(seller)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                >
                  Eliminar
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty state -->
        <div
          v-if="sellers.length === 0"
          class="text-center py-12"
        >
          <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
          <p class="text-slate-600 dark:text-slate-400 text-lg">No se encontraron vendedores</p>
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

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
              {{ isEditing ? 'Editar Vendedor' : 'Nuevo Vendedor' }}
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

        <form @submit.prevent="saveSeller" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Nombre
              </label>
              <input
                v-model="form.first_name"
                type="text"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Apellido
              </label>
              <input
                v-model="form.last_name"
                type="text"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Username
            </label>
            <input
              v-model="form.username"
              type="text"
              required
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Email
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Comisión (%)
            </label>
            <input
              v-model.number="form.commission"
              type="number"
              step="0.01"
              min="0"
              max="100"
              required
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Estado
            </label>
            <select
              v-model="form.status"
              required
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="active">Activo</option>
              <option value="inactive">Inactivo</option>
            </select>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              {{ isEditing ? 'Actualizar' : 'Crear' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { debounce } from '~/layers/shared/utils/helpers'

definePageMeta({
  middleware: ['auth'],
  layout: 'admin'
})

interface Seller {
  id: string
  first_name: string
  last_name: string
  name: string
  username: string
  email: string
  commission: number
  rooms_count: number
  status: 'active' | 'inactive'
  avatar?: string
}

const { getSellers, createSeller, updateSeller, deleteSeller: deleteSellerApi } = useAdminApi()
const uiStore = useUIStore()

// Estados
const sellers = ref<Seller[]>([])
const showModal = ref(false)
const isEditing = ref(false)
const selectedSeller = ref<Seller | null>(null)

const filters = ref({
  search: '',
  status: ''
})

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
  from: 0,
  to: 0
})

const stats = ref({
  total: 0,
  active: 0,
  rooms: 0
})

const form = ref({
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  commission: 0,
  status: 'active' as 'active' | 'inactive'
})

// Computed
const debouncedSearch = debounce(() => {
  loadSellers()
}, 500)

// Métodos
const loadSellers = async () => {
  try {
    const response = await getSellers({
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
      search: filters.value.search,
      status: filters.value.status
    })

    sellers.value = response.data
    pagination.value = {
      currentPage: response.current_page,
      lastPage: response.last_page,
      perPage: response.per_page,
      total: response.total,
      from: response.from,
      to: response.to
    }

    // Update stats
    const totalRooms = response.data.reduce((sum: number, s: Seller) => sum + s.rooms_count, 0)
    stats.value = {
      total: response.total,
      active: response.data.filter((s: Seller) => s.status === 'active').length,
      rooms: totalRooms
    }
  } catch (error) {
    uiStore.notifyError('Error cargando vendedores')
  }
}

const openCreateModal = () => {
  isEditing.value = false
  resetForm()
  showModal.value = true
}

const editSeller = (seller: Seller) => {
  isEditing.value = true
  selectedSeller.value = seller
  form.value = {
    first_name: seller.first_name,
    last_name: seller.last_name,
    username: seller.username,
    email: seller.email,
    commission: seller.commission,
    status: seller.status
  }
  showModal.value = true
}

const viewSeller = (seller: Seller) => {
  navigateTo(`/admin/sellers/${seller.id}`)
}

const deleteSeller = async (seller: Seller) => {
  if (!confirm(`¿Estás seguro de eliminar a ${seller.name}?`)) return

  try {
    await deleteSellerApi(seller.id)
    uiStore.notifySuccess('Vendedor eliminado exitosamente')
    loadSellers()
  } catch (error) {
    uiStore.notifyError('Error eliminando vendedor')
  }
}

const saveSeller = async () => {
  try {
    if (isEditing.value && selectedSeller.value) {
      await updateSeller(selectedSeller.value.id, form.value)
      uiStore.notifySuccess('Vendedor actualizado exitosamente')
    } else {
      await createSeller(form.value)
      uiStore.notifySuccess('Vendedor creado exitosamente')
    }

    closeModal()
    loadSellers()
  } catch (error: any) {
    uiStore.notifyError(error.message || 'Error guardando vendedor')
  }
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    commission: 0,
    status: 'active'
  }
  selectedSeller.value = null
}

const nextPage = () => {
  if (pagination.value.currentPage < pagination.value.lastPage) {
    pagination.value.currentPage++
    loadSellers()
  }
}

const previousPage = () => {
  if (pagination.value.currentPage > 1) {
    pagination.value.currentPage--
    loadSellers()
  }
}

// Cargar al montar
onMounted(() => {
  loadSellers()
})
</script>
