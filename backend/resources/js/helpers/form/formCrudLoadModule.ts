// helpers/form/formCrudLoadModule.ts
import { getAppDataProperty } from '@/data';
import { loadModule } from '@/helpers/module/loadModule';
import type { ModuleType } from '@/helpers/module/types/formModule.types';
import { setFormCrudState } from './state/formCrudState';
import { initFormCrudController } from './controller/formCrudController';

// ==========================
// Estado interno del módulo cargado
// ==========================
let _module: Readonly<ModuleType> | null = null;

/**
 * Inicializa el módulo CRUD dinámico solo si no está cargado aún.
 *
 * Responsabilidad:
 * - Cargar el módulo dinámico
 * - Inicializar el estado global CRUD
 * - Registrar listeners base (tabla)
 *
 * ❌ No maneja eventos UI
 * ❌ No ejecuta lógica CRUD
 */
export async function initFormCrud(): Promise<void> {
    if (_module) return; // evita doble carga

    const moduleFile = getAppDataProperty<string>('moduleFile', '');
    if (!moduleFile) {
        console.warn('⚠️ moduleFile no definido en appData. Se omite initFormCrud.');
        return;
    }

    _module = await loadModule<ModuleType>(moduleFile);

    const moduleId = _module.moduleId;

    const tableId =
        getAppDataProperty<string>('tableId', '') ||
        `${moduleId.replace('-form', '')}-table`;

    const modalId =
        document.getElementById(`modal-${moduleId}`) as HTMLDivElement | null;

    if (!modalId) {
        console.warn(`⚠️ Modal con id 'modal-${moduleId}' no encontrado`);
    }

    /**
     * Inicializar estado CRUD global
     */
    setFormCrudState({
        moduleId,
        moduleFile,
        tableId,
        modalId,
        formTitleSuffix: _module.formTitleSuffix,
        insertInfo: _module.insertInfo ?? (() => { }),
        otherCasesMap: _module.otherCasesMap ?? {},
        requiredFields: _module.requiredFields ?? [],
        routes: _module.routes
    });
    /**
     * Inicializar controller CRUD (UI + eventos)
     * Se ejecuta SOLO después de que el módulo y el estado estén listos
     */
    initFormCrudController();
}
