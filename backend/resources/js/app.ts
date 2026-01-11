// js/system.ts
import '@/utils/mounApp' // loader / wrapper global (side effects controlados)

import Alpine from 'alpinejs'
import { tableSelection } from '@/alpine/tableSelection'
import { loadModule } from '@/helpers/module/loadModule'
import { initFormCrud } from '@/helpers/form/formCrudLoadModule'
import { initAppData, getAppData, type AppData } from '@/data'
import { initGlobalSafe } from '@/utils/global'
import { initLogout } from '@/helpers/user/logout'

let bootstrapPromise: Promise<void> | null = null

/**
 * Inicializa Alpine UNA sola vez
 */
function initAlpine(): void {
    if ((window as any).__ALPINE_STARTED__) return

    Alpine.data('tableSelection', tableSelection)
        ; (window as any).Alpine = Alpine
    Alpine.start()
        ; (window as any).__ALPINE_STARTED__ = true

    if (import.meta.env.DEV) {
        console.log('✅ Alpine.js iniciado')
    }
}

/**
 * Bootstrap global del sistema
 * ⚠️ NO monta Vue
 * ⚠️ NO monta Inertia
 */
export function bootstrapSystem(): Promise<void> {
    if (bootstrapPromise) return bootstrapPromise

    bootstrapPromise = (async () => {
        try {
            if (import.meta.env.DEV) {
                console.log('⏳ bootstrapSystem iniciado')
            }

            // 1️⃣ Datos base de la app
            await initAppData()
            const appData = getAppData()

            if (import.meta.env.DEV) {
                console.log('🚀 appData listo:', appData)
            }

            // 2️⃣ Alpine + helpers globales
            initAlpine()
            await initGlobalSafe()
            initLogout()

            // 3️⃣ Módulos legacy / opcionales
            if (appData.moduleFile) {
                await loadModule(appData.moduleFile)

                if (appData.enableFormCrud) {
                    await initFormCrud()
                }

                if (import.meta.env.DEV) {
                    console.log(`🟢 Módulo "${appData.moduleFile}" cargado`)
                }
            }

            if (import.meta.env.DEV) {
                console.log('🟢 bootstrapSystem completado')
            }
        } catch (error) {
            console.error('❌ Error crítico en bootstrapSystem', error)
            throw error
        }
    })()

    return bootstrapPromise
}

/**
 * Acceso seguro y sincronizado a appData
 */
export function getAppDataSync(): Readonly<AppData> {
    return getAppData()
}

/**
 * Notificar a Vue que la app está lista
 */
function notifyVueReady() {
    (window as any).vueAppIsReady?.();
}

// 🔹 Ejecutar bootstrapSystem de forma segura al cargar DOM
if (!(window as any)._bootstrapAttached) {
    (window as any)._bootstrapAttached = true;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapSystem, { once: true });
        document.addEventListener('DOMContentLoaded', notifyVueReady, { once: true });
    } else {
        bootstrapSystem();
        notifyVueReady();
    }
}

