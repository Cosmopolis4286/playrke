import { ref } from 'vue'

/**
 * Filtro de rango de fechas (date_from/date_to) + link de exportación CSV,
 * para usar junto a TableWithFilterBridge en modo server-side vía el slot
 * #action-buttons (ver Wallet/Movements/Index.vue, el patrón original).
 *
 * No incluye búsqueda/otros filtros — esos ya viajan en `extraParams` cuando
 * el consumidor arma el conjunto completo de parámetros activos (ver
 * `activeParams` en useWalletMovementsList.ts para el ejemplo de composición).
 *
 * @param exportPath Ruta del endpoint de export (ej. '/wallet-movements/export').
 * @param onRangeChange Callback disparado al cambiar cualquiera de las dos fechas — normalmente un refetch a página 1.
 */
export function useDateRangeExport(exportPath: string, onRangeChange: () => void) {
    const dateFrom = ref('')
    const dateTo = ref('')

    function applyDateRange() {
        onRangeChange()
    }

    function clearDateRange() {
        dateFrom.value = ''
        dateTo.value = ''
        onRangeChange()
    }

    /**
     * Construye la URL de export combinando el rango de fechas con cualquier
     * otro filtro/búsqueda activo que el consumidor pase en `extraParams`
     * (ej. status, tipo, búsqueda de texto) — todos opcionales, se omiten si
     * son falsy/undefined.
     */
    function exportUrl(extraParams: Record<string, string | undefined> = {}): string {
        const params = new URLSearchParams()
        const all = { ...extraParams, date_from: dateFrom.value || undefined, date_to: dateTo.value || undefined }

        for (const [key, value] of Object.entries(all)) {
            if (value) params.set(key, value)
        }

        return `${exportPath}?${params.toString()}`
    }

    return { dateFrom, dateTo, applyDateRange, clearDateRange, exportUrl }
}
