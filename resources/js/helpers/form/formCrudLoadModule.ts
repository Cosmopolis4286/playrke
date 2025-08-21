// resources/js/helpers/form/formCrudLoadModule.ts
import { getModuleFile } from '@/data/index';
import { loadModule } from '@/helpers/module/loadModule';
import type { ModuleType, OtherCase } from '@/helpers/module/types/moduleFormTypes';
import { submitForm } from './formCrudInit';
import { copyInfo } from '@/utils/copyInfo';

let _formTitleSuffix = '';
let _insertInfo: (action: string, data?: any) => void = () => {};
let _modal: HTMLDivElement | null = null;
let _module: ModuleType | null = null;
let _otherCasesMap: Record<string, OtherCase> = {};
let _requiredFields: string[] = [];
let _route: (name: string, params?: Record<string, string | number>) => string = () => '';
let _viewId = '';

// Export seguro del módulo con getters dinámicos
export const formCrudExports = {
    get formTitleSuffix() { return _formTitleSuffix; },
    get insertInfo() { return _insertInfo; },
    get modal() { return _modal; },
    get otherCasesMap() { return _otherCasesMap; },
    get requiredFields() { return _requiredFields; },
    get route() { return _route; },
    get viewId() { return _viewId; },
    get moduleFile() { return getModuleFile(); } // ✅ dinámico
};

/**
 * Inicializa el módulo CRUD dinámico solo si no está cargado aún
 */
export async function initFormCrud(): Promise<void> {
    const moduleFile = getModuleFile(); // ✅ obtener valor dinámico
    if (!moduleFile) return;            // si no existe, salir
    if (_module) return;                // evita doble carga

    _module = await loadModule<ModuleType>(moduleFile);

    _viewId = _module.viewId;
    _formTitleSuffix = _module.formTitleSuffix;
    _insertInfo = _module.insertInfo ?? (() => {});
    _otherCasesMap = _module.otherCasesMap ?? {};
    _requiredFields = _module.requiredFields ?? [];
    _route = _module.route ?? (() => '');

    _modal = document.getElementById(`modal-${_viewId}`) as HTMLDivElement | null;
    if (!_modal) console.warn(`Modal con id 'modal-${_viewId}' no encontrado`);

    setupListeners();
}

function setupListeners(): void {
      const initClickListeners = () => {
        document.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            if (!target) return;

            if (target.closest('#btn-confirm')) submitForm(_viewId, _requiredFields);
            if (target.closest('#btn-copy')) copyInfo('Contraseña Copiada');
            if (target.closest('.popover-dismiss')) $(target).popover('toggle');
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initClickListeners();
        });
    } else {
        initClickListeners();
    }
}
