// formCrud.ts
import { ajaxStart } from './loader.js';
import { copyInfo } from './copyInfo.js';
import { showToast, showToastOptionBtns } from './toastNotify.js';
import { onDOMReady } from '@/utils/domReady.js';

// --------------------------------------
// Definición de la interfaz del módulo dinámico
// --------------------------------------
interface ModuleType {
    formTitleSuffix: string;
    otherCasesMap?: Record<string, (encryptedId: string) => void>;
    requiredFields?: string[];
    viewId: string;
    initListeners?: () => void;
    insertInfo?: (action: string, data?: any) => void;
    route: (name: string, params?: Record<string, string | number>) => string;
}

// ========================
// Variables globales
// ========================

/** 
 * Módulo cargado dinámicamente según el formulario actual.
 * Contendrá funciones y datos específicos del módulo.
 */
let module: ModuleType | null = null;

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
 * Identificador dinámico del sufijo del form.
 * Se asigna o actualiza tras importar el módulo dinámico.
 */
let formTitleSuffix: string = '';

/**
 * Identificador dinámico del view/route actual.
 * Se asigna o actualiza tras importar el módulo dinámico.
 */
let viewId: string = '';

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

// Mapea todos los archivos .ts dentro de views/modules y subcarpetas
const modules = import.meta.glob('../views/modules/**/*.ts');

// Si moduleFile es 'seller/form/sellerForm.ts' o sin extensión, elimina la extensión para buscar clave correcta
const key = `../views/modules/${moduleFile}`.replace(/\.ts$/, '.ts');

// Verifica que el módulo existe
if (modules[key]) {
    modules[key]().then((mod) => {
        const typedMod = mod as ModuleType;

        module = typedMod; // Aquí ya esta el módulo importado

        // Lógica para asignar variables exportadas
        formTitleSuffix = typedMod.formTitleSuffix;
        viewId = typedMod.viewId;
        otherCasesMap = typedMod.otherCasesMap ?? {};
        requiredFields = typedMod.requiredFields ?? [];
        route = typedMod.route;

        // Si el módulo tiene un initListeners, lo ejecutamos cuando el DOM esté listo
        if (typeof typedMod.initListeners === 'function') {
            onDOMReady(typedMod.initListeners);
        }
    }).catch(err => {
        console.error(`Error al importar el módulo dinámico '${moduleFile}':`, err);
    });
} else {
    console.error(`Módulo '${key}' no encontrado en import.meta.glob`);
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

    // Definir action
    const action = elem.dataset.action ?? '';

    // Definir encryptId
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
export function submitForm(fields: string[] = []): void {
    const form = document.getElementById(`form-${formTitleSuffix}`) as HTMLFormElement | null;
    if (!form) {
        console.warn(`Formulario no encontrado para submit: ${formTitleSuffix}`);
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

/**
 * Prepara un formulario para su uso en un modal, configurando:
 * - El título del modal relacionado.
 * - La URL de acción del formulario con la ruta y parámetros adecuados.
 * - El método HTTP del formulario (POST, PUT, PATCH, DELETE).
 * - Inserta dinámicamente un input oculto `_method` para métodos que no son GET o POST.
 * 
 * @param form Elemento HTMLFormElement que se prepara.
 * @param routeName Clave de la ruta para generar la URL.
 * @param modalTitle Texto para actualizar el título del modal.
 * @param method Método HTTP para enviar el formulario. Por defecto 'POST'.
 * @param id Parámetro opcional para rutas que requieren un identificador (editar, eliminar).
 */
function prepareForm(
    form: HTMLFormElement,
    routeName: string,
    modalTitle: string,
    method: string = 'POST',
    id?: string
): void {
    const methodUpper = method.toUpperCase();
    // Actualiza el texto del label del modal asociado (se asume convención 'form' -> 'modal')
    const modalLabel = document.querySelector(`#${form.id.replace('form', 'modal')}-label`);
    if (modalLabel) modalLabel.textContent = `${modalTitle} ${formTitleSuffix}`;

    try {
        const methodRequiresId = ['PUT', 'PATCH', 'DELETE'].includes(methodUpper);

        // Generar URL con o sin parámetro id según método
        const actionUrl = methodRequiresId
            ? (id ? route(routeName, { id }) : (() => { throw new Error(`El parámetro 'id' es obligatorio para método ${methodUpper}`); })())
            : route(routeName);

        // Establecer la URL de acción del formulario
        form.setAttribute('action', actionUrl);

        // Establecer el método HTTP del formulario (GET, POST, etc.)
        form.method = methodUpper;
    } catch (error) {
        console.error(`Error generando la ruta para '${routeName}':`, error);
        return;
    }

    // Eliminar cualquier input oculto previo para el método HTTP para evitar duplicados
    form.querySelectorAll('input[name="_method"]').forEach(input => input.remove());

    // Insertar input oculto _method solo si el método es distinto de GET o POST,
    // necesario para simular métodos PUT, PATCH o DELETE en formularios HTML
    if (methodUpper !== 'POST' && methodUpper !== 'GET') {
        const hiddenMethodInput = document.createElement('input');
        hiddenMethodInput.type = 'hidden';
        hiddenMethodInput.name = '_method';
        hiddenMethodInput.value = methodUpper;
        form.appendChild(hiddenMethodInput);
    }

    // Insertar token CSRF si no existe
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
    // Verificar que jQuery esté disponible
    if (typeof $ === 'function') {
        // Solo inicializar Select2 si existen elementos con clase 'select2'
        const $select2Elements = $('.select2');

        if ($select2Elements.length) {
            $select2Elements.select2({
                theme: 'bootstrap4',
                width: 'style',
                placeholder() {
                    // Obtener el placeholder del atributo o devolver cadena vacía
                    return $(this).attr('placeholder') || '';
                },
                allowClear() {
                    // Obtener si permite limpiar el select basado en data-attribute
                    return Boolean($(this).data('allow-clear'));
                }
            });
        }
    }
    // Manejar eventos click 
    document.addEventListener('click', (event: MouseEvent) => {
        const target = event.target as HTMLElement | null;
        if (!target) return;

        if (target.closest('#btn-confirm')) {
            submitForm(requiredFields);
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
