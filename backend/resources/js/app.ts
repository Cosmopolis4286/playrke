// js/app.ts
import '@js/utils/loader/loaderApp'
import './plugins/echo' // inicializa Echo y lo expone en window.Echo para el navbar
import { initGlobalSafe } from '@js/utils/global'

/**
 * Bootstrap global del sistema
 * ⚠️ NO monta Vue
 * ⚠️ NO monta Inertia
 */
export function bootstrapSystem(): Promise<void> {
    return initGlobalSafe()
}

// 🔹 Ejecutar bootstrapSystem de forma segura al cargar DOM
if (!(window as any)._bootstrapAttached) {
    (window as any)._bootstrapAttached = true
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapSystem, { once: true })
    } else {
        bootstrapSystem()
    }
}
