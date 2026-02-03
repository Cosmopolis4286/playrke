import { Ref } from 'vue'
import type { TableFilterState } from './useTableFiltering'

export function useFilterableSelection<T extends { id: string | number }>(options: {
    currentFilters: Ref<TableFilterState>
    selectedIds: Ref<Array<T['id']>>
    filteredRows: Ref<T[]>
    onSelectionChange?: (ids: Array<T['id']>) => void
}) {
    const {
        currentFilters,
        selectedIds,
        filteredRows,
        onSelectionChange,
    } = options

    function handleFilter(filterData: TableFilterState): void {
        currentFilters.value = filterData

        selectedIds.value = selectedIds.value.filter(id =>
            filteredRows.value.some(row => row.id === id)
        )

        emitSelectionChange()
    }

    function emitSelectionChange(): void {
        onSelectionChange?.([...selectedIds.value])
    }

    return {
        handleFilter,
        emitSelectionChange,
    }
}
