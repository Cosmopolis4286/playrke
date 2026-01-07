import { formCrudState as crud } from '../state/formCrudState';
import { resetForm } from '../formHelpers';
import { getInfo } from './getInfoService';
import { route } from '@/routes/helper';

/**
 * Configura el formulario antes de enviarlo
 */
export function prepareForm(
    form: HTMLFormElement,
    routeName: string,
    method: string = 'POST',
    id?: string,
) {
    const methodUpper = method.toUpperCase();

    try {
        const actionUrl = ['PUT', 'PATCH', 'DELETE'].includes(methodUpper)
            ? (id ? route(crud.routes, routeName, { id }) : (() => { throw new Error(`'id' requerido para ${methodUpper}`); })())
            : route(crud.routes, routeName);

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

function initForm(): HTMLFormElement | null {
    if (!crud.modalId) return null;

    const form = document.querySelector<HTMLFormElement>(`#${crud.moduleId}`);
    if (!form) return null;

    resetForm(form);
    return form;
}

function modalLabel(modalTitle: string): void {
    const modalLabel = document.querySelector(`#modal-${crud.moduleId}-label`);

    if (modalLabel) modalLabel.textContent = modalTitle;
}

export function prepareCreate(): void {
    const form = initForm();
    if (!form) return;

    crud.insertInfo?.('create');
    modalLabel(`Registrar ${crud.formTitleSuffix}`);
    $(crud.modalId!).modal('show');

    prepareForm(form, 'store', 'POST');
}

export async function prepareEdit(encryptId: string): Promise<void> {
    const form = initForm();
    if (!form) return;

    modalLabel(`Editar ${crud.formTitleSuffix}`);
    await getInfo(encryptId);

    prepareForm(form, 'update', 'PUT', encryptId);
}
