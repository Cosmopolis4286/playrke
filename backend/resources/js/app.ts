// js/app.ts
import '@js/utils/loader/loaderApp'
import { loadModule } from '@js/helpers/module/loadModule'
import { initFormCrud } from '@js/helpers/form/formCrudLoadModule'
import { initAppData, getAppData, type AppData } from '@js/core/appData'
import { initGlobalSafe } from '@js/utils/global'

let bootstrapPromise: Promise<void> | null = null

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

            await initGlobalSafe()

            // 2️⃣ Módulos legacy / opcionales
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

// 🔹 Ejecutar bootstrapSystem de forma segura al cargar DOM
if (!(window as any)._bootstrapAttached) {
    (window as any)._bootstrapAttached = true;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapSystem, { once: true });
    } else {
        bootstrapSystem();
    }
}

