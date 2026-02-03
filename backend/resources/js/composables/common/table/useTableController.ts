import { ref, computed, Ref } from 'vue'
import type { Column } from '@/js/types/common/table/column-row'
import { useTableFiltering, TableFilterState } from './useTableFiltering'
import { useFilterableSelection } from './useFilterableSelection'

export function useTableController<T extends { id: string | number }>(options: {
    rows: Ref<T[]>
    columns: Ref<Column[]>
    pageSize?: number
    onSelectionChange?: (ids: Array<T['id']>) => void
}) {
    const { rows, columns, pageSize = 10, onSelectionChange } = options

    /* ---------------- filtros ---------------- */
    const currentFilters = ref<TableFilterState>({
        search: '',
        filters: {},
    })

    const { filteredRows } = useTableFiltering({
        rows,
        columns,
        filters: currentFilters,
    })

    /* ---------------- selección ---------------- */
    const selectedIds = ref<Array<T['id']>>([])

    const { handleFilter } = useFilterableSelection({
        currentFilters,
        selectedIds,
        filteredRows,
        onSelectionChange,
    })

    /* ---------------- paginación ---------------- */
    const currentPage = ref(1)

    const totalPages = computed(() =>
        Math.ceil(filteredRows.value.length / pageSize)
    )

    const paginatedRows = computed(() => {
        const start = (currentPage.value - 1) * pageSize
        return filteredRows.value.slice(start, start + pageSize)
    })

    function setPage(page: number) {
        currentPage.value = Math.max(1, Math.min(page, totalPages.value))
    }

    /* reset page cuando cambian filtros */
    function handleFilterWithReset(filters: TableFilterState) {
        currentPage.value = 1
        handleFilter(filters)
    }

    return {
        // filtros
        currentFilters,
        handleFilter: handleFilterWithReset,

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
