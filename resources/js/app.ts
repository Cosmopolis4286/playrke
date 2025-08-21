// resources/js/app.ts
import Alpine from 'alpinejs';
import { tableSelection } from '@/alpine/tableSelection';
import { loadModule } from '@/helpers/module/loadModule';
import { initFormCrud } from '@/helpers/form/formCrudLoadModule';
import { initAppData, getModuleFile } from '@/data/index';

// --------------------------
// Inicializar Alpine inmediatamente
// --------------------------

// Registrar tableSelection dentro de Alpine
Alpine.data('tableSelection', tableSelection);

// Inicializar Alpine
(window as any).Alpine = Alpine;
Alpine.start();

console.log('✅ Alpine.js iniciado con tableSelection disponible');

// --------------------------
// Ejecutar resto de lógica cuando el DOM esté listo
// --------------------------
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Inicializar appData
        await initAppData();
        console.log('🚀 appData inicializado');

        // Cargar helpers globales en secuencia
        await loadModule('./helpers/mounApp.ts');                   // Loader y wrapper
        await loadModule('./helpers/global.ts', 'initGlobalSafe');  // Inicialización global segura

        // Inicializar módulo principal dinámico si existe
        const moduleFile = getModuleFile();
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
