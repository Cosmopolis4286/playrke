// formCrud.ts
import axios from '../axios.js';
import { ajaxStart } from './loader.js';
import { copyInfo } from './copyInfo.js';
import { showToast, showToastConfirmAction } from './toastNotify.js';
import { onDOMReady } from '../utils/domReady.js';
import Swal, { SweetAlertIcon } from 'sweetalert2';

// --------------------------------------
// Definición de la interfaz del módulo dinámico
// --------------------------------------
interface ModuleType {
    formTitleSuffix: string;
    otherCasesMap?: Record<string, OtherCase>;
    requiredFields?: string[];
    viewId: string;
    initListeners?: () => void;
    insertInfo?: (action: string, data?: any) => void;
    route: (name: string, params?: Record<string, string | number>) => string;
}

/**
 * Tipo de cada acción personalizada que se puede ejecutar.
 */
export interface OtherCase {
    routeName: string;               // Nombre de la ruta backend
    text: string;                    // Texto para mostrar en el modal
    icon: SweetAlertIcon;            // Icono para SweetAlert
    confirmButtonText: string;       // Texto del botón de confirmación
    method?: 'PUT' | 'DELETE';       // Método HTTP (por defecto PUT)
}

// ========================
// Variables globales
// ========================

let module: ModuleType | null = null;
let otherCasesMap: Record<string, OtherCase> = {};
let requiredFields: string[] = [];
let formTitleSuffix: string = '';
let viewId: string = '';
let route: (name: string, params?: Record<string, string | number>) => string = () => {
    console.warn('Función route no definida todavía');
    return '';
};
let encryptId = '';
const moduleFile = (document.getElementById('module-file') as HTMLInputElement)?.value ?? '';

// ========================
// Carga dinámica del módulo
// ========================

if (moduleFile) {
    const modules = import.meta.glob('../views/modules/**/*.ts');
    const key = `../views/modules/${moduleFile}`.replace(/\.ts$/, '.ts');

    if (modules[key]) {
        modules[key]().then((mod) => {
            const typedMod = mod as ModuleType;
            module = typedMod;

            formTitleSuffix = typedMod.formTitleSuffix;
            viewId = typedMod.viewId;
            otherCasesMap = typedMod.otherCasesMap ?? {};
            requiredFields = typedMod.requiredFields ?? [];
            route = typedMod.route;

            if (typeof typedMod.initListeners === 'function') {
                onDOMReady(typedMod.initListeners);
            }
        }).catch(err => {
            console.error(`Error al importar el módulo dinámico '${moduleFile}':`, err);
        });
    } else {
        console.error(`Módulo '${key}' no encontrado en import.meta.glob`);
    }
}

// ========================
// Funciones principales
// ========================

/**
 * Gestiona el flujo principal de acciones CRUD (crear, editar, eliminar o especiales).
 * @param element - Elemento HTML que dispara la acción.
 */
export async function formCRUD(elem: HTMLElement): Promise<void> {
    const modalLabel = document.getElementById(`modal-${viewId}-label`);
    const form = document.querySelector<HTMLFormElement>(`#form-${viewId}`);

    if (!form || !modalLabel) {
        console.warn(`Formulario o modal no encontrado para: ${viewId}`);
        return;
    }

    resetForm(form);

    const action = elem.dataset.action ?? '';
    encryptId = document.getElementById(`elem-${viewId}`)?.dataset.id ?? '';

    switch (action) {
        case 'create':
            insertInfo('create');
            prepareForm(form, 'create', 'Registrar', 'POST');
            break;

        case 'edit':
            await getInfo();
            prepareForm(form, 'update', 'Editar', 'PUT', encryptId);
            break;

        case 'delete':
            await handleOtherCases('delete');
            break;

        default:
            await handleOtherCases(action);
            break;
    }
}

/**
 * Ejecuta la acción personalizada registrada en otherCasesMap.
 * Solo ejecuta la petición PUT o DELETE si el usuario confirma la acción.
 * @param action Clave de la acción a ejecutar
 */
export async function handleOtherCases(action: string): Promise<void> {
    const globalDelete: OtherCase = {
        routeName: 'deleteVendor',
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
            Swal.fire('Los cambios no se guardaron', '', 'info');
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
async function getInfo(): Promise<void> {
    try {
        if (!encryptId) throw new Error('ID cifrado no definido para getInfo');

        // Construir URL usando la función route
        const url = route('getInfo', { id: encryptId });

        // Petición GET con Axios
        const response = await axios.get(url);

        // Insertar datos en el formulario usando insertInfo
        insertInfo('edit', response.data);
    } catch (error: any) {
        console.error('Error al obtener información:', error?.response?.data || error.message);
    }
}

// ========================
// Funciones auxiliares
// ========================

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
    method: string = 'POST',
    id?: string
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

// ========================
// Listeners globales
// ========================

document.addEventListener('DOMContentLoaded', () => {
    if (typeof $ === 'function') {
        const $select2Elements = $('.select2');
        if ($select2Elements.length) {
            $select2Elements.select2({
                theme: 'bootstrap4',
                width: 'style',
                placeholder() { return $(this).attr('placeholder') || ''; },
                allowClear() { return Boolean($(this).data('allow-clear')); }
            });
        }
    }

    document.addEventListener('click', (event: MouseEvent) => {
        const target = event.target as HTMLElement | null;
        if (!target) return;

        if (target.closest('#btn-confirm')) submitForm(viewId, requiredFields);
        if (target.closest('#btn-copy')) copyInfo('Contraseña Copiada');
        if (target.closest('.popover-dismiss')) $(target).popover('toggle');
    });
});
