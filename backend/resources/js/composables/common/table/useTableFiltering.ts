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

/**
 * Detecta strings en formato fecha ISO 8601 (YYYY-MM-DD, con u hora/offset
 * opcional) y devuelve su timestamp para ordenar cronológicamente. Se exige
 * el patrón explícito antes de intentar Date.parse para no tratar como fecha
 * cualquier string que casualmente contenga números y guiones.
 */
const ISO_DATE_PATTERN = /^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?(\.\d+)?(Z|[+-]\d{2}:?\d{2})?)?$/

function parseDateValue(value: unknown): number | null {
    if (typeof value !== 'string' || !ISO_DATE_PATTERN.test(value)) return null
    const timestamp = Date.parse(value)
    return isNaN(timestamp) ? null : timestamp
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

    /**
     * Etapas encadenadas (search → filtros → orden) en vez de un único
     * computed monolítico: así, por ejemplo, cambiar solo el orden no vuelve
     * a ejecutar la búsqueda de texto ni los filtros por columna sobre todo
     * el dataset — cada etapa solo se recomputa si sus propias dependencias
     * cambiaron.
     */

    // 🔍 búsqueda global (solo columnas no marcadas explícitamente como searchable: false)
    const searchedRows = computed(() => {
        const search = filters.value.search
        if (!search) return rows.value

        const searchLower = search.toLowerCase()
        const searchableColumns = columns.value.filter(col => col.searchable !== false)

        return rows.value.filter(row =>
            searchableColumns.some(col =>
                String(getNestedValue(row, col.key))
                    .toLowerCase()
                    .includes(searchLower)
            )
        )
    })

    // 🎯 filtros por columna
    const filteredByColumns = computed(() => {
        let result = searchedRows.value

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

    // ↕️ ordenamiento por columna
    const filteredRows = computed(() => {
        const sortKey = sort?.value.key
        if (!sortKey) return filteredByColumns.value

        const direction = sort!.value.direction === 'desc' ? -1 : 1
        return [...filteredByColumns.value].sort((a, b) => {
            const aVal = getNestedValue(a, sortKey)
            const bVal = getNestedValue(b, sortKey)

            if (typeof aVal === 'number' && typeof bVal === 'number') {
                return (aVal - bVal) * direction
            }

            // Fechas: comparar por timestamp real en vez de orden lexicográfico
            // casual (que solo "funciona por casualidad" si el formato es ISO
            // y se rompe silenciosamente con cualquier otro formato de fecha).
            const aDate = parseDateValue(aVal)
            const bDate = parseDateValue(bVal)
            if (aDate !== null && bDate !== null) {
                return (aDate - bDate) * direction
            }

            return String(aVal).localeCompare(String(bVal), undefined, { numeric: true }) * direction
        })
    })

    return {
        filteredRows,
    }
}
