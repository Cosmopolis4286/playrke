// resources/js/helpers/loadModule.ts
import { showToast } from '@/helpers/toastNotify';
import { onDOMReady } from '@/utils/domReady';
import type { ModuleType } from '@/helpers/module/moduleTypes';

/**
 * Carga dinámicamente un módulo desde `/resources/js/` usando Vite import.meta.glob
 *
 * @param moduleFile Ruta relativa dentro de `/resources/js/` (ej: "helpers/global.ts")
 * @param initFunction Nombre de función a ejecutar dentro del módulo (opcional)
 * @returns Una promesa resuelta con `{ module, modal? }`
 */
export async function loadModule(
    moduleFile: string,
    initFunction?: string
): Promise<{ module: ModuleType; modal?: HTMLDivElement }> {
    if (!moduleFile) throw new Error('⚠️ No se proporcionó `moduleFile`.');

    // 🔹 Glob de Vite para todos los módulos dentro de /resources/js/
    const modules = import.meta.glob('/resources/js/**/*.ts');

    const file = moduleFile.trim().replace(/\.ts$/, ''); // quitar extensión si existe
    const key = `/resources/js/${file}.ts`; // agregar .ts

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

        // Extraer export default o named export
        const mod: ModuleType = imported.default ?? imported;

        // Ejecutar función específica si se pasa y existe en el módulo
        if (initFunction && typeof mod[initFunction] === 'function') {
            try {
                mod[initFunction]();
                console.log(`🟢 Función '${initFunction}' ejecutada en módulo '${file}'`);
            } catch (err) {
                console.error(`❌ Error ejecutando '${initFunction}' en '${file}':`, err);
            }
        }

        // Inicializar listeners si existen
        if (typeof mod.initListeners === 'function') {
            onDOMReady(mod.initListeners);
        }

        // Buscar modal si existe viewId
        let modalEl: HTMLDivElement | undefined;
        if (mod.viewId) {
            modalEl = document.getElementById(`modal-${mod.viewId}`) as HTMLDivElement | null;
            if (!modalEl) {
                const msg = `⚠️ Modal con id "modal-${mod.viewId}" no encontrado en el DOM.`;
                console.warn(msg);
                showToast(msg, 'warning');
            }
        }

        console.log(`✅ Módulo "${file}" cargado correctamente.`);
        return { module: mod, modal: modalEl ?? undefined };
    } catch (err) {
        const msg = `❌ Error al importar el módulo '${file}'.`;
        console.error(msg, err);
        showToast(msg, 'warning');
        throw err;
    }
}
