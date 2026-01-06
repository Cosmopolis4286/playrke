/**
 * @file tableSelectionListener.ts
 * @description Listener centralizado para la selección de filas en tablas.
 * Mantiene un estado interno por tableId y permite registrar callbacks
 * sin exponer estado global directamente.
 */

/**
 * Callback ejecutado cuando cambia la selección de una tabla.
 * @param selectedIds - IDs seleccionados (array plano)
 */
export type TableSelectionCallback = (selectedIds: string[]) => void;

/**
 * Registro de callbacks por tableId
 */
type TableSelectionRegistry = Record<string, Set<TableSelectionCallback>>;

/**
 * Estado centralizado de selecciones por tabla
 */
const tableSelections: Record<string, string[]> = Object.create(null);

/**
 * Callbacks registrados por tabla
 */
const registry: TableSelectionRegistry = Object.create(null);

/**
 * Estado interno para integración CRUD basada en selección de tabla
 */
const tableSelectionStore = {
    encryptId: '',
    listeners: new Map<string, TableSelectionCallback>()
};

/**
 * Evita inicializar el listener global más de una vez
 */
let initialized = false;

/**
 * Handler del evento global de actualización de selección.
 * Actualiza el estado centralizado y ejecuta los callbacks registrados.
 *
 * @param event - Evento CustomEvent con detail { tableId, selectedIds }
 */
function handleSelectionUpdate(event: Event): void {
    const customEvent = event as CustomEvent;

    if (!customEvent?.detail) return;

    const { tableId, selectedIds } = customEvent.detail;

    if (typeof tableId !== 'string' || !Array.isArray(selectedIds)) return;

    const snapshot = [...selectedIds];

    // Actualiza estado centralizado
    tableSelections[tableId] = snapshot;
    console.log('tableSelections', tableSelections);

    // Ejecuta callbacks registrados
    registry[tableId]?.forEach(cb => cb(snapshot));
}

/**
 * Inicializa el listener global (solo una vez).
 * Debe ejecutarse en entorno cliente.
 */
export function initTableSelectionListener(): void {
    if (initialized) return;
    initialized = true;

    alert('initTableSelectionListener');
    window.addEventListener(
        'table-selection-updated',
        handleSelectionUpdate as EventListener
    );
}

/**
 * Registra un callback para un tableId específico.
 * El callback se ejecuta cada vez que cambia la selección.
 *
 * @param tableId - Identificador único de la tabla
 * @param callback - Función a ejecutar con los IDs seleccionados
 * @returns Función de cleanup para eliminar el callback
 */
export function onTableSelectionUpdate(
    tableId: string,
    callback: TableSelectionCallback
): () => void {
    if (!tableId) return () => undefined;

    (registry[tableId] ??= new Set()).add(callback);

    return () => {
        registry[tableId]?.delete(callback);
    };
}

/**
 * Registra un listener de selección para integración con CRUD.
 * Garantiza que solo exista un listener por tableId.
 *
 * @param tableId - ID de la tabla
 */
export function registerTableListener(tableId: string): void {
    if (!tableId || tableSelectionStore.listeners.has(tableId)) return;

    const listener: TableSelectionCallback = (selectedIds) => {
        tableSelectionStore.encryptId =
            selectedIds.length === 1 ? selectedIds[0] : '';
    };

    onTableSelectionUpdate(tableId, listener);
    tableSelectionStore.listeners.set(tableId, listener);
}

/**
 * Elimina un callback previamente registrado.
 * Útil para limpieza manual cuando una tabla se destruye.
 *
 * @param tableId - Identificador de la tabla
 * @param callback - Callback a eliminar
 */
export function removeTableSelectionCallback(
    tableId: string,
    callback: TableSelectionCallback
): void {
    registry[tableId]?.delete(callback);
}

/**
 * Obtiene los IDs seleccionados actualmente para una tabla.
 * Siempre devuelve un nuevo array.
 *
 * @param tableId - Identificador de la tabla
 * @returns IDs seleccionados o array vacío
 */
export function getSelectedIds(tableId: string): string[] {
    if (!tableId) return [];
    return [...(tableSelections[tableId] ?? [])];
}

/**
 * Obtiene el ID cifrado actual desde la tabla o desde el estado
 *
 * @param tableId - Identificador de la tabla
 * @returns ID cifrado o string vacío
 */
export function getCurrentEncryptId(tableId: string): string {
    const selectedIds = getSelectedIds(tableId);
    return selectedIds.length === 1 ? selectedIds[0] : '';
}
