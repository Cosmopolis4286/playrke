import { Ref, computed } from 'vue'
import type { Column } from '@/js/types/common/table/column-row'

export interface TableFilterState {
    search: string
    filters: Record<string, string>
}

interface UseTableFilteringOptions<T extends Record<string, any>> {
    rows: Ref<T[]>
    columns: Ref<Column[]>
    filters: Ref<TableFilterState>
}

export function useTableFiltering<T extends Record<string, any>>(
    options: UseTableFilteringOptions<T>
) {
    const { rows, columns, filters } = options

    function getNestedValue(obj: T, key: string): any {
        return obj?.[key] ?? ''
    }

    const filteredRows = computed(() => {
        let result = rows.value

        // 🔍 búsqueda global
        if (filters.value.search) {
            const searchLower = filters.value.search.toLowerCase()

            result = result.filter(row =>
                columns.value.some(col =>
                    String(getNestedValue(row, col.key))
                        .toLowerCase()
                        .includes(searchLower)
                )
            )
        }

        // 🎯 filtros por columna
        for (const [key, val] of Object.entries(filters.value.filters)) {
            if (val) {
                result = result.filter(
                    row => String(getNestedValue(row, key)) === val
                )
            }
        }

        return result
    })

    return {
        filteredRows,
    }
}
