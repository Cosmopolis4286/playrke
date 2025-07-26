// resources/js/helpers/functions.ts
import { showToast, showToastOptionBtns } from './toast-notify';

// Tipado global
declare const params: Record<string, any>;
type InsertInfoFn = (type: number, data?: any) => void;

// Loader
export function ajaxStart(): void {
    ['loader', 'loader-sm'].forEach(id =>
        document.getElementById(id)?.classList.remove('hidden')
    );
    document.querySelectorAll<HTMLButtonElement>('button')
        .forEach(btn => btn.disabled = true);
}

export function ajaxStop(): void {
    ['loader', 'loader-sm'].forEach(id =>
        document.getElementById(id)?.classList.add('hidden')
    );
    document.querySelectorAll<HTMLButtonElement>('button:not([data-block])')
        .forEach(btn => btn.disabled = false);
}

// Copiar texto (usa el texto que se pasa, no el valor de un input)
export function copyInfo(text: string): void {
    if (!navigator.clipboard) {
        showToast('Tu navegador no soporta copiar al portapapeles', 'error');
        return;
    }

    navigator.clipboard.writeText(text)
        .then(() => showToast('Texto copiado: ' + text, 'success'))
        .catch(() => showToast('Error al copiar el texto', 'error'));
}

// Eliminar mensajes flash
export function deleteSessionFlash(keys: string[] = []): void {
    fetch(window.route('clear.flash.messages'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message_keys: keys }),
    }).catch(err => console.error('Error eliminando flash messages:', err));
}

// Validar y enviar formulario
export function submitForm(formName: string, requiredFields: string[] = []): void {
    const form = document.getElementById(`form-${formName}`) as HTMLFormElement;
    if (!form) return;

    if (!requiredFields.length) {
        requiredFields = Array.from(form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input[required], select[required]'))
            .map(el => {
                if (!el.id) console.warn('Campo requerido sin id:', el);
                return el.id;
            }).filter(Boolean) as string[];
    }

    const invalid = requiredFields.filter(id => {
        const el = document.getElementById(id) as HTMLInputElement | HTMLSelectElement;
        return !el?.value;
    });

    if (invalid.length) {
        showToast('Todos los campos son requeridos', 'warning');
        return;
    }

    ajaxStart();
    form.submit();
}

// Extra info global
let extraInfo: string | false = false;
export function setExtraInfo(value: string): void {
    extraInfo = value;
}

// Modificar variable global
export function setVariable(name: string, value: any): void {
    if (Object.prototype.hasOwnProperty.call(params, name)) {
        params[name] = value;
    } else {
        console.error(`Variable ${name} no existe.`);
    }
}

// CRUD
export function formCRUD(
    elem: HTMLElement | [number, string],
    name: string,
    title: string,
    otherCasesMap: { [key: number]: (id: string) => void } = {}
): void {
    const modalLabel = document.getElementById(`modal-${name}-label`);
    const form = document.getElementById(`form-${name}`) as HTMLFormElement;
    if (!form || !modalLabel) return;

    form.reset();
    form.querySelectorAll('select:not(.not-empty)').forEach(select => {
        (select as HTMLSelectElement).innerHTML = '';
    });

    let type: number;
    let encryptedId = '';

    if (Array.isArray(elem)) {
        [type, encryptedId] = elem;
    } else if (elem instanceof HTMLElement) {
        const data = elem.dataset;
        if (!data.type) {
            console.error('data-type no definido en el elemento');
            return;
        }
        type = Number(data.type);
        if (type > 1) {
            encryptedId = extraInfo
                ? encodeURIComponent(JSON.stringify({ id: data.id, ...JSON.parse(extraInfo) }))
                : data.id || '';
        }
    } else {
        console.error('elem no es un HTMLElement ni un array válido');
        return;
    }

    switch (type) {
        case 1:
            modalLabel.textContent = `Registrar ${title}`;
            form.setAttribute('action', window.route(`${name}.create`));
            form.querySelectorAll('input[name="_method"]').forEach(i => i.remove());
            (window as any).insertInfo?.(type);
            break;

        case 2:
            modalLabel.textContent = `Editar ${title}`;
            form.setAttribute('action', window.route(`${name}.update`, encryptedId));
            form.querySelectorAll('input[name="_method"]').forEach(i => i.remove());

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = '_method';
            hiddenInput.value = 'PUT';
            form.appendChild(hiddenInput);

            getInfo(name, encryptedId);
            break;

        case 3:
            showToastOptionBtns(
                'El registro será eliminado definitivamente',
                'info',
                'Sí, eliminar',
                '#form-other-options',
                window.route(`${name}.delete`, encryptedId)
            );
            break;

        default:
            otherCasesMap[type]?.(encryptedId)
                ?? console.error(`Tipo de caso no manejado: ${type}`);
    }
}

// Obtener información
export function getInfo(name: string, id: string): void {
    fetch(window.route(`${name}.getInfo`, id), {
        method: 'GET'
    })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            return res.json();
        })
        .then(data => (window as any).insertInfo?.(2, data))
        .catch(err => console.error('Error al obtener información:', err));
}

// Validar números
const inputTimers = new Map<HTMLInputElement, number>();

function cleanInput(input: HTMLInputElement): void {
    input.value = input.value
        .replace(/,/g, '.')
        .replace(/[^\d.]/g, '')
        .replace(/^(\d*\.?\d{0,2}).*$/, '$1');
}

export function validateInput(
    input: HTMLInputElement,
    isFloat: boolean = true,
    callback: ((valid: boolean) => void) | null = null
): void {
    if (inputTimers.has(input)) clearTimeout(inputTimers.get(input)!);

    cleanInput(input);

    const timerId = window.setTimeout(() => {
        const raw = input.value;
        let val = isFloat ? parseFloat(raw) : parseInt(raw, 10);
        const min = parseFloat(input.min || '0');
        const max = parseFloat(input.max || '100');
        let hasError = false;

        if (isNaN(val)) {
            val = min;
            hasError = true;
            showToast('Ingresa un número válido', 'info');
        } else if (val < min || val > max) {
            val = Math.min(Math.max(val, min), max);
            hasError = true;
            showToast(`El valor debe estar entre ${min} y ${max}`, 'info');
        }

        input.value = isFloat ? val.toFixed(2) : val.toString();
        callback?.(!hasError);
        inputTimers.delete(input);
    }, 850);

    inputTimers.set(input, timerId);
}

// String a array
export function stringToArray(str: string, separator: string = ';'): string[] {
    return typeof str === 'string'
        ? str.split(separator).map(s => s.trim()).filter(Boolean)
        : [];
}

// 🔓 Exportar funciones al window global
const exports = {
    ajaxStart,
    ajaxStop,
    copyInfo,
    formCRUD,
    validateInput,
};

Object.entries(exports).forEach(([key, fn]) => {
    // Asignamos en globalThis para que sea accesible como variable global
    (globalThis as any)[key] = fn;
});