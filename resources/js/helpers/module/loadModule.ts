// resources/js/helpers/loadModule.ts
import { showToast } from '@/helpers/toastNotify';
import { onDOMReady } from '@/utils/domReady';

/**
 * loadModule.ts
 * Permite cargar cualquier módulo dentro de /resources/js/ usando rutas "relativas al root" (resources/js)
 * desde cualquier archivo que llame a loadModule, evitando problemas de rutas relativas.
 */
export async function loadModule<T = Record<string, any>>(
    modulePath: string,
    initFunction?: keyof T & string
): Promise<T> {
    if (!modulePath) throw new Error('⚠️ No se proporcionó `modulePath`.');

    try {
        // Normalizar extensión
        const path = modulePath.endsWith('.ts') ? modulePath : `${modulePath}.ts`;

        // Resolver ruta absoluta relativa a /resources/js
        const resolvedPath = `/resources/js/${path}`;
        console.log(`📦 Importando módulo dinámico: ${resolvedPath}`);

        // Import dinámico con @vite-ignore para que Vite no lo resuelva en compile time
        const imported = (await import(/* @vite-ignore */ resolvedPath)) as Record<string, any>;
        const mod: T = (imported.default ?? imported) as T;

        // Ejecutar función de inicialización si existe
        if (initFunction && typeof (mod as any)[initFunction] === 'function') {
            try {
                (mod as any)[initFunction]();
                console.log(`🟢 Función '${initFunction}' ejecutada en '${modulePath}'`);
            } catch (err) {
                console.error(`❌ Error ejecutando '${initFunction}' en '${modulePath}':`, err);
                showToast(`Error ejecutando '${initFunction}' en '${modulePath}'`, 'warning');
            }
        }

        // Ejecutar initListeners si existe
        if (typeof (mod as any).initListeners === 'function') {
            onDOMReady((mod as any).initListeners);
        }

        console.log(`✅ Módulo "${modulePath}" cargado correctamente.`);
        return mod;
    } catch (err) {
        const msg = `❌ Error al importar '${modulePath}'.`;
        console.error(msg, err);
        showToast(msg, 'warning');
        throw err;
    }
}
