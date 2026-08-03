<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
import MovementDetailModal from '@js/Components/Wallet/Admin/MovementDetailModal.vue'
import { useWalletMovementsList } from '@js/composables/wallet/admin/useWalletMovementsList'
import { useWalletMovementDetail } from '@js/composables/wallet/admin/useWalletMovementDetail'
import { useMovementFormatters } from '@js/composables/wallet/admin/useMovementFormatters'
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

// ── Lista + filtros + paginación (server-side) + tiempo real ───────────────
const {
  movements, meta, loading,
  columns, tableFilters,
  onQueryChange, updateMovement,
} = useWalletMovementsList(
  props.movements,
  props.meta,
  computed(() => props.movementTypes),
  props.initialPendingCount,
)

// ── Modal de detalle + acciones admin ───────────────────────────────────────
const {
  showDetail, detailLoading, detail,
  adminNotes, actionLoading, actionError, showRejectForm, receiptZoom,
  openDetail, handleApprove, handleReject,
  isReceiptImage, isPending, isActionable,
} = useWalletMovementDetail(updateMovement, computed(() => props.movementTypes))

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
    @update:receipt-zoom="receiptZoom = $event"
    @update:admin-notes="adminNotes = $event"
    @update:show-reject-form="showRejectForm = $event"
    @approve="handleApprove"
    @reject="handleReject"
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

.mv-date { font-size: 0.78rem; color: var(--ds-text-2); white-space: nowrap; }
</style>
