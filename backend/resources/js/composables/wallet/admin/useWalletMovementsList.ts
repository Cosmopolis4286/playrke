import { ref, computed, type Ref } from 'vue'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toast'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'
import type { ServerQuery } from '@js/composables/common/table/useTableController'
import { useDateRangeExport } from '@js/composables/common/table/useDateRangeExport'
import type { MovementType } from '@js/types/movementType'
import type { Movement, MovementsMeta } from '@js/types/wallet/admin-movement'
import { useWalletMovementsRealtime, type WalletMovementProcessedPayload, type WalletMovementAssignedPayload, type WalletMovementCriticalBacklogPayload } from './useWalletMovementsRealtime'

/**
 * 5 columnas compactas (antes 10): ID+fecha → "Movimiento", monto+moneda →
 * "Monto", estado+antigüedad+asignado → "Estado". La columna "Acciones"
 * desapareció: "Ver detalle" vive en la barra de acciones junto a
 * Aprobar/Rechazar (aparece al seleccionar 1 fila), y el ID es clicable.
 */
const COLUMNS = [
  { key: 'movement', label: 'Movimiento', sortable: false },
  { key: 'user',     label: 'Usuario',    sortable: false },
  { key: 'type',     label: 'Tipo',       sortable: false },
  { key: 'amount',   label: 'Monto',      sortable: false },
  { key: 'status',   label: 'Estado',     sortable: false },
]

/**
 * Lista paginada/filtrable de movimientos de billetera para el panel
 * admin/superadmin (aprobar/rechazar depósitos y retiros).
 *
 * Server-side: cada cambio de búsqueda/filtro/página dispara un fetch nuevo
 * (ver `fetchMovements`/`onQueryChange`, consumido por TableWithFilterBridge).
 *
 * Tiempo real: además de la sincronización local tras aprobar/rechazar desde
 * el modal de detalle (`updateMovement`), esta lista escucha el canal
 * `admins.wallet-movements` para que TODOS los admins con la página abierta
 * vean sin recargar: (a) nuevos movimientos pendientes en cuanto se filtra
 * por PENDING/sin filtro de estado en la página 1, y (b) el estado final de
 * un movimiento que otro admin acaba de procesar.
 */
export function useWalletMovementsList(
  initialMovements: Movement[],
  initialMeta: MovementsMeta | null,
  movementTypes: Ref<MovementType[]> | MovementType[],
  initialPendingCount = 0,
  onCriticalBacklog?: (payload: WalletMovementCriticalBacklogPayload) => void,
  /** Disparado ante cualquier evento en vivo que cambia la cola (nuevo, procesado) — para refrescar KPIs/tendencia. */
  onRealtimeChange?: () => void,
) {
  const movements   = ref<Movement[]>(initialMovements)
  const meta        = ref<MovementsMeta | null>(initialMeta)
  const loading     = ref(false)
  const lastSearch  = ref('')
  const lastFilters = ref<Record<string, string>>({})
  const lastPage    = ref(1)

  // Rango de fechas — controles propios fuera del sistema de filtros
  // genérico (TableFilterInterface solo soporta selects de opciones fijas),
  // se combinan con lastFilters al hacer fetch y se reflejan en el link de
  // export para que descargue exactamente lo que el admin está viendo.
  const { dateFrom, dateTo, applyDateRange, exportUrl: exportUrlBase } = useDateRangeExport(
    '/wallet-movements/export',
    () => fetchMovements(1),
  )

  const columns = COLUMNS

  const tableFilters = computed<TableFilterInterface[]>(() => {
    const types = Array.isArray(movementTypes) ? movementTypes : movementTypes.value
    return [
      {
        key: 'type',
        label: 'Tipo',
        placeholder: 'Todos los tipos',
        options: types.map(t => ({ value: t.code, label: t.label })),
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
      {
        key: 'fraud_flag',
        label: 'Riesgo',
        placeholder: 'Todos',
        options: [
          { value: 'duplicate_receipt', label: 'Posible comprobante duplicado' },
        ],
      },
      {
        key: 'assigned_to_user_id',
        label: 'Asignado a',
        placeholder: 'Todos',
        options: [
          { value: 'mine', label: 'Asignados a mí' },
        ],
      },
    ]
  })

  /** Params compartidos por fetch/stats/export para que los tres reflejen el mismo filtro activo. */
  const activeParams = computed(() => ({
    type:                 lastFilters.value.type                 || undefined,
    status:               lastFilters.value.status                || undefined,
    search:               lastSearch.value                        || undefined,
    date_from:            dateFrom.value                          || undefined,
    date_to:              dateTo.value                            || undefined,
    fraud_flag:           lastFilters.value.fraud_flag            || undefined,
    assigned_to_user_id:  lastFilters.value.assigned_to_user_id   || undefined,
  }))

  // Secuencia de requests: si el admin cambia filtros rápido (búsqueda con
  // debounce + select + página), una respuesta lenta de un filtro anterior
  // podía llegar DESPUÉS y pisar la lista correcta. Solo se aplica la última.
  let fetchSeq = 0

  async function fetchMovements(page = 1) {
    const seq = ++fetchSeq
    loading.value = true
    lastPage.value = page
    try {
      const { data } = await backendClient.get('/wallet-movements/fetch', {
        params: { page, per_page: 20, ...activeParams.value },
      })
      if (seq !== fetchSeq) return
      movements.value = data.data ?? []
      meta.value      = data.meta ?? null
    } catch (e) {
      if (seq !== fetchSeq) return
      console.error('[movements] Error cargando movimientos:', e)
      showToast('No se pudo cargar la lista de movimientos. Reintenta en unos segundos.', 'error')
    } finally {
      if (seq === fetchSeq) loading.value = false
    }
  }

  /**
   * Antes solo pasaba type/status/search: el CSV ignoraba los filtros de
   * Riesgo y Asignado a, y descargaba más filas de las que el admin veía.
   * Ahora usa exactamente activeParams (igual que tabla, stats, tendencia y PDF).
   */
  function exportUrl(): string {
    return exportUrlBase(activeParams.value)
  }

  /** Adapta el evento query-change de TableWithFilterBridge (server-side) al fetch. */
  function onQueryChange(query: ServerQuery) {
    lastSearch.value  = query.search
    lastFilters.value = query.filters
    fetchMovements(query.page)
  }

  /**
   * "Ver todos los movimientos de este usuario" desde el modal de detalle —
   * precarga el filtro de búsqueda con el email del usuario y refresca la
   * lista. El input visual de búsqueda de TableFilter.vue no acepta un valor
   * inicial externo (ref interno, sin prop), así que el término no queda
   * reflejado en la caja de texto, pero el filtro sí se aplica y persiste en
   * fetches subsiguientes (activeParams.search ya usa lastSearch).
   */
  function searchByUser(search: string) {
    lastSearch.value = search
    lastFilters.value = {}
    dateFrom.value = ''
    dateTo.value = ''
    fetchMovements(1)
  }

  /** Reemplaza un movimiento en la lista tras aprobar/rechazar desde el modal de detalle. */
  function updateMovement(updated: Movement) {
    applyUpdate(updated)
  }

  /**
   * Aplica la actualización de un movimiento respetando el filtro de estado
   * activo: si el filtro es, por ejemplo, status=PENDING y el movimiento pasó
   * a COMPLETED/REJECTED, se remueve de la lista visible en vez de dejarlo
   * "fantasma" hasta el próximo refetch manual. meta.total se ajusta igual.
   */
  function applyUpdate(updated: Movement) {
    const idx = movements.value.findIndex(m => m.id === updated.id)
    const matchesActiveFilter =
      !lastFilters.value.status || lastFilters.value.status === updated.status

    if (idx === -1) return

    if (matchesActiveFilter) {
      movements.value[idx] = updated
      return
    }

    movements.value.splice(idx, 1)
    if (meta.value) meta.value = { ...meta.value, total: Math.max(0, meta.value.total - 1) }
  }

  /**
   * Inserta un movimiento recién solicitado por un jugador al tope de la
   * lista, solo si estamos en la página 1 y el filtro activo lo incluiría
   * (sin filtro de estado, o filtrado justo por PENDING).
   */
  function insertIncomingMovement(movement: Movement) {
    if (lastPage.value !== 1) return
    if (lastFilters.value.status && lastFilters.value.status !== 'PENDING') return
    if (lastFilters.value.type && lastFilters.value.type !== movement.type) return

    movements.value = [movement, ...movements.value].slice(0, movements.value.length || 20)
    if (meta.value) meta.value = { ...meta.value, total: meta.value.total + 1 }
  }

  async function fetchMovementById(id: string): Promise<Movement | null> {
    try {
      const { data } = await backendClient.get(`/wallet-movements/${id}`)
      return data.data ?? null
    } catch {
      return null
    }
  }

  /**
   * Un WalletMovementRequested solo trae un resumen (no el Movement completo
   * que espera la tabla), así que se pide el detalle completo antes de
   * insertarlo — evita duplicar el mapeo de `WalletMovementController::mapMovement()`
   * en el frontend.
   */
  async function handleRequested(payload: { movement_id: string }) {
    onRealtimeChange?.()
    const movement = await fetchMovementById(payload.movement_id)
    if (movement) insertIncomingMovement(movement)
  }

  async function handleProcessed(payload: WalletMovementProcessedPayload) {
    onRealtimeChange?.()
    const movement = await fetchMovementById(payload.movement_id)
    if (movement) applyUpdate(movement)
  }

  /**
   * Refleja en vivo cuándo otro admin toma/libera un caso — hace visible el
   * lock de WalletService::guardNotLockedByOther() antes de que alguien
   * choque con el 409 al intentar aprobar/rechazar. No dispara un fetch
   * completo: el payload ya trae todo lo necesario para actualizar la fila.
   */
  function handleAssigned(payload: WalletMovementAssignedPayload) {
    const idx = movements.value.findIndex(m => m.id === payload.movement_id)
    if (idx === -1) return

    movements.value[idx] = {
      ...movements.value[idx],
      assigned_to: payload.assigned_to_user_id ? {
        id: payload.assigned_to_user_id,
        first_name: payload.assigned_to_name,
        last_name: null,
        email: '',
      } : null,
      assigned_at: payload.assigned_to_user_id ? new Date().toISOString() : null,
    }
  }

  const { pendingCount, isLive } = useWalletMovementsRealtime(initialPendingCount, handleRequested, handleProcessed, handleAssigned, onCriticalBacklog)

  const totalPages  = computed(() => meta.value?.last_page    ?? 1)
  const currentPage = computed(() => meta.value?.current_page ?? 1)

  return {
    movements, meta, loading, pendingCount, isLive,
    columns, tableFilters,
    fetchMovements, onQueryChange, updateMovement, searchByUser,
    totalPages, currentPage,
    dateFrom, dateTo, applyDateRange, exportUrl, activeParams,
  }
}
