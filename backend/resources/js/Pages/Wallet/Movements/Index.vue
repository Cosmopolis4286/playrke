<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
import MovementDetailModal from '@js/Components/Wallet/Admin/MovementDetailModal.vue'
import { useWalletMovementsList } from '@js/composables/wallet/admin/useWalletMovementsList'
import { useWalletMovementDetail } from '@js/composables/wallet/admin/useWalletMovementDetail'
import { useWalletMovementsStats } from '@js/composables/wallet/admin/useWalletMovementsStats'
import { useWalletMovementsBulk } from '@js/composables/wallet/admin/useWalletMovementsBulk'
import { useMovementAssignment } from '@js/composables/wallet/admin/useMovementAssignment'
import { useMovementFormatters, ageInfo, resolutionTimeInfo } from '@js/composables/wallet/admin/useMovementFormatters'
import type { MovementType } from '@js/types/movementType'
import type { Movement, MovementsMeta } from '@js/types/wallet/admin-movement'

interface Props {
  title:              string
  movements:          Movement[]
  movementTypes:      MovementType[]
  meta:               MovementsMeta
  initialPendingCount: number
}
const props = defineProps<Props>()

const page = usePage()
const currentUserId = computed(() => String((page.props.auth as any)?.user?.id ?? ''))

// ── Lista + filtros + paginación (server-side) + tiempo real ───────────────
const {
  movements, meta, loading,
  columns, tableFilters,
  onQueryChange, updateMovement, fetchMovements,
  dateFrom, dateTo, applyDateRange, exportUrl, activeParams,
} = useWalletMovementsList(
  props.movements,
  props.meta,
  computed(() => props.movementTypes),
  props.initialPendingCount,
)

// ── KPIs del header — se refrescan cuando cambian los filtros activos
// (tipo/estado/búsqueda/fecha) y tras cada aprobar/rechazar, para reflejar
// siempre el mismo conjunto que ve el admin en la tabla.
const { stats, fetchStats } = useWalletMovementsStats()

function refreshStats() {
  fetchStats(activeParams.value)
}

watch(activeParams, refreshStats, { immediate: true, deep: true })

// ── Modal de detalle + acciones admin ───────────────────────────────────────
function handleMovementUpdated(updated: Movement) {
  updateMovement(updated)
  refreshStats()
}

const {
  showDetail, detailLoading, detail,
  adminNotes, actionLoading, actionError, showRejectForm, receiptZoom,
  openDetail, handleApprove, handleReject,
  isReceiptImage, isPending, isActionable,
  nextPendingId,
} = useWalletMovementDetail(handleMovementUpdated, computed(() => props.movementTypes), movements)

// ── Tomar/liberar caso desde el modal de detalle ────────────────────────────
function handleAssignmentUpdated(updated: Movement) {
  detail.value = updated
  updateMovement(updated)
}

const { assignLoading, assignToMe, unassign } = useMovementAssignment(handleAssignmentUpdated)

// ── Aprobación/rechazo en lote desde la selección de la tabla ──────────────
function handleBulkProcessed() {
  fetchMovements(meta.value?.current_page ?? 1)
  refreshStats()
}

const { bulkLoading, bulkApprove, bulkReject } = useWalletMovementsBulk(handleBulkProcessed)

function selectedMovements(ids: string[]): Movement[] {
  return movements.value.filter(m => ids.includes(m.id))
}

// ── Atajos de teclado en el modal: A aprobar, R abrir rechazo, Esc cerrar ──
// Solo activos con el modal abierto y sin foco en un campo de texto (notas),
// para no interceptar la letra 'a'/'r' mientras el admin escribe.
function handleShortcut(e: KeyboardEvent) {
  if (!showDetail.value || !isActionable.value) return
  const tag = (e.target as HTMLElement)?.tagName
  if (tag === 'TEXTAREA' || tag === 'INPUT') return

  if (e.key === 'a' || e.key === 'A') {
    if (!showRejectForm.value && !actionLoading.value) handleApprove()
  } else if (e.key === 'r' || e.key === 'R') {
    if (!showRejectForm.value) showRejectForm.value = true
  } else if (e.key === 'Escape' && showRejectForm.value) {
    showRejectForm.value = false
  }
}

onMounted(() => document.addEventListener('keydown', handleShortcut))
onBeforeUnmount(() => document.removeEventListener('keydown', handleShortcut))

// ── Deep-link desde notificación: /wallet-movements?movement={id} ──────────
function openMovementFromQuery() {
  const params     = new URLSearchParams(window.location.search)
  const movementId = params.get('movement')
  if (!movementId) return

  params.delete('movement')
  const newQuery = params.toString()
  const newUrl   = window.location.pathname + (newQuery ? `?${newQuery}` : '')
  window.history.replaceState(null, '', newUrl)

  openDetail(movementId)
}

onMounted(() => {
  openMovementFromQuery()
})

// ── Helpers visuales ─────────────────────────────────────────────────────────
const {
  typeConfig, isCreditByCode, prizeTypeConfig, statusConfig, directoryTypeLabel,
  userName, adminName, formatAmount, formatDate,
} = useMovementFormatters(computed(() => props.movementTypes))
</script>

<template>
  <Head :title="title" />

  <PageHeader
    icon="fa-money-bill-transfer"
    title="Movimientos de Billetera"
    subtitle="Historial de depósitos, retiros y compras — se actualiza en tiempo real."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Movimientos de Billetera' },
    ]"
  >
    <template #actions>
      <div v-if="stats" class="mv-stats">
        <StatTile
          label="Pendientes"
          :value="stats.pending_count"
          icon="fa-hourglass-half"
          :variant="stats.pending_count > 0 ? 'warning' : 'default'"
        />
        <StatTile label="Aprobados hoy" :value="stats.approved_today" icon="fa-check" variant="success" />
        <StatTile label="Rechazados hoy" :value="stats.rejected_today" icon="fa-xmark" variant="danger" />
        <StatTile label="Tasa de rechazo hoy" :value="`${stats.rejection_rate_today}%`" icon="fa-chart-line" variant="info" />
        <StatTile
          label="Resolución media (hoy)"
          :value="resolutionTimeInfo(stats.avg_resolution_minutes).label"
          icon="fa-stopwatch"
          :variant="resolutionTimeInfo(stats.avg_resolution_minutes).level === 'slow' ? 'danger' : 'default'"
        />
      </div>
    </template>
  </PageHeader>

  <div class="container-fluid">
    <TableWithFilterBridge
      table-id="wallet-movements"
      title="Movimientos de Billetera"
      subtitle="Historial de depósitos, retiros y compras — se actualiza en tiempo real"
      :columns="columns"
      :rows="movements"
      :filters="tableFilters"
      search-placeholder="Buscar por nombre, apellido, usuario o email..."
      :debounce-delay="400"
      empty-message="No hay movimientos registrados"
      :loading="loading"
      server-side
      :server-total-pages="meta?.last_page ?? 1"
      @query-change="onQueryChange"
    >
      <template #action-buttons="{ selectedIds }">
        <div class="mv-date-range">
          <input
            v-model="dateFrom"
            type="date"
            class="form-control form-control-sm"
            title="Desde"
            @change="applyDateRange"
          />
          <span class="mv-date-range__sep">—</span>
          <input
            v-model="dateTo"
            type="date"
            class="form-control form-control-sm"
            title="Hasta"
            @change="applyDateRange"
          />
        </div>
        <a class="btn btn-sm btn-outline-secondary" title="Exportar movimientos filtrados a CSV" :href="exportUrl()">
          <i class="fas fa-file-csv mr-1"></i>Exportar
        </a>
        <button
          v-if="selectedIds.length > 0"
          class="btn btn-sm btn-success"
          :disabled="bulkLoading"
          @click="bulkApprove(selectedMovements(selectedIds))"
        >
          <i class="fas fa-check mr-1"></i>Aprobar ({{ selectedIds.length }})
        </button>
        <button
          v-if="selectedIds.length > 0"
          class="btn btn-sm btn-danger"
          :disabled="bulkLoading"
          @click="bulkReject(selectedMovements(selectedIds))"
        >
          <i class="fas fa-times mr-1"></i>Rechazar ({{ selectedIds.length }})
        </button>
      </template>

      <template #cell-id="{ row }">
        <span class="mv-id">{{ String((row as Movement).id).slice(-8) }}</span>
      </template>

      <template #cell-user="{ row }">
        <div v-if="(row as Movement).user" class="mv-user">
          <span class="mv-user__name">{{ userName((row as Movement).user) }}</span>
          <span class="mv-user__email">{{ (row as Movement).user!.email }}</span>
        </div>
        <span v-else class="text-muted">—</span>
      </template>

      <template #cell-type="{ row }">
        <span
          :class="typeConfig[(row as Movement).type]?.cls ?? 'mv-badge'"
          :style="typeConfig[(row as Movement).type]?.style"
        >
          <i :class="typeConfig[(row as Movement).type]?.icon"></i>
          {{ typeConfig[(row as Movement).type]?.label ?? (row as Movement).type }}
        </span>
        <span
          v-if="['PRIZE', 'BONUS_PRIZE'].includes((row as Movement).type) && (row as Movement).prize_type"
          class="mv-badge mv-badge--prize-sub"
        >
          <i :class="prizeTypeConfig[(row as Movement).prize_type!].icon"></i>
          {{ prizeTypeConfig[(row as Movement).prize_type!].label }}
        </span>
      </template>

      <template #cell-amount="{ row }">
        <span
          class="mv-amount"
          :class="isCreditByCode[(row as Movement).type] ? 'mv-amount--positive' : 'mv-amount--negative'"
        >
          {{ formatAmount(row as Movement) }}
        </span>
      </template>

      <template #cell-currency="{ row }">
        <span v-if="(row as Movement).currency" class="mv-currency">
          {{ (row as Movement).currency!.symbol }} {{ (row as Movement).currency!.iso_code }}
        </span>
        <span v-else class="text-muted">—</span>
      </template>

      <template #cell-status="{ row }">
        <span :class="statusConfig[(row as Movement).status]?.cls ?? 'mv-status'">
          {{ statusConfig[(row as Movement).status]?.label ?? (row as Movement).status }}
        </span>
      </template>

      <template #cell-created_at="{ row }">
        <span class="mv-date">{{ formatDate((row as Movement).created_at) }}</span>
      </template>

      <template #cell-age="{ row }">
        <span
          v-if="(row as Movement).status === 'PENDING'"
          :class="ageInfo((row as Movement).created_at).cls"
        >
          <i :class="{
            'fas fa-circle-check': ageInfo((row as Movement).created_at).level === 'normal',
            'fas fa-clock':        ageInfo((row as Movement).created_at).level === 'warning',
            'fas fa-triangle-exclamation': ageInfo((row as Movement).created_at).level === 'critical',
          }"></i>
          {{ ageInfo((row as Movement).created_at).label }}
        </span>
        <span v-else class="text-muted">—</span>
      </template>

      <template #cell-assigned="{ row }">
        <span v-if="(row as Movement).assigned_to" class="mv-assigned" :title="`En revisión por ${adminName((row as Movement).assigned_to)}`">
          <i class="fas fa-user-clock"></i>
          {{ adminName((row as Movement).assigned_to) }}
        </span>
        <span v-else class="text-muted">—</span>
      </template>

      <template #cell-actions="{ row }">
        <button
          class="btn btn-sm btn-outline-info"
          title="Ver detalle"
          @click="openDetail(String((row as Movement).id))"
        >
          <i class="fas fa-eye"></i>
        </button>
      </template>
    </TableWithFilterBridge>
  </div>

  <!-- ── Modal detalle ─────────────────────────────────────────────────── -->
  <MovementDetailModal
    v-model="showDetail"
    :detail-loading="detailLoading"
    :detail="detail"
    :is-actionable="isActionable"
    :is-receipt-image="isReceiptImage"
    :receipt-zoom="receiptZoom"
    :admin-notes="adminNotes"
    :action-loading="actionLoading"
    :action-error="actionError"
    :show-reject-form="showRejectForm"
    :type-config="typeConfig"
    :prize-type-config="prizeTypeConfig"
    :status-config="statusConfig"
    :directory-type-label="directoryTypeLabel"
    :is-credit-by-code="isCreditByCode"
    :user-name="userName"
    :admin-name="adminName"
    :format-amount="formatAmount"
    :format-date="formatDate"
    :current-user-id="currentUserId"
    :assign-loading="assignLoading"
    @update:receipt-zoom="receiptZoom = $event"
    @update:admin-notes="adminNotes = $event"
    @update:show-reject-form="showRejectForm = $event"
    @approve="handleApprove"
    @reject="handleReject"
    @view-movement="openDetail"
    @assign="detail && assignToMe(detail.id)"
    @unassign="detail && unassign(detail.id)"
  />
</template>

<style scoped>
.mv-id {
  font-family: ui-monospace, monospace;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  background: var(--ds-surface-2);
  padding: 2px 6px;
  border-radius: 4px;
  white-space: nowrap;
}

.mv-user        { display: flex; flex-direction: column; gap: 1px; }
.mv-user__name  { font-size: 0.85rem; font-weight: 600; color: var(--ds-text); }
.mv-user__email { font-size: 0.72rem; color: var(--ds-text-muted); }

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
.mv-badge--deposit      { background: rgba(22,163,74,.16);  color: #4ade80; }
.mv-badge--withdrawal   { background: rgba(217,119,6,.18);  color: #fbbf24; }
.mv-badge--purchase     { background: rgba(225,29,72,.16);  color: #fb7185; }
.mv-badge--prize        { background: rgba(202,138,4,.18);  color: #facc15; }
.mv-badge--prize-sub    { background: var(--ds-surface-2);  color: var(--ds-text-2); margin-left: 6px; }
.mv-badge--trivia-entry { background: rgba(124,58,237,.18); color: #a78bfa; }

.mv-amount           { font-size: 0.875rem; font-weight: 700; white-space: nowrap; }
.mv-amount--positive { color: #4ade80; }
.mv-amount--negative { color: #f87171; }

.mv-currency { font-size: 0.78rem; font-weight: 600; color: var(--ds-text-2); }

.mv-status {
  display: inline-flex;
  align-items: center;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  white-space: nowrap;
}
.mv-status--pending   { background: rgba(217,119,6,.18); color: #fbbf24; }
.mv-status--completed { background: rgba(22,163,74,.16); color: #4ade80; }
.mv-status--cancelled { background: var(--ds-surface-2); color: var(--ds-text-2); }
.mv-status--rejected  { background: rgba(225,29,72,.16); color: #fb7185; }

.mv-date { display: block; font-size: 0.78rem; color: var(--ds-text-2); white-space: nowrap; }

.mv-age {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
  white-space: nowrap;
}
.mv-age--normal   { background: var(--ds-surface-2);  color: var(--ds-text-muted); }
.mv-age--warning  { background: rgba(217,119,6,.18);  color: #fbbf24; }
.mv-age--critical { background: rgba(220,38,38,.18);  color: #f87171; }

.mv-assigned {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #60a5fa;
  white-space: nowrap;
}

.mv-stats {
  display: grid;
  grid-template-columns: repeat(5, minmax(130px, 1fr));
  gap: 0.6rem;
}
@media (max-width: 1200px) {
  .mv-stats { grid-template-columns: repeat(3, minmax(130px, 1fr)); }
}
@media (max-width: 768px) {
  .mv-stats { grid-template-columns: repeat(2, minmax(130px, 1fr)); }
}

.mv-date-range {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-right: 0.5rem;
}
.mv-date-range input[type="date"] { width: 140px; }
.mv-date-range__sep { color: var(--ds-text-muted); font-size: 0.8rem; }

.mr-1 { margin-right: 0.25rem; }
</style>
