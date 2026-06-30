// js/app.ts
import { hideAppLoader } from '@js/utils/loader/loaderApp'
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

    const run = () => {
        bootstrapSystem()

        // Fallback: en páginas SIN Inertia (login, registro, etc. vía
        // guest-layout.blade.php) nadie llama hideAppLoader() tras un
        // app.mount(), así que lo hacemos aquí. En páginas CON Inertia
        // (#app con data-page), inertia.ts ya lo hizo justo tras montar
        // — no lo repetimos para no arriesgar ocultarlo antes de tiempo.
        if (!document.querySelector('#app[data-page]')) {
            hideAppLoader()
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true })
    } else {
        run()
    }
}
