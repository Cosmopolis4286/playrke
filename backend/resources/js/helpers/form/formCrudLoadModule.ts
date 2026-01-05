// helpers/form/formCrudLoadModule.ts
import { getAppDataProperty } from '@/data';
import { loadModule } from '@/helpers/module/loadModule';
import type { ModuleType } from '@/helpers/module/types/formModule.types';
import { copyInfo } from '@/utils/copyInfo';
import { onDOMReady } from '@/utils/domReady';
import { formCRUD, submitForm } from '@/helpers/form/formCrudInit';
import { registerTableListener } from '@/alpine/tableSelectionListener';

// ==========================
// Estado interno del módulo cargado
// ==========================
let _module: Readonly<ModuleType> | null = null;

let _moduleId: string = '';
let _formTitleSuffix: string = '';
let _tableId: string = '';

let _modalId: HTMLDivElement | null = null;

let _insertInfo: ModuleType['insertInfo'] = () => { };
let _otherCasesMap: NonNullable<ModuleType['otherCasesMap']> = {};
let _requiredFields: NonNullable<ModuleType['requiredFields']> = [];
let _routeFn: NonNullable<ModuleType['routeFn']> = () => '';


/**
 * Export seguro del módulo con getters dinámicos
 */
export const formCrudExports = {
    get moduleId() { return _moduleId; },
    get moduleFile() {
        return getAppDataProperty<string>('moduleFile', '');
    },
    get formTitleSuffix() { return _formTitleSuffix; },
    get tableId() { return _tableId; },
    get modalId() { return _modalId; },
    get insertInfo() { return _insertInfo; },
    get otherCasesMap() { return _otherCasesMap; },
    get requiredFields() { return _requiredFields; },
    get routeFn() { return _routeFn; },
};

/**
 * Inicializa el módulo CRUD dinámico solo si no está cargado aún
 */
export async function initFormCrud(): Promise<void> {
    if (_module) return; // evita doble carga

    const moduleFile = getAppDataProperty<string>('moduleFile', '');
    if (!moduleFile) {
        console.warn('⚠️ moduleFile no definido en appData. Se omite initFormCrud.');
        return;
    }

    _module = await loadModule<ModuleType>(moduleFile);

    _moduleId = _module.moduleId;
    _formTitleSuffix = _module.formTitleSuffix;
    _insertInfo = _module.insertInfo ?? (() => { });
    _otherCasesMap = _module.otherCasesMap ?? {};
    _requiredFields = _module.requiredFields ?? [];
    _routeFn = _module.routeFn ?? (() => '');

    _tableId =
        getAppDataProperty<string>('tableId', '') ||
        `${_moduleId.replace('-form', '')}-table`;

    _modalId = document.getElementById(`modal-${_moduleId}`) as HTMLDivElement | null;

    if (!_modalId) {
        console.warn(`Modal con id 'modal-${_moduleId}' no encontrado`);
    }
    setupListeners();
    registerTableListener(_tableId);
}

/**
 * Configura listeners de click para botones dentro del módulo CRUD
 */
function setupListeners(): void {
    onDOMReady(() => {
        const modalId = `modal-${_moduleId}`;
        const modal = document.getElementById(modalId);

        if (!modal) {
            import.meta.env.DEV &&
                console.warn(`⚠️ Modal con id '${modalId}' no encontrado`);
            return;
        }

        document.addEventListener('click', handleClick);
    });
}

async function handleClick(e: MouseEvent): Promise<void> {
    const target = e.target as HTMLElement | null;
    if (!target) return;

    // Acciones directas (rápidas)
    if (target.closest('#btn-confirm')) {
        submitForm(_moduleId, _requiredFields);
        return;
    }

    if (target.closest('#btn-copy')) {
        copyInfo('Contraseña Copiada');
        return;
    }

    if (target.closest('.popover-dismiss')) {
        $(target).popover('toggle');
        return;
    }

    // Botones del módulo
    const btn = target.closest<HTMLElement>(`.btn-${_moduleId}`);
    if (!btn) return;

    try {
        await formCRUD(btn);
    } catch (err) {
        console.error('Error en formCRUD:', err);
    }
}

