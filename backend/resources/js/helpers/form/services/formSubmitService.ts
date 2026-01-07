import axios from '@/plugins/axios';
import { validateAndFocus } from '../ui/crudFormUI';
import { showToast } from '@/helpers/toastNotify';
import { ajaxStart, ajaxStop } from '@/utils/loader';

export type SubmitMethod =
    | 'post'
    | 'put'
    | 'patch'
    | 'delete';

interface SubmitFormOptions {
    form: HTMLFormElement;
    method?: SubmitMethod;
    url?: string;
    headers?: Record<string, string>;
}

/**
 * Servicio único y reutilizable para enviar formularios vía Axios
 * No maneja UI, loaders ni validaciones
 */
async function submitFormService({
    form,
    method,
    url,
    headers = {},
}: SubmitFormOptions) {
    if (!form.action && !url) {
        throw new Error('submitFormService: form.action o url es requerido');
    }

    const formData = new FormData(form);

    // Obtener método real: si se pasa explícitamente, usarlo,
    // si no, verificar si hay _method oculto en el formData,
    // si no, usar form.method o 'post' por defecto
    const formMethod = form.method?.toLowerCase() as SubmitMethod | undefined;
    const hiddenMethod = formData.get('_method') as string | null;

    const realMethod =
        method ||
        (hiddenMethod ? hiddenMethod.toLowerCase() : formMethod) ||
        'post';

    console.log({
        url: url,
        action: form.action,
        method: realMethod,
        data: formData,
        headers,
    });

    return axios.request({
        url: url || form.action,
        method: realMethod,
        data: formData,
        headers,
    });
}

export async function submitForm(formId: string, fields: string[] = []) {
    const form = document.getElementById(formId) as HTMLFormElement | null;
    if (!form) return;

    validateAndFocus(formId, fields);

    try {
        ajaxStart();

        const { data } = await submitFormService({ form });

        if (!data?.success) {
            throw new Error(data?.message);
        }

        showToast(data.message || 'Operación exitosa', 'success');

        setTimeout(() => window.location.reload(), 1000);
    } catch (error: any) {
        showToast(
            error.response?.data?.message || 'Error al enviar formulario',
            'error'
        );
    } finally {
        ajaxStop();
    }
}
