// components/shared/table-filter-bridge-init.ts
import { createVueAppWithSharedComponents } from '@/helpers/vue/registerSharedComponents'

/**
 * Inicializa una instancia de Vue para TableWithFilterBridge
 * @param tableId - ID único de la tabla
 * @param moduleId - ID del módulo para integración con formCRUD
 */
export function initTableFilterBridge(tableId: string, moduleId: string): void {
    const wrapperId = `#table-${tableId}-vue-wrapper`

    // Verificar que el elemento existe
    const wrapperElement = document.querySelector(wrapperId)
    if (!wrapperElement) {
        console.error(`No se encontró el wrapper ${wrapperId}`)
        return
    }

    // Crear y montar aplicación Vue
    const app = createVueAppWithSharedComponents()
    app.mount(wrapperId)

    if (import.meta.env.DEV) {
        console.log(`✅ TableFilterBridge montado en ${wrapperId}`)
    }

    // Integración con el sistema existente
    setupEventListeners(moduleId, tableId)
}

/**
 * Configura listeners para integración con formCRUD y Alpine
 */
function setupEventListeners(moduleId: string, tableId: string): void {
    // Evento: Crear
    window.addEventListener('table:create', (e: any) => {
        if (e.detail.moduleId === moduleId) {
            const btn = document.querySelector(`.btn-${moduleId}[data-action="create"]`) as HTMLElement
            if (btn) btn.click()
        }
    })

    // Evento: Editar
    window.addEventListener('table:edit', (e: any) => {
        if (e.detail.moduleId === moduleId) {
            const btn = document.querySelector(`.btn-${moduleId}[data-action="edit"]`) as HTMLElement
            if (btn) btn.click()
        }
    })

    // Evento: Eliminar
    window.addEventListener('table:delete', (e: any) => {
        if (e.detail.moduleId === moduleId) {
            const btn = document.querySelector(`.btn-${moduleId}[data-action="delete"]`) as HTMLElement
            if (btn) btn.click()
        }
    })

    // Evento: Cambio de selección
    window.addEventListener('table:selection-change', (e: any) => {
        if (e.detail.moduleId === moduleId) {
            // Actualizar selección global si se usa Alpine
            if ((window as any).Alpine) {
                const element = document.querySelector(`[data-table-id="${tableId}"]`) as any
                if (element && element.__x) {
                    const component = element.__x.$data
                    if (component && component.selected) {
                        component.selected = e.detail.selectedIds
                    }
                }
            }
        }
    })
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    (window as any).initTableFilterBridge = initTableFilterBridge
}
