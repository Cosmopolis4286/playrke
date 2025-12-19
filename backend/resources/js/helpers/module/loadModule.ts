// resources/js/helpers/loadModule.ts
import { showToast } from '@/helpers/toastNotify';
import { onDOMReady } from '@/utils/domReady';

/**
 * Cache interno para módulos ya cargados
 * Evita que un mismo módulo se importe varias veces
 */
const moduleCache: Record<string, Promise<any>> = {};

/**
 * loadModule.ts
 * Permite cargar cualquier módulo dentro de /resources/js/ usando rutas "relativas al root" (resources/js)
 * desde cualquier archivo que llame a loadModule, evitando problemas de rutas relativas y duplicación de imports.
 *
 * @template T - Tipo del módulo que se importa
 * @param modulePath - Ruta relativa al directorio /resources/js (ej: 'modules/commerce/form/commerceForm.ts')
 * @param initFunction - Nombre de la función de inicialización a ejecutar en el módulo (opcional)
 * @returns Módulo importado como promesa
 */
export async function loadModule<T = Record<string, any>>(
    modulePath: string,
    initFunction?: keyof T & string
): Promise<T> {
    if (!modulePath) throw new Error('⚠️ No se proporcionó `modulePath`.');

    // Si ya se cargó previamente, usar cache
    if (moduleCache[modulePath]) {
        if (import.meta.env.DEV) {
            console.log(`♻️ Módulo "${modulePath}" ya cargado, usando cache`);
        }
        return moduleCache[modulePath];
    }

    const loadPromise = (async () => {
        try {
            // Normalizar extensión
            const path = modulePath.endsWith('.ts') ? modulePath : `${modulePath}.ts`;

            // Resolver ruta absoluta relativa a /resources/js
            const resolvedPath = `/resources/js/${path}`;

            if (import.meta.env.DEV) {
                console.log(`📦 Importando módulo dinámico: ${resolvedPath}`);
            }

            // Import dinámico con @vite-ignore para que Vite no lo resuelva en compile time
            const imported = (await import(/* @vite-ignore */ resolvedPath)) as Record<string, any>;
            const mod: T = (imported.default ?? imported) as T;

            // Ejecutar función de inicialización si existe
            if (initFunction && typeof (mod as any)[initFunction] === 'function') {
                try {
                    (mod as any)[initFunction]();
                    if (import.meta.env.DEV) {
                        console.log(`🟢 Función '${initFunction}' ejecutada en '${modulePath}'`);
                    }
                } catch (err) {
                    console.error(`❌ Error ejecutando '${initFunction}' en '${modulePath}':`, err);
                    showToast(`Error ejecutando '${initFunction}' en '${modulePath}'`, 'warning');
                }
            }

            // Ejecutar initListeners si existe
            if (typeof (mod as any).initListeners === 'function') {
                onDOMReady((mod as any).initListeners);
            }

            if (import.meta.env.DEV) {
                console.log(`✅ Módulo "${modulePath}" cargado correctamente.`);
            }
            return mod;
        } catch (err) {
            const msg = `❌ Error al importar '${modulePath}'.`;
            console.error(msg, err);
            showToast(msg, 'warning');
            throw err;
        }
    })();

    moduleCache[modulePath] = loadPromise;
    return loadPromise;
}
