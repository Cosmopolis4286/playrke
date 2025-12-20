/**
 * Utilidades para validación visual de formularios
 *
 * Proporciona funciones para validar campos requeridos y mostrar
 * indicadores visuales (bordes rojos) cuando faltan datos obligatorios.
 *
 * @module shared/utils/form/fieldValidation
 */

/**
 * Clase CSS aplicada a campos inválidos
 */
const INVALID_CLASS = 'is-invalid';

/**
 * Clase CSS aplicada a campos válidos
 */
const VALID_CLASS = 'is-valid';

/**
 * Configuración de colores para los bordes de validación
 */
const BORDER_COLORS = {
    invalid: '#dc3545', // Bootstrap danger color
    valid: '#28a745',   // Bootstrap success color
    default: '#ced4da'  // Bootstrap default border color
} as const;

/**
 * Resultado de la validación de un campo individual
 */
export interface FieldValidationResult {
    /** Nombre del campo */
    fieldName: string;
    /** Si el campo es válido */
    isValid: boolean;
    /** Mensaje de error (si aplica) */
    errorMessage?: string;
    /** Elemento HTML del campo */
    element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null;
}

/**
 * Resultado de la validación completa del formulario
 */
export interface FormValidationResult {
    /** Si el formulario completo es válido */
    isValid: boolean;
    /** Lista de campos faltantes */
    missingFields: string[];
    /** Detalles de validación por campo */
    fieldResults: FieldValidationResult[];
    /** Primer campo inválido encontrado */
    firstInvalidField: HTMLElement | null;
}

/**
 * Aplica estilo de error visual a un campo de formulario
 *
 * @param element - Elemento HTML del campo (input, select, textarea)
 * @param errorMessage - Mensaje de error opcional a mostrar
 *
 * @example
 * ```typescript
 * const input = document.getElementById('email') as HTMLInputElement;
 * highlightInvalidField(input, 'El email es requerido');
 * ```
 */
export function highlightInvalidField(
    element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement,
    errorMessage?: string
): void {
    // Remover clase válida si existe
    element.classList.remove(VALID_CLASS);

    // Agregar clase inválida
    element.classList.add(INVALID_CLASS);

    // Aplicar borde rojo directamente
    element.style.borderColor = BORDER_COLORS.invalid;
    element.style.borderWidth = '2px';

    // Si hay Select2, aplicar también al contenedor
    if (element.classList.contains('select2') && typeof $ !== 'undefined') {
        const $element = $(element);
        const select2Container = $element.next('.select2-container');

        if (select2Container.length) {
            select2Container.find('.select2-selection').css({
                'border-color': BORDER_COLORS.invalid,
                'border-width': '2px'
            });
        }
    }

    // Agregar mensaje de error si se proporciona
    if (errorMessage) {
        removeErrorMessage(element);
        const errorSpan = document.createElement('span');
        errorSpan.className = 'invalid-feedback d-block';
        errorSpan.textContent = errorMessage;
        errorSpan.dataset.validationError = 'true';

        element.parentElement?.appendChild(errorSpan);
    }
}

/**
 * Limpia los estilos de error de un campo de formulario
 *
 * @param element - Elemento HTML del campo
 *
 * @example
 * ```typescript
 * const input = document.getElementById('email') as HTMLInputElement;
 * clearFieldValidation(input);
 * ```
 */
export function clearFieldValidation(
    element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
): void {
    // Remover clases de validación
    element.classList.remove(INVALID_CLASS, VALID_CLASS);

    // Restaurar borde por defecto
    element.style.borderColor = '';
    element.style.borderWidth = '';

    // Si hay Select2, limpiar también
    if (element.classList.contains('select2') && typeof $ !== 'undefined') {
        const $element = $(element);
        const select2Container = $element.next('.select2-container');

        if (select2Container.length) {
            select2Container.find('.select2-selection').css({
                'border-color': '',
                'border-width': ''
            });
        }
    }

    // Remover mensaje de error
    removeErrorMessage(element);
}

/**
 * Aplica estilo de campo válido
 *
 * @param element - Elemento HTML del campo
 *
 * @example
 * ```typescript
 * const input = document.getElementById('email') as HTMLInputElement;
 * highlightValidField(input);
 * ```
 */
export function highlightValidField(
    element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
): void {
    // Remover clase inválida si existe
    element.classList.remove(INVALID_CLASS);

    // Agregar clase válida
    element.classList.add(VALID_CLASS);

    // Aplicar borde verde
    element.style.borderColor = BORDER_COLORS.valid;

    // Si hay Select2, aplicar también
    if (element.classList.contains('select2') && typeof $ !== 'undefined') {
        const $element = $(element);
        const select2Container = $element.next('.select2-container');

        if (select2Container.length) {
            select2Container.find('.select2-selection').css({
                'border-color': BORDER_COLORS.valid
            });
        }
    }

    // Remover mensaje de error si existe
    removeErrorMessage(element);
}

/**
 * Remueve el mensaje de error de un campo
 *
 * @param element - Elemento HTML del campo
 */
function removeErrorMessage(
    element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
): void {
    const errorSpan = element.parentElement?.querySelector('[data-validation-error="true"]');
    errorSpan?.remove();
}

/**
 * Valida si un campo tiene un valor válido
 *
 * @param element - Elemento HTML del campo
 * @returns `true` si el campo tiene un valor válido, `false` en caso contrario
 *
 * @example
 * ```typescript
 * const input = document.getElementById('email') as HTMLInputElement;
 * if (!isFieldValid(input)) {
 *     console.log('El campo email está vacío');
 * }
 * ```
 */
export function isFieldValid(
    element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null
): boolean {
    if (!element) return false;

    const value = element.value?.trim();

    // Validar según tipo de campo
    if (element instanceof HTMLSelectElement) {
        // Para selects, validar que no sea el placeholder (value vacío)
        return value !== '' && value !== null;
    }

    // Para inputs y textareas, validar que no esté vacío
    return value !== '';
}

/**
 * Valida campos requeridos de un formulario y aplica estilos visuales
 *
 * @param formId - ID del formulario a validar
 * @param requiredFields - Array con los nombres de los campos requeridos
 * @param showErrorMessages - Si se deben mostrar mensajes de error (default: true)
 * @returns Resultado de la validación con detalles
 *
 * @example
 * ```typescript
 * const result = validateRequiredFields('myForm', ['name', 'email', 'phone']);
 *
 * if (!result.isValid) {
 *     console.log('Campos faltantes:', result.missingFields);
 *     result.firstInvalidField?.focus();
 * }
 * ```
 */
export function validateRequiredFields(
    formId: string,
    requiredFields: string[],
    showErrorMessages: boolean = true
): FormValidationResult {
    const form = document.getElementById(formId) as HTMLFormElement | null;

    if (!form) {
        console.error(`❌ Formulario con ID "${formId}" no encontrado`);
        return {
            isValid: false,
            missingFields: [],
            fieldResults: [],
            firstInvalidField: null
        };
    }

    const missingFields: string[] = [];
    const fieldResults: FieldValidationResult[] = [];
    let firstInvalidField: HTMLElement | null = null;

    // Validar cada campo requerido
    requiredFields.forEach(fieldName => {
        const element = form.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
            `[name="${fieldName}"], #${fieldName}`
        );

        const valid = isFieldValid(element);

        fieldResults.push({
            fieldName,
            isValid: valid,
            element,
            errorMessage: valid ? undefined : `El campo ${fieldName} es requerido`
        });

        if (!valid) {
            missingFields.push(fieldName);

            if (element) {
                const errorMsg = showErrorMessages ? `Este campo es requerido` : undefined;
                highlightInvalidField(element, errorMsg);

                // Guardar referencia al primer campo inválido
                if (!firstInvalidField) {
                    firstInvalidField = element;
                }
            }
        } else if (element) {
            // Limpiar validación de campos válidos
            clearFieldValidation(element);
        }
    });

    return {
        isValid: missingFields.length === 0,
        missingFields,
        fieldResults,
        firstInvalidField
    };
}

/**
 * Limpia todas las validaciones visuales de un formulario
 *
 * @param formId - ID del formulario
 *
 * @example
 * ```typescript
 * clearFormValidation('myForm');
 * ```
 */
export function clearFormValidation(formId: string): void {
    const form = document.getElementById(formId) as HTMLFormElement | null;

    if (!form) {
        console.error(`❌ Formulario con ID "${formId}" no encontrado`);
        return;
    }

    // Limpiar todos los campos con clase is-invalid o is-valid
    const invalidFields = form.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
        `.${INVALID_CLASS}, .${VALID_CLASS}`
    );

    invalidFields.forEach(field => clearFieldValidation(field));

    // Remover todos los mensajes de error
    const errorMessages = form.querySelectorAll('[data-validation-error="true"]');
    errorMessages.forEach(msg => msg.remove());
}

/**
 * Agrega listeners de validación en tiempo real a campos de un formulario
 *
 * Los campos se validarán automáticamente cuando el usuario escriba o cambie el valor.
 *
 * @param formId - ID del formulario
 * @param requiredFields - Array con los nombres de los campos requeridos
 *
 * @example
 * ```typescript
 * attachRealtimeValidation('myForm', ['name', 'email', 'phone']);
 * ```
 */
export function attachRealtimeValidation(
    formId: string,
    requiredFields: string[]
): void {
    const form = document.getElementById(formId) as HTMLFormElement | null;

    if (!form) {
        console.error(`❌ Formulario con ID "${formId}" no encontrado`);
        return;
    }

    requiredFields.forEach(fieldName => {
        const element = form.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
            `[name="${fieldName}"], #${fieldName}`
        );

        if (!element) return;

        // Evento para inputs y textareas
        if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
            element.addEventListener('input', () => {
                if (isFieldValid(element)) {
                    clearFieldValidation(element);
                }
            });

            element.addEventListener('blur', () => {
                if (!isFieldValid(element)) {
                    highlightInvalidField(element, 'Este campo es requerido');
                }
            });
        }

        // Evento para selects
        if (element instanceof HTMLSelectElement) {
            element.addEventListener('change', () => {
                if (isFieldValid(element)) {
                    clearFieldValidation(element);
                } else {
                    highlightInvalidField(element, 'Debe seleccionar una opción');
                }
            });
        }
    });
}
