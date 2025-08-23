// resources/js/app.ts
import Alpine from 'alpinejs';
import { tableSelection } from '@/alpine/tableSelection';
import { loadModule } from '@/helpers/module/loadModule';
import { initFormCrud } from '@/helpers/form/formCrudLoadModule';
import { initAppData, getAppDataProperty, getAppData } from '@/data/index';

let _appData: Record<string, any> = {};
let _bootstrapPromise: Promise<void> | null = null;

/**
 * --------------------------
 * Inicialización de Alpine.js
 * --------------------------
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
 * Getter seguro y síncrono para appData
 */
export function getAppDataSync(): Record<string, any> {
    return _appData;
}

/**
 * Getter asíncrono para garantizar que appData ya está listo
 */
export async function waitForAppData(): Promise<Record<string, any>> {
    if (_bootstrapPromise) {
        await _bootstrapPromise;
    }
    return _appData;
}

/**
 * --------------------------
 * Función bootstrap de la app
 * --------------------------
 */
async function bootstrapApp() {
    if (_bootstrapPromise) return _bootstrapPromise; // 🔹 Garantizar que no se repita

    _bootstrapPromise = (async () => {
        try {
            // 🔹 Inicializar appData desde <script id="page-data">
            await initAppData();
            _appData = getAppData();
            console.log('🚀 appData inicializado y disponible:', _appData);

            // 🔹 Inicializar Alpine
            initAlpine();

            // 🔹 Cargar helpers globales en paralelo
            await Promise.all([
                loadModule('./helpers/mounApp.ts'),
                loadModule('./helpers/global.ts', 'initGlobalSafe')
            ]);
            console.log('🟢 Helpers globales cargados');

            // 🔹 Inicializar módulo principal dinámico si existe en appData
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

// Ejecutar bootstrap una sola vez al cargar el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapApp, { once: true });
} else {
    bootstrapApp();
}
