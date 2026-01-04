// helpers/vue/registerSharedComponents.ts
import { App, createApp } from 'vue'
import TableFilter from '@/components/shared/TableFilter.vue'
import TableWithFilter from '@/components/shared/TableWithFilter.vue'
import TableWithFilterBridge from '@/components/shared/TableWithFilterBridge.vue'

/**
 * Registra componentes Vue compartidos globalmente
 * @param app - Instancia de la aplicación Vue
 */
export function registerSharedComponents(app: App): void {
    // Registrar componentes de tabla
    app.component('TableFilter', TableFilter)
    app.component('TableWithFilter', TableWithFilter)
    app.component('TableWithFilterBridge', TableWithFilterBridge)

    if (import.meta.env.DEV) {
        console.log('✅ Componentes Vue compartidos registrados')
    }
}

/**
 * Crea una aplicación Vue con componentes compartidos ya registrados
 */
export function createVueAppWithSharedComponents(): App {
    const app = createApp({})
    registerSharedComponents(app)
    return app
}
