// components/shared/table-filter-bridge-init.ts
import { createVueAppWithSharedComponents } from '@/helpers/vue/registerSharedComponents'

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
        setupEventListeners(moduleId, tableId)
        initializedModules.add(moduleId)
    }
}

/**
 * Configura listeners globales para integración con formCRUD
 * Usa eventos canónicos del sistema
 */
function setupEventListeners(moduleId: string, tableId: string): void {
    /**
     * Helper interno para acciones CRUD
     */
    const triggerAction = (action: 'create' | 'edit' | 'delete') => {
        const btn = document.querySelector<HTMLElement>(
            `.btn-${moduleId}[data-action="${action}"]`
        )
        btn?.click()
    }

    window.addEventListener('table:create', (e: Event) => {
        const ev = e as CustomEvent
        if (ev.detail?.moduleId === moduleId) {
            triggerAction('create')
        }
    })

    window.addEventListener('table:edit', (e: Event) => {
        const ev = e as CustomEvent
        if (ev.detail?.moduleId === moduleId) {
            triggerAction('edit')
        }
    })

    window.addEventListener('table:delete', (e: Event) => {
        const ev = e as CustomEvent
        if (ev.detail?.moduleId === moduleId) {
            triggerAction('delete')
        }
    })

    /**
     * Evento canónico de selección de tabla
     * Vue reacciona, Alpine se mantiene desacoplado
     */
    window.addEventListener('table-selection-updated', (e: Event) => {
        const ev = e as CustomEvent
        const { tableId: eventTableId, selectedIds } = ev.detail || {}

        if (eventTableId !== tableId) return

        if (import.meta.env.DEV) {
            console.log('🔁 Selección de tabla actualizada:', {
                tableId,
                selectedIds,
            })
        }

        /**
         * Si Vue necesita reaccionar, lo hará vía su propio estado
         * Alpine ya está sincronizado por su propio componente
         */
    })
}

/**
 * Exponer inicializador en window (uso legacy)
 */
if (typeof window !== 'undefined') {
    ; (window as any).initTableFilterBridge = initTableFilterBridge
}
