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
export async function submitFormService({
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
