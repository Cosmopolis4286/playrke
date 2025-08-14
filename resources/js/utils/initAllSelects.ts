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
 * Mapeo de datasets importados desde data.ts
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
        if (!dataset) return;

        // Key de valor y label tipado correctamente
        const valueKey = select.dataset.value as keyof typeof dataset[number];
        const labelKey = select.dataset.label as keyof typeof dataset[number];
        const placeholder = select.dataset.placeholder;

        // Limpiar select
        select.innerHTML = '';

        // Placeholder
        if (placeholder) {
            const placeholderOption = new Option(placeholder, '', true, false);
            placeholderOption.disabled = true;
            select.appendChild(placeholderOption);
        }

        // Ordenar dataset por label
        const sortedData = [...dataset].sort((a, b) =>
            String(a[labelKey]).localeCompare(String(b[labelKey]), 'es', { sensitivity: 'base' })
        );

        // Agregar opciones
        sortedData.forEach(item => {
            const value = String(item[valueKey]);
            const label = String(item[labelKey]);
            const isSelected = selectedValues?.[select.id] !== undefined &&
                item[valueKey] === selectedValues[select.id];

            const option = new Option(label, value, false, isSelected);

            if (disableFn?.(select.id, item)) {
                option.disabled = true;
            }

            select.appendChild(option);
        });
    });
}
