import { ajaxStart } from './loader.js';
import { copyInfo } from './copyInfo.js';
import { route } from '@/app.js';
import { showToast, showToastOptionBtns } from './toastNotify.js';

// ========================
// Variables globales
// ========================

/** Módulo dinámico cargado según el formulario actual */
let module: any = null;

/** Acciones personalizadas mapeadas desde el módulo dinámico */
export let otherCasesMap: Record<string, (encryptedId: string) => void> = {};

/** Campos requeridos para validación antes de enviar formulario */
export let requiredFields: string[] = [];

/** Nombre del archivo de módulo dinámico (sin extensión) */
const moduleFile = (document.getElementById('module-file') as HTMLInputElement)?.value ?? '';

/** Nombre base de la ruta usado en Ziggy */
const routeURL = (document.getElementById('route-url') as HTMLInputElement)?.value ?? '';

/** ID cifrado global, usado en operaciones edit/delete y otras personalizadas */
let encryptId = '';

// ========================
// Carga dinámica de módulo
// ========================

if (moduleFile) {
    import(`../views/modules/${moduleFile}`)
        .then((mod) => {
            module = mod;
            otherCasesMap = mod.otherCasesMap ?? {};
            requiredFields = mod.requiredFields ?? [];
        })
        .catch((err) => console.error('Error al importar módulo dinámico:', err));
}

// ========================
// Funciones principales
// ========================

/**
 * Controla el flujo CRUD principal (create, edit, delete o casos especiales).
 * @param elem - Elemento HTML que dispara la acción o arreglo con [acción, id cifrado]
 * @param name - Nombre base del formulario/modal
 * @param title - Título descriptivo para mostrar en modal
 */
export async function formCRUD(
    elem: HTMLElement | [string, string],
    name: string,
    title: string
): Promise<void> {
    const modalLabel = document.getElementById(`modal-${name}-label`);
    const form = document.getElementById(`form-${name}`) as HTMLFormElement | null;

    if (!form || !modalLabel) {
        console.warn(`Formulario o modal no encontrado para: ${name}`);
        return;
    }

    resetForm(form);

    let action = '';

    // Determinar acción y encryptId
    if (Array.isArray(elem)) {
        [action, encryptId] = elem;
    } else if (elem instanceof HTMLElement) {
        action = elem.dataset.action ?? '';
        if (['edit', 'delete'].includes(action)) {
            encryptId = elem.dataset.id ?? '';
        }
    }

    await window.ziggyPreloadPromise;

    switch (action) {
        case 'create':
            prepareForm(form, `${routeURL}.create`, `Registrar ${title}`);
            insertInfo('create');
            break;

        case 'edit':
            prepareForm(form, `${routeURL}.update`, `Editar ${title}`, 'PUT');
            await getInfo();
            break;

        case 'delete':
            showToastOptionBtns(
                'El registro será eliminado definitivamente',
                'info',
                'Sí, eliminar',
                route(`${routeURL}.delete`, { id: encryptId })
            );
            break;

        default:
            handleOtherCases(action);
            break;
    }
}

/**
 * Ejecuta la acción personalizada registrada en otherCasesMap.
 */
export function handleOtherCases(action: string): void {
    if (otherCasesMap[action]) {
        otherCasesMap[action](encryptId);
    } else {
        console.error(`Acción no reconocida: ${action}`);
    }
}

/**
 * Valida campos requeridos y envía el formulario.
 */
export function submitForm(formName: string, fields: string[] = []): void {
    const form = document.getElementById(`form-${formName}`) as HTMLFormElement | null;
    if (!form) {
        console.warn(`Formulario no encontrado para submit: ${formName}`);
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
 * Inserta datos en el formulario usando la función insertInfo del módulo dinámico.
 */
export function insertInfo(action: string, data: any = ''): void {
    if (module?.insertInfo instanceof Function) {
        module.insertInfo(action, data);
    } else {
        console.error(
            `La función insertInfo no existe o el módulo '${moduleFile}' no está cargado aún.`
        );
    }
}

/**
 * Obtiene información desde el backend y la carga en el formulario (modo edición).
 */
export async function getInfo(): Promise<void> {
    try {
        if (!encryptId) throw new Error('ID cifrado no definido para getInfo');
        const url = route(`${routeURL}.getInfo`, { id: encryptId });
        const response = await fetch(url);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const data = await response.json();
        insertInfo('edit', data);
    } catch (error) {
        console.error('Error al obtener información:', error);
    }
}

// ========================
// Funciones auxiliares
// ========================

/** Limpia el formulario y campos de select que no tengan la clase `.not-empty` */
function resetForm(form: HTMLFormElement): void {
    form.reset();
    form.querySelectorAll('select:not(.not-empty)').forEach((select) => {
        (select as HTMLSelectElement).innerHTML = '';
    });
}

/**
 * Configura la acción, método y título del formulario/modal.
 * Espera a que las rutas estén precargadas antes de usar `route()`.
 */
function prepareForm(
    form: HTMLFormElement,
    routeName: string,
    modalTitle: string,
    method: string = ''
): void {
    const modalLabel = document.querySelector(`#${form.id.replace('form', 'modal')}-label`);
    if (modalLabel) modalLabel.textContent = modalTitle;

    try {
        if (method) {
            // Para rutas con método PUT (update, delete, etc.) pasa el parámetro id en objeto
            form.setAttribute('action', route(routeName, { id: encryptId }));
        } else {
            // Para create u otras rutas sin parámetro
            form.setAttribute('action', route(routeName));
        }
    } catch (e) {
        console.error(`Error generando ruta para ${routeName}:`, e);
    }

    // Elimina inputs hidden _method anteriores
    form.querySelectorAll('input[name="_method"]').forEach((i) => i.remove());

    if (method) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = '_method';
        hiddenInput.value = method;
        form.appendChild(hiddenInput);
    }
}

// ========================
// Listeners globales
// ========================

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (event: MouseEvent) => {
        const target = event.target as HTMLElement | null;
        if (!target) return;

        if (target.closest(`.btn-${routeURL}`)) {
            formCRUD(target, routeURL, 'Vendedor').catch((e) =>
                console.error('Error en formCRUD:', e)
            );
        }

        if (target.closest('#btn-confirm')) {
            submitForm(routeURL, requiredFields);
        }

        if (target.closest('#btn-copy')) {
            copyInfo('Contraseña Copiada');
        }

        if (target.closest('.popover-dismiss')) {
            // Bootstrap 4 popover requiere jQuery
            $(target).popover('toggle');
        }
    });
});
