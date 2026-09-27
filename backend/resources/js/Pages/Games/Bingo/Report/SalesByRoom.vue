<script setup lang="ts">
  /**
   * Página Reporte de Ventas por Sala del Bingo
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
  import type { SalesByRoomPageProps, RoomInfo } from '@/js/types/games/bingo/report/sales-by-room-report'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'
  import type { Pagination } from '@/js/types/common/table/pagination'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<SalesByRoomPageProps>()

  // ==========================================================================
  // Datos de la tabla
  // ==========================================================================
  const roomSalesData = ref(props.roomSales.rows)
  // room_info se reemplaza en cada refetch porque el backend lo recalcula
  // sobre TODOS los cartones de la sala (agregación SQL), no solo la página
  // visible — ver BingoReportController::salesByRoomInfo.
  const roomInfo = ref<RoomInfo>(props.roomSales.room_info)

  // Key forzar re-render de la tabla
  const tableKey = ref(0)

  const forceTableRefresh = () => {
    tableKey.value++
  }

  // ==========================================================================
  // Paginación server-side
  // ==========================================================================
  // El backend solo pagina 15 cartones por vez (BingoReportController::
  // salesReportByRoom) — antes se truncaba silenciosamente en el límite fijo
  // de 500.
  const loading = ref(false)
  const pagination = ref<Pagination>(props.room_sales_pagination)

  /**
   * Traduce la key de columna/filtro del frontend a query param del backend.
   * 'payment_method' y 'status' no están mapeados: son datos resueltos desde
   * la relación cardLot.payments en PHP, no una columna SQL directa filtrable
   * con where() simple sobre bingo_cards — filtran solo dentro de la página
   * ya cargada, no contra el dataset completo del servidor.
   */
  const FILTER_KEY_TO_PARAM: Record<string, string> = {
    currency: 'currency',
  }

  /** Última query aplicada — reutilizada por handleExport() para exportar con los mismos filtros visibles en la tabla. */
  const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

  async function fetchRoomSalesPage(query: ServerQuery) {
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

      const { data } = await backendClient.get(`/bingo/sales-report-room-data/${props.roomId}`, { params })

      roomSalesData.value = data.roomSales?.rows ?? []
      if (data.roomSales?.room_info) roomInfo.value = data.roomSales.room_info
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
   * Opciones de métodos de pago para filtrado.
   */
  const paymentMethodOptions = computed(() => {
    return (props.paymentMethods ?? []).map(p => ({
      value: p.value,
      label: p.label
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
      key: 'payment_method',
      label: 'Método de Pago',
      placeholder: 'Filtrar por método de pago',
      options: paymentMethodOptions.value
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'Paid', label: 'Pagado' },
        { value: 'Pending', label: 'Pendiente' },
        { value: 'Processing', label: 'Procesando' },
        { value: 'Cancelled', label: 'Cancelado' },
        { value: 'Refunded', label: 'Reembolsado' }
      ]
    }
  ])

  // ==========================================================================
  // No se necesitan botones extra para este reporte
  // ==========================================================================
  const extraButtons: any[] = []

  const subtitle = `<a title="Volver a reporte general" href="/bingo/sales-report" class="text-primary font-weight-bold">← Volver a Reporte General</a>`

  /** Exporta a CSV el reporte completo de esta sala (sin paginar) con los mismos filtros activos en la tabla. */
  function handleExport() {
    const params = new URLSearchParams()
    if (lastQuery.value.filters.currency) params.set('currency', lastQuery.value.filters.currency)
    window.location.href = `/bingo/sales-report-room-export/${props.roomId}?${params.toString()}`
  }
</script>

<template>
  <div>
    <!-- Información de la Sala -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Información de la Sala</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-2">
                <div class="text-center">
                  <h6 class="text-muted mb-1">ID Sala</h6>
                  <h5 class="text-primary">{{ roomInfo.id }}</h5>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h6 class="text-muted mb-1">Nombre</h6>
                  <h5 class="text-dark">{{ roomInfo.name }}</h5>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h6 class="text-muted mb-1">Tipo</h6>
                  <h5 class="text-info">{{ roomInfo.type }}</h5>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h6 class="text-muted mb-1">Cartones Vendidos</h6>
                  <h5 class="text-success">{{ roomInfo.total_cards_sold }}</h5>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h6 class="text-muted mb-1">Monto Total</h6>
                  <h5 class="text-primary">{{ roomInfo.currency }} {{ roomInfo.total_amount.toFixed(2) }}</h5>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h6 class="text-muted mb-1">{{ props.sellerView ? 'Total Banca' : 'Monto Neto' }}</h6>
                  <h5 class="text-warning">{{ roomInfo.currency }} {{ roomInfo.net_amount.toFixed(2) }}</h5>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de Ventas por Sala -->
    <TableWithFilterBridge
      :key="tableKey"
      tableId="bingo-sales-by-room-table"
      :title="props.title"
      :subtitle="subtitle"
      :columns="props.roomSales.columns"
      :rows="roomSalesData"
      :filters="filters"
      search-placeholder="Buscar por número de cartón, jugador..."
      :showCreateButton="false"
      :showEditButton="false"
      :showDeleteButton="false"
      :extra-buttons="extraButtons"
      :loading="loading"
      server-side
      :server-total-pages="pagination.last_page"
      @query-change="fetchRoomSalesPage"
    >
      <template #action-buttons>
        <button class="btn btn-sm btn-outline-secondary" title="Exportar ventas de la sala filtradas a CSV" @click="handleExport">
          <i class="fas fa-file-csv mr-1"></i>Exportar
        </button>
      </template>
    </TableWithFilterBridge>
  </div>
</template>
