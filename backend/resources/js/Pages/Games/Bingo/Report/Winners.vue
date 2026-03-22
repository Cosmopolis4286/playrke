<script setup lang="ts">
  /**
   * Página Reporte de Ganadores del Bingo
   */
  import { computed, ref } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { WinnersPageProps } from '@/js/types/games/bingo/report/winners-report'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<WinnersPageProps>()

  // ==========================================================================
  // Datos de la tabla
  // ==========================================================================
  const winnersData = ref(props.winners.rows)

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
   * Opciones de tipos de premio para filtrado.
   */
  const prizeTypeOptions = computed(() => {
    return (props.prizeTypes ?? []).map(p => ({
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
      key: 'prize_type',
      label: 'Tipo de Premio',
      placeholder: 'Filtrar por tipo de premio',
      options: prizeTypeOptions.value
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'Paid', label: 'Pagado' },
        { value: 'Pending', label: 'Pendiente' },
        { value: 'Cancelled', label: 'Cancelado' },
        { value: 'Processing', label: 'Procesando' }
      ]
    }
  ])

  // ==========================================================================
  // No se necesitan botones extra para este reporte
  // ==========================================================================
  const extraButtons: any[] = []

  const subtitle = `<a title="Ir a reportes del Bingo" href="/bingo/sales-report" class="text-primary font-weight-bold">Ir a Reporte de Ventas</a>`
</script>

<template>
  <TableWithFilterBridge
    :key="tableKey"
    tableId="bingo-winners-table"
    :title="props.title"
    :subtitle="subtitle"
    :columns="props.winners.columns"
    :rows="winnersData"
    :filters="filters"
    search-placeholder="Buscar por ganador, sala, tipo de premio..."
    :showCreateButton="false"
    :showEditButton="false"
    :showDeleteButton="false"
    :extra-buttons="extraButtons"
  />
</template>
