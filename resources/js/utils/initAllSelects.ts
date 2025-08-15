import { coins, arrCountries, arrTimeZones } from '@/data/index.js';
import { Coin, Country, TimeZone } from '@/types/dataModels.js';

/**
 * Tipado de datasets disponibles en window.appData
 */
type DatasetMap = {
    coins: Coin[];
    countries: Country[];
    timeZones: TimeZone[];
};

/**
 * Mapeo de datasets importados desde index.ts
 */
const datasets: DatasetMap = {
    coins,
    countries: arrCountries,
    timeZones: arrTimeZones
};

/**
 * Inicializa todos los <select> que tengan el atributo `data-source`.
 *
 * Recorre cada select, determina su dataset según `data-source`, limpia el contenido,
 * agrega un placeholder si existe, ordena las opciones por label y marca la opción
 * seleccionada si se proporciona en `selectedValues`.
 *
 * Validaciones adicionales:
 * - Verifica que el dataset exista y tenga elementos.
 * - Comprueba que `data-value` y `data-label` estén definidos en el select.
 * - Evita errores si un item del dataset no tiene la propiedad indicada.
 *
 * @param selectedValues - Opcional: objeto { [selectId]: valorPreseleccionado }
 * @param disableFn - Opcional: función para deshabilitar opciones. Recibe (selectId, item) => boolean
 */
export function initAllSelects<
    T extends keyof DatasetMap
>(
    selectedValues?: Partial<Record<string, string | number>>,
    disableFn?: (selectId: string, item: DatasetMap[T][number]) => boolean
) {
    document.querySelectorAll<HTMLSelectElement>('select[data-source]').forEach(select => {
        const sourceKey = select.dataset.source as keyof DatasetMap;
        const dataset = datasets[sourceKey];

        if (!dataset || dataset.length === 0) {
            console.warn(`Dataset '${sourceKey}' no existe o está vacío para el select #${select.id}.`);
            return;
        }

        const valueKey = select.dataset.value as keyof typeof dataset[number];
        const labelKey = select.dataset.label as keyof typeof dataset[number];
        const placeholder = select.dataset.placeholder;

        if (!valueKey || !labelKey) {
            console.warn(`Select #${select.id} requiere atributos 'data-value' y 'data-label'.`);
            return;
        }

        // Limpiar select
        select.innerHTML = '';

        // Placeholder
        if (placeholder) {
            const placeholderOption = new Option(placeholder, '', true, false);
            placeholderOption.disabled = true;
            select.appendChild(placeholderOption);
        }

        // Ordenar dataset por label
        const sortedData = [...dataset].sort((a, b) => {
            const aLabel = a[labelKey];
            const bLabel = b[labelKey];

            return String(aLabel).localeCompare(String(bLabel), 'es', { sensitivity: 'base' });
        });

        // Agregar opciones
        sortedData.forEach(item => {
            const value = item[valueKey];
            const label = item[labelKey];

            // Validación de existencia de la propiedad
            if (value === undefined || label === undefined) {
                console.warn(`Item del dataset '${sourceKey}' no tiene '${String(valueKey)}' o '${String(labelKey)}'.`);
                return;
            }

            const isSelected = selectedValues?.[select.id] !== undefined &&
                item[valueKey] === selectedValues[select.id];

            const option = new Option(String(label), String(value), false, isSelected);

            if (disableFn?.(select.id, item)) {
                option.disabled = true;
            }

            select.appendChild(option);
        });
    });
}
