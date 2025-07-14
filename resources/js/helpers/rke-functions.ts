// rke-functions.ts

import { showToast } from './toast-notify'

// Mostrar loader
export function ajaxStart(): void {
    const loader = document.getElementById('loader');
    const loaderSm = document.getElementById('loader-sm');
    loader?.classList.remove('d-none');
    loaderSm?.classList.remove('d-none');

    document.querySelectorAll<HTMLButtonElement>('button').forEach(btn => {
        btn.disabled = true;
    });
}

// Ocultar loader
export function ajaxStop(): void {
    const loader = document.getElementById('loader');
    const loaderSm = document.getElementById('loader-sm');
    loader?.classList.add('d-none');
    loaderSm?.classList.add('d-none');

    document.querySelectorAll<HTMLButtonElement>('button:not([data-block])').forEach(btn => {
        btn.disabled = false;
    });
}

// Copiar texto al portapapeles
export function copyInfo(text: string, elementId: string = 'info-to-copy'): void {
    const elem = document.getElementById(elementId) as HTMLInputElement | null;
    if (!navigator.clipboard || !elem) return;

    navigator.clipboard.writeText(elem.value)
        .then(() => showToast(text, 'success'))
        .catch(() => showToast('No se pudo copiar', 'error'));
}

// Eliminar mensajes flash (async con try/catch para manejo errores)
export async function deleteSessionFlash(keys: string[]): Promise<void> {
    const csrfToken = (document.getElementById('5K3_70K') as HTMLInputElement)?.value ?? '';

    try {
        const response = await fetch(route('clear.flash.messages'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ message_keys: keys })
        });

        if (!response.ok) {
            console.error('Error al eliminar mensajes flash:', response.statusText);
        }
    } catch (error) {
        console.error('Error en deleteSessionFlash:', error);
    }
}

// Validar y enviar formulario
export function submitForm(formId: string, requiredFields: string[] = []): void {
    const form = document.getElementById(`form-${formId}`) as HTMLFormElement | null;
    if (!form) return;

    if (!requiredFields.length) {
        const inputs = form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[required]');
        requiredFields = Array.from(inputs).map(input => input.id).filter(Boolean);
    }

    const invalidFields = requiredFields.filter(id => {
        const el = document.getElementById(id) as HTMLInputElement | null;
        return !el || !el.value.trim();
    });

    if (invalidFields.length) {
        showToast('Todos los campos son requeridos', 'warning');
        return;
    }

    ajaxStart();
    form.submit();
}

// Convertir string a array
export function stringToArray(str: string, separator: string = ';'): string[] {
    if (!str) return [];
    return str.split(separator).map(s => s.trim()).filter(Boolean);
}

// Validación de inputs numéricos
export function validateInput(
    input: HTMLInputElement,
    isFloat = true,
    callback?: (valid: boolean) => void
): void {
    // Limpieza inmediata del valor
    let value = input.value.replace(/,/g, '.').replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
    input.value = value;

    setTimeout(() => {
        const rawValue = input.value;
        const parsed = isFloat ? parseFloat(rawValue) : parseInt(rawValue);
        const min = parseFloat(input.min || '0');
        const max = parseFloat(input.max || '100');

        let hasError = false;

        if (isNaN(parsed)) {
            input.value = isFloat ? min.toFixed(2) : min.toString();
            hasError = true;
            showToast('Ingresa un número válido', 'info');
        } else if (parsed < min || parsed > max) {
            input.value = isFloat ? Math.min(Math.max(parsed, min), max).toFixed(2) : Math.min(Math.max(parsed, min), max).toString();
            hasError = true;
            showToast(`El valor debe estar entre ${min} y ${max}`, 'info');
        } else {
            input.value = isFloat ? parsed.toFixed(2) : parsed.toString();
        }

        if (callback) callback(!hasError);
    }, 500);
}

// Declaraciones globales para evitar errores de TS
declare function route(name: string, params?: any): string;

// Exportar opcionalmente todas como objeto default
export default {
    ajaxStart,
    ajaxStop,
    copyInfo,
    deleteSessionFlash,
    submitForm,
    stringToArray,
    validateInput,
}