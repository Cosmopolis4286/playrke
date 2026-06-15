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
                result = result.filter(row => {
                    const rowVal = getNestedValue(row, key)
                    // Soporte para campos con múltiples valores (arrays)
                    if (Array.isArray(rowVal)) {
                        return rowVal
                            .map(v => String(v).toLowerCase())
                            .includes(String(val).toLowerCase())
                    }
                    // Comparación case-insensitive para campos string
                    return String(rowVal).toLowerCase() === String(val).toLowerCase()
                })
            }
        }

        return result
    })

    return {
        filteredRows,
    }
}
