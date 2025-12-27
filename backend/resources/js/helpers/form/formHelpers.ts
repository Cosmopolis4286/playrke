// helpers/form/formHelpers.ts

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
