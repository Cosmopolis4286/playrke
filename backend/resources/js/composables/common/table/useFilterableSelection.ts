import { Ref } from 'vue'
import type { TableFilterState } from './useTableFiltering'
import { Row } from '@/js/types/common/table/column-row'

export function useFilterableSelection<T extends { id: string }>(options: {
    currentFilters: Ref<TableFilterState>
    selectedIds: Ref<string[]>
    filteredRows: Ref<Row[]>
    onSelectionChange?: (ids: string[]) => void
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
