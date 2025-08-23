// resources/js/app.ts
import Alpine from 'alpinejs';
import { tableSelection } from '@/alpine/tableSelection';
import { loadModule } from '@/helpers/module/loadModule';
import { initFormCrud } from '@/helpers/form/formCrudLoadModule';
import { initAppData, getAppDataProperty } from '@/data/index';

/**
 * --------------------------
 * Registro de Alpine.js
 * --------------------------
 * Registramos la función tableSelection como componente Alpine
 * para poder usar x-data="tableSelection()" en cualquier Blade
 */
Alpine.data('tableSelection', tableSelection);

// Inicializar Alpine
(window as any).Alpine = Alpine;
Alpine.start();

console.log('✅ Alpine.js iniciado con tableSelection disponible');

/**
 * --------------------------
 * Inicialización de la aplicación
 * --------------------------
 * Ejecuta la carga de appData y módulos de manera segura
 * solo cuando el DOM esté completamente cargado
 */
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // 🔹 Inicializar appData desde <script type="application/json" id="page-data">
        await initAppData();
        console.log('🚀 appData inicializado correctamente');

        // 🔹 Cargar helpers globales en secuencia
        await loadModule('./helpers/mounApp.ts');                   // Loader y wrapper general
        await loadModule('./helpers/global.ts', 'initGlobalSafe');  // Inicialización global segura

        // 🔹 Inicializar módulo principal dinámico si existe en appData
        const moduleFile = getAppDataProperty<string>('moduleFile', '');
        if (moduleFile) {
            await initFormCrud();
            console.log(`🟢 Módulo principal "${moduleFile}" cargado correctamente`);
        } else {
            console.warn('⚠️ moduleFile no definido en appData, se omite initFormCrud');
        }

        console.log('🟢 Todos los módulos cargados correctamente');
    } catch (err) {
        console.error('❌ Error en la carga asincrónica de módulos:', err);
    }
});
