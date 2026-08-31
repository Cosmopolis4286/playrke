<script setup lang="ts">
  /**
   * Página Reporte de Cartones Vendidos del Bingo
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
  import type { CardsSoldPageProps } from '@/js/types/games/bingo/report/cards-sold-report'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'
  import type { Pagination } from '@/js/types/common/table/pagination'

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

  // ==========================================================================
  // Paginación server-side
  // ==========================================================================
  // El backend solo pagina 15 cartones por vez (BingoReportController::
  // cardsSold) — sin esto, los cartones más allá de la página 1 quedaban
  // invisibles (antes se truncaban silenciosamente en el límite fijo de 500).
  const loading = ref(false)
  const pagination = ref<Pagination>(props.cards_pagination)

  /** Traduce la key de columna/filtro del frontend a query param del backend. */
  const FILTER_KEY_TO_PARAM: Record<string, string> = {
    currency: 'currency',
  }

  /** Última query aplicada — reutilizada por handleExport() para exportar con los mismos filtros visibles en la tabla. */
  const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

  async function fetchCardsSoldPage(query: ServerQuery) {
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

      const { data } = await backendClient.get('/bingo/cards-sold-data', { params })

      cardsData.value = data.cards?.rows ?? []
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
    window.location.href = `/bingo/cards-sold-export?${params.toString()}`
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
  // 'status' no está en FILTER_KEY_TO_PARAM: es una columna calculada en PHP
  // (no una columna SQL real de bingo_cards), igual criterio que
  // BingoRoomContextService — filtra solo dentro de la página ya cargada,
  // no contra el dataset completo del servidor.

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
    :loading="loading"
    server-side
    :server-total-pages="pagination.last_page"
    @query-change="fetchCardsSoldPage"
  >
    <template #action-buttons>
      <button class="btn btn-sm btn-outline-secondary" title="Exportar cartones filtrados a CSV" @click="handleExport">
        <i class="fas fa-file-csv mr-1"></i>Exportar
      </button>
    </template>
  </TableWithFilterBridge>
</template>
