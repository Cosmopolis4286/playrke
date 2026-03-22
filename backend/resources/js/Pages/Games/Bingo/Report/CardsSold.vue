<script setup lang="ts">
  /**
   * Página Reporte de Cartones Vendidos del Bingo
   */
  import { computed, ref } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { CardsSoldPageProps } from '@/js/types/games/bingo/report/cards-sold-report'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<CardsSoldPageProps>()

  // ==========================================================================
  // Datos de la tabla
  // ==========================================================================
  const cardsData = ref(props.cards.rows)

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
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'Active', label: 'Activo' },
        { value: 'Used', label: 'Usado' },
        { value: 'Expired', label: 'Expirado' },
        { value: 'Refunded', label: 'Reembolsado' }
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
    tableId="bingo-cards-sold-table"
    :title="props.title"
    :subtitle="subtitle"
    :columns="props.cards.columns"
    :rows="cardsData"
    :filters="filters"
    search-placeholder="Buscar por número de cartón, sala, jugador..."
    :showCreateButton="false"
    :showEditButton="false"
    :showDeleteButton="false"
    :extra-buttons="extraButtons"
  />
</template>
