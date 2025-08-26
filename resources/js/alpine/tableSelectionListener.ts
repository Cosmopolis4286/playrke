// js/alpine/tableSelectionListener.ts

/**
 * Tipo de callback que se ejecuta cuando cambian los IDs seleccionados de una tabla.
 * @param selectedIds - Array de IDs seleccionados de la tabla
 */
export type TableSelectionCallback = (selectedIds: number[]) => void;

/**
 * Registro interno de callbacks por tableId
 */
interface TableSelectionRegistry {
    [tableId: string]: TableSelectionCallback[];
}

/**
 * Estado centralizado de todas las selecciones de tablas
 * Permite obtener los IDs seleccionados de cualquier tabla rápidamente
 */
const tableSelections: { [tableId: string]: number[] } = {};

const registry: TableSelectionRegistry = {};

/**
 * Registrar un callback para un tableId específico.
 * Se ejecuta cada vez que cambian los IDs seleccionados de la tabla.
 * @param tableId - Identificador único de la tabla
 * @param callback - Función que recibe los IDs seleccionados
 */
export function onTableSelectionUpdate(tableId: string, callback: TableSelectionCallback) {
    if (!registry[tableId]) registry[tableId] = [];
    registry[tableId].push(callback);
}

/**
 * Eliminar un callback previamente registrado para una tabla específica.
 * Útil si la tabla se destruye dinámicamente para evitar memory leaks.
 * @param tableId - Identificador único de la tabla
 * @param callback - Callback a eliminar
 */
export function removeTableSelectionCallback(tableId: string, callback: TableSelectionCallback) {
    if (!registry[tableId]) return;
    registry[tableId] = registry[tableId].filter(cb => cb !== callback);
}

/**
 * Obtener los IDs seleccionados de cualquier tabla de manera rápida.
 * @param tableId - Identificador único de la tabla
 * @returns Array de IDs seleccionados o vacío si no hay ninguno
 */
export function getSelectedIds(tableId: string): number[] {
    return tableSelections[tableId] || [];
}

/**
 * Listener global que recibe todos los eventos de selección de tablas
 * y ejecuta los callbacks registrados para cada tableId.
 * También actualiza el estado centralizado `tableSelections`.
 */
window.addEventListener(
    'table-selection-updated',
    (event: Event) => {
        // Convertimos a CustomEvent con tipado seguro
        const customEvent = event as CustomEvent<{ tableId: string; selectedIds: number[] }>;

        if (!customEvent?.detail) return;
        const { tableId, selectedIds } = customEvent.detail;

        if (!tableId || !Array.isArray(selectedIds)) return;

        // Actualizamos el estado centralizado
        tableSelections[tableId] = selectedIds;

        // Ejecutamos todos los callbacks registrados para esta tabla
        registry[tableId]?.forEach(cb => cb(selectedIds));
    }
);
