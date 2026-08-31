<script setup lang="ts">
  /**
   * Página Reporte de Ganadores del Bingo
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
  import type { WinnersPageProps } from '@/js/types/games/bingo/report/winners-report'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'
  import type { Pagination } from '@/js/types/common/table/pagination'

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

  // ==========================================================================
  // Paginación server-side
  // ==========================================================================
  // El backend solo pagina 15 ganadores por vez (BingoReportController::
  // winners) — antes se truncaba silenciosamente en el límite fijo de 500.
  const loading = ref(false)
  const pagination = ref<Pagination>(props.winners_pagination)

  /** Traduce la key de columna/filtro del frontend a query param del backend. */
  const FILTER_KEY_TO_PARAM: Record<string, string> = {
    currency: 'currency',
    prize_type: 'type',
  }

  /** Última query aplicada — reutilizada por handleExport() para exportar con los mismos filtros visibles en la tabla. */
  const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

  async function fetchWinnersPage(query: ServerQuery) {
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

      const { data } = await backendClient.get('/bingo/winners-data', { params })

      winnersData.value = data.winners?.rows ?? []
      if (data.pagination) pagination.value = data.pagination
    } catch {
      // silencioso — la tabla conserva la última página cargada correctamente
    } finally {
      loading.value = false
    }
  }

  /** Exporta a CSV el reporte completo (sin paginar) con los mismos filtros activos en la tabla. */
  function handleExport() {
    const params = new URLSearchParams()
    if (lastQuery.value.filters.currency) params.set('currency', lastQuery.value.filters.currency)
    if (lastQuery.value.filters.prize_type) params.set('type', lastQuery.value.filters.prize_type)
    window.location.href = `/bingo/winners-export?${params.toString()}`
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
  // 'status' no está en FILTER_KEY_TO_PARAM: es una columna calculada en PHP
  // (no una columna SQL real de bingo_card_prizes) — filtra solo dentro de la
  // página ya cargada, no contra el dataset completo del servidor.

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
    :loading="loading"
    server-side
    :server-total-pages="pagination.last_page"
    @query-change="fetchWinnersPage"
  >
    <template #action-buttons>
      <button class="btn btn-sm btn-outline-secondary" title="Exportar ganadores filtrados a CSV" @click="handleExport">
        <i class="fas fa-file-csv mr-1"></i>Exportar
      </button>
    </template>
  </TableWithFilterBridge>
</template>
