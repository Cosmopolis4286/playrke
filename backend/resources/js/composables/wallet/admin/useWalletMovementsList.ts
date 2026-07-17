import { ref, computed, type Ref } from 'vue'
import backendClient from '@js/plugins/axios'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'
import type { MovementType } from '@js/types/movementType'
import type { Movement, MovementsMeta } from '@js/types/wallet/admin-movement'

const COLUMNS = [
  { key: 'id',         label: 'ID',       sortable: false },
  { key: 'user',       label: 'Usuario',  sortable: false },
  { key: 'type',       label: 'Tipo',     sortable: false },
  { key: 'amount',     label: 'Monto',    sortable: false },
  { key: 'currency',   label: 'Moneda',   sortable: false },
  { key: 'status',     label: 'Estado',   sortable: false },
  { key: 'created_at', label: 'Fecha',    sortable: false },
  { key: 'actions',    label: 'Acciones', sortable: false },
]

/**
 * Lista paginada/filtrable de movimientos de billetera para el panel
 * admin/superadmin (aprobar/rechazar depósitos y retiros).
 */
export function useWalletMovementsList(
  initialMovements: Movement[],
  initialMeta: MovementsMeta | null,
  movementTypes: Ref<MovementType[]> | MovementType[],
) {
  const movements   = ref<Movement[]>(initialMovements)
  const meta        = ref<MovementsMeta | null>(initialMeta)
  const loading     = ref(false)
  const lastSearch  = ref('')
  const lastFilters = ref<Record<string, string>>({})

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
    ]
  })

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

  /** Reemplaza un movimiento en la lista tras aprobar/rechazar desde el modal de detalle. */
  function updateMovement(updated: Movement) {
    const idx = movements.value.findIndex(m => m.id === updated.id)
    if (idx !== -1) movements.value[idx] = updated
  }

  const totalPages  = computed(() => meta.value?.last_page    ?? 1)
  const currentPage = computed(() => meta.value?.current_page ?? 1)

  return {
    movements, meta, loading,
    columns, tableFilters,
    fetchMovements, onFilter, updateMovement,
    totalPages, currentPage,
  }
}
