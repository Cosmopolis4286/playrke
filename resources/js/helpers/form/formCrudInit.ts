// formCrudInit.ts
import axios from '@/plugins/axios';
import { ajaxStart } from '@/utils/loader';
import { showToast, showToastConfirmAction } from '@/helpers/toastNotify';
import type { OtherCase } from '@/helpers/module/types/moduleFormTypes';
import { formCrudExports } from './formCrudLoadModule';

/**
 * Llama insertInfo si existe y muestra el modal
 */
function safeInsertInfo(action: string, data?: any) {
    const insertFn = formCrudExports.insertInfo;
    const modalEl = formCrudExports.modal;

    if (typeof insertFn === 'function') {
        insertFn(action, data);
        if (modalEl) $(modalEl).modal('show');
    } else {
        console.error(
            `La función insertInfo no existe o el módulo '${formCrudExports.moduleFile}' no está cargado aún.`
        );
    }
}

/**
 * Ejecuta el flujo principal de acciones CRUD
 */
export async function formCRUD(elem: HTMLElement): Promise<void> {
    const moduleId = formCrudExports.moduleId;
    const modalEl = formCrudExports.modal;
    const routeFn = formCrudExports.route;

    if (!modalEl) return console.warn(`Modal no encontrado para: ${moduleId}`);

    const form = document.querySelector<HTMLFormElement>(`#form-${moduleId}`);
    if (form) resetForm(form);

    const action = elem.dataset.action ?? '';
    const encryptId = document.getElementById(`elem-${moduleId}`)?.dataset.id ?? '';
    console.log('formCrud');

    switch (action) {
        case 'create':
            safeInsertInfo('create');
            form && prepareForm(form, 'create', 'Registrar', 'POST', undefined, formCrudExports.formTitleSuffix, routeFn);
            break;
        case 'edit':
            await getInfo(encryptId);
            form && prepareForm(form, 'update', 'Editar', 'PUT', encryptId, formCrudExports.formTitleSuffix, routeFn);
            break;
        case 'delete':
            await handleOtherCases('delete', encryptId);
            break;
        default:
            await handleOtherCases(action, encryptId);
            break;
    }
}

/**
 * Ejecuta acciones personalizadas de otherCasesMap
 */
async function handleOtherCases(action: string, encryptId: string) {
    const moduleId = formCrudExports.moduleId;
    const otherCases = formCrudExports.otherCasesMap;
    const routeFn = formCrudExports.route;

    const globalDelete: OtherCase = {
        routeName: 'delete',
        text: 'El registro será eliminado definitivamente',
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
        method: 'DELETE',
    };

    console.log('action', action);

    const caseData: OtherCase | undefined = action === 'delete' ? globalDelete : otherCases[action];
    if (!caseData) return console.error(`Acción no reconocida: ${action}`);

    try {
        const confirmed = await showToastConfirmAction(caseData.text, caseData.icon, caseData.confirmButtonText);
        if (!confirmed) return showToast('Los cambios no se guardaron', 'info');

        const url = routeFn?.(caseData.routeName, { id: encryptId }) ?? '';
        await axios.request({
            method: (caseData.method ?? 'PUT').toLowerCase() as 'get' | 'post' | 'put' | 'delete' | 'patch',
            url,
        });

        console.log(`Acción '${action}' registrada correctamente para ${moduleId}`);
    } catch (error: any) {
        console.error(`Error al registrar la acción '${action}':`, error?.response?.data || error.message);
    }
}

/**
 * Obtiene datos desde el backend para editar
 */
async function getInfo(encryptId: string) {
    if (!encryptId) return console.error('ID cifrado no definido para getInfo');

    const routeFn = formCrudExports.route;

    try {
        const url = routeFn?.('getInfo', { id: encryptId }) ?? '';
        const { data } = await axios.get(url);
        safeInsertInfo('edit', data);
    } catch (error: any) {
        console.error('Error al obtener información:', error?.response?.data || error.message);
    }
}

/**
 * Valida campos requeridos y envía el formulario
 */
export function submitForm(formSuffix: string, fields: string[] = []) {
    const form = document.getElementById(`form-${formSuffix}`) as HTMLFormElement | null;
    if (!form) return console.warn(`Formulario no encontrado para submit: ${formSuffix}`);

    if (!fields.length) {
        fields = Array.from(form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input[required], select[required]'))
            .map(el => el.id)
            .filter(Boolean);
    }

    const missing = fields.some(id => !(document.getElementById(id) as HTMLInputElement | HTMLSelectElement)?.value);
    if (missing) return showToast('Todos los campos son requeridos', 'warning');

    ajaxStart();
    form.submit();
}

/**
 * Resetea un formulario y limpia selects con data-reset="true"
 */
export function resetForm(form: HTMLFormElement) {
    form.reset();
    form.querySelectorAll<HTMLSelectElement>('select[data-reset="true"]').forEach(select => select.innerHTML = '');
}

/**
 * Configura el formulario antes de enviarlo
 */
export function prepareForm(
    form: HTMLFormElement,
    routeName: string,
    modalTitle: string,
    method: string = 'POST',
    id?: string,
    suffix: string = '',
    routeFn: (name: string, params?: Record<string, string | number>) => string = () => ''
) {
    const methodUpper = method.toUpperCase();
    const modalLabel = document.querySelector(`#${form.id.replace('form', 'modal')}-label`);
    if (modalLabel) modalLabel.textContent = `${modalTitle} ${suffix}`;

    try {
        const actionUrl = ['PUT', 'PATCH', 'DELETE'].includes(methodUpper)
            ? (id ? routeFn(routeName, { id }) : (() => { throw new Error(`'id' requerido para ${methodUpper}`); })())
            : routeFn(routeName);

        form.action = actionUrl;
        form.method = methodUpper;
    } catch (error) {
        console.error(`Error generando la ruta para '${routeName}':`, error);
    }

    // Limpiar métodos anteriores
    form.querySelectorAll('input[name="_method"]').forEach(input => input.remove());

    if (!['POST', 'GET'].includes(methodUpper)) {
        const hiddenMethod = document.createElement('input');
        hiddenMethod.type = 'hidden';
        hiddenMethod.name = '_method';
        hiddenMethod.value = methodUpper;
        form.appendChild(hiddenMethod);
    }

    // CSRF token
    if (!form.querySelector('input[name="_token"]')) {
        const tokenMeta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
        if (tokenMeta?.content) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = tokenMeta.content;
            form.appendChild(tokenInput);
        } else console.warn('Token CSRF no encontrado en meta.');
    }
}
