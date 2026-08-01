// Selección masiva (columna completa / categoría completa) para matrices
// rol×item (PermissionMatrix.vue, SidebarMenuVisibilityMatrix.vue). Solo
// orquesta múltiples toggles individuales sobre el pendingChanges de cada
// composable consumidor — no dispara guardado ni conoce axios.
export function useMatrixBulkSelection<Row, Col extends { id: string }>(options: {
    rows: () => Row[]
    cols: () => Col[]
    getRowId?: (row: Row) => string
    getValue: (rowId: string, colId: string) => boolean
    setValue: (rowId: string, colId: string, value: boolean) => void
    isCellDisabled?: (rowId: string, colId: string) => boolean
}) {
    const getRowId = options.getRowId ?? ((row: Row) => (row as unknown as { id: string }).id)

    function eligibleRows(colId: string): Row[] {
        return options.rows().filter(r => !options.isCellDisabled?.(getRowId(r), colId))
    }

    function isColumnFullyChecked(colId: string): boolean {
        const rows = eligibleRows(colId)
        return rows.length > 0 && rows.every(r => options.getValue(getRowId(r), colId))
    }

    function isColumnPartiallyChecked(colId: string): boolean {
        const rows = eligibleRows(colId)
        const checked = rows.filter(r => options.getValue(getRowId(r), colId)).length
        return checked > 0 && checked < rows.length
    }

    function toggleColumn(colId: string): void {
        const target = !isColumnFullyChecked(colId)
        for (const row of eligibleRows(colId)) {
            const rowId = getRowId(row)
            if (options.getValue(rowId, colId) !== target) {
                options.setValue(rowId, colId, target)
            }
        }
    }

    function isCategoryFullyChecked(categoryRows: Row[]): boolean {
        if (!categoryRows.length) return false
        return categoryRows.every(row =>
            options.cols()
                .filter(c => !options.isCellDisabled?.(getRowId(row), c.id))
                .every(c => options.getValue(getRowId(row), c.id))
        )
    }

    function isCategoryPartiallyChecked(categoryRows: Row[]): boolean {
        let checked = 0
        let total = 0
        for (const row of categoryRows) {
            const rowId = getRowId(row)
            for (const col of options.cols()) {
                if (options.isCellDisabled?.(rowId, col.id)) continue
                total++
                if (options.getValue(rowId, col.id)) checked++
            }
        }
        return checked > 0 && checked < total
    }

    function toggleCategory(categoryRows: Row[]): void {
        const target = !isCategoryFullyChecked(categoryRows)
        for (const row of categoryRows) {
            const rowId = getRowId(row)
            for (const col of options.cols()) {
                if (options.isCellDisabled?.(rowId, col.id)) continue
                if (options.getValue(rowId, col.id) !== target) {
                    options.setValue(rowId, col.id, target)
                }
            }
        }
    }

    return {
        isColumnFullyChecked,
        isColumnPartiallyChecked,
        toggleColumn,
        isCategoryFullyChecked,
        isCategoryPartiallyChecked,
        toggleCategory,
    }
}
