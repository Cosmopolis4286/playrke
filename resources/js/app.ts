// resources/js/app.ts
import Alpine from 'alpinejs';
import { tableSelection } from '@/alpine/tableSelection';
import { loadModule } from '@/helpers/module/loadModule';
import { initFormCrud } from '@/helpers/form/formCrudLoadModule';
import { initAppData, getAppDataProperty, getAppData } from '@/data/index';

/**
 * --------------------------
 * Inicialización de Alpine.js
 * --------------------------
 * Registramos el componente tableSelection y aseguramos que Alpine
 * solo se inicie una vez
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

// Variable interna para almacenar appData
let _appData: Record<string, any> = {};

// Getter seguro para obtener appData en cualquier momento
export function getAppDataSync(): Record<string, any> {
    console.log
    return _appData;
}

/**
 * --------------------------
 * Función bootstrap de la app
 * --------------------------
 */
async function bootstrapApp() {
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
            await loadModule(moduleFile); // importar el módulo dinámico
            await initFormCrud();
            console.log(`🟢 Módulo principal "${moduleFile}" cargado correctamente`);
        } else {
            console.warn('⚠️ moduleFile no definido en appData, se omite initFormCrud');
        }

        console.log('🟢 Todos los módulos cargados correctamente');
    } catch (err) {
        console.error('❌ Error en bootstrapApp:', err);
    }
}

// Ejecutar bootstrap al DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapApp, { once: true });
} else {
    bootstrapApp();
}
