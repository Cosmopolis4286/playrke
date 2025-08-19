import axios from '@/plugins/axios';
import { ajaxStart } from '@/utils/loader';
import { showToast, showToastConfirmAction } from '@/helpers/toastNotify';
import type { OtherCase } from '@/helpers/module/types/moduleFormTypes';
import { otherCasesMap, route } from './formCrudInit';

/**
 * Gestiona el flujo principal de acciones CRUD.
 */
export async function formCRUD(elem: HTMLElement): Promise<void> {
    const form = document.querySelector<HTMLFormElement>(`#form-${viewId}`);

    if (!modal) {
        console.warn(`Formulario o modal no encontrado para: ${viewId}`);
        return;
    }

    if (form) resetForm(form);

    const action = elem.dataset.action ?? '';
    const encryptId = document.getElementById(`elem-${viewId}`)?.dataset.id ?? '';

    switch (action) {
        case 'create':
            insertInfo('create');
            if (form) prepareForm(form, 'create', 'Registrar', 'POST', undefined, formTitleSuffix, route);
            break;

        case 'edit':
            await getInfo(encryptId);
            if (form) prepareForm(form, 'update', 'Editar', 'PUT', encryptId, formTitleSuffix, route);
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
 * Ejecuta acciones personalizadas registradas en otherCasesMap.
 */
async function handleOtherCases(action: string, encryptId: string): Promise<void> {
    const globalDelete: OtherCase = {
        routeName: 'delete',
        text: 'El registro será eliminado definitivamente',
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
        method: 'DELETE',
    };

    const caseData: OtherCase | undefined =
        action === 'delete' ? globalDelete : otherCasesMap[action];

    if (!caseData) {
        console.error(`Acción no reconocida: ${action}`);
        return;
    }

    try {
        const confirmed = await showToastConfirmAction(
            caseData.text,
            caseData.icon,
            caseData.confirmButtonText
        );

        if (!confirmed) {
            showToast('Los cambios no se guardaron', 'info');
            return;
        }

        const url = route(caseData.routeName, { id: encryptId });

        const response = caseData.method === 'DELETE'
            ? await axios.delete(url)
            : await axios.put(url);

        console.log('Acción registrada correctamente:', response.data);
    } catch (error) {
        console.error('Error al registrar la acción:', error);
    }
}

/**
 * Obtiene información desde el backend y la carga en el formulario (modo edición).
 */
async function getInfo(encryptId: string): Promise<void> {
    try {
        if (!encryptId) throw new Error('ID cifrado no definido para getInfo');

        const url = route('getInfo', { id: encryptId });

        const response = await axios.get(url);

        insertInfo('edit', response.data);
    } catch (error: any) {
        console.error('Error al obtener información:', error?.response?.data || error.message);
    }
}

/**
 * Valida campos requeridos y envía el formulario.
 * @param formSuffix Sufijo del ID del form
 * @param fields Lista de campos requeridos (opcional)
 */
export function submitForm(formSuffix: string, fields: string[] = []): void {
    const form = document.getElementById(`form-${formSuffix}`) as HTMLFormElement | null;
    if (!form) {
        console.warn(`Formulario no encontrado para submit: ${formSuffix}`);
        return;
    }

    // Detectar campos requeridos si no se pasan manualmente
    if (fields.length === 0) {
        fields = Array.from(
            form.querySelectorAll<HTMLInputElement | HTMLSelectElement>(
                'input[required], select[required]'
            )
        )
            .map((el) => el.id)
            .filter(Boolean);
    }

    const invalidFields = fields.filter((id) => {
        const el = document.getElementById(id) as HTMLInputElement | HTMLSelectElement | null;
        return !el?.value;
    });

    if (invalidFields.length) {
        showToast('Todos los campos son requeridos', 'warning');
        return;
    }

    ajaxStart();
    form.submit();
}

/**
 * Resetea un formulario y limpia selects.
 */
export function resetForm(form: HTMLFormElement): void {
    form.reset();
    form.querySelectorAll('select:not(.not-empty)').forEach((select) => {
        (select as HTMLSelectElement).innerHTML = '';
    });
}

/**
 * Configura atributos y tokens del formulario antes de enviarlo.
 */
export function prepareForm(
    form: HTMLFormElement,
    routeName: string,
    modalTitle: string,
    method: string = 'POST',
    id?: string,
    formTitleSuffix: string = '',
    route: (name: string, params?: Record<string, string | number>) => string = () => ''
): void {
    const methodUpper = method.toUpperCase();
    const modalLabel = document.querySelector(`#${form.id.replace('form', 'modal')}-label`);
    if (modalLabel) modalLabel.textContent = `${modalTitle} ${formTitleSuffix}`;

    try {
        const methodRequiresId = ['PUT', 'PATCH', 'DELETE'].includes(methodUpper);
        const actionUrl = methodRequiresId
            ? (id ? route(routeName, { id }) : (() => { throw new Error(`El parámetro 'id' es obligatorio para método ${methodUpper}`); })())
            : route(routeName);

        form.setAttribute('action', actionUrl);
        form.method = methodUpper;
    } catch (error) {
        console.error(`Error generando la ruta para '${routeName}':`, error);
        return;
    }

    // Limpiar inputs previos
    form.querySelectorAll('input[name="_method"]').forEach(input => input.remove());

    if (methodUpper !== 'POST' && methodUpper !== 'GET') {
        const hiddenMethodInput = document.createElement('input');
        hiddenMethodInput.type = 'hidden';
        hiddenMethodInput.name = '_method';
        hiddenMethodInput.value = methodUpper;
        form.appendChild(hiddenMethodInput);
    }

    if (!form.querySelector('input[name="_token"]')) {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
        if (tokenMeta?.content) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = tokenMeta.content;
            form.appendChild(tokenInput);
        } else {
            console.warn('Token CSRF no encontrado en meta.');
        }
    }
}
