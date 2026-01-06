// helpers/form/formHelpers.ts
import { validateRequiredFields, clearFormValidation } from '@/shared/utils/form/fieldValidation';

/**
 * Llena un formulario con valores de un objeto,
 * mapeando IDs con "-" a propiedades con "_".
 *
 * @param data - Objeto con los datos (ej: commerce)
 * @param root - Elemento raíz del formulario (opcional)
 */
export function fillFormFromObject<T extends Record<string, any>>(data: T, root: HTMLElement | Document = document) {
    root.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('[id]').forEach(input => {
        const domId = input.id;
        const prop = domId.replace(/-/g, '_'); // cambia "-" → "_"

        if (prop in data) {
            const value = data[prop] ?? '';
            if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                input.value = value;
            } else if (input instanceof HTMLSelectElement) {
                input.value = value.toString();
            }
        }
    });
}

/**
 * Limpia los errores de validación del formulario
 *
 * @param formId - ID del formulario a limpiar
 */
export function clearValidationErrors(formId: string): void {
    const form = document.getElementById(formId);
    if (!form) return;

    // Remover clases is-invalid
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Remover mensajes de error
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
}

/**
 * Resetea el formulario y oculta los contenedores dinámicos
 *
 * @param formId - ID del formulario a resetear
 * @param containerIds - Array de IDs de contenedores a ocultar (opcional)
 */
export function resetFormAndContainers(formId: string, containerIds: string[] = []): void {
    const form = document.getElementById(formId) as HTMLFormElement | null;
    if (!form) return;

    // Resetear el formulario (limpia todos los inputs, selects, etc)
    form.reset();

    // Limpiar selects con data-reset="true"
    form.querySelectorAll<HTMLSelectElement>('select[data-reset="true"]').forEach(select => {
        select.innerHTML = '';
    });

    // Ocultar contenedores dinámicos especificados
    containerIds.forEach(containerId => {
        const container = document.getElementById(containerId);
        container?.classList.add('d-none');
    });

    // Limpiar selects Select2 dentro del formulario
    form.querySelectorAll<HTMLSelectElement>('select').forEach(select => {
        if (window.jQuery && (window.jQuery as any)(select).data('select2')) {
            (window.jQuery as any)(select).val(null).trigger('change');
        }
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
    route: (name: string, params?: Record<string, string | number>) => string = () => ''
) {
    const methodUpper = method.toUpperCase();
    const modalLabel = document.querySelector(`#modal-${crud.moduleId}-label`);

    if (modalLabel) modalLabel.textContent = modalTitle;

    try {
        const actionUrl = ['PUT', 'PATCH', 'DELETE'].includes(methodUpper)
            ? (id ? route(routeName, { id }) : (() => { throw new Error(`'id' requerido para ${methodUpper}`); })())
            : route(routeName);

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

/**
 * Valida campos requeridos y envía el formulario
 *
 * @param formId - ID del formulario a validar
 * @param fields - Array de nombres de campos requeridos
 */
export function submitFormOld(formId: string, fields: string[] = []) {
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
        })
        .finally(() => {
            // Siempre rehabilitar botones después de la petición (éxito o error)
            ajaxStop();
        });
}


import { submitFormService } from './services/formSubmitService';

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
