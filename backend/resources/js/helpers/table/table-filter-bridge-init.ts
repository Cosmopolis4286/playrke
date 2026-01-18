// helpers/table/table-filter-bridge-init.ts
import { createVueAppWithSharedComponents } from '@js/helpers/vue/registerSharedComponents'

/**
 * Registro para evitar listeners duplicados por módulo
 */
const initializedModules = new Set<string>()

/**
 * Inicializa una instancia de Vue para TableWithFilterBridge
 * @param tableId - ID único de la tabla
 * @param moduleId - ID del módulo para integración con formCRUD
 */
export function initTableFilterBridge(tableId: string, moduleId: string): void {
    const wrapperId = `#table-${tableId}-vue-wrapper`

    const wrapperElement = document.querySelector(wrapperId)
    if (!wrapperElement) {
        if (import.meta.env.DEV) {
            console.warn(`[TableFilterBridge] Wrapper no encontrado: ${wrapperId}`)
        }
        return
    }

    // Montar Vue una sola vez
    if (!wrapperElement.hasAttribute('data-vue-mounted')) {
        const app = createVueAppWithSharedComponents()
        app.mount(wrapperElement)
        wrapperElement.setAttribute('data-vue-mounted', 'true')

        if (import.meta.env.DEV) {
            console.log(`✅ TableFilterBridge montado en ${wrapperId}`)
        }
    }

    // Registrar listeners solo una vez por módulo
    if (!initializedModules.has(moduleId)) {
        initializedModules.add(moduleId)
    }
}

/**
 * Exponer inicializador en window (uso legacy)
 */
if (typeof window !== 'undefined') {
    ; (window as any).initTableFilterBridge = initTableFilterBridge
}
