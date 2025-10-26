<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Tesoreros</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">
          Gestiona los tesoreros y sus tasas de cambio
        </p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Nuevo Tesorero
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Total Tesoreros</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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
            <p class="text-sm text-slate-600 dark:text-slate-400">Países Cubiertos</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ stats.countries }}</p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Tasas Configuradas</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ stats.rates }}</p>
          </div>
          <div class="p-3 bg-orange-100 dark:bg-orange-900/20 rounded-lg">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
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
            @change="loadTreasurers"
          >
            <option value="">Todos los estados</option>
            <option value="active">Activos</option>
            <option value="inactive">Inactivos</option>
          </select>
          <select
            v-model="filters.country"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadTreasurers"
          >
            <option value="">Todos los países</option>
            <option value="VE">Venezuela</option>
            <option value="CO">Colombia</option>
            <option value="BR">Brasil</option>
            <option value="US">Estados Unidos</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Tesorero
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Email
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                País/Moneda
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
              v-for="treasurer in treasurers"
              :key="treasurer.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <img
                    :src="treasurer.avatar || '/images/default-avatar.png'"
                    :alt="treasurer.name"
                    class="w-10 h-10 rounded-full"
                  />
                  <div class="ml-4">
                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                      {{ treasurer.name }}
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                      @{{ treasurer.username }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white">{{ treasurer.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white">
                  {{ treasurer.country }} / {{ treasurer.currency }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white">
                  {{ treasurer.commission }}%
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    treasurer.status === 'active'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                      : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                  ]"
                >
                  {{ treasurer.status === 'active' ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewRates(treasurer)"
                  class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 mr-3"
                >
                  Tasas
                </button>
                <button
                  @click="editTreasurer(treasurer)"
                  class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3"
                >
                  Editar
                </button>
                <button
                  @click="deleteTreasurer(treasurer)"
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
          v-if="treasurers.length === 0"
          class="text-center py-12"
        >
          <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-slate-600 dark:text-slate-400 text-lg">No se encontraron tesoreros</p>
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
              {{ isEditing ? 'Editar Tesorero' : 'Nuevo Tesorero' }}
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

        <form @submit.prevent="saveTreasurer" class="p-6 space-y-4">
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

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                País
              </label>
              <select
                v-model="form.country"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Seleccionar país</option>
                <option value="VE">Venezuela</option>
                <option value="CO">Colombia</option>
                <option value="BR">Brasil</option>
                <option value="US">Estados Unidos</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Moneda
              </label>
              <select
                v-model="form.currency"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Seleccionar moneda</option>
                <option value="USD">USD</option>
                <option value="VES">VES</option>
                <option value="BRL">BRL</option>
                <option value="COP">COP</option>
              </select>
            </div>
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
import { useUIStore } from '~/layers/shared/stores/ui'

definePageMeta({
  middleware: ['auth'],
  layout: 'admin'
})

interface Treasurer {
  id: string
  first_name: string
  last_name: string
  name: string
  username: string
  email: string
  country: string
  currency: string
  commission: number
  status: 'active' | 'inactive'
  avatar?: string
}

const { getTreasurers, createTreasurer, updateTreasurer, deleteTreasurer: deleteTreasurerApi } = useAdminApi()
const uiStore = useUIStore()

// Estados
const treasurers = ref<Treasurer[]>([])
const showModal = ref(false)
const isEditing = ref(false)
const selectedTreasurer = ref<Treasurer | null>(null)

const filters = ref({
  search: '',
  status: '',
  country: ''
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
  countries: 0,
  rates: 0
})

const form = ref({
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  country: '',
  currency: '',
  commission: 0,
  status: 'active' as 'active' | 'inactive'
})

// Computed
const debouncedSearch = debounce(() => {
  loadTreasurers()
}, 500)

// Métodos
const loadTreasurers = async () => {
  try {
    const response = await getTreasurers({
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
      search: filters.value.search,
      status: filters.value.status,
      country: filters.value.country
    })

    treasurers.value = response.data
    pagination.value = {
      currentPage: response.current_page,
      lastPage: response.last_page,
      perPage: response.per_page,
      total: response.total,
      from: response.from,
      to: response.to
    }

    // Update stats
    const uniqueCountries = new Set(response.data.map((t: Treasurer) => t.country))
    stats.value = {
      total: response.total,
      active: response.data.filter((t: Treasurer) => t.status === 'active').length,
      countries: uniqueCountries.size,
      rates: response.data.length // Cada tesorero tiene una tasa
    }
  } catch (error) {
    uiStore.notifyError('Error cargando tesoreros')
  }
}

const openCreateModal = () => {
  isEditing.value = false
  resetForm()
  showModal.value = true
}

const editTreasurer = (treasurer: Treasurer) => {
  isEditing.value = true
  selectedTreasurer.value = treasurer
  form.value = {
    first_name: treasurer.first_name,
    last_name: treasurer.last_name,
    username: treasurer.username,
    email: treasurer.email,
    country: treasurer.country,
    currency: treasurer.currency,
    commission: treasurer.commission,
    status: treasurer.status
  }
  showModal.value = true
}

const viewRates = (treasurer: Treasurer) => {
  navigateTo(`/admin/treasurers/${treasurer.id}/rates`)
}

const deleteTreasurer = async (treasurer: Treasurer) => {
  if (!confirm(`¿Estás seguro de eliminar a ${treasurer.name}?`)) return

  try {
    await deleteTreasurerApi(treasurer.id)
    uiStore.notifySuccess('Tesorero eliminado exitosamente')
    loadTreasurers()
  } catch (error) {
    uiStore.notifyError('Error eliminando tesorero')
  }
}

const saveTreasurer = async () => {
  try {
    if (isEditing.value && selectedTreasurer.value) {
      await updateTreasurer(selectedTreasurer.value.id, form.value)
      uiStore.notifySuccess('Tesorero actualizado exitosamente')
    } else {
      await createTreasurer(form.value)
      uiStore.notifySuccess('Tesorero creado exitosamente')
    }

    closeModal()
    loadTreasurers()
  } catch (error: any) {
    uiStore.notifyError(error.message || 'Error guardando tesorero')
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
    country: '',
    currency: '',
    commission: 0,
    status: 'active'
  }
  selectedTreasurer.value = null
}

const nextPage = () => {
  if (pagination.value.currentPage < pagination.value.lastPage) {
    pagination.value.currentPage++
    loadTreasurers()
  }
}

const previousPage = () => {
  if (pagination.value.currentPage > 1) {
    pagination.value.currentPage--
    loadTreasurers()
  }
}

// Cargar al montar
onMounted(() => {
  loadTreasurers()
})
</script>
