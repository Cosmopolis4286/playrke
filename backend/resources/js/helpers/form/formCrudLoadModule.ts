// helpers/form/formCrudLoadModule.ts
import { getAppDataProperty } from '@/data';
import { loadModule } from '@/helpers/module/loadModule';
import type { ModuleType } from '@/helpers/module/types/formModule.types';
import { copyInfo } from '@/utils/copyInfo';
import { onDOMReady } from '@/utils/domReady';
import { devConsole } from '@/utils/console';

// ==========================
// Estado interno del módulo cargado
// ==========================
let _module: Readonly<ModuleType> | null = null;

let _moduleId: string = '';
let _formTitleSuffix: string = '';
let _tableId: string = '';

let _modal: HTMLDivElement | null = null;

let _insertInfo: ModuleType['insertInfo'] = () => { };
let _otherCasesMap: NonNullable<ModuleType['otherCasesMap']> = {};
let _requiredFields: NonNullable<ModuleType['requiredFields']> = [];
let _route: NonNullable<ModuleType['route']> = () => '';


/**
 * Export seguro del módulo con getters dinámicos
 */
export const formCrudExports = {
    get moduleId() { return _moduleId; },
    get formTitleSuffix() { return _formTitleSuffix; },
    get tableId() { return _tableId; },
    get modal() { return _modal; },

    get insertInfo() { return _insertInfo; },
    get otherCasesMap() { return _otherCasesMap; },
    get requiredFields() { return _requiredFields; },
    get route() { return _route; },

    get moduleFile() {
        return getAppDataProperty<string>('moduleFile', '');
    },
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
    _route = _module.route ?? (() => '');

    _tableId =
        getAppDataProperty<string>('tableId', '') ||
        `${_moduleId.replace('-form', '')}-table`;

    _modal = document.getElementById(`modal-${_moduleId}`) as HTMLDivElement | null;

    if (!_modal) {
        console.warn(`Modal con id 'modal-${_moduleId}' no encontrado`);
    }

    devConsole('log', '🧩 CRUD MODULE LOADED:', {
        moduleFile,
        moduleId: _moduleId,
        routeTest: _route('changeStatus', { id: 'TEST' }),
    });

    setupListeners();
    setupTableListener();
}

/**
 * Configura el listener de selección de tabla
 */
function setupTableListener(): void {
    if (!_tableId) return;

    // Importación dinámica para evitar dependencias innecesarias
    import('@/helpers/form/formCrudInit').then(({ registerTableListener }) => {
        registerTableListener(_tableId);
    });
}

/**
 * Configura listeners de click para botones dentro del módulo CRUD
 */
function setupListeners(): void {
    onDOMReady(() => {
        document.addEventListener('click', async (e) => {
            const target = e.target as HTMLElement | null;
            if (!target) return;

            // Submit automático (solo si el módulo lo permite)
            if (target.closest('#btn-confirm')) {
                const { submitForm } = await import('@/helpers/form/formCrudInit');
                submitForm(_moduleId, _requiredFields);
                return;
            }

            if (target.closest('#btn-copy')) {
                copyInfo('Contraseña Copiada');
                return;
            }

            if (target.closest('.popover-dismiss')) {
                $(target).popover('toggle');
            }
        });
    });
}
