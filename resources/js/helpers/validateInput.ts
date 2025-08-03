import { showToast } from './toastNotify';

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
