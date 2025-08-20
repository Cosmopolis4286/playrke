// resources/js/app.ts
import Alpine from 'alpinejs';
import { loadModule } from '@/helpers/module/loadModule';
import { initFormCrud } from '@/helpers/form/formCrudLoadModule';
import { initAppData } from '@/data/index';

// --------------------------
// Inicializar Alpine
// --------------------------
(window as any).Alpine = Alpine;
Alpine.start();
console.log('✅ Alpine.js iniciado correctamente');

// --------------------------
// Inicialización de AppData
// --------------------------
initAppData();

// --------------------------
// Espera a que el DOM esté listo y appData exista
// --------------------------
document.addEventListener('DOMContentLoaded', async () => {
    try {
        console.log('🚀 Iniciando carga de módulos con loadModule');

        const appData = (window as any).appData;
        if (!appData) {
            throw new Error('❌ window.appData no está definido. Asegúrate de declararlo en Blade antes de cargar los scripts.');
        }

        console.log('appData', appData);

        // Cargar helpers globales
        await loadModule('helpers/mounApp');              // Loader y wrapper
        await loadModule('helpers/global', 'initGlobalSafe'); // Inicialización global segura

        // Inicializar módulo principal dinámico si existe
        if (appData.moduleFile) {
            await initFormCrud(); // Carga el módulo y asigna propiedades a formCrudExports
            console.log(`🟢 Módulo principal "${appData.moduleFile}" cargado correctamente`);
        } else {
            console.warn('⚠️ moduleFile no definido en window.appData');
        }

        console.log('🟢 Todos los módulos cargados correctamente');
    } catch (err) {
        console.error('❌ Error en la carga asincrónica de módulos:', err);
    }
});
