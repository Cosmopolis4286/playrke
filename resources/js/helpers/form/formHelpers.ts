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
