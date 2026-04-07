<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
import CardHeader from '@js/Components/Common/Card/CardHeader.vue'

interface Movement {
  id: string
  wallet_id: string
  type: 'DEPOSIT' | 'WITHDRAW' | 'TRANSFER'
  amount: number
  currency: string
  status: 'PENDING' | 'COMPLETED' | 'FAILED'
  created_at: string
  user?: {
    id: string
    name: string
    email: string
  }
}

interface Props {
  title: string
  movements?: Movement[]
}

const props = defineProps<Props>()

const loading = ref(false)
const movements = ref<Movement[]>(props.movements || [])

// Columnas para la tabla
const columns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'user.name', label: 'Usuario', sortable: true },
  { key: 'type', label: 'Tipo', sortable: true },
  { key: 'amount', label: 'Monto', sortable: true },
  { key: 'currency', label: 'Moneda', sortable: true },
  { key: 'status', label: 'Estado', sortable: true },
  { key: 'created_at', label: 'Fecha', sortable: true },
  { key: 'actions', label: 'Acciones', sortable: false },
]

// Fetch movements from API
const fetchMovements = async () => {
  loading.value = true
  try {
    const response = await fetch('/wallet-movements/fetch')
    const data = await response.json()
    movements.value = data.data || []
  } catch (error) {
    console.error('Error fetching movements:', error)
  } finally {
    loading.value = false
  }
}

// Get badge class for status
const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    PENDING: 'badge badge-warning',
    COMPLETED: 'badge badge-success',
    FAILED: 'badge badge-danger',
  }
  return classes[status] || 'badge badge-secondary'
}

// Get badge class for type
const getTypeClass = (type: string) => {
  const classes: Record<string, string> = {
    DEPOSIT: 'badge badge-success',
    WITHDRAW: 'badge badge-danger',
    TRANSFER: 'badge badge-info',
  }
  return classes[type] || 'badge badge-secondary'
}

// Get type label
const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    DEPOSIT: 'Depósito',
    WITHDRAW: 'Retiro',
    TRANSFER: 'Transferencia',
  }
  return labels[type] || type
}

// Get status label
const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    PENDING: 'Pendiente',
    COMPLETED: 'Completado',
    FAILED: 'Fallido',
  }
  return labels[status] || status
}

onMounted(() => {
  fetchMovements()
})
</script>

<template>
  <Head :title="title" />

  <div class="container-fluid">
    <CardHeader :title="title" icon="fas fa-exchange-alt">
      <template #actions>
        <button class="btn btn-primary btn-sm" @click="fetchMovements" :disabled="loading">
          <i class="fas fa-sync" :class="{ 'fa-spin': loading }"></i>
          Actualizar
        </button>
      </template>
    </CardHeader>

    <div class="card">
      <div class="card-body">
        <BaseTable
          table-id="wallet-movements"
          :columns="columns"
          :rows="movements"
          empty-message="No hay movimientos registrados"
        >
          <template #cell-type="{ row }">
            <span :class="getTypeClass(row.type)">{{ getTypeLabel(row.type) }}</span>
          </template>

          <template #cell-amount="{ row }">
            <span :class="row.type === 'DEPOSIT' ? 'text-success' : 'text-danger'">
              {{ row.type === 'DEPOSIT' ? '+' : '-' }}{{ row.amount }}
            </span>
          </template>

          <template #cell-status="{ row }">
            <span :class="getStatusClass(row.status)">{{ getStatusLabel(row.status) }}</span>
          </template>

          <template #cell-actions="{ row }">
            <a :href="`/wallet-movements/${row.id}`" class="btn btn-sm btn-info">
              <i class="fas fa-eye"></i>
            </a>
          </template>
        </BaseTable>
      </div>
    </div>
  </div>
</template>
