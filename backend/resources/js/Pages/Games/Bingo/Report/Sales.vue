<script setup lang="ts">
  /**
   * Página Reporte de Ventas del Bingo
   */
  import { computed, ref } from 'vue'
  import backendClient from '@js/plugins/axios'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { SalesPageProps, SalesSummary } from '@/js/types/games/bingo/report/sales-report'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'
  import type { Pagination } from '@/js/types/common/table/pagination'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<SalesPageProps>()

  // ==========================================================================
  // Datos de la tabla
  // ==========================================================================
  const salesData = ref(props.sales.rows)
  // summary se reemplaza en cada refetch (fetchSalesPage) porque se recalcula
  // sobre el dataset COMPLETO en el backend, no solo la página visible — ver
  // BingoReportController::salesReportSummary.
  const summaryData = ref<SalesSummary>(props.sales.summary)

  /**
   * Desglose de totales por moneda, como array para iterar en el template
   * (una sala puede vender en varias monedas — ver metrics_by_currency).
   */
  const currencyTotals = computed(() =>
    Object.entries(summaryData.value.by_currency ?? {}).map(([iso, totals]) => ({
      iso,
      ...totals
    }))
  )

  // Key forzar re-render de la tabla
  const tableKey = ref(0)

  const forceTableRefresh = () => {
    tableKey.value++
  }

  // ==========================================================================
  // Paginación server-side
  // ==========================================================================
  // El backend pagina sobre SALAS, no sobre las filas expandidas por moneda
  // (una sala puede tener 1 fila por cada moneda vendida) — ver
  // BingoReportController::salesReportRoomsQuery. Antes se truncaba
  // silenciosamente en el límite fijo de 500 filas ya expandidas.
  const loading = ref(false)
  const pagination = ref<Pagination>(props.sales_pagination)

  /**
   * Traduce la key de columna/filtro del frontend a query param del backend.
   * 'room_type' y 'status' no están mapeados: son columnas calculadas en PHP
   * (roomType.code / status.code resueltos vía relaciones, no una columna SQL
   * directa filtrable con where() simple) — filtran solo dentro de la página
   * ya cargada, no contra el dataset completo del servidor.
   */
  const FILTER_KEY_TO_PARAM: Record<string, string> = {
    currency: 'currency',
  }

  /** Última query aplicada — reutilizada por handleExport() para exportar con los mismos filtros visibles en la tabla. */
  const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

  async function fetchSalesPage(query: ServerQuery) {
    lastQuery.value = query
    loading.value = true
    try {
      const params: Record<string, string | number> = {
        page: query.page,
        per_page: pagination.value.per_page,
      }

      if (query.search) params.search = query.search

      for (const [filterKey, value] of Object.entries(query.filters)) {
        if (!value) continue
        const param = FILTER_KEY_TO_PARAM[filterKey]
        if (param) params[param] = value
      }

      const { data } = await backendClient.get('/bingo/sales-report-data', { params })

      salesData.value = data.sales?.rows ?? []
      if (data.sales?.summary) summaryData.value = data.sales.summary
      if (data.pagination) pagination.value = data.pagination
    } catch {
      // silencioso — la tabla conserva la última página cargada correctamente
    } finally {
      loading.value = false
    }
  }

  /**
   * Opciones de monedas para filtrado.
   */
  const currencyOptions = computed(() => {
    return (props.currencies ?? []).map(c => ({
      value: String(c.id),
      label: c.iso_code
    }))
  })

  /**
   * Opciones de tipos de sala para filtrado.
   */
  const roomTypeOptions = computed(() => {
    return (props.roomTypes ?? []).map(r => ({
      value: r.value,
      label: r.label
    }))
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'currency',
      label: 'Moneda',
      placeholder: 'Filtrar por moneda',
      options: currencyOptions.value
    },
    {
      key: 'room_type',
      label: 'Tipo de Sala',
      placeholder: 'Filtrar por tipo de sala',
      options: roomTypeOptions.value
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'Completed', label: 'Completado' },
        { value: 'Processing', label: 'Procesando' },
        { value: 'Pending', label: 'Pendiente' },
        { value: 'Cancelled', label: 'Cancelado' }
      ]
    }
  ])

  // ==========================================================================
  // Botones extra para navegación a detalles de sala
  // ==========================================================================
  const extraButtons: any[] = [
    {
      icon: 'fas fa-eye',
      class: 'btn-info text-white',
      title: 'Ver Detalles de Sala',
      show: (ids: string[]) => ids.length === 1,
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          const row = salesData.value.find(r => String(r.id) === String(ids[0]))
          // row.id es compuesto "roomId:currencyId" (una sala puede tener una fila
          // por cada moneda vendida) — se extrae solo el roomId para la navegación.
          const roomId = row?.id.split(':')[0]
          if (roomId) {
            window.location.href = `/bingo/sales-report/room/${roomId}`
          }
        }
      }
    }
  ]

  const subtitle = `<a title="Ir a cartones vendidos" href="/bingo/cards-sold" class="text-primary font-weight-bold">Ir a Cartones Vendidos</a>`

  /** Exporta a CSV el reporte completo (sin paginar) con los mismos filtros activos en la tabla. */
  function handleExport() {
    const params = new URLSearchParams()
    if (lastQuery.value.filters.currency) params.set('currency', lastQuery.value.filters.currency)
    window.location.href = `/bingo/sales-report-export?${params.toString()}`
  }
</script>

<template>
  <div>
    <!-- Resumen de Ventas -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Resumen de Ventas</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3">
                <div class="text-center">
                  <h4 class="text-primary">{{ summaryData.total_rooms }}</h4>
                  <p class="mb-0">Total Salas</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <h4 class="text-success">{{ summaryData.total_cards_sold }}</h4>
                  <p class="mb-0">Cartones Vendidos</p>
                </div>
              </div>
              <div class="col-md-3" v-for="ct in currencyTotals" :key="ct.iso">
                <div class="text-center">
                  <h4 class="text-info">{{ ct.iso }} {{ ct.amount.toFixed(2) }}</h4>
                  <p class="mb-0">Total {{ ct.iso }}</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <h4 class="text-warning">{{ summaryData.total_commission.toFixed(2) }}</h4>
                  <p class="mb-0">{{ props.sellerView ? 'Mi Comisión' : 'Total Comisión' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de Ventas -->
    <TableWithFilterBridge
      :key="tableKey"
      tableId="bingo-sales-table"
      :title="props.title"
      :subtitle="subtitle"
      :columns="props.sales.columns"
      :rows="salesData"
      :filters="filters"
      search-placeholder="Buscar por sala, tipo de sala..."
      :showCreateButton="false"
      :showEditButton="false"
      :showDeleteButton="false"
      :extra-buttons="extraButtons"
      :loading="loading"
      server-side
      :server-total-pages="pagination.last_page"
      @query-change="fetchSalesPage"
    >
      <template #action-buttons>
        <button class="btn btn-sm btn-outline-secondary" title="Exportar ventas filtradas a CSV" @click="handleExport">
          <i class="fas fa-file-csv mr-1"></i>Exportar
        </button>
      </template>
    </TableWithFilterBridge>
  </div>
</template>
