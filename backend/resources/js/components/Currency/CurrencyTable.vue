<script setup lang="ts">
  import { ref, computed } from 'vue'

  import type { Currency } from '@js/types/currency'

  interface Props {
    currencies: Currency[]
    selectedIds: string[]
    hasPrivilegedRole?: boolean
  }

  interface Emits {
    (e: 'selection-change', ids: string[]): void
    (e: 'edit', id: string): void
    (e: 'delete', id: string): void
  }

  const props = withDefaults(defineProps<Props>(), {
    hasPrivilegedRole: false
  })

  const emit = defineEmits<Emits>()

  const headers = computed(() => [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Nombre', sortable: true },
    { key: 'description', label: 'Descripción', sortable: true },
    { key: 'iso_code', label: 'Código ISO', sortable: true },
    { key: 'symbol', label: 'Símbolo', sortable: true },
    { key: 'type', label: 'Tipo', sortable: true },
    { key: 'status', label: 'Estado', sortable: true },
    { key: 'country', label: 'País', sortable: true },
    { key: 'actions', label: 'Acciones', sortable: false }
  ])

  const searchQuery = ref('')
  const sortBy = ref('id')
  const sortDirection = ref<'asc' | 'desc'>('asc')

  const filteredCurrencies = computed(() => {
    let filtered = props.currencies

    // Apply search filter
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      filtered = filtered.filter(currency =>
        currency.name.toLowerCase().includes(query) ||
        currency.description.toLowerCase().includes(query) ||
        currency.iso_code.toLowerCase().includes(query) ||
        currency.symbol.toLowerCase().includes(query)
      )
    }

    // Apply sorting
    if (sortBy.value) {
      filtered = [...filtered].sort((a, b) => {
        let aValue: any = a[sortBy.value as keyof Currency]
        let bValue: any = b[sortBy.value as keyof Currency]

        if (sortBy.value === 'country') {
          aValue = a.country?.name || ''
          bValue = b.country?.name || ''
        }

        if (typeof aValue === 'string') {
          aValue = aValue.toLowerCase()
          bValue = bValue.toLowerCase()
        }

        if (sortDirection.value === 'asc') {
          return aValue > bValue ? 1 : -1
        } else {
          return aValue < bValue ? 1 : -1
        }
      })
    }

    return filtered
  })

  function toggleSort(column: string) {
    if (sortBy.value === column) {
      sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortBy.value = column
      sortDirection.value = 'asc'
    }
  }

  function handleSelectionChange(event: Event) {
    const target = event.target as HTMLInputElement
    const id = target.value
    const isChecked = target.checked

    let newSelectedIds = [...props.selectedIds]

    if (isChecked) {
      if (!newSelectedIds.includes(id)) {
        newSelectedIds.push(id)
      }
    } else {
      newSelectedIds = newSelectedIds.filter(selectedId => selectedId !== id)
    }

    emit('selection-change', newSelectedIds)
  }

  function handleSelectAll(event: Event) {
    const target = event.target as HTMLInputElement
    const isChecked = target.checked

    if (isChecked) {
      const allIds = filteredCurrencies.value.map(currency => String(currency.id))
      emit('selection-change', allIds)
    } else {
      emit('selection-change', [])
    }
  }

  function isAllSelected() {
    return filteredCurrencies.value.length > 0 && 
           filteredCurrencies.value.every(currency => props.selectedIds.includes(String(currency.id)))
  }

  function isSomeSelected() {
    return filteredCurrencies.value.some(currency => props.selectedIds.includes(String(currency.id)))
  }
</script>

<template>
  <div class="table-responsive">
    <!-- Search Bar -->
    <div class="mb-3">
      <input
        v-model="searchQuery"
        type="text"
        class="form-control"
        placeholder="Buscar monedas..."
      />
    </div>

    <table class="table table-striped table-hover">
      <thead class="table-dark">
        <tr>
          <th style="width: 50px;">
            <div class="form-check">
              <input
                class="form-check-input"
                type="checkbox"
                :checked="isAllSelected()"
                :indeterminate="isSomeSelected() && !isAllSelected()"
                @change="handleSelectAll"
              />
            </div>
          </th>
          <th
            v-for="header in headers"
            :key="header.key"
            @click="header.sortable ? toggleSort(header.key) : null"
            :class="{
              'sortable': header.sortable,
              'cursor-pointer': header.sortable
            }"
          >
            {{ header.label }}
            <span
              v-if="header.sortable && sortBy === header.key"
              class="ms-1"
            >
              {{ sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="filteredCurrencies.length === 0">
          <td :colspan="headers.length + 1" class="text-center py-4">
            No se encontraron monedas
          </td>
        </tr>
        <tr v-for="currency in filteredCurrencies" :key="currency.id">
          <td>
            <div class="form-check">
              <input
                class="form-check-input"
                type="checkbox"
                :value="String(currency.id)"
                :checked="selectedIds.includes(String(currency.id))"
                @change="handleSelectionChange"
              />
            </div>
          </td>
          <td>{{ currency.id }}</td>
          <td>{{ currency.name }}</td>
          <td>{{ currency.description }}</td>
          <td>{{ currency.iso_code }}</td>
          <td>{{ currency.symbol }}</td>
          <td>
            <span class="badge" :class="{
              'bg-primary': currency.type === 'FIAT',
              'bg-warning': currency.type === 'CRYPTO',
              'bg-info': currency.type === 'TOKEN'
            }">
              {{ currency.type }}
            </span>
          </td>
          <td>
            <span class="badge" :class="{
              'bg-success': currency.status === 'ACTIVE',
              'bg-danger': currency.status === 'INACTIVE'
            }">
              {{ currency.status === 'ACTIVE' ? 'Activa' : 'Inactiva' }}
            </span>
          </td>
          <td>
            {{ currency.country?.name || 'N/A' }}
          </td>
          <td>
            <div class="btn-group btn-group-sm" role="group">
              <button
                class="btn btn-outline-primary"
                title="Editar"
                @click="$emit('edit', String(currency.id))"
              >
                <i class="fas fa-edit"></i>
              </button>
              <button
                class="btn btn-outline-danger"
                title="Eliminar"
                @click="$emit('delete', String(currency.id))"
              >
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.sortable {
  user-select: none;
}

.sortable:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.cursor-pointer {
  cursor: pointer;
}

.form-check-input[indeterminate] {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.form-check-input[indeterminate]::after {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='white' stroke='none' d='M5 10h10v2H5z'/%3e%3c/svg%3e");
}
</style>
