import { Ref, computed } from 'vue'
import type { Column } from '@/js/types/common/table/column-row'

export interface TableFilterState {
    search: string
    filters: Record<string, string>
}

export type SortDirection = 'asc' | 'desc'

export interface TableSortState {
    key: string | null
    direction: SortDirection
}

interface UseTableFilteringOptions<T extends Record<string, any>> {
    rows: Ref<T[]>
    columns: Ref<Column[]>
    filters: Ref<TableFilterState>
    sort?: Ref<TableSortState>
}

export function useTableFiltering<T extends Record<string, any>>(
    options: UseTableFilteringOptions<T>
) {
    const { rows, columns, filters, sort } = options

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

        // ↕️ ordenamiento por columna
        const sortKey = sort?.value.key
        if (sortKey) {
            const direction = sort!.value.direction === 'desc' ? -1 : 1
            result = [...result].sort((a, b) => {
                const aVal = getNestedValue(a, sortKey)
                const bVal = getNestedValue(b, sortKey)

                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    return (aVal - bVal) * direction
                }

                return String(aVal).localeCompare(String(bVal), undefined, { numeric: true }) * direction
            })
        }

        return result
    })

    return {
        filteredRows,
    }
}
