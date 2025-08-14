// resources/js/types/initSelect.ts
/**
 * Rellena un <select> con un dataset dado.
 * @param selectElement - El elemento select que se poblará
 * @param dataset - Lista de objetos con datos
 * @param valueKey - Clave usada como value de <option>
 * @param labelKey - Clave usada como texto visible
 * @param placeholder - Texto inicial opcional
 * @param selectedValue - Valor que debe quedar seleccionado (opcional)
 */
export function initSelect<T extends Record<string, any>>(
    selectElement: HTMLSelectElement,
    dataset: T[],
    valueKey: keyof T,
    labelKey: keyof T,
    placeholder?: string,
    selectedValue?: T[keyof T]
): void {
    if (!selectElement) return;

    selectElement.innerHTML = '';

    // Placeholder
    if (placeholder) {
        const option = new Option(placeholder, '', true, false);
        option.disabled = true;
        selectElement.appendChild(option);
    }

    dataset.forEach(item => {
        const value = String(item[valueKey]);
        const label = String(item[labelKey]);
        const isSelected = selectedValue !== undefined && item[valueKey] === selectedValue;
        const option = new Option(label, value, false, isSelected);
        selectElement.appendChild(option);
    });
}
