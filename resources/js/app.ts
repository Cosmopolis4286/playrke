// resources/js/app.ts

// ==========================
// Importar Alpine.js
// ==========================
import Alpine from 'alpinejs';
import { loadModule } from '@/helpers/module/loadModule';

try {
    (window as any).Alpine = Alpine;
    Alpine.start();
    console.log('✅ Alpine.js iniciado correctamente');
} catch (err) {
    console.error('❌ Error al inicializar Alpine.js:', err);
}

// ==========================
// Inicialización asincrónica de módulos
// ==========================
(async () => {
    try {
        console.log('🚀 Iniciando carga de módulos con loadModule');

        // Módulos helpers conocidos
        await loadModule('helpers/mounApp'); // Control de loader y wrapper
        await loadModule('helpers/global', 'initGlobalSafe'); // Inicialización global segura

        console.log('🟢 Todos los módulos cargados correctamente con loadModule');
    } catch (err) {
        console.error('❌ Error en la carga asincrónica de módulos:', err);
    }
})();
