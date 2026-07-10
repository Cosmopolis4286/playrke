import { ref, computed, watch, Ref } from 'vue'
import type { Column, Row } from '@/js/types/common/table/column-row'
import { useTableFiltering, TableFilterState, TableSortState, SortDirection } from './useTableFiltering'
import { useFilterableSelection } from './useFilterableSelection'

export interface ServerQuery {
    search: string
    filters: Record<string, string>
    sort: TableSortState
    page: number
}

export function useTableController<T extends { id: string }>(options: {
    columns: Ref<Column[]>
    rows: Ref<Row[]>
    pageSize?: number
    onSelectionChange?: (ids: string[]) => void
    /**
     * Cuando es true, filtrado/orden/paginación no se calculan en memoria:
     * `rows` se asume ya resuelto por el backend, y cada cambio de query
     * se notifica vía `onQueryChange` para que el consumidor haga el fetch.
     */
    serverSide?: boolean
    onQueryChange?: (query: ServerQuery) => void
}) {
    const { rows, columns, pageSize = 10, onSelectionChange, serverSide = false, onQueryChange } = options

    /* ---------------- filtros ---------------- */
    const currentFilters = ref<TableFilterState>({
        search: '',
        filters: {},
    })

    /* ---------------- orden ---------------- */
    const currentSort = ref<TableSortState>({
        key: null,
        direction: 'asc',
    })

    const { filteredRows: computedRows } = useTableFiltering({
        rows,
        columns,
        filters: currentFilters,
        sort: currentSort,
    })

    // En modo server-side, `rows` ya viene filtrado/ordenado/paginado por el backend.
    const filteredRows = computed(() => (serverSide ? rows.value : computedRows.value))

    /* ---------------- selección ---------------- */
    const selectedIds = ref<string[]>([])

    const { handleFilter } = useFilterableSelection({
        currentFilters,
        selectedIds,
        filteredRows,
        onSelectionChange,
    })

    /**
     * En modo server-side, cada nuevo `rows` es una página/orden distinta
     * resuelta por el backend (no hay `filteredRows` propio que compare contra
     * el dataset completo). Sin esto, IDs seleccionados de una página anterior
     * quedarían "seleccionados" para siempre aunque ya no sean visibles ni
     * existan en la página actual — comparación superficial por IDs, no deep,
     * para no recorrer objetos anidados en cada fetch.
     */
    if (serverSide) {
        watch(rows, newRows => {
            const validIds = new Set(newRows.map(r => String(r.id)))
            const next = selectedIds.value.filter(id => validIds.has(id))
            if (next.length !== selectedIds.value.length) {
                selectedIds.value = next
                onSelectionChange?.([...next])
            }
        })
    }

    /* ---------------- paginación ---------------- */
    const currentPage = ref(1)

    const totalPages = computed(() =>
        Math.ceil(filteredRows.value.length / pageSize)
    )

    const paginatedRows = computed(() => {
        if (serverSide) return filteredRows.value
        const start = (currentPage.value - 1) * pageSize
        return filteredRows.value.slice(start, start + pageSize)
    })

    function emitQueryChange(): void {
        onQueryChange?.({
            search: currentFilters.value.search,
            filters: { ...currentFilters.value.filters },
            sort: { ...currentSort.value },
            page: currentPage.value,
        })
    }

    function setPage(page: number) {
        currentPage.value = serverSide ? Math.max(1, page) : Math.max(1, Math.min(page, totalPages.value))
        if (serverSide) emitQueryChange()
    }

    /* reset page cuando cambian filtros */
    function handleFilterWithReset(filters: TableFilterState) {
        currentPage.value = 1
        handleFilter(filters)
        if (serverSide) emitQueryChange()
    }

    /* alterna/asigna el ordenamiento de una columna */
    function setSort(key: string): void {
        if (currentSort.value.key === key) {
            const nextDirection: SortDirection = currentSort.value.direction === 'asc' ? 'desc' : 'asc'
            currentSort.value = { key, direction: nextDirection }
        } else {
            currentSort.value = { key, direction: 'asc' }
        }
        currentPage.value = 1
        if (serverSide) emitQueryChange()
    }

    return {
        // filtros
        currentFilters,
        handleFilter: handleFilterWithReset,

        // orden
        currentSort,
        setSort,

        // selección
        selectedIds,

        // datos
        filteredRows,
        paginatedRows,

        // paginación
        currentPage,
        totalPages,
        setPage,
    }
}
