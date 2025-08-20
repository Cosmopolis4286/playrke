// resources/js/helpers/loadModule.ts
import { showToast } from '@/helpers/toastNotify';
import { onDOMReady } from '@/utils/domReady';

/**
 * Carga dinámicamente un módulo desde `/resources/js/` usando Vite import.meta.glob
 *
 * @template T Tipo esperado del módulo (opcional, para tipado fuerte)
 * @param moduleFile Ruta relativa dentro de `/resources/js/` (ej: "helpers/global.ts")
 * @param initFunction Nombre de función a ejecutar dentro del módulo (opcional)
 * @returns Una promesa resuelta con el módulo tipado como `T`
 */
export async function loadModule<T = Record<string, any>>(
    moduleFile: string,
    initFunction?: keyof T & string
): Promise<T> {
    if (!moduleFile) throw new Error('⚠️ No se proporcionó `moduleFile`.');

    const modules = import.meta.glob('/resources/js/**/*.ts');

    const file = moduleFile.trim().replace(/\.ts$/, '');
    const key = `/resources/js/${file}.ts`;

    const importer = modules[key];
    if (!importer) {
        const msg = `❌ Módulo '${key}' no encontrado en import.meta.glob.`;
        console.error(msg);
        showToast(msg, 'warning');
        throw new Error(msg);
    }

    try {
        console.log(`📦 Importando módulo dinámico: ${key}`);
        const imported = (await importer()) as Record<string, any>;

        const mod: T = (imported.default ?? imported) as T;

        if (initFunction && typeof (mod as any)[initFunction] === 'function') {
            try {
                (mod as any)[initFunction]();
                console.log(`🟢 Función '${initFunction}' ejecutada en módulo '${file}'`);
            } catch (err) {
                console.error(`❌ Error ejecutando '${initFunction}' en '${file}':`, err);
            }
        }

        if (typeof (mod as any).initListeners === 'function') {
            onDOMReady((mod as any).initListeners);
        }
        console.log(new Error(`Trace carga módulo ${file}`).stack);

        console.log(`✅ Módulo "${file}" cargado correctamente.`);
        return mod;
    } catch (err) {
        const msg = `❌ Error al importar el módulo '${file}'.`;
        console.error(msg, err);
        showToast(msg, 'warning');
        throw err;
    }
}
