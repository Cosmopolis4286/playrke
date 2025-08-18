import { showToast } from '@/helpers/toastNotify';
import { onDOMReady } from '@/utils/domReady';
import type { ModuleType } from '@/helpers/form/formTypes'; 

/**
 * Carga un módulo dinámico desde ../views/modules/ usando Vite import.meta.glob
 * @param moduleFile Nombre del archivo de módulo a cargar (ej: "commerce/form/commerceForm.ts")
 * @returns Promise que se resuelve con el módulo cargado y el modal HTMLDivElement
 */
export async function loadModule(moduleFile: string): Promise<{ module: ModuleType, modal: HTMLDivElement }> {
    if (!moduleFile) throw new Error('No se proporcionó moduleFile');

    const file = moduleFile.trim();
    const modules = import.meta.glob('../views/modules/**/*.ts');
    const key = `../views/modules/${file.replace(/\.ts$/, '.ts')}`;

    if (!modules[key]) {
        const error = `Módulo '${key}' no encontrado en import.meta.glob`;
        console.error(error);
        showToast(error, 'warning');
        throw new Error(error);
    }

    try {
        console.log('Intentando importar módulo...', key);
        const importedModule = await modules[key]();
        const typedMod: ModuleType = importedModule.default ?? importedModule;

        // Asignar variables globales
        const { viewId, formTitleSuffix, route, otherCasesMap = {}, requiredFields = [], initListeners } = typedMod;

        // Ejecutar initListeners si existe
        if (typeof initListeners === 'function') {
            onDOMReady(initListeners);
        }

        // Obtener modal de manera segura
        const modalEl = document.getElementById(`modal-${viewId}`) as HTMLDivElement;
        if (!modalEl) {
            const error = `Modal con id "modal-${viewId}" no encontrado en el DOM`;
            console.error(error);
            showToast(error, 'warning');
            throw new Error(error);
        }

        console.log(`Módulo "${file}" cargado correctamente`);
        return { module: typedMod, modal: modalEl };
    } catch (err) {
        const error = `Error al importar el módulo dinámico '${file}':`;
        console.error(error, err);
        showToast(error, 'warning');
        throw err;
    }
}
