<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
import backendClient from '@js/plugins/axios'

interface Props {
  title: string
}
defineProps<Props>()

// ── Tipos ──────────────────────────────────────────────────────────────────
interface Movement {
  id: string
  type: 'DEPOSIT' | 'WITHDRAWAL' | 'PURCHASE' | 'PRIZE'
  status: 'PENDING' | 'COMPLETED' | 'CANCELLED' | 'REJECTED'
  amount: number
  balance_before: number
  balance_after: number
  description: string | null
  reference_id: string | null
  created_at: string
  currency: { iso_code: string; symbol: string; decimal_places: number } | null
  user: { id: string; name: string; email: string; username: string | null } | null
}

interface Meta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// ── Estado ─────────────────────────────────────────────────────────────────
const movements = ref<Movement[]>([])
const meta      = ref<Meta | null>(null)
const loading   = ref(false)

// Filtros
const filterType   = ref('')
const filterStatus = ref('')
const filterSearch = ref('')

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
  { key: 'user',       label: 'Usuario',  sortable: false },
  { key: 'type',       label: 'Tipo',     sortable: false },
  { key: 'amount',     label: 'Monto',    sortable: false },
  { key: 'currency',   label: 'Moneda',   sortable: false },
  { key: 'status',     label: 'Estado',   sortable: false },
  { key: 'created_at', label: 'Fecha',    sortable: false },
  { key: 'actions',    label: 'Acciones', sortable: false },
]

// ── Fetch ──────────────────────────────────────────────────────────────────
async function fetchMovements(page = 1) {
  loading.value = true
  try {
    const { data } = await backendClient.get('/wallet-movements/fetch', {
      params: {
        page,
        per_page: 20,
        type:   filterType.value   || undefined,
        status: filterStatus.value || undefined,
        search: filterSearch.value || undefined,
      },
    })
    movements.value = data.data  ?? []
    meta.value      = data.meta  ?? null
  } catch (e) {
    console.error('[movements] Error cargando movimientos:', e)
    movements.value = []
  } finally {
    loading.value = false
  }
}

function applyFilters() { fetchMovements(1) }
function resetFilters()  {
  filterType.value   = ''
  filterStatus.value = ''
  filterSearch.value = ''
  fetchMovements(1)
}

// ── Helpers visuales ───────────────────────────────────────────────────────
const typeConfig: Record<string, { label: string; cls: string; icon: string }> = {
  DEPOSIT:    { label: 'Depósito',    cls: 'mv-badge mv-badge--deposit',    icon: 'fas fa-arrow-down' },
  WITHDRAWAL: { label: 'Retiro',      cls: 'mv-badge mv-badge--withdrawal', icon: 'fas fa-arrow-up' },
  PURCHASE:   { label: 'Compra',      cls: 'mv-badge mv-badge--purchase',   icon: 'fas fa-ticket-alt' },
  PRIZE:      { label: 'Premio',      cls: 'mv-badge mv-badge--prize',      icon: 'fas fa-trophy' },
}

const statusConfig: Record<string, { label: string; cls: string }> = {
  PENDING:   { label: 'Pendiente',  cls: 'mv-status mv-status--pending' },
  COMPLETED: { label: 'Completado', cls: 'mv-status mv-status--completed' },
  CANCELLED: { label: 'Cancelado',  cls: 'mv-status mv-status--cancelled' },
  REJECTED:  { label: 'Rechazado',  cls: 'mv-status mv-status--rejected' },
}

function formatAmount(mov: Movement): string {
  const dp  = mov.currency?.decimal_places ?? 2
  const sym = mov.currency?.symbol ?? ''
  const val = new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: dp,
    maximumFractionDigits: dp,
  }).format(mov.amount)
  const sign = ['DEPOSIT', 'PRIZE'].includes(mov.type) ? '+' : '-'
  return `${sign}${sym} ${val}`
}

function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('es-VE', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(new Date(iso))
}

const totalPages = computed(() => meta.value?.last_page ?? 1)
const currentPage = computed(() => meta.value?.current_page ?? 1)

onMounted(() => fetchMovements())
</script>

<template>
  <Head :title="title" />

  <div class="container-fluid">
    <CardHeader title="Movimientos de Billetera" icon="fas fa-exchange-alt">
      <template #actions>
        <button class="btn btn-sm btn-outline-secondary" :disabled="loading" @click="fetchMovements(currentPage)">
          <i class="fas fa-sync" :class="{ 'fa-spin': loading }"></i>
          Actualizar
        </button>
      </template>
    </CardHeader>

    <!-- Filtros -->
    <div class="card mb-3">
      <div class="card-body py-3">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-3">
            <label class="form-label form-label-sm">Tipo</label>
            <select v-model="filterType" class="form-select form-select-sm">
              <option value="">Todos</option>
              <option value="DEPOSIT">Depósito</option>
              <option value="WITHDRAWAL">Retiro</option>
              <option value="PURCHASE">Compra</option>
              <option value="PRIZE">Premio</option>
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label form-label-sm">Estado</label>
            <select v-model="filterStatus" class="form-select form-select-sm">
              <option value="">Todos</option>
              <option value="PENDING">Pendiente</option>
              <option value="COMPLETED">Completado</option>
              <option value="CANCELLED">Cancelado</option>
              <option value="REJECTED">Rechazado</option>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label form-label-sm">Buscar usuario</label>
            <input
              v-model="filterSearch"
              type="text"
              class="form-control form-control-sm"
              placeholder="Nombre o email..."
              @keyup.enter="applyFilters"
            />
          </div>
          <div class="col-12 col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-grow-1" :disabled="loading" @click="applyFilters">
              <i class="fas fa-search"></i> Filtrar
            </button>
            <button class="btn btn-sm btn-outline-secondary" :disabled="loading" @click="resetFilters">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="card-body p-0">

        <!-- Loading skeleton -->
        <div v-if="loading" class="mv-loading">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div>
          Cargando movimientos...
        </div>

        <!-- Tabla con datos -->
        <BaseTable
          v-else
          table-id="wallet-movements"
          :columns="columns"
          :rows="movements"
          empty-message="No hay movimientos registrados"
        >
          <!-- Usuario -->
          <template #cell-user="{ row }">
            <div v-if="row.user" class="mv-user">
              <span class="mv-user__name">{{ row.user.name }}</span>
              <span class="mv-user__email">{{ row.user.email }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>

          <!-- Tipo -->
          <template #cell-type="{ row }">
            <span :class="typeConfig[row.type]?.cls ?? 'mv-badge'">
              <i :class="typeConfig[row.type]?.icon"></i>
              {{ typeConfig[row.type]?.label ?? row.type }}
            </span>
          </template>

          <!-- Monto -->
          <template #cell-amount="{ row }">
            <span
              class="mv-amount"
              :class="['DEPOSIT','PRIZE'].includes(row.type) ? 'mv-amount--positive' : 'mv-amount--negative'"
            >
              {{ formatAmount(row) }}
            </span>
          </template>

          <!-- Moneda -->
          <template #cell-currency="{ row }">
            <span v-if="row.currency" class="mv-currency">
              {{ row.currency.symbol }} {{ row.currency.iso_code }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>

          <!-- Estado -->
          <template #cell-status="{ row }">
            <span :class="statusConfig[row.status]?.cls ?? 'mv-status'">
              {{ statusConfig[row.status]?.label ?? row.status }}
            </span>
          </template>

          <!-- Fecha -->
          <template #cell-created_at="{ row }">
            <span class="mv-date">{{ formatDate(row.created_at) }}</span>
          </template>

          <!-- Acciones -->
          <template #cell-actions="{ row }">
            <a :href="`/wallet-movements/${row.id}`" class="btn btn-sm btn-outline-info" title="Ver detalle">
              <i class="fas fa-eye"></i>
            </a>
          </template>
        </BaseTable>

        <!-- Paginación -->
        <div v-if="meta && totalPages > 1" class="mv-pagination">
          <span class="mv-pagination__info">
            Página {{ currentPage }} de {{ totalPages }} · {{ meta.total }} movimientos
          </span>
          <div class="mv-pagination__btns">
            <button
              class="btn btn-sm btn-outline-secondary"
              :disabled="currentPage <= 1 || loading"
              @click="fetchMovements(currentPage - 1)"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            <button
              class="btn btn-sm btn-outline-secondary"
              :disabled="currentPage >= totalPages || loading"
              @click="fetchMovements(currentPage + 1)"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.mv-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #6c757d;
  font-size: 0.875rem;
}

/* Usuario */
.mv-user { display: flex; flex-direction: column; }
.mv-user__name  { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.mv-user__email { font-size: 0.75rem; color: #94a3b8; }

/* Tipo badge */
.mv-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
}
.mv-badge--deposit    { background: #dcfce7; color: #16a34a; }
.mv-badge--withdrawal { background: #fef3c7; color: #d97706; }
.mv-badge--purchase   { background: #ffe4e6; color: #e11d48; }
.mv-badge--prize      { background: #fef9c3; color: #ca8a04; }

/* Monto */
.mv-amount { font-size: 0.875rem; font-weight: 700; }
.mv-amount--positive { color: #16a34a; }
.mv-amount--negative { color: #dc2626; }

/* Moneda */
.mv-currency { font-size: 0.78rem; font-weight: 600; color: #64748b; }

/* Estado */
.mv-status {
  display: inline-flex;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
}
.mv-status--pending   { background: #fef3c7; color: #d97706; }
.mv-status--completed { background: #dcfce7; color: #16a34a; }
.mv-status--cancelled { background: #f1f5f9; color: #64748b; }
.mv-status--rejected  { background: #ffe4e6; color: #e11d48; }

/* Fecha */
.mv-date { font-size: 0.78rem; color: #64748b; }

/* Paginación */
.mv-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1.25rem;
  border-top: 1px solid #f1f5f9;
}
.mv-pagination__info { font-size: 0.78rem; color: #94a3b8; }
.mv-pagination__btns { display: flex; gap: 6px; }
</style>
