import '@/helpers/mounApp'; // 🔹 Primero: loader y wrapper
import Alpine from 'alpinejs';
import { tableSelection } from '@/alpine/tableSelection';
import { loadModule } from '@/helpers/module/loadModule';
import { initFormCrud } from '@/helpers/form/formCrudLoadModule';
import { initAppData, getAppData, AppData } from '@/data/index';
import { initGlobalSafe } from '@/helpers/global';

let _bootstrapPromise: Promise<void> | null = null;

/**
 * Reexportar AppData para que otros módulos lo usen desde app.ts
 */
export type { AppData } from '@/data/index';

export { getAppDataProperty, getAppData } from '@/data/index';

/**
 * Inicialización de Alpine.js
 */
function initAlpine() {
  if (!(window as any).AlpineStarted) {
    Alpine.data('tableSelection', tableSelection);
    (window as any).Alpine = Alpine;
    Alpine.start();
    (window as any).AlpineStarted = true;
    console.log('✅ Alpine.js iniciado con tableSelection disponible');
  }
}

/**
 * Getter seguro para appData
 */
export function getAppDataSync(): Readonly<AppData> {
  return getAppData();
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
  if (_bootstrapPromise) return _bootstrapPromise;

  _bootstrapPromise = (async () => {
    try {
      console.log('⏳ Iniciando bootstrapApp...');

      // 🔹 Inicializar appData
      await initAppData();
      const appData = getAppDataSync();
      console.log('🚀 appData inicializado:', appData);

      // 🔹 Inicializar Alpine
      initAlpine();

      // 🔹 Inicializar helpers globales
      await initGlobalSafe();
      console.log('🟢 Helpers globales cargados');

      // 🔹 Cargar módulo principal dinámico si existe
      if (appData.moduleFile) {
        await loadModule(appData.moduleFile);
        if (appData.enableFormCrud) {
          await initFormCrud();
        }
        console.log(`🟢 Módulo principal "${appData.moduleFile}" cargado correctamente`);
      } else {
        console.warn('⚠️ moduleFile no definido en appData, se omite initFormCrud');
      }

      console.log('🟢 Todos los módulos cargados correctamente');
    } catch (err) {
      console.error('❌ Error en bootstrapApp:', err instanceof Error ? err.message : err, err);
    }
  })();

  return _bootstrapPromise;
}

// Ejecutar bootstrapApp solo una vez al cargar el DOM
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrapApp, { once: true });
  document.addEventListener('DOMContentLoaded', notifyVueReady, { once: true });
} else {
  bootstrapApp();
  notifyVueReady();
}
