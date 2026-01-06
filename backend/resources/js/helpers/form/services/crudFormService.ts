import { formCrudState as crud } from '../state/formCrudState';
import { prepareForm, resetForm } from '../formHelpers';
import { getInfo } from './getInfoService';

function initForm(): HTMLFormElement | null {
    if (!crud.modalId) return null;

    const form = document.querySelector<HTMLFormElement>(`#${crud.moduleId}`);
    if (!form) return null;

    resetForm(form);
    return form;
}

export function prepareCreate(): void {
    const form = initForm();
    if (!form) return;

    crud.insertInfo?.('create');
    $(crud.modalId!).modal('show');

    prepareForm(
        form,
        'store',
        `Registrar ${crud.formTitleSuffix}`,
        'POST',
        undefined,
        crud.routeFn
    );
}

export async function prepareEdit(encryptId: string): Promise<void> {
    const form = initForm();
    if (!form) return;

    await getInfo(encryptId);

    prepareForm(
        form,
        'update',
        `Editar ${crud.formTitleSuffix}`,
        'PUT',
        encryptId,
        crud.routeFn
    );
}
