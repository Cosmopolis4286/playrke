// resources/js/app.ts
import '@/helpers/mounApp'; // 🔹 Primero: loader y wrapper
import Alpine from 'alpinejs';
import { tableSelection } from '@/alpine/tableSelection';
import { loadModule } from '@/helpers/module/loadModule';
import { initFormCrud } from '@/helpers/form/formCrudLoadModule';
import { initAppData, getAppDataProperty, getAppData } from '@/data/index';
import { initGlobalSafe } from '@/helpers/global';

let _appData: Record<string, any> = {};
let _bootstrapPromise: Promise<void> | null = null;

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
export function getAppDataSync(): Record<string, any> {
    return _appData;
}

/**
 * Garantiza que appData esté listo
 */
export async function waitForAppData(): Promise<Record<string, any>> {
    if (_bootstrapPromise) {
        await _bootstrapPromise;
    }
    return _appData;
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
            _appData = getAppData();
            console.log('🚀 appData inicializado:', _appData);

            // 🔹 Inicializar Alpine
            initAlpine();

            // 🔹 Inicializar helpers globales
            await initGlobalSafe();
            console.log('🟢 Helpers globales cargados');

            // 🔹 Cargar módulo principal dinámico si existe
            const moduleFile = getAppDataProperty<string>('moduleFile', '');
            if (moduleFile) {
                await loadModule(moduleFile);
                await initFormCrud();
                console.log(`🟢 Módulo principal "${moduleFile}" cargado correctamente`);
            } else {
                console.warn('⚠️ moduleFile no definido en appData, se omite initFormCrud');
            }

            console.log('🟢 Todos los módulos cargados correctamente');
        } catch (err) {
            console.error('❌ Error en bootstrapApp:', err);
        }
    })();

    return _bootstrapPromise;
}

// Ejecutar bootstrapApp solo una vez al cargar el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapApp, { once: true });
} else {
    bootstrapApp();
}

// Mostrar app inmediatamente si Vue ya está listo
if (document.readyState !== 'loading') {
    (window as any).vueAppIsReady?.();
} else {
    document.addEventListener('DOMContentLoaded', () => {
        (window as any).vueAppIsReady?.();
    });
}
