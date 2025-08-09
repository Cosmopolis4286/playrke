import { ajaxStart } from './loader.js';
import { copyInfo } from './copyInfo.js';
import { route } from 'ziggy-js';
import { showToast, showToastOptionBtns } from './toastNotify.js';

// Variables para almacenar los datos del módulo dinámico
let module: any = null;
export let otherCasesMap: Record<string, (encryptedId: string) => void> = {};
export let requiredFields: string[] = [];

// Obtener nombre del módulo a cargar desde input oculto en el DOM
const moduleFile = (document.getElementById('module-file') as HTMLInputElement | null)?.value ?? '';

// Importar módulo dinámicamente solo una vez al cargar el script
if (moduleFile) {
    import(`./${moduleFile}.js`)
        .then(mod => {
            module = mod;
            otherCasesMap = mod.otherCasesMap ?? {};
            requiredFields = mod.requiredFields ?? [];
        })
        .catch(err => {
            console.error('Error al importar módulo dinámico:', err);
        });
}

// Obtener URL base para rutas desde el DOM
const routeURL = (document.getElementById('route-url') as HTMLInputElement | null)?.value ?? '';

// Variable global para id cifrado, se usa en varias funciones
let encryptId: string = '';

/**
 * Función principal para manejar formularios CRUD.
 * @param elem - HTMLElement o array con [acción, id cifrado]
 * @param name - nombre base para formularios y modales
 * @param title - título para mostrar en modal
 */
export async function formCRUD(
    elem: HTMLElement | [string, string],
    name: string,
    title: string
): Promise<void> {
    const modalLabel = document.getElementById(`modal-${name}-label`);
    const form = document.getElementById(`form-${name}`) as HTMLFormElement;

    if (!form || !modalLabel) return;

    form.reset();
    form.querySelectorAll('select:not(.not-empty)').forEach(select => {
        (select as HTMLSelectElement).innerHTML = '';
    });

    let action: string;

    if (Array.isArray(elem)) {
        [action, encryptId] = elem;
    } else if (elem instanceof HTMLElement) {
        const data = elem.dataset;
        action = data.action || '';
        if (action === 'edit' || action === 'delete') {
            encryptId = data.id || '';
        }
    } else {
        console.error('elem no es un HTMLElement ni un array válido');
        return;
    }

    switch (action) {
        case 'create':
            modalLabel.textContent = `Registrar ${title}`;
            form.setAttribute('action', route(`${routeURL}.create`));
            form.querySelectorAll('input[name="_method"]').forEach(i => i.remove());
            insertInfo('create');
            break;

        case 'edit':
            modalLabel.textContent = `Editar ${title}`;
            form.setAttribute('action', route(`${routeURL}.update`, encryptId));
            form.querySelectorAll('input[name="_method"]').forEach(i => i.remove());

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = '_method';
            hiddenInput.value = 'PUT';

            form.appendChild(hiddenInput);
            await getInfo();
            break;

        case 'delete':
            showToastOptionBtns(
                'El registro será eliminado definitivamente',
                'info',
                'Sí, eliminar',
                route(`${routeURL}.delete`, encryptId)
            );
            break;

        default:
            handleOtherCases(action);
            break;
    }
}

/**
 * Ejecuta función especial si la acción está definida en otherCasesMap.
 * @param action - clave de la acción a ejecutar
 */
export function handleOtherCases(action: string): void {
    otherCasesMap[action]?.(encryptId)
        ?? console.error(`Acción no reconocida: ${action}`);
}

/**
 * Valida campos requeridos y envía el formulario si todo está OK.
 * @param formName - nombre base del formulario
 * @param fields - array opcional de campos requeridos, si no se pasan se infieren del DOM
 */
export function submitForm(formName: string, fields: string[] = []): void {
    const form = document.getElementById(`form-${formName}`) as HTMLFormElement;
    if (!form) return;

    if (fields.length === 0) {
        // Infiero campos required si no se pasan explícitamente
        fields = Array.from(form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input[required], select[required]'))
            .map(el => el.id)
            .filter(Boolean);
    }

    const invalidFields = fields.filter(id => {
        const el = document.getElementById(id) as HTMLInputElement | HTMLSelectElement;
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
 * Llama a la función insertInfo del módulo cargado dinámicamente.
 * @param action - tipo de acción, como 'create' o 'edit'
 * @param data - datos opcionales para cargar en el formulario
 */
export function insertInfo(action: string, data: any = ''): void {
    if (module && typeof module.insertInfo === 'function') {
        module.insertInfo(action, data);
    } else {
        console.error(`La función insertInfo no existe o el módulo '${moduleFile}' no está cargado aún.`);
    }
}

/**
 * Obtiene la información para editar y la carga en el formulario.
 */
export async function getInfo(): Promise<void> {
    try {
        const url = route(`${routeURL}.getInfo`, encryptId);
        const response = await fetch(url, { method: 'GET' });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        insertInfo('edit', data);
    } catch (error) {
        console.error('Error al obtener información:', error);
    }
}

// Ejecutar cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    // Escuchar eventos click en todo el documento
    document.addEventListener('click', (event: MouseEvent) => {
        const target = event.target as HTMLElement | null;
        if (!target) return;

        // Manejar botón con clase `.btn-{routeURL}`
        if (target.closest(`.btn-${routeURL}`)) {
            formCRUD(target, routeURL, 'Vendedor');
        }

        // Manejar botón confirmar formulario
        if (target.closest('#btn-confirm')) {
            submitForm(routeURL, requiredFields);
        }

        // Manejar botón para copiar información
        if (target.closest('#btn-copy')) {
            copyInfo('Contraseña Copiada');
        }

        // Manejar toggle de popover en elementos con clase `.popover-dismiss`
        if (target.closest('.popover-dismiss')) {
            $(target).popover('toggle');
        }
    });

    // Escuchar eventos input en todo el documento para validación en tiempo real
    // document.addEventListener('input', (event: Event) => {
    // });
});
