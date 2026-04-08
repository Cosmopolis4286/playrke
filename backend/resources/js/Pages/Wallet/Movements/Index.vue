<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import CardHeader      from '@js/Components/Common/Card/CardHeader.vue'
import BaseTable       from '@js/Components/Common/Table/BaseTable.vue'
import TableFilter     from '@js/Components/Common/Table/TableFilter.vue'
import TableFooterInfo from '@js/Components/Common/Table/TableFooterInfo.vue'
import backendClient   from '@js/plugins/axios'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'

interface Props { title: string }
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
  user: { id: string; first_name: string | null; last_name: string | null; email: string; username: string | null } | null
}

interface Meta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// ── Estado ─────────────────────────────────────────────────────────────────
const movements  = ref<Movement[]>([])
const meta       = ref<Meta | null>(null)
const loading    = ref(false)
const lastSearch = ref('')
const lastFilters = ref<Record<string, string>>({})

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

// ── Definición de filtros para TableFilter ─────────────────────────────────
const tableFilters: TableFilterInterface[] = [
  {
    key: 'type',
    label: 'Tipo',
    placeholder: 'Todos los tipos',
    options: [
      { value: 'DEPOSIT',    label: 'Depósito'  },
      { value: 'WITHDRAWAL', label: 'Retiro'    },
      { value: 'PURCHASE',   label: 'Compra'    },
      { value: 'PRIZE',      label: 'Premio'    },
    ],
  },
  {
    key: 'status',
    label: 'Estado',
    placeholder: 'Todos los estados',
    options: [
      { value: 'PENDING',   label: 'Pendiente'  },
      { value: 'COMPLETED', label: 'Completado' },
      { value: 'CANCELLED', label: 'Cancelado'  },
      { value: 'REJECTED',  label: 'Rechazado'  },
    ],
  },
]

// ── Fetch server-side ──────────────────────────────────────────────────────
async function fetchMovements(page = 1) {
  loading.value = true
  try {
    const { data } = await backendClient.get('/wallet-movements/fetch', {
      params: {
        page,
        per_page: 20,
        type:     lastFilters.value.type   || undefined,
        status:   lastFilters.value.status || undefined,
        search:   lastSearch.value         || undefined,
      },
    })
    movements.value = data.data ?? []
    meta.value      = data.meta ?? null
  } catch (e) {
    console.error('[movements] Error cargando movimientos:', e)
    movements.value = []
  } finally {
    loading.value = false
  }
}

// Recibe el evento @filter de TableFilter (ya incluye debounce)
function onFilter(payload: { search: string; filters: Record<string, string> }) {
  lastSearch.value  = payload.search
  lastFilters.value = payload.filters
  fetchMovements(1)
}

// ── Helpers visuales ───────────────────────────────────────────────────────
const typeConfig: Record<string, { label: string; cls: string; icon: string }> = {
  DEPOSIT:    { label: 'Depósito', cls: 'mv-badge mv-badge--deposit',    icon: 'fas fa-arrow-down'  },
  WITHDRAWAL: { label: 'Retiro',   cls: 'mv-badge mv-badge--withdrawal', icon: 'fas fa-arrow-up'    },
  PURCHASE:   { label: 'Compra',   cls: 'mv-badge mv-badge--purchase',   icon: 'fas fa-ticket-alt'  },
  PRIZE:      { label: 'Premio',   cls: 'mv-badge mv-badge--prize',      icon: 'fas fa-trophy'      },
}

const statusConfig: Record<string, { label: string; cls: string }> = {
  PENDING:   { label: 'Pendiente',  cls: 'mv-status mv-status--pending'   },
  COMPLETED: { label: 'Completado', cls: 'mv-status mv-status--completed' },
  CANCELLED: { label: 'Cancelado',  cls: 'mv-status mv-status--cancelled' },
  REJECTED:  { label: 'Rechazado',  cls: 'mv-status mv-status--rejected'  },
}

function userName(mov: Movement): string {
  if (!mov.user) return '—'
  const full = [mov.user.first_name, mov.user.last_name].filter(Boolean).join(' ')
  return full || mov.user.username || '—'
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

const totalPages  = computed(() => meta.value?.last_page    ?? 1)
const currentPage = computed(() => meta.value?.current_page ?? 1)

onMounted(() => fetchMovements())
</script>

<template>
  <Head :title="title" />

  <div class="container-fluid">

    <!-- Card principal -->
    <div class="card">
      <CardHeader title="Movimientos de Billetera" subtitle="Historial de depósitos, retiros y compras" />

      <div class="card-body">

        <!-- Filtros — usa TableFilter del sistema (debounce + pills incluidos) -->
        <TableFilter
          search-placeholder="Buscar por nombre, apellido o email..."
          :filters="tableFilters"
          :debounce-delay="400"
          @filter="onFilter"
        />

        <!-- Loading -->
        <div v-if="loading" class="mv-loading">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div>
          Cargando movimientos...
        </div>

        <!-- Tabla -->
        <BaseTable
          v-else
          table-id="wallet-movements"
          :columns="columns"
          :rows="movements"
          empty-message="No hay movimientos registrados"
          class="mt-3"
        >
          <!-- Usuario -->
          <template #cell-user="{ row }">
            <div v-if="(row as Movement).user" class="mv-user">
              <span class="mv-user__name">{{ userName(row as Movement) }}</span>
              <span class="mv-user__email">{{ (row as Movement).user!.email }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>

          <!-- Tipo -->
          <template #cell-type="{ row }">
            <span :class="typeConfig[(row as Movement).type]?.cls ?? 'mv-badge'">
              <i :class="typeConfig[(row as Movement).type]?.icon"></i>
              {{ typeConfig[(row as Movement).type]?.label ?? (row as Movement).type }}
            </span>
          </template>

          <!-- Monto -->
          <template #cell-amount="{ row }">
            <span
              class="mv-amount"
              :class="['DEPOSIT','PRIZE'].includes((row as Movement).type) ? 'mv-amount--positive' : 'mv-amount--negative'"
            >
              {{ formatAmount(row as Movement) }}
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
            <a
              :href="`/wallet-movements/${row.id}`"
              class="btn btn-sm btn-outline-info"
              title="Ver detalle"
            >
              <i class="fas fa-eye"></i>
            </a>
          </template>
        </BaseTable>

        <!-- Footer info -->
        <TableFooterInfo
          :filtered-count="movements.length"
          :total-count="meta?.total ?? 0"
        />

        <!-- Paginación -->
        <div v-if="meta && totalPages > 1" class="mv-pagination">
          <span class="mv-pagination__info">
            Página {{ currentPage }} de {{ totalPages }}
            <span class="text-muted">· {{ meta.total }} movimientos</span>
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
              v-for="p in totalPages"
              :key="p"
              class="btn btn-sm"
              :class="p === currentPage ? 'btn-primary' : 'btn-outline-secondary'"
              :disabled="loading"
              @click="fetchMovements(p)"
            >
              {{ p }}
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
.mv-user        { display: flex; flex-direction: column; gap: 1px; }
.mv-user__name  { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.mv-user__email { font-size: 0.72rem; color: #94a3b8; }

/* Tipo badge */
.mv-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
  white-space: nowrap;
}
.mv-badge--deposit    { background: #dcfce7; color: #16a34a; }
.mv-badge--withdrawal { background: #fef3c7; color: #d97706; }
.mv-badge--purchase   { background: #ffe4e6; color: #e11d48; }
.mv-badge--prize      { background: #fef9c3; color: #ca8a04; }

/* Monto */
.mv-amount           { font-size: 0.875rem; font-weight: 700; white-space: nowrap; }
.mv-amount--positive { color: #16a34a; }
.mv-amount--negative { color: #dc2626; }

/* Moneda */
.mv-currency { font-size: 0.78rem; font-weight: 600; color: #64748b; }

/* Estado */
.mv-status {
  display: inline-flex;
  align-items: center;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  white-space: nowrap;
}
.mv-status--pending   { background: #fef3c7; color: #d97706; }
.mv-status--completed { background: #dcfce7; color: #16a34a; }
.mv-status--cancelled { background: #f1f5f9; color: #64748b; }
.mv-status--rejected  { background: #ffe4e6; color: #e11d48; }

/* Fecha */
.mv-date { font-size: 0.78rem; color: #64748b; white-space: nowrap; }

/* Paginación */
.mv-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-top: 1px solid #f1f5f9;
  margin-top: 0.5rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.mv-pagination__info { font-size: 0.78rem; color: #64748b; }
.mv-pagination__btns { display: flex; align-items: center; gap: 3px; }
.mv-pagination__btns .btn { min-width: 32px; }
</style>
