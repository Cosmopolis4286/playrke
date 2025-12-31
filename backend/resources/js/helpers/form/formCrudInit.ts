// helpers/form/formCrudInit.ts
import axios from '@/plugins/axios';
import { ajaxStart } from '@/utils/loader';
import Swal, { showToast, showToastConfirmAction } from '@/helpers/toastNotify';
import type { OtherCase } from '@/helpers/module/types/moduleFormTypes';
import { formCrudExports } from './formCrudLoadModule';
import { validateRequiredFields, clearFormValidation } from '@/shared/utils/form/fieldValidation';
import { resetFormAndContainers } from './formHelpers';
import { getSelectedIds } from '@/alpine/tableSelectionListener';

// Estado del módulo CRUD
const crudState = {
    encryptId: '',
    tableListeners: new Map<string, (ids: string[]) => void>()
};

/**
 * Registra un listener para la selección de tabla
 * @param tableId - ID de la tabla
 */
export function registerTableListener(tableId: string): void {
    // Importación dinámica para evitar dependencia circular
    import('@/alpine/tableSelectionListener').then(({ onTableSelectionUpdate }) => {
        if (!crudState.tableListeners.has(tableId)) {
            const listener = (selectedIds: string[]) => {
                crudState.encryptId = selectedIds.length === 1 ? selectedIds[0] : '';
            };
            onTableSelectionUpdate(tableId, listener);
            crudState.tableListeners.set(tableId, listener);
        }
    });
}

/**
 * Llama insertInfo si existe y muestra el modal
 */
function safeInsertInfo(action: string, data?: any) {
    const insertFn = formCrudExports.insertInfo;
    const modalEl = formCrudExports.modal;

    if (typeof insertFn === 'function') {
        insertFn(action, data);
        if (modalEl) $(modalEl).modal('show');
    } else {
        console.error(
            `La función insertInfo no existe o el módulo '${formCrudExports.moduleFile}' no está cargado aún.`
        );
    }
}

/**
 * Ejecuta el flujo principal de acciones CRUD
 *
 * @param {HTMLElement} elem - Elemento que contiene el atributo data-action
 */
export async function formCRUD(elem: HTMLElement): Promise<void> {
    type CrudAction = 'create' | 'edit' | 'delete' | string;
    const action: CrudAction = elem.dataset.action ?? '';

    const { moduleId, modal, route, formTitleSuffix } = formCrudExports;

    // Inicialización de formulario y modal (solo para create/edit)
    const initForm = (): HTMLFormElement | null => {
        if (!modal) {
            console.warn(`Modal no encontrado para: ${moduleId}`);
            return null;
        }

        const form = document.querySelector<HTMLFormElement>(`#${moduleId}`);
        if (!form) {
            console.warn(`Formulario '#${moduleId}' no encontrado`);
            return null;
        }

        resetForm(form);
        return form;
    };

    switch (action) {
        case 'create': {
            const form = initForm();
            if (!form) return;

            const modalTitle = `Registrar ${formTitleSuffix}`;
            safeInsertInfo('create');
            prepareForm(form, 'create', modalTitle, 'POST', undefined, route);
            break;
        }

        case 'edit': {
            const form = initForm();
            if (!form) return;

            const modalTitle = `Editar ${formTitleSuffix}`;
            await getInfo(crudState.encryptId);
            prepareForm(form, 'update', modalTitle, 'PUT', crudState.encryptId, route);
            break;
        }

        case 'delete':
            await handleOtherCases('delete', crudState.encryptId, elem);
            break;

        default:
            await handleOtherCases(action, crudState.encryptId, elem);
            break;
    }
}

/**
 * Genera mensajes dinámicos para acciones de cambio de estado
 */
function getStatusChangeMessages(status: string | undefined, entityName: string = 'registro') {
    const messages = {
        DELETED: {
            text: `¿Deseas eliminar este ${entityName}?`,
            icon: 'warning' as const,
            confirmButtonText: 'Sí, eliminar'
        },
        ACTIVE: {
            text: `¿Deseas activar este ${entityName}?`,
            icon: 'question' as const,
            confirmButtonText: 'Sí, activar'
        },
        INACTIVE: {
            text: `¿Deseas desactivar este ${entityName}?`,
            icon: 'question' as const,
            confirmButtonText: 'Sí, desactivar'
        },
        BLOCKED: {
            text: `¿Deseas bloquear este ${entityName}?`,
            icon: 'warning' as const,
            confirmButtonText: 'Sí, bloquear'
        }
    };

    return status && status in messages
        ? messages[status as keyof typeof messages]
        : {
            text: `¿Deseas cambiar el estado de este ${entityName}?`,
            icon: 'question' as const,
            confirmButtonText: 'Sí, cambiar'
        };
}

/**
 * Ejecuta acciones personalizadas de otherCasesMap
 */
async function handleOtherCases(action: string, encryptId: string, elem?: HTMLElement) {
    const otherCases = formCrudExports.otherCasesMap;
    const routeFn = formCrudExports.route;
    const entityName = formCrudExports.formTitleSuffix?.toLowerCase() || 'registro';

    // Obtener el status del data-attribute si existe
    const status = elem?.dataset?.status;

    // Acciones globales por defecto
    const globalActions: Record<string, OtherCase> = {
        'delete': {
            routeName: 'destroy',
            text: `¿Deseas cambiar el estado de este ${entityName}?`,
            icon: 'question',
            confirmButtonText: 'Sí, cambiar',
            method: 'PUT',
        },
        'change-status': {
            routeName: 'changeStatus',
            ...getStatusChangeMessages(status, entityName),
            method: 'PUT',
        },
        'change-password': {
            routeName: 'changePassword',
            text: `¿Deseas generar una nueva contraseña para este ${entityName}?`,
            icon: 'warning',
            confirmButtonText: 'Sí, generar',
            method: 'PUT',
        }
    };

    // Determinar qué caso usar
    const caseData = globalActions[action] || otherCases[action];

    if (!caseData) return console.error(`Acción no reconocida: ${action}`);

    // Obtener IDs seleccionados desde la tabla (si existe)
    const tableId = elem?.closest('[data-table-id]')?.getAttribute('data-table-id');
    const selectedIds = tableId ? getSelectedIds(tableId) : [];

    // Si hay múltiples IDs seleccionados, usar esos; si no, usar el encryptId individual
    const idsToProcess = selectedIds.length > 0 ? selectedIds : (encryptId ? [encryptId] : []);

    if (idsToProcess.length === 0) {
        return console.error('No hay IDs seleccionados para procesar');
    }

    // Actualizar texto de confirmación para múltiples elementos
    const confirmText = idsToProcess.length > 1
        ? caseData.text.replace('este', `estos ${idsToProcess.length}`)
        : caseData.text;

    try {
        const confirmed = await showToastConfirmAction(confirmText, caseData.icon, caseData.confirmButtonText);
        if (!confirmed) return showToast('Los cambios no se guardaron', 'info');

        // Preparar datos para enviar
        const requestData: Record<string, any> = {};
        if (status) {
            requestData.status = status;
        }

        // Procesar cada ID seleccionado
        const promises = idsToProcess.map(async (id) => {
            const url = routeFn?.(caseData.routeName, { id }) ?? '';
            console.log(`🔍 Generando URL para acción '${action}':`, {
                routeName: caseData.routeName,
                id,
                url,
                routeFnExists: !!routeFn
            });

            console.log(`🚀 Enviando petición para ID '${id}' con método '${caseData.method ?? 'PUT'}' a la URL:`, url);

            return axios.request({
                method: (caseData.method ?? 'PUT').toLowerCase() as 'get' | 'post' | 'put' | 'delete' | 'patch',
                url,
                data: requestData,
            });
        });

        // Ejecutar todas las peticiones en paralelo
        const results = await Promise.all(promises);

        // Verificar si alguna respuesta contiene una contraseña generada
        const passwordResponse = results.find((result: any) => result?.data?.password);

        if (passwordResponse?.data?.password) {
            // Mostrar modal con la contraseña generada usando SweetAlert
            await Swal.fire({
                title: 'Nueva Contraseña Generada',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Contraseña:</strong></p>
                        <div style="position: relative; display: flex; align-items: center; gap: 8px;">
                            <div id="password-display" style="flex: 1; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 16px; word-break: break-all;">
                                ${passwordResponse.data.password}
                            </div>
                            <button id="copy-password-btn" type="button" style="padding: 10px 15px; background-color: #6366f1; color: white; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px; min-width: 80px; justify-content: center;" title="Copiar al portapapeles">
                                <i class="fas fa-copy"></i>
                                <span>Copiar</span>
                            </button>
                        </div>
                        <p style="color: #6c757d; font-size: 14px; margin-top: 15px;">
                            <i class="fas fa-info-circle"></i> Guarda esta contraseña en un lugar seguro. No podrás verla nuevamente.
                        </p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Entendido',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    // Añadir funcionalidad de copiar al botón
                    const copyBtn = document.getElementById('copy-password-btn');
                    const password = passwordResponse.data.password;

                    if (copyBtn) {
                        copyBtn.addEventListener('click', async () => {
                            try {
                                await navigator.clipboard.writeText(password);

                                // Cambiar el texto y estilo del botón temporalmente
                                const btnText = copyBtn.querySelector('span');
                                const btnIcon = copyBtn.querySelector('i');

                                if (btnText && btnIcon) {
                                    btnIcon.className = 'fas fa-check';
                                    btnText.textContent = 'Copiado';
                                    copyBtn.style.backgroundColor = '#10b981';

                                    setTimeout(() => {
                                        btnIcon.className = 'fas fa-copy';
                                        btnText.textContent = 'Copiar';
                                        copyBtn.style.backgroundColor = '#6366f1';
                                    }, 2000);
                                }
                            } catch (err) {
                                console.error('Error al copiar:', err);
                                showToast('Error al copiar la contraseña', 'error');
                            }
                        });
                    }
                }
            });
        } else {
            const successMessage = idsToProcess.length > 1
                ? `Acción completada exitosamente en ${idsToProcess.length} elementos`
                : 'Acción completada exitosamente';

            showToast(successMessage, 'success');
        }
    } catch (error: any) {
        console.error(`Error al registrar la acción '${action}':`, error?.response?.data || error.message);
        showToast('Error al procesar la acción', 'error');
    }
}

/**
 * Obtiene datos desde el backend para editar
 */
async function getInfo(encryptId: string): Promise<void> {
    if (!encryptId) {
        console.error('ID cifrado no definido para getInfo');
        return;
    }

    const routeFn = formCrudExports.route;
    if (!routeFn) {
        console.error('Función de rutas no definida');
        return;
    }

    try {
        const url = routeFn('show', { id: encryptId });

        console.log('url', url);

        const response = await axios.get(url);
        const payload = response.data?.data;

        if (!payload) {
            console.warn('Respuesta sin data válida', response.data);
            return;
        }

        safeInsertInfo('edit', payload);
    } catch (error: any) {
        const message =
            error?.response?.data?.message ??
            error?.response?.data ??
            error?.message ??
            'Error desconocido';

        console.error('Error al obtener información:', message);
    }
}

/**
 * Valida campos requeridos y envía el formulario
 *
 * @param formId - ID del formulario a validar
 * @param fields - Array de nombres de campos requeridos
 */
export function submitForm(formId: string, fields: string[] = []) {
    const form = document.getElementById(formId) as HTMLFormElement | null;
    if (!form) {
        console.warn(`Formulario no encontrado para submit: ${formId}`);
        return;
    }

    // Si no se proporcionan campos, detectarlos automáticamente
    if (!fields.length) {
        fields = Array.from(
            form.querySelectorAll<HTMLInputElement | HTMLSelectElement>(
                'input[required], select[required]'
            )
        )
            .map(el => el.id || el.name)
            .filter(Boolean);
    }

    if (import.meta.env.DEV) {
        console.log(`📋 Campos requeridos a validar: (${fields.length})`, fields);
    }

    // Validar campos usando la utilidad compartida
    const validationResult = validateRequiredFields(formId, fields, true);

    if (!validationResult.isValid) {
        console.warn(`⚠️ Campos faltantes o vacíos: ${validationResult.missingFields.join(', ')}`);

        // Enfocar el primer campo inválido
        if (validationResult.firstInvalidField) {
            validationResult.firstInvalidField.focus();

            // Scroll suave hacia el campo
            validationResult.firstInvalidField.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Mostrar notificación
        return showToast(
            `Campos requeridos faltantes: ${validationResult.missingFields.join(', ')}`,
            'warning'
        );
    }

    // Limpiar validaciones visuales antes de enviar
    clearFormValidation(formId);

    // Enviar formulario con AJAX
    ajaxStart();

    const formData = new FormData(form);
    const url = form.action;
    const method = (form.method || 'POST').toUpperCase();

    axios.request({
        method: method.toLowerCase() as 'get' | 'post' | 'put' | 'delete' | 'patch',
        url,
        data: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(async (response) => {
            const data = response.data;

            // Si la respuesta incluye una contraseña, mostrar modal con botón de copiar
            if (data.success && data.password) {
                await Swal.fire({
                    title: 'Registro Exitoso',
                    html: `
                    <div style="text-align: left;">
                        <p>${data.message || '✅ Comercio creado exitosamente'}</p>
                        <p style="margin-top: 15px;"><strong>Contraseña generada:</strong></p>
                        <div style="position: relative; display: flex; align-items: center; gap: 8px;">
                            <div id="password-display" style="flex: 1; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 16px; word-break: break-all;">
                                ${data.password}
                            </div>
                            <button id="copy-password-btn" type="button" style="padding: 10px 15px; background-color: #6366f1; color: white; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px; min-width: 80px; justify-content: center;" title="Copiar al portapapeles">
                                <i class="fas fa-copy"></i>
                                <span>Copiar</span>
                            </button>
                        </div>
                        <p style="color: #6c757d; font-size: 14px; margin-top: 15px;">
                            <i class="fas fa-info-circle"></i> Guarda esta contraseña en un lugar seguro. No podrás verla nuevamente.
                        </p>
                    </div>
                `,
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        const copyBtn = document.getElementById('copy-password-btn');
                        const password = data.password;

                        if (copyBtn) {
                            copyBtn.addEventListener('click', async () => {
                                try {
                                    await navigator.clipboard.writeText(password);

                                    const btnText = copyBtn.querySelector('span');
                                    const btnIcon = copyBtn.querySelector('i');

                                    if (btnText && btnIcon) {
                                        btnIcon.className = 'fas fa-check';
                                        btnText.textContent = 'Copiado';
                                        copyBtn.style.backgroundColor = '#10b981';

                                        setTimeout(() => {
                                            btnIcon.className = 'fas fa-copy';
                                            btnText.textContent = 'Copiar';
                                            copyBtn.style.backgroundColor = '#6366f1';
                                        }, 2000);
                                    }
                                } catch (err) {
                                    console.error('Error al copiar:', err);
                                    showToast('Error al copiar la contraseña', 'error');
                                }
                            });
                        }
                    }
                });
            } else if (data.success) {
                showToast(data.message || 'Operación exitosa', 'success');
            }

            // Recargar la página después de cerrar el modal
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch((error) => {
            console.error('Error al enviar formulario:', error);
            const message = error.response?.data?.message || 'Error al procesar la solicitud';
            showToast(message, 'error');
        });
}

/**
 * Resetea un formulario usando la utilidad compartida.
 * Además quita el atributo `readonly` de los inputs (específico de CRUD).
 *
 * @param {HTMLFormElement} form - El formulario a resetear
 */
export function resetForm(form: HTMLFormElement): void {
    if (!form.id) {
        console.warn('El formulario no tiene ID, no se puede resetear');
        return;
    }

    // Usar la utilidad compartida para resetear el formulario
    resetFormAndContainers(form.id);

    // Quitar readonly a todos los inputs (funcionalidad específica de CRUD)
    form.querySelectorAll<HTMLInputElement>('input[readonly]').forEach(input => {
        input.readOnly = false;
    });
}

/**
 * Configura el formulario antes de enviarlo
 */
export function prepareForm(
    form: HTMLFormElement,
    routeName: string,
    modalTitle: string,
    method: string = 'POST',
    id?: string,
    routeFn: (name: string, params?: Record<string, string | number>) => string = () => ''
) {
    const moduleId = formCrudExports.moduleId;

    const methodUpper = method.toUpperCase();
    const modalLabel = document.querySelector(`#modal-${moduleId}-label`);
    if (modalLabel) modalLabel.textContent = modalTitle;

    try {
        const actionUrl = ['PUT', 'PATCH', 'DELETE'].includes(methodUpper)
            ? (id ? routeFn(routeName, { id }) : (() => { throw new Error(`'id' requerido para ${methodUpper}`); })())
            : routeFn(routeName);

        form.action = actionUrl;
        form.method = methodUpper;
    } catch (error) {
        console.error(`Error generando la ruta para '${routeName}':`, error);
    }

    // Limpiar métodos anteriores
    form.querySelectorAll('input[name="_method"]').forEach(input => input.remove());

    if (!['POST', 'GET'].includes(methodUpper)) {
        const hiddenMethod = document.createElement('input');
        hiddenMethod.type = 'hidden';
        hiddenMethod.name = '_method';
        hiddenMethod.value = methodUpper;
        form.appendChild(hiddenMethod);
    }

    // CSRF token
    if (!form.querySelector('input[name="_token"]')) {
        const tokenMeta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
        if (tokenMeta?.content) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = tokenMeta.content;
            form.appendChild(tokenInput);
        } else console.warn('Token CSRF no encontrado en meta.');
    }
}
