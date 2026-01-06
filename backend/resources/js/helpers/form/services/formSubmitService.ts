import axios from '@/plugins/axios';

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

    return axios.request({
        url: url || form.action,
        method: method || (form.method?.toLowerCase() as SubmitMethod) || 'post',
        data: formData,
        headers,
    });
}

export async function submitForm(formId: string, fields: string[] = []) {
    const form = document.getElementById(formId) as HTMLFormElement | null;
    if (!form) return;

    const validation = validateRequiredFields(formId, fields, true);
    if (!validation.isValid) {
        showToast(
            `Campos requeridos faltantes: ${validation.missingFields.join(', ')}`,
            'warning'
        );
        validation.firstInvalidField?.focus();
        return;
    }

    clearFormValidation(formId);

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
