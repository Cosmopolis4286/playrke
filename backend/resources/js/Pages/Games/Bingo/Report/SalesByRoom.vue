<script setup lang="ts">
  /**
   * Página Reporte de Ventas por Sala del Bingo
   */
  import { computed, ref } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { SalesByRoomPageProps } from '@/js/types/games/bingo/report/sales-by-room-report'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<SalesByRoomPageProps>()

  // ==========================================================================
  // Datos de la tabla
  // ==========================================================================
  const roomSalesData = ref(props.roomSales.rows)
  const roomInfo = computed(() => props.roomSales.room_info)

  // Key forzar re-render de la tabla
  const tableKey = ref(0)

  const forceTableRefresh = () => {
    tableKey.value++
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
                  <h6 class="text-muted mb-1">Monto Neto</h6>
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
    />
  </div>
</template>
