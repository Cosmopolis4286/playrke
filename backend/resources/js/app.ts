// js/system.ts
import '@/helpers/mounApp' // loader / wrapper global (side effects controlados)

import Alpine from 'alpinejs'
import { tableSelection } from '@/alpine/tableSelection'
import { loadModule } from '@/helpers/module/loadModule'
import { initFormCrud } from '@/helpers/form/formCrudLoadModule'
import { initAppData, getAppData, type AppData } from '@/data'
import { initGlobalSafe } from '@/helpers/global'
import { initLogout } from '@/helpers/user/logout'

let bootstrapPromise: Promise<void> | null = null

/**
 * Reexport útil para otros entrypoints
 */
export type { AppData }
export { getAppData }

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
 * Garantiza que appData esté listo
 */
export async function waitForAppData(): Promise<Readonly<AppData>> {
  if (_bootstrapPromise) {
    await _bootstrapPromise;
  }
  return getAppDataSync();
}

/**
 * Notificar a Vue que la app está lista
 */
function notifyVueReady() {
  (window as any).vueAppIsReady?.();
}

/**
 * Bootstrap principal de la app
 */
async function bootstrapApp() {
  if (_bootstrapPromise) {
    if (!_logsShown) {
      console.warn('⚠️ bootstrapApp ya estaba en ejecución o completado');
      console.trace('🔍 Llamada duplicada de bootstrapApp detectada');
      _logsShown = true;
    }
    return _bootstrapPromise;
  }

  _bootstrapPromise = (async () => {
    try {
      if (import.meta.env.DEV) {
        console.trace('🔍 Primera ejecución de bootstrapApp');
        console.log('⏳ Iniciando bootstrapApp...');
      }

      await initAppData();
      const appData = getAppDataSync();

      if (import.meta.env.DEV) {
        console.log('🚀 appData inicializado:', appData);
      }

      initAlpine();
      await initGlobalSafe();
      initLogout(); // Inicializar sistema de logout

      if (import.meta.env.DEV) {
        console.log('🟢 Helpers globales y logout cargados');
      }

      if (appData.moduleFile) {
        await loadModule(appData.moduleFile);
        if (appData.enableFormCrud) await initFormCrud();
        if (import.meta.env.DEV) {
          console.log(`🟢 Módulo principal "${appData.moduleFile}" cargado correctamente`);
        }
      } else if (import.meta.env.DEV) {
        console.warn('⚠️ moduleFile no definido en appData, se omite initFormCrud');
      }

      if (import.meta.env.DEV) {
        console.log('🟢 Todos los módulos cargados correctamente');
      }
    } catch (err) {
      console.error('❌ Error en bootstrapApp:', err instanceof Error ? err.message : err, err);
    }
  })();

  return _bootstrapPromise;
}

// 🔹 Ejecutar bootstrapApp de forma segura al cargar DOM
if (!(window as any)._bootstrapAttached) {
  (window as any)._bootstrapAttached = true;
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapApp, { once: true });
    document.addEventListener('DOMContentLoaded', notifyVueReady, { once: true });
  } else {
    bootstrapApp();
    notifyVueReady();
  }
}

