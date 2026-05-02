<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import CardHeader      from '@js/Components/Common/Card/CardHeader.vue'
import BaseTable       from '@js/Components/Common/Table/BaseTable.vue'
import TableFilter     from '@js/Components/Common/Table/TableFilter.vue'
import TableFooterInfo from '@js/Components/Common/Table/TableFooterInfo.vue'
import Modal           from '@js/Components/Common/Modal/Modal.vue'
import backendClient   from '@js/plugins/axios'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'

interface Props { title: string }
defineProps<Props>()

// ── Tipos ──────────────────────────────────────────────────────────────────
interface MovementUser {
  id:         string
  first_name: string | null
  last_name:  string | null
  email:      string
  username:   string | null
}

interface MovementAdmin {
  id:         string
  first_name: string | null
  last_name:  string | null
  email:      string
}

interface MovementCurrency {
  iso_code:       string
  symbol:         string
  decimal_places: number
}

interface MovementMetadata {
  directory_id?:   string
  directory_type?: string
  [key: string]:   unknown
}

interface Movement {
  id:             string
  type:           'DEPOSIT' | 'WITHDRAWAL' | 'PURCHASE' | 'PRIZE'
  status:         'PENDING' | 'COMPLETED' | 'CANCELLED' | 'REJECTED'
  amount:         number
  balance_before: number
  balance_after:  number
  description:    string | null
  reference_id:   string | null
  receipt_path:   string | null
  receipt_url:    string | null
  metadata:       MovementMetadata | null
  admin_notes:    string | null
  processed_at:   string | null
  processed_by:   MovementAdmin | null
  created_at:     string
  currency:       MovementCurrency | null
  user:           MovementUser | null
}

interface Meta {
  current_page: number
  last_page:    number
  per_page:     number
  total:        number
}

// ── Estado lista ───────────────────────────────────────────────────────────
const movements   = ref<Movement[]>([])
const meta        = ref<Meta | null>(null)
const loading     = ref(false)
const lastSearch  = ref('')
const lastFilters = ref<Record<string, string>>({})

// ── Estado modal detalle ───────────────────────────────────────────────────
const showDetail    = ref(false)
const detailLoading = ref(false)
const detail        = ref<Movement | null>(null)
const adminNotes    = ref('')
const actionLoading = ref(false)
const actionError   = ref('')
const showRejectForm = ref(false)
const receiptZoom   = ref(false)

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

// ── Filtros para TableFilter ───────────────────────────────────────────────
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

// ── Fetch lista ────────────────────────────────────────────────────────────
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

function onFilter(payload: { search: string; filters: Record<string, string> }) {
  lastSearch.value  = payload.search
  lastFilters.value = payload.filters
  fetchMovements(1)
}

// ── Fetch detalle (modal) ──────────────────────────────────────────────────
async function openDetail(id: string) {
  detail.value        = null
  showDetail.value    = true
  detailLoading.value = true
  adminNotes.value    = ''
  actionError.value   = ''
  showRejectForm.value = false
  receiptZoom.value   = false
  try {
    const { data } = await backendClient.get(`/wallet-movements/${id}`)
    detail.value = data.data ?? null
  } catch (e) {
    console.error('[movements] Error cargando detalle:', e)
    showDetail.value = false
  } finally {
    detailLoading.value = false
  }
}

// ── Acciones admin ─────────────────────────────────────────────────────────
async function handleApprove() {
  if (!detail.value) return
  actionError.value   = ''
  actionLoading.value = true
  try {
    const { data } = await backendClient.post(`/wallet-movements/${detail.value.id}/approve`, {
      admin_notes: adminNotes.value || null,
    })
    detail.value = data.data
    // Actualizar el item en la lista
    const idx = movements.value.findIndex(m => m.id === detail.value!.id)
    if (idx !== -1) movements.value[idx] = data.data
    adminNotes.value = ''
  } catch (e: any) {
    actionError.value = e?.response?.data?.message ?? 'Error al aprobar el movimiento.'
  } finally {
    actionLoading.value = false
  }
}

async function handleReject() {
  if (!detail.value) return
  actionError.value   = ''
  actionLoading.value = true
  try {
    const { data } = await backendClient.post(`/wallet-movements/${detail.value.id}/reject`, {
      admin_notes: adminNotes.value || null,
    })
    detail.value = data.data
    const idx = movements.value.findIndex(m => m.id === detail.value!.id)
    if (idx !== -1) movements.value[idx] = data.data
    adminNotes.value    = ''
    showRejectForm.value = false
  } catch (e: any) {
    actionError.value = e?.response?.data?.message ?? 'Error al rechazar el movimiento.'
  } finally {
    actionLoading.value = false
  }
}

// ── Helpers visuales ───────────────────────────────────────────────────────
const typeConfig: Record<string, { label: string; cls: string; icon: string }> = {
  DEPOSIT:      { label: 'Depósito',       cls: 'mv-badge mv-badge--deposit',      icon: 'fas fa-arrow-down'  },
  WITHDRAWAL:   { label: 'Retiro',         cls: 'mv-badge mv-badge--withdrawal',   icon: 'fas fa-arrow-up'    },
  PURCHASE:     { label: 'Compra',         cls: 'mv-badge mv-badge--purchase',     icon: 'fas fa-ticket-alt'  },
  PRIZE:        { label: 'Premio',         cls: 'mv-badge mv-badge--prize',        icon: 'fas fa-trophy'      },
  TRIVIA_ENTRY: { label: 'Entrada Trivia', cls: 'mv-badge mv-badge--trivia-entry', icon: 'fas fa-brain'       },
  TRIVIA_PRIZE: { label: 'Premio Trivia',  cls: 'mv-badge mv-badge--trivia-prize', icon: 'fas fa-brain'       },
}

const statusConfig: Record<string, { label: string; cls: string }> = {
  PENDING:   { label: 'Pendiente',  cls: 'mv-status mv-status--pending'   },
  COMPLETED: { label: 'Completado', cls: 'mv-status mv-status--completed' },
  CANCELLED: { label: 'Cancelado',  cls: 'mv-status mv-status--cancelled' },
  REJECTED:  { label: 'Rechazado',  cls: 'mv-status mv-status--rejected'  },
}

const directoryTypeLabel: Record<string, string> = {
  pago_movil: 'Pago Móvil',
  pix:        'PIX',
  binance:    'Binance',
}

function userName(u: MovementUser | null): string {
  if (!u) return '—'
  const full = [u.first_name, u.last_name].filter(Boolean).join(' ')
  return full || u.username || '—'
}

function adminName(u: MovementAdmin | null): string {
  if (!u) return '—'
  return [u.first_name, u.last_name].filter(Boolean).join(' ') || u.email
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

const isReceiptImage = computed(() => {
  if (!detail.value?.receipt_path) return false
  return /\.(jpg|jpeg|png|webp|gif)$/i.test(detail.value.receipt_path)
})

const isPending    = computed(() => detail.value?.status === 'PENDING')
const isActionable = computed(() => isPending.value && (detail.value?.type === 'DEPOSIT' || detail.value?.type === 'WITHDRAWAL'))

const totalPages  = computed(() => meta.value?.last_page    ?? 1)
const currentPage = computed(() => meta.value?.current_page ?? 1)

onMounted(() => fetchMovements())
</script>

<template>
  <Head :title="title" />

  <div class="container-fluid">

    <div class="card">
      <CardHeader title="Movimientos de Billetera" subtitle="Historial de depósitos, retiros y compras" />

      <div class="card-body">

        <TableFilter
          search-placeholder="Buscar por nombre, apellido o email..."
          :filters="tableFilters"
          :debounce-delay="400"
          @filter="onFilter"
        />

        <div v-if="loading" class="mv-loading">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div>
          Cargando movimientos...
        </div>

        <BaseTable
          v-else
          table-id="wallet-movements"
          :columns="columns"
          :rows="movements"
          empty-message="No hay movimientos registrados"
          class="mt-3"
        >
          <template #cell-user="{ row }">
            <div v-if="(row as Movement).user" class="mv-user">
              <span class="mv-user__name">{{ userName((row as Movement).user) }}</span>
              <span class="mv-user__email">{{ (row as Movement).user!.email }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>

          <template #cell-type="{ row }">
            <span :class="typeConfig[(row as Movement).type]?.cls ?? 'mv-badge'">
              <i :class="typeConfig[(row as Movement).type]?.icon"></i>
              {{ typeConfig[(row as Movement).type]?.label ?? (row as Movement).type }}
            </span>
          </template>

          <template #cell-amount="{ row }">
            <span
              class="mv-amount"
              :class="['DEPOSIT','PRIZE'].includes((row as Movement).type) ? 'mv-amount--positive' : 'mv-amount--negative'"
            >
              {{ formatAmount(row as Movement) }}
            </span>
          </template>

          <template #cell-currency="{ row }">
            <span v-if="row.currency" class="mv-currency">
              {{ row.currency.symbol }} {{ row.currency.iso_code }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>

          <template #cell-status="{ row }">
            <span :class="statusConfig[row.status]?.cls ?? 'mv-status'">
              {{ statusConfig[row.status]?.label ?? row.status }}
            </span>
          </template>

          <template #cell-created_at="{ row }">
            <span class="mv-date">{{ formatDate(row.created_at) }}</span>
          </template>

          <template #cell-actions="{ row }">
            <button
              class="btn btn-sm btn-outline-info"
              title="Ver detalle"
              @click="openDetail(row.id)"
            >
              <i class="fas fa-eye"></i>
            </button>
          </template>
        </BaseTable>

        <TableFooterInfo
          :filtered-count="movements.length"
          :total-count="meta?.total ?? 0"
        />

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

  <!-- ── Modal detalle ─────────────────────────────────────────────────── -->
  <Modal
    v-model="showDetail"
    title="Detalle del movimiento"
    icon="fa-exchange-alt"
    size="lg"
  >
    <!-- Loading -->
    <div v-if="detailLoading" class="mv-modal-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando...
    </div>

    <!-- Contenido -->
    <div v-else-if="detail" class="mv-detail">

      <!-- ── Alerta de acción requerida ──────────────────────────────── -->
      <div v-if="isActionable" class="mv-action-banner">
        <i class="fas fa-clock me-2"></i>
        Este movimiento está <strong>pendiente</strong> de revisión.
        <template v-if="detail.type === 'WITHDRAWAL'">
          El saldo ya fue descontado del usuario.
        </template>
      </div>

      <!-- ── Fila: Usuario + Tipo + Estado ─────────────────────────── -->
      <div class="mv-detail__row mv-detail__row--3">
        <div class="mv-detail__section">
          <p class="mv-detail__label">Usuario</p>
          <p class="mv-detail__value">{{ userName(detail.user) }}</p>
          <p v-if="detail.user" class="mv-detail__sub">{{ detail.user.email }}</p>
        </div>
        <div class="mv-detail__section">
          <p class="mv-detail__label">Tipo</p>
          <span :class="typeConfig[detail.type]?.cls ?? 'mv-badge'">
            <i :class="typeConfig[detail.type]?.icon"></i>
            {{ typeConfig[detail.type]?.label ?? detail.type }}
          </span>
        </div>
        <div class="mv-detail__section">
          <p class="mv-detail__label">Estado</p>
          <span :class="statusConfig[detail.status]?.cls ?? 'mv-status'">
            {{ statusConfig[detail.status]?.label ?? detail.status }}
          </span>
        </div>
      </div>

      <div class="mv-detail__divider" />

      <!-- ── Montos ──────────────────────────────────────────────────── -->
      <div class="mv-detail__row">
        <div class="mv-detail__section">
          <p class="mv-detail__label">Monto</p>
          <p
            class="mv-detail__amount"
            :class="['DEPOSIT','PRIZE'].includes(detail.type) ? 'mv-amount--positive' : 'mv-amount--negative'"
          >
            {{ formatAmount(detail) }}
          </p>
        </div>
        <div class="mv-detail__section">
          <p class="mv-detail__label">Moneda</p>
          <p class="mv-detail__value">
            {{ detail.currency ? `${detail.currency.symbol} ${detail.currency.iso_code}` : '—' }}
          </p>
        </div>
        <div class="mv-detail__section">
          <p class="mv-detail__label">Saldo antes / después</p>
          <p class="mv-detail__value mv-detail__value--mono">
            {{ detail.currency?.symbol }}
            {{ detail.balance_before.toLocaleString('es-VE', { minimumFractionDigits: detail.currency?.decimal_places ?? 2 }) }}
            →
            {{ detail.balance_after.toLocaleString('es-VE', { minimumFractionDigits: detail.currency?.decimal_places ?? 2 }) }}
          </p>
        </div>
      </div>

      <div class="mv-detail__divider" />

      <!-- ── Comprobante (DEPOSIT) ───────────────────────────────────── -->
      <template v-if="detail.type === 'DEPOSIT'">
        <div class="mv-detail__section">
          <p class="mv-detail__label">Comprobante / Baucher</p>
          <div v-if="detail.receipt_url" class="mv-receipt">
            <!-- Imagen -->
            <template v-if="isReceiptImage">
              <div
                class="mv-receipt__img-wrap"
                :class="{ 'mv-receipt__img-wrap--zoomed': receiptZoom }"
                @click="receiptZoom = !receiptZoom"
                title="Clic para ampliar"
              >
                <img :src="detail.receipt_url" alt="Comprobante" class="mv-receipt__img" />
                <span class="mv-receipt__zoom-hint">
                  <i :class="receiptZoom ? 'fas fa-compress-alt' : 'fas fa-expand-alt'"></i>
                </span>
              </div>
            </template>
            <!-- PDF u otro -->
            <template v-else>
              <div class="mv-receipt__file">
                <i class="fas fa-file-pdf mv-receipt__file-icon"></i>
                <div>
                  <p class="mv-receipt__file-name">{{ detail.receipt_path?.split('/').pop() }}</p>
                  <a :href="detail.receipt_url" target="_blank" class="mv-receipt__file-link">
                    <i class="fas fa-external-link-alt me-1"></i> Abrir archivo
                  </a>
                </div>
              </div>
            </template>
          </div>
          <p v-else class="mv-detail__value text-muted">Sin comprobante adjunto</p>
        </div>

        <!-- Referencia del usuario -->
        <div v-if="detail.reference_id" class="mv-detail__section mt-2">
          <p class="mv-detail__label">Número de referencia</p>
          <p class="mv-detail__value mv-detail__value--mono">{{ detail.reference_id }}</p>
        </div>

        <div class="mv-detail__divider" />
      </template>

      <!-- ── Directorio destino (WITHDRAWAL) ───────────────────────── -->
      <template v-if="detail.type === 'WITHDRAWAL' && detail.metadata">
        <div class="mv-detail__section">
          <p class="mv-detail__label">Directorio destino</p>
          <div class="mv-dir-info">
            <span class="mv-dir-info__type">
              <i class="fas fa-wallet me-1"></i>
              {{ directoryTypeLabel[detail.metadata.directory_type ?? ''] ?? detail.metadata.directory_type }}
            </span>
            <span class="mv-dir-info__id mv-detail__value--mono">
              ID: {{ detail.metadata.directory_id }}
            </span>
          </div>
        </div>
        <div class="mv-detail__divider" />
      </template>

      <!-- ── Descripción ─────────────────────────────────────────────── -->
      <div v-if="detail.description" class="mv-detail__section">
        <p class="mv-detail__label">Descripción</p>
        <p class="mv-detail__value">{{ detail.description }}</p>
        <div class="mv-detail__divider" />
      </div>

      <!-- ── Procesado por ───────────────────────────────────────────── -->
      <template v-if="detail.processed_by">
        <div class="mv-detail__row">
          <div class="mv-detail__section">
            <p class="mv-detail__label">Procesado por</p>
            <p class="mv-detail__value">{{ adminName(detail.processed_by) }}</p>
            <p class="mv-detail__sub">{{ detail.processed_by.email }}</p>
          </div>
          <div class="mv-detail__section">
            <p class="mv-detail__label">Procesado el</p>
            <p class="mv-detail__value">{{ detail.processed_at ? formatDate(detail.processed_at) : '—' }}</p>
          </div>
        </div>
        <div v-if="detail.admin_notes" class="mv-detail__section mt-2">
          <p class="mv-detail__label">Notas del administrador</p>
          <p class="mv-detail__value mv-admin-notes">{{ detail.admin_notes }}</p>
        </div>
        <div class="mv-detail__divider" />
      </template>

      <!-- ── Fecha ───────────────────────────────────────────────────── -->
      <div class="mv-detail__section">
        <p class="mv-detail__label">Fecha de solicitud</p>
        <p class="mv-detail__value">{{ formatDate(detail.created_at) }}</p>
      </div>

      <!-- ── Acciones admin (solo PENDING DEPOSIT o WITHDRAWAL) ────── -->
      <template v-if="isActionable">
        <div class="mv-detail__divider" />
        <div class="mv-actions">
          <p class="mv-detail__label mb-2">Acciones de administración</p>

          <div v-if="actionError" class="alert alert-danger py-2 px-3 mb-2 small">
            <i class="fas fa-exclamation-circle me-1"></i> {{ actionError }}
          </div>

          <!-- Notas admin -->
          <div class="mb-2">
            <label class="mv-detail__label mb-1">Notas (opcional)</label>
            <textarea
              v-model="adminNotes"
              class="form-control form-control-sm"
              rows="2"
              placeholder="Motivo de aprobación o rechazo..."
              :disabled="actionLoading"
            ></textarea>
          </div>

          <!-- Botones -->
          <div v-if="!showRejectForm" class="mv-actions__btns">
            <button
              class="btn btn-success btn-sm"
              :disabled="actionLoading"
              @click="handleApprove"
            >
              <span v-if="actionLoading" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="fas fa-check me-1"></i>
              Aprobar
            </button>
            <button
              class="btn btn-danger btn-sm"
              :disabled="actionLoading"
              @click="showRejectForm = true"
            >
              <i class="fas fa-times me-1"></i>
              Rechazar
            </button>
          </div>

          <!-- Confirmar rechazo -->
          <div v-else class="mv-reject-confirm">
            <p class="mv-reject-confirm__text">
              <i class="fas fa-exclamation-triangle text-warning me-1"></i>
              <template v-if="detail.type === 'WITHDRAWAL'">
                ¿Rechazar el retiro? El saldo será <strong>devuelto</strong> al usuario.
              </template>
              <template v-else>
                ¿Rechazar el depósito? No se acreditará ningún monto.
              </template>
            </p>
            <div class="mv-actions__btns">
              <button
                class="btn btn-danger btn-sm"
                :disabled="actionLoading"
                @click="handleReject"
              >
                <span v-if="actionLoading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="fas fa-times me-1"></i>
                Confirmar rechazo
              </button>
              <button
                class="btn btn-outline-secondary btn-sm"
                :disabled="actionLoading"
                @click="showRejectForm = false"
              >
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </template>

    </div>
  </Modal>
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
.mv-badge--deposit      { background: #dcfce7; color: #16a34a; }
.mv-badge--withdrawal   { background: #fef3c7; color: #d97706; }
.mv-badge--purchase     { background: #ffe4e6; color: #e11d48; }
.mv-badge--prize        { background: #fef9c3; color: #ca8a04; }
.mv-badge--trivia-entry { background: #ede9fe; color: #7c3aed; }
.mv-badge--trivia-prize { background: #ede9fe; color: #7c3aed; }

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

/* ── Modal detalle ───────────────────────────────────────────────────────── */
.mv-modal-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6c757d;
  font-size: 0.85rem;
}

.mv-detail { display: flex; flex-direction: column; gap: 0; }

.mv-detail__divider {
  height: 1px;
  background: #f1f5f9;
  margin: 0.75rem 0;
}

.mv-detail__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.mv-detail__row--3 {
  grid-template-columns: 1fr 1fr 1fr;
}

.mv-detail__section { display: flex; flex-direction: column; gap: 2px; }

.mv-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
  margin: 0;
}

.mv-detail__value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #1e293b;
  margin: 0;
}

.mv-detail__value--mono {
  font-family: ui-monospace, monospace;
  font-size: 0.82rem;
}

.mv-detail__sub {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 0;
}

.mv-detail__amount {
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0;
}

/* Banner acción requerida */
.mv-action-banner {
  background: #fffbeb;
  border: 1px solid #fbbf24;
  border-radius: 8px;
  padding: 0.6rem 0.9rem;
  font-size: 0.82rem;
  color: #92400e;
  margin-bottom: 0.75rem;
}

/* Comprobante */
.mv-receipt { margin-top: 6px; }

.mv-receipt__img-wrap {
  position: relative;
  display: inline-block;
  max-width: 100%;
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #e2e8f0;
  transition: border-color 0.2s;
}
.mv-receipt__img-wrap:hover { border-color: #667eea; }
.mv-receipt__img-wrap--zoomed { max-width: 100%; width: 100%; }

.mv-receipt__img {
  display: block;
  max-width: 280px;
  max-height: 220px;
  object-fit: contain;
  background: #f8fafc;
  transition: max-width 0.3s, max-height 0.3s;
}
.mv-receipt__img-wrap--zoomed .mv-receipt__img {
  max-width: 100%;
  max-height: 500px;
}

.mv-receipt__zoom-hint {
  position: absolute;
  bottom: 6px;
  right: 6px;
  background: rgba(0,0,0,0.5);
  color: #fff;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 0.7rem;
}

.mv-receipt__file {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.9rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.mv-receipt__file-icon { font-size: 1.8rem; color: #e11d48; }
.mv-receipt__file-name { font-size: 0.8rem; font-weight: 500; color: #1e293b; margin: 0; }
.mv-receipt__file-link { font-size: 0.75rem; color: #667eea; text-decoration: none; }
.mv-receipt__file-link:hover { text-decoration: underline; }

/* Directorio info */
.mv-dir-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-top: 4px;
}
.mv-dir-info__type {
  display: inline-flex;
  align-items: center;
  font-size: 0.78rem;
  font-weight: 600;
  color: #667eea;
}
.mv-dir-info__id {
  font-size: 0.75rem;
  color: #64748b;
  font-family: ui-monospace, monospace;
}

/* Admin notes */
.mv-admin-notes {
  background: #f8fafc;
  border-left: 3px solid #94a3b8;
  padding: 0.4rem 0.6rem;
  border-radius: 0 6px 6px 0;
  font-style: italic;
  color: #475569;
}

/* Acciones */
.mv-actions {
  background: #f8fafc;
  border-radius: 10px;
  padding: 1rem;
  border: 1px dashed #cbd5e1;
}

.mv-actions__btns {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.mv-reject-confirm {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mv-reject-confirm__text {
  font-size: 0.85rem;
  color: #475569;
  margin: 0;
  background: #fffbeb;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #fbbf24;
}

.mt-2 { margin-top: 0.5rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-1 { margin-bottom: 0.25rem; }
.me-1 { margin-right: 0.25rem; }
.me-2 { margin-right: 0.5rem; }
</style>
