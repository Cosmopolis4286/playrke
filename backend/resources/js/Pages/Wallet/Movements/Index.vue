<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import CardHeader      from '@js/Components/Common/Card/CardHeader.vue'
import TableFilter     from '@js/Components/Common/Table/TableFilter.vue'
import TableFooterInfo from '@js/Components/Common/Table/TableFooterInfo.vue'
import MovementsTable      from '@js/Components/Wallet/Admin/MovementsTable.vue'
import MovementsPagination from '@js/Components/Wallet/Admin/MovementsPagination.vue'
import MovementDetailModal from '@js/Components/Wallet/Admin/MovementDetailModal.vue'
import { useWalletMovementsList } from '@js/composables/wallet/admin/useWalletMovementsList'
import { useWalletMovementDetail } from '@js/composables/wallet/admin/useWalletMovementDetail'
import { useMovementFormatters } from '@js/composables/wallet/admin/useMovementFormatters'
import type { MovementType } from '@js/types/movementType'
import type { Movement, MovementsMeta } from '@js/types/wallet/admin-movement'

interface Props {
  title:         string
  movements:     Movement[]
  movementTypes: MovementType[]
  meta:          MovementsMeta
}
const props = defineProps<Props>()

// ── Lista + filtros + paginación ────────────────────────────────────────────
const {
  movements, meta, loading,
  columns, tableFilters,
  fetchMovements, onFilter, updateMovement,
  totalPages, currentPage,
} = useWalletMovementsList(props.movements, props.meta, computed(() => props.movementTypes))

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

        <MovementsTable
          v-else
          :columns="columns"
          :movements="movements"
          :type-config="typeConfig"
          :prize-type-config="prizeTypeConfig"
          :status-config="statusConfig"
          :is-credit-by-code="isCreditByCode"
          :user-name="userName"
          :format-amount="formatAmount"
          :format-date="formatDate"
          @view="openDetail"
        />

        <TableFooterInfo
          :filtered-count="movements.length"
          :total-count="meta?.total ?? 0"
        />

        <MovementsPagination
          :meta="meta"
          :current-page="currentPage"
          :total-pages="totalPages"
          :loading="loading"
          @page-change="fetchMovements"
        />

      </div>
    </div>

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
.mv-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: var(--ds-text-2);
  font-size: 0.875rem;
}
</style>
