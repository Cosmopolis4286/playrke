import { copyInfo } from '@/utils/copyInfo';
import { moduleFile } from '@/data/index';
import { loadModule } from '@/helpers/module/loadModule';
import type { ModuleType, OtherCase } from '@/helpers/module/types/moduleFormTypes';
import { submitForm } from './formCrudCore';

let module: ModuleType | null = null;
export let otherCasesMap: Record<string, OtherCase> = {};
let requiredFields: string[] = [];
let formTitleSuffix: string = '';
let viewId: string = '';
export let route: (name: string, params?: Record<string, string | number>) => string = () => {
    console.warn('Función route no definida todavía');
    return '';
};

let modal: HTMLDivElement | null = null;

/**
 * Inicializa todo el sistema CRUD dinámico (módulo, modal y listeners).
 */
export async function initFormCrud(): Promise<void> {
    if (moduleFile) {
        // await loadModule(moduleFile);
        modal = document.getElementById(`modal-${viewId}`) as HTMLDivElement | null;
        if (!modal) console.warn(`Modal con id 'modal-${viewId}' no encontrado`);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof $ === 'function') {
            const $select2Elements = $('.select2');
            if ($select2Elements.length) {
                $select2Elements.select2({
                    theme: 'bootstrap4',
                    width: 'style',
                    placeholder() { return $(this).attr('placeholder') || ''; },
                    allowClear() { return Boolean($(this).data('allow-clear')); }
                });
            }
        }

        document.addEventListener('click', (event: MouseEvent) => {
            const target = event.target as HTMLElement | null;
            if (!target) return;

            if (target.closest('#btn-confirm')) submitForm(viewId, requiredFields);
            if (target.closest('#btn-copy')) copyInfo('Contraseña Copiada');
            if (target.closest('.popover-dismiss')) $(target).popover('toggle');
        });
    });
}

/**
 * Inserta datos en el formulario usando la función insertInfo del módulo dinámico.
 */
export function insertInfo(action: string, data: any = ''): void {
    if (module?.insertInfo instanceof Function) {
        module.insertInfo(action, data);
        if (modal) $(modal).modal('show');
    } else {
        console.error(
            `La función insertInfo no existe o el módulo '${moduleFile}' no está cargado aún.`
        );
    }
}

document.addEventListener('DOMContentLoaded', () => { initFormCrud() });