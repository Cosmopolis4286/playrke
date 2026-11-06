<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
import MovementDetailModal from '@js/Components/Wallet/Admin/MovementDetailModal.vue'
import { useWalletMovementsList } from '@js/composables/wallet/admin/useWalletMovementsList'
import { useWalletMovementDetail } from '@js/composables/wallet/admin/useWalletMovementDetail'
import { useWalletMovementsStats } from '@js/composables/wallet/admin/useWalletMovementsStats'
import { useWalletMovementsTrend } from '@js/composables/wallet/admin/useWalletMovementsTrend'
import DashboardMultiTrendChart from '@js/Components/Common/Dashboard/DashboardMultiTrendChart.vue'
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
  /** wallet.movements.manage — sin él la página es de solo lectura (sin aprobar/rechazar/tomar/lote). */
  canManage?:         boolean
}
const props = withDefaults(defineProps<Props>(), { canManage: false })

// Reloj de 60s para que las antigüedades relativas ("hace 3h", SLA del
// pendiente más antiguo) avancen solas — ageInfo() lee Date.now(), que no es
// reactivo: sin esto la cola mostraba "hace unos minutos" durante horas.
const nowTick = ref(Date.now())
let nowTimer: ReturnType<typeof setInterval> | undefined
onMounted(() => { nowTimer = setInterval(() => { nowTick.value = Date.now() }, 60_000) })
onBeforeUnmount(() => clearInterval(nowTimer))

const page = usePage()
const currentUserId = computed(() => String((page.props.auth as any)?.user?.id ?? ''))

// ── Banner de escalamiento crítico (>24h) en tiempo real ────────────────────
const criticalBacklogCount = ref(0)
function handleCriticalBacklog(payload: { critical_count: number }) {
  criticalBacklogCount.value = payload.critical_count
}

// ── Lista + filtros + paginación (server-side) + tiempo real ───────────────
const {
  movements, meta, loading, isLive,
  columns, tableFilters,
  onQueryChange, updateMovement, fetchMovements, searchByUser,
  dateFrom, dateTo, applyDateRange, exportUrl, activeParams
} = useWalletMovementsList(
  props.movements,
  props.meta,
  computed(() => props.movementTypes),
  props.initialPendingCount,
  handleCriticalBacklog,
  () => scheduleOverviewRefresh(),
)

// Eventos en vivo (nuevo pendiente / procesado por otro admin) → KPIs y
// tendencia también se actualizan; antes solo la tabla, y el "Pendientes"
// del resumen quedaba desfasado hasta cambiar un filtro. Debounce para que
// una ráfaga de eventos (p. ej. un lote de otro admin) haga 1 solo refetch.
let overviewTimer: ReturnType<typeof setTimeout> | undefined
function scheduleOverviewRefresh() {
  clearTimeout(overviewTimer)
  overviewTimer = setTimeout(() => { refreshStats(); refreshTrend() }, 1500)
}
onBeforeUnmount(() => clearTimeout(overviewTimer))

// ── KPIs del header — se refrescan cuando cambian los filtros activos
// (tipo/estado/búsqueda/fecha) y tras cada aprobar/rechazar, para reflejar
// siempre el mismo conjunto que ve el admin en la tabla.
const { stats, fetchStats } = useWalletMovementsStats()
const { trend, fetchTrend } = useWalletMovementsTrend()

function refreshStats() {
  fetchStats(activeParams.value)
}

function refreshTrend() {
  fetchTrend(activeParams.value)
}

watch(activeParams, () => { refreshStats(); refreshTrend() }, { immediate: true, deep: true })

/** Fecha sola (sin hora) para el título del gráfico — dateFrom/dateTo vienen de un <input type="date"> como "YYYY-MM-DD". */
function formatDateOnly(isoDate: string): string {
  return new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' })
    .format(new Date(`${isoDate}T00:00:00`))
}

// Título dinámico: refleja el rango real que está mostrando el gráfico — antes
// decía "últimos 30 días" siempre, aunque el backend ahora ajusta el eje al
// date_from/date_to activo en la tabla (ver WalletMovementRepository::historicalTrend()).
const trendTitle = computed(() => {
  if (dateFrom.value && dateTo.value) {
    return dateFrom.value === dateTo.value
      ? `Tendencia (${formatDateOnly(dateFrom.value)})`
      : `Tendencia (${formatDateOnly(dateFrom.value)} — ${formatDateOnly(dateTo.value)})`
  }
  if (dateFrom.value) return `Tendencia (desde ${formatDateOnly(dateFrom.value)})`
  if (dateTo.value) return `Tendencia (hasta ${formatDateOnly(dateTo.value)})`
  return 'Tendencia (últimos 30 días)'
})

const trendCategories = computed(() => trend.value.map(p => p.date))
const trendSeries = computed(() => [
  { name: 'Aprobados', data: trend.value.map(p => p.approved) },
  { name: 'Rechazados', data: trend.value.map(p => p.rejected) },
  { name: 'Resolución media (min)', data: trend.value.map(p => p.avg_resolution_minutes), yAxisIndex: 1 }
])

// ── Modal de detalle + acciones admin ───────────────────────────────────────
function handleMovementUpdated(updated: Movement) {
  updateMovement(updated)
  refreshStats()
  refreshTrend()
}

const {
  showDetail, detailLoading, detail,
  adminNotes, playerMessage, actionLoading, actionError, showRejectForm, receiptZoom,
  openDetail, handleApprove, handleReject,
  isReceiptImage, isPending, isActionable,
  nextPendingId, typeLabel, canSubmitReject
} = useWalletMovementDetail(handleMovementUpdated, computed(() => props.movementTypes), movements)

/** Acciones solo si el movimiento es procesable Y el admin tiene permiso de gestión. */
const canAct = computed(() => props.canManage && isActionable.value)

// ── SLA de la cola (tarjeta "Pendientes") ───────────────────────────────────
const oldestPending = computed(() => {
  nowTick.value // re-evalúa cada minuto
  return stats.value?.oldest_pending_at ? ageInfo(stats.value.oldest_pending_at) : null
})

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
  refreshTrend()
}

const { bulkLoading, bulkApprove, bulkReject } = useWalletMovementsBulk(handleBulkProcessed)

function selectedMovements(ids: string[]): Movement[] {
  return movements.value.filter(m => ids.includes(m.id))
}

// ── Export PDF (B.2) — mismos filtros activos que el CSV/stats/tabla ───────
function exportPdfUrl(): string {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(activeParams.value)) {
    if (value) params.set(key, value)
  }
  return `/wallet-movements/export-pdf?${params.toString()}`
}

// ── Atajos de teclado en el modal: A aprobar, R abrir rechazo, Esc cerrar ──
// Solo activos con el modal abierto y sin foco en un campo de texto (notas),
// para no interceptar la letra 'a'/'r' mientras el admin escribe.
function handleShortcut(e: KeyboardEvent) {
  if (!showDetail.value || !canAct.value) return
  // Con un SweetAlert abierto (confirmación de aprobar) no se re-disparan atajos.
  if (document.querySelector('.swal2-container')) return
  if (e.ctrlKey || e.metaKey || e.altKey) return
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
  userName, adminName, formatAmount, formatDate
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
      <span v-if="!canManage" class="mv-readonly" title="Tu perfil tiene wallet.movements.view sin wallet.movements.manage">
        <i class="fas fa-eye" />Solo lectura
      </span>
      <!-- Refleja el estado real del canal (antes era un badge fijo): si
           Reverb cae, el admin sabe que la cola puede estar desactualizada. -->
      <span
        class="mv-live"
        :class="{ 'mv-live--off': !isLive }"
        :title="isLive ? 'Lista y KPIs se actualizan en tiempo real' : 'Sin conexión en vivo — los datos se actualizan al filtrar o recargar'"
      >
        <span class="mv-live__dot" />{{ isLive ? 'En vivo' : 'Reconectando…' }}
      </span>
    </template>
  </PageHeader>

  <div class="container-fluid">
    <div v-if="criticalBacklogCount > 0" class="mv-critical-banner">
      <i class="fas fa-triangle-exclamation me-2" />
      {{ criticalBacklogCount }} movimiento(s) llevan más de 24h sin resolver — revisión urgente requerida.
      <button type="button" class="mv-critical-banner__dismiss" @click="criticalBacklogCount = 0">
        <i class="fas fa-times" />
      </button>
    </div>

    <!--
      Antes los KPIs vivían en el slot #actions del PageHeader: ese contenedor
      es flex-shrink:0 con ancho de contenido, así que el grid auto-fit no tenía
      ancho real y colapsaba a 1 columna (5 tiles apilados al lado del título).
      Ahora van en su propia fila, junto al gráfico: cola de revisión a la
      izquierda (lo accionable), tendencia a la derecha (el contexto).
    -->
    <div class="mv-overview">
      <section class="mv-queue">
        <div
          class="mv-queue__hero"
          :class="{ 'mv-queue__hero--active': (stats?.pending_count ?? 0) > 0 }"
        >
          <div class="mv-queue__hero-icon"><i class="fas fa-hourglass-half" /></div>
          <div class="mv-queue__hero-body">
            <span class="mv-queue__hero-label">Pendientes de revisión</span>
            <span class="mv-queue__hero-value">{{ stats?.pending_count ?? '—' }}</span>
            <span class="mv-queue__hero-hint">
              <template v-if="!stats">Cargando…</template>
              <template v-else-if="stats.pending_count === 0">Cola al día <i class="fas fa-circle-check" /></template>
              <template v-else-if="oldestPending">
                El más antiguo espera desde
                <strong :class="`mv-sla mv-sla--${oldestPending.level}`">{{ oldestPending.label.replace('hace ', '') }}</strong>
              </template>
              <template v-else>Esperando aprobación o rechazo</template>
            </span>
            <span v-if="stats && stats.critical_pending_count > 0" class="mv-queue__critical">
              <i class="fas fa-triangle-exclamation" />
              {{ stats.critical_pending_count }} con más de 24h
            </span>
          </div>
        </div>

        <div class="mv-queue__grid">
          <StatTile label="Aprobados hoy" :value="stats?.approved_today ?? '—'" icon="fa-check" variant="success" />
          <StatTile label="Rechazados hoy" :value="stats?.rejected_today ?? '—'" icon="fa-xmark" variant="danger" />
          <StatTile
            label="Tasa de rechazo"
            :value="stats ? `${stats.rejection_rate_today}%` : '—'"
            icon="fa-percent"
            variant="info"
          />
          <StatTile
            label="Resolución media"
            :value="stats ? resolutionTimeInfo(stats.avg_resolution_minutes).label : '—'"
            icon="fa-stopwatch"
            :variant="stats && resolutionTimeInfo(stats.avg_resolution_minutes).level === 'slow' ? 'danger' : 'default'"
          />
        </div>
        <p class="mv-queue__foot">Métricas de hoy · reflejan los filtros activos de la tabla</p>
      </section>

      <section class="mv-trend-card">
        <div class="mv-trend-card__header">
          <p class="mv-trend-card__title"><i class="fas fa-chart-line me-1" />{{ trendTitle }}</p>
          <span class="mv-trend-card__hint">
            <i class="fas fa-calendar-days me-1" />Rango según el filtro de fecha de la tabla
          </span>
        </div>
        <DashboardMultiTrendChart
          :categories="trendCategories"
          :series="trendSeries"
          secondary-axis-label="minutos"
        />
      </section>
    </div>

    <TableWithFilterBridge
      table-id="wallet-movements"
      title="Historial"
      subtitle="Depósitos, retiros, compras y premios"
      :columns="columns"
      :rows="movements"
      :filters="tableFilters"
      search-placeholder="Buscar por usuario, email, ID o referencia..."
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
            @change="applyDateRange">
          <span class="mv-date-range__sep">—</span>
          <input
            v-model="dateTo"
            type="date"
            class="form-control form-control-sm"
            title="Hasta"
            @change="applyDateRange">
          <button
            v-if="dateFrom || dateTo"
            type="button"
            class="btn btn-sm btn-outline-secondary"
            title="Quitar filtro de fecha"
            @click="dateFrom = ''; dateTo = ''; applyDateRange()"
          >
            <i class="fas fa-xmark" />
          </button>
        </div>
        <a class="btn btn-sm btn-outline-secondary" title="Exportar movimientos filtrados a CSV" :href="exportUrl()">
          <i class="fas fa-file-csv mr-1" />Exportar
        </a>
        <a class="btn btn-sm btn-outline-secondary" title="Exportar resumen en PDF" :href="exportPdfUrl()">
          <i class="fas fa-file-pdf mr-1" />PDF
        </a>
        <!-- Acciones sobre la selección: Ver detalle (1 fila) + Aprobar/Rechazar
             (1..N), todas en la misma zona en vez de una columna por fila. -->
        <template v-if="selectedIds.length > 0">
          <span class="mv-selection-sep" />
          <button
            class="btn btn-sm btn-outline-info"
            :disabled="selectedIds.length !== 1"
            :title="selectedIds.length === 1 ? 'Ver detalle del movimiento' : 'Selecciona un solo movimiento para ver su detalle'"
            @click="openDetail(selectedIds[0])"
          >
            <i class="fas fa-eye mr-1" />Ver detalle
          </button>
          <button
            v-if="canManage"
            class="btn btn-sm btn-success"
            :disabled="bulkLoading"
            @click="bulkApprove(selectedMovements(selectedIds))"
          >
            <i class="fas fa-check mr-1" />Aprobar ({{ selectedIds.length }})
          </button>
          <button
            v-if="canManage"
            class="btn btn-sm btn-danger"
            :disabled="bulkLoading"
            @click="bulkReject(selectedMovements(selectedIds))"
          >
            <i class="fas fa-times mr-1" />Rechazar ({{ selectedIds.length }})
          </button>
        </template>
      </template>

      <!-- Movimiento: ID corto (clicable → detalle) + fecha de creación. -->
      <template #cell-movement="{ row }">
        <div class="mv-stack">
          <button
            type="button"
            class="mv-id mv-id--link"
            title="Ver detalle"
            @click="openDetail(String((row as Movement).id))"
          >
            #{{ String((row as Movement).id).slice(-8) }}
          </button>
          <span class="mv-sub">{{ formatDate((row as Movement).created_at) }}</span>
        </div>
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
          <i :class="typeConfig[(row as Movement).type]?.icon" />
          {{ typeConfig[(row as Movement).type]?.label ?? (row as Movement).type }}
        </span>
        <span
          v-if="['PRIZE', 'BONUS_PRIZE'].includes((row as Movement).type) && (row as Movement).prize_type"
          class="mv-badge mv-badge--prize-sub"
        >
          <i :class="prizeTypeConfig[(row as Movement).prize_type!].icon" />
          {{ prizeTypeConfig[(row as Movement).prize_type!].label }}
        </span>
      </template>

      <!-- Monto + moneda (ISO) en la misma celda. -->
      <template #cell-amount="{ row }">
        <div class="mv-stack">
          <span
            class="mv-amount"
            :class="isCreditByCode[(row as Movement).type] ? 'mv-amount--positive' : 'mv-amount--negative'"
          >
            {{ formatAmount(row as Movement) }}
          </span>
          <span v-if="(row as Movement).currency" class="mv-sub">{{ (row as Movement).currency!.iso_code }}</span>
        </div>
      </template>

      <!-- Estado + (si está pendiente) antigüedad y quién lo revisa.
           :data-tick lee nowTick → la antigüedad se re-renderiza cada minuto. -->
      <template #cell-status="{ row }">
        <div class="mv-stack" :data-tick="nowTick">
          <span :class="statusConfig[(row as Movement).status]?.cls ?? 'mv-status'">
            {{ statusConfig[(row as Movement).status]?.label ?? (row as Movement).status }}
          </span>
          <span
            v-if="(row as Movement).status === 'PENDING'"
            class="mv-sub"
            :class="`mv-sub--${ageInfo((row as Movement).created_at).level}`"
          >
            <i :class="{
              'fas fa-clock': ageInfo((row as Movement).created_at).level !== 'critical',
              'fas fa-triangle-exclamation': ageInfo((row as Movement).created_at).level === 'critical',
            }" />
            {{ ageInfo((row as Movement).created_at).label }}
          </span>
          <span
            v-if="(row as Movement).assigned_to"
            class="mv-sub mv-assigned"
            :title="`En revisión por ${adminName((row as Movement).assigned_to)}`"
          >
            <i class="fas fa-user-clock" />
            {{ adminName((row as Movement).assigned_to) }}
          </span>
        </div>
      </template>
    </TableWithFilterBridge>
  </div>

  <!-- ── Modal detalle ─────────────────────────────────────────────────── -->
  <MovementDetailModal
    v-model="showDetail"
    :detail-loading="detailLoading"
    :detail="detail"
    :is-actionable="canAct"
    :can-manage="canManage"
    :type-label="typeLabel"
    :can-submit-reject="canSubmitReject"
    :is-receipt-image="isReceiptImage"
    :receipt-zoom="receiptZoom"
    :admin-notes="adminNotes"
    :player-message="playerMessage"
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
    @update:player-message="playerMessage = $event"
    @update:show-reject-form="showRejectForm = $event"
    @approve="handleApprove"
    @reject="handleReject"
    @view-movement="openDetail"
    @view-user-movements="(search) => { showDetail = false; searchByUser(search) }"
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
.mv-id--link {
  border: 1px solid transparent;
  cursor: pointer;
  align-self: flex-start;
  transition: border-color .15s, color .15s;
}
.mv-id--link:hover { border-color: #667eea; color: #667eea; }

/* Celdas fusionadas: dato principal arriba, secundario abajo en pequeño. */
.mv-stack { display: flex; flex-direction: column; align-items: flex-start; gap: 3px; }
.mv-sub {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  white-space: nowrap;
}
.mv-sub--warning  { color: #fbbf24; font-weight: 600; }
.mv-sub--critical { color: #f87171; font-weight: 600; }

.mv-selection-sep {
  width: 1px;
  align-self: stretch;
  background: var(--ds-border);
  margin: 0 0.25rem;
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

.mv-assigned {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #60a5fa;
  white-space: nowrap;
}

/* ── Indicador "En vivo" del header ───────────────────────────────────── */
.mv-live {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #34d399;
  background: rgba(5,150,105,.12);
  border: 1px solid rgba(5,150,105,.3);
  padding: 4px 12px;
  border-radius: 20px;
}
.mv-live__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: currentColor;
  animation: mv-pulse 1.8s ease-in-out infinite;
}
.mv-live--off {
  color: #fbbf24;
  background: rgba(217,119,6,.12);
  border-color: rgba(217,119,6,.3);
}
.mv-readonly {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ds-text-2);
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  padding: 4px 12px;
  border-radius: 20px;
}

/* SLA del pendiente más antiguo — mismos colores que .mv-age--* de la tabla. */
.mv-sla--normal   { color: var(--ds-text); }
.mv-sla--warning  { color: #fbbf24; }
.mv-sla--critical { color: #f87171; }

.mv-queue__critical {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: 0.35rem;
  align-self: flex-start;
  font-size: 0.72rem;
  font-weight: 700;
  color: #f87171;
  background: rgba(220,38,38,.14);
  padding: 2px 9px;
  border-radius: 20px;
}

@keyframes mv-pulse {
  0%, 100% { opacity: 1; }
  50%      { opacity: 0.3; }
}

/* ── Resumen: cola de revisión + tendencia ─────────────────────────────── */
.mv-overview {
  display: grid;
  grid-template-columns: minmax(300px, 380px) minmax(0, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
  align-items: stretch;
}
@media (max-width: 1199px) {
  .mv-overview { grid-template-columns: 1fr; }
}

.mv-queue {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.mv-queue__hero {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem 1.2rem;
  border-radius: 12px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface);
  transition: border-color .2s, background .2s;
}
.mv-queue__hero--active {
  border-color: rgba(217,119,6,.45);
  background: linear-gradient(135deg, rgba(217,119,6,.12), rgba(217,119,6,.03)), var(--ds-surface);
}
.mv-queue__hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
}
.mv-queue__hero--active .mv-queue__hero-icon { background: rgba(217,119,6,.18); color: #fbbf24; }
.mv-queue__hero-body { display: flex; flex-direction: column; min-width: 0; }
.mv-queue__hero-label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ds-text-muted);
}
.mv-queue__hero-value {
  font-size: 2.1rem;
  font-weight: 800;
  line-height: 1.1;
  color: var(--ds-text);
}
.mv-queue__hero-hint { font-size: 0.78rem; color: var(--ds-text-2); }
.mv-queue__hero-hint .fa-circle-check { color: #34d399; }

.mv-queue__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}
/* En pantallas medianas el panel ocupa todo el ancho: 4 tiles en una fila. */
@media (min-width: 768px) and (max-width: 1199px) {
  .mv-queue__grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

.mv-queue__foot {
  margin: auto 0 0;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
}

.mv-date-range {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-right: 0.5rem;
}
.mv-date-range input[type="date"] { width: 140px; }
.mv-date-range__sep { color: var(--ds-text-muted); font-size: 0.8rem; }

.mv-critical-banner {
  display: flex;
  align-items: center;
  background: rgba(220,38,38,.16);
  border: 1px solid rgba(220,38,38,.45);
  color: #f87171;
  border-radius: 10px;
  padding: 0.65rem 1rem;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 1rem;
}
.mv-critical-banner__dismiss {
  margin-left: auto;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0 0.25rem;
}

.mv-trend-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 1rem 1.1rem 0.5rem;
  min-width: 0;
}
.mv-trend-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}
.mv-trend-card__title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0;
}
.mv-trend-card__hint { font-size: 0.72rem; color: var(--ds-text-muted); }

.mr-1 { margin-right: 0.25rem; }
</style>
