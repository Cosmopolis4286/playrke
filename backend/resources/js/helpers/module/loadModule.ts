// resources/js/helpers/loadModule.ts
import { showToast } from '@/helpers/toastNotify';
import { onDOMReady } from '@/utils/domReady';

/**
 * Cache interno para módulos ya cargados
 * Evita que un mismo módulo se importe varias veces
 */
const moduleCache: Record<string, Promise<any>> = {};

/**
 * Pre-cargar todos los módulos posibles usando import.meta.glob
 * Esto permite a Vite procesar correctamente los imports para producción
 *
 * IMPORTANTE: Usamos el alias @/ que apunta a /resources/js/
 * Vite procesa correctamente estos paths en build
 */
const modules = import.meta.glob<Record<string, any>>('@/modules/**/*.ts', {
    eager: false
});

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
            const cleanPath = modulePath.replace(/\.ts$/, '');
            const pathWithTs = cleanPath + '.ts';

            // Buscar el módulo en el glob
            // Las keys del glob pueden venir con diferentes prefijos según el entorno:
            // - Desarrollo: @/modules/...
            // - Producción: /resources/js/modules/...
            const moduleKey = Object.keys(modules).find(key => {
                const normalizedKey = key
                    .replace(/^@\//, '')                    // Quita @/
                    .replace(/^\/resources\/js\//, '');    // Quita /resources/js/
                return normalizedKey === pathWithTs;
            });

            if (!moduleKey) {
                throw new Error(`Módulo no encontrado: ${pathWithTs}. Rutas disponibles: ${Object.keys(modules).join(', ')}`);
            }

            if (import.meta.env.DEV) {
                console.log(`📦 Importando módulo dinámico: ${moduleKey}`);
            }

            // Importar usando el glob pre-cargado
            const imported = await modules[moduleKey]();
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
