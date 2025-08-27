/**
 * @file tableSelectionListener.ts
 * @description Listener centralizado para la selección de filas en tablas.
 * Mantiene un estado centralizado y callbacks por tableId.
 * Permite obtener la selección actual sin exponer nada globalmente.
 */

/**
 * Tipo de callback que se ejecuta cuando cambian los IDs seleccionados de una tabla.
 * @param selectedIds - Array plano de IDs seleccionados de la tabla
 */
export type TableSelectionCallback = (selectedIds: string[]) => void;

/**
 * Registro interno de callbacks por tableId
 */
interface TableSelectionRegistry {
    [tableId: string]: Set<TableSelectionCallback>;
}

/**
 * Estado centralizado de todas las selecciones de tablas
 * Permite obtener los IDs seleccionados de cualquier tabla rápidamente
 */
const tableSelections: { [tableId: string]: string[] } = {};

/** Registro de callbacks para cada tabla */
const registry: TableSelectionRegistry = {};

/** Bandera para asegurar que el listener global se inicializa solo una vez */
let listenerInitialized = false;

/**
 * Handler del evento global que actualiza el estado centralizado y
 * ejecuta todos los callbacks registrados para la tabla correspondiente.
 * @param event - CustomEvent enviado desde tableSelection Alpine
 */
function handleSelectionUpdate(event: Event) {
    if (!(event instanceof CustomEvent)) return;
    const { tableId, selectedIds } = event.detail ?? {};
    if (!tableId || !Array.isArray(selectedIds)) return;

    // Actualizamos el estado centralizado (mutable)
    tableSelections[tableId] = [...selectedIds];

    // Ejecutamos todos los callbacks registrados para esta tabla
    registry[tableId]?.forEach(cb => cb([...selectedIds]));
}

/**
 * Inicializa el listener global de selección de tablas (solo una vez)
 * Se autoejecuta al importar el módulo
 */
function initListener() {
    if (listenerInitialized) return; // evita inicializaciones múltiples
    listenerInitialized = true;

    window.addEventListener('table-selection-updated', handleSelectionUpdate);
}

// Auto-inicialización al importar el módulo
initListener();

/**
 * Registrar un callback para un tableId específico.
 * Se ejecuta cada vez que cambian los IDs seleccionados de la tabla.
 * Devuelve una función para eliminar el callback (cleanup fácil)
 * @param tableId - Identificador único de la tabla
 * @param callback - Función que recibe los IDs seleccionados
 * @returns Función para eliminar el callback
 */
export function onTableSelectionUpdate(
    tableId: string,
    callback: TableSelectionCallback
) {
    if (!registry[tableId]) registry[tableId] = new Set();
    registry[tableId].add(callback);

    // Retorna función de cleanup
    return () => registry[tableId].delete(callback);
}

/**
 * Eliminar un callback previamente registrado para una tabla específica.
 * Útil si la tabla se destruye dinámicamente para evitar memory leaks.
 * @param tableId - Identificador único de la tabla
 * @param callback - Callback a eliminar
 */
export function removeTableSelectionCallback(
    tableId: string,
    callback: TableSelectionCallback
) {
    registry[tableId]?.delete(callback);
}

/**
 * Obtener los IDs seleccionados de cualquier tabla de manera rápida.
 * Siempre devuelve un array plano.
 * @param tableId - Identificador único de la tabla
 * @returns Array de IDs seleccionados o vacío si no hay ninguno
 */
export function getSelectedIds(tableId: string): string[] {
    return tableSelections[tableId] ? [...tableSelections[tableId]] : [];
}
