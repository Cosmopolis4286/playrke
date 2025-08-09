import { showToast } from './toastNotify.js';

/**
 * Limpia el valor de un input permitiendo solo números y punto decimal,
 * y limita a máximo dos decimales si es float.
 * 
 * @param input - Elemento HTMLInputElement a limpiar
 * @param isFloat - Indica si el número puede ser decimal (true) o solo entero (false)
 */
function cleanInput(input: HTMLInputElement, isFloat: boolean): void {
    input.value = input.value
        .replace(/,/g, '.')           // Reemplaza comas por puntos
        .replace(/[^\d.]/g, '');      // Elimina todo excepto dígitos y puntos

    if (isFloat) {
        // Limita a máximo 2 decimales
        input.value = input.value.replace(/^(\d*\.?\d{0,2}).*$/, '$1');
    } else {
        // Solo dígitos enteros, elimina puntos
        input.value = input.value.replace(/\./g, '');
    }
}

/**
 * Valida la entrada numérica de un input HTML, permitiendo números enteros o flotantes,
 * asegurando que el valor esté dentro de un rango específico.
 * Aplica debounce para evitar validar en cada pulsación.
 * 
 * @param input - Elemento HTMLInputElement a validar
 * @param isFloat - Permite números decimales (true) o solo enteros (false). Por defecto true.
 * @param min - Valor mínimo permitido (inclusive). Por defecto 0.
 * @param max - Valor máximo permitido (inclusive). Por defecto 100.
 * @param callback - Función opcional que recibe true si el valor es válido, false si no.
 */
export function validateInput(
    input: HTMLInputElement,
    isFloat: boolean = true,
    min: number = 0,
    max: number = 100,
    callback: ((valid: boolean) => void) | null = null
): void {
    cleanInput(input, isFloat);

    // Limpia timer anterior si existe para debounce
    if ((input as any)._validationTimer) {
        clearTimeout((input as any)._validationTimer);
    }

    // Asigna nuevo timer para validar tras 850ms sin escribir
    (input as any)._validationTimer = window.setTimeout(() => {
        let val = isFloat ? parseFloat(input.value) : parseInt(input.value, 10);
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

        delete (input as any)._validationTimer;
    }, 850);
}
