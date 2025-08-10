// formCrud.ts
import { ajaxStart } from './loader.js';
import { copyInfo } from './copyInfo.js';
import { showToast, showToastOptionBtns } from './toastNotify.js';

// ========================
// Variables globales
// ========================

/** 
 * Módulo cargado dinámicamente según el formulario actual.
 * Contendrá funciones y datos específicos del módulo.
 */
let module: any = null;

/**
 * Función `route` para generar URLs según nombre y parámetros.
 * Inicialmente es un stub que avisa que no está definida.
 * Se sobrescribe tras la carga dinámica del módulo.
 */
let route: (name: string, params?: Record<string, string | number>) => string = () => {
    console.warn('Función route no definida todavía');
    return '';
};

/**
 * Mapeo de acciones personalizadas a funciones que reciben un ID cifrado.
 * Se llena tras la carga dinámica del módulo.
 */
let otherCasesMap: Record<string, (encryptedId: string) => void> = {};

/**
 * Lista de campos requeridos para validar antes de enviar formularios.
 * Se establece tras importar el módulo dinámico.
 */
let requiredFields: string[] = [];

/**
 * Identificador dinámico del elemento actual.
 * Se asigna o actualiza tras importar el módulo dinámico.
 */
let elemId: string = '';

/**
 * Nombre del archivo del módulo dinámico a cargar (sin extensión).
 * Se obtiene desde un input hidden en el DOM, para saber qué módulo cargar.
 */
const moduleFile = (document.getElementById('module-file') as HTMLInputElement)?.value ?? '';

/**
 * ID cifrado global que se usa en operaciones como editar, eliminar,
 * o para acciones personalizadas, asignado dinámicamente según contexto.
 */
let encryptId = '';

// ========================
// Carga dinámica del módulo
// ========================

if (moduleFile) {
    import(`../views/modules/${moduleFile}`)
        .then((mod) => {
            // Guardamos el módulo importado para uso posterior
            module = mod;

            // Extraemos y asignamos variables exportadas, con fallback a valores por defecto
            elemId = mod.elemId;
            otherCasesMap = mod.otherCasesMap ?? {};
            requiredFields = mod.requiredFields ?? [];
            route = mod.route;

            console.log(`Módulo dinámico '${moduleFile}' cargado correctamente.`);
        })
        .catch((err) => {
            console.error(`Error al importar el módulo dinámico '${moduleFile}':`, err);
        });
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

    switch (action) {
        case 'create':
            prepareForm(form, 'create', `Registrar ${title}`);
            insertInfo('create');
            break;

        case 'edit':
            prepareForm(form, 'update', `Editar ${title}`, 'PUT');
            await getInfo();
            break;

        case 'delete':
            showToastOptionBtns(
                'El registro será eliminado definitivamente',
                'info',
                'Sí, eliminar',
                route('delete', { id: encryptId })
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
        const url = route('getInfo', { id: encryptId });
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

        if (target.closest(`.btn-${elemId}`)) {
            formCRUD(target, elemId, 'Vendedor').catch((e) =>
                console.error('Error en formCRUD:', e)
            );
        }

        if (target.closest('#btn-confirm')) {
            submitForm(elemId, requiredFields);
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
