import type { Coin, Country, Game, TimeZone } from '@/types/dataModels';
import { getCoins, getCountries, getGames, getTimeZones } from '@/data/index';

/**
 * Tipado de datasets disponibles en appData
 */
type DatasetMap = {
    coins: Coin[];
    countries: Country[];
    games: Game[];
    timeZones: TimeZone[];
};

/** Funciones para obtener datasets dinámicamente */
const datasetGetters: Record<keyof DatasetMap, () => any[]> = {
    coins: getCoins,
    countries: getCountries,
    games: getGames,
    timeZones: getTimeZones,
};

/**
 * Inicializa todos los <select> que tengan el atributo `data-source`.
 * 
 * Funciona tanto para selects nativos como para Select2.
 * Permite placeholders dinámicos, selección inicial y deshabilitación condicional.
 *
 * @param selectedValues - Opcional: objeto { [selectId]: valorPreseleccionado }
 * @param disableFn - Opcional: función para deshabilitar opciones. Recibe (selectId, item) => boolean
 */
export function initAllSelects<T extends keyof DatasetMap>(
    selectedValues?: Partial<Record<string, string | number>>,
    disableFn?: (selectId: string, item: DatasetMap[T][number]) => boolean
): void {
    // Recorremos todos los selects con data-source
    document.querySelectorAll<HTMLSelectElement>('select[data-source]').forEach(select => {
        const sourceKey = select.dataset.source as keyof DatasetMap;
        const dataset = datasetGetters[sourceKey]?.() ?? [];

        if (!dataset.length) {
            console.warn(`Dataset '${sourceKey}' no existe o está vacío para el select #${select.id}.`);
            return;
        }

        const valueKey = select.dataset.value as keyof typeof dataset[number];
        const labelKey = select.dataset.label as keyof typeof dataset[number];

        if (!valueKey || !labelKey) {
            console.warn(`Select #${select.id} requiere atributos 'data-value' y 'data-label'.`);
            return;
        }

        // Limpiar select y crear placeholder si existe
        select.innerHTML = '';
        let placeholderOption: HTMLOptionElement | null = null;
        if (select.dataset.placeholder) {
            placeholderOption = new Option(select.dataset.placeholder, '', true, false);
            select.appendChild(placeholderOption);
        }

        // Agregar las opciones del dataset ordenadas por label
        [...dataset]
            .sort((a, b) => String(a[labelKey]).localeCompare(String(b[labelKey]), 'es', { sensitivity: 'base' }))
            .forEach(item => {
                const value = item[valueKey];
                const label = item[labelKey];

                if (value === undefined || label === undefined) {
                    console.warn(`Item del dataset '${sourceKey}' no tiene '${String(valueKey)}' o '${String(labelKey)}'.`);
                    return;
                }

                const option = new Option(
                    String(label),
                    String(value),
                    false,
                    selectedValues?.[select.id] === value
                );

                if (disableFn?.(select.id, item)) option.disabled = true;

                select.appendChild(option);
            });

        // ⚡ Placeholder: deshabilitar si ya hay un valor seleccionado
        if (selectedValues?.[select.id] && placeholderOption) {
            placeholderOption.disabled = true;
        } else if (placeholderOption) {
            // ⚡ Si no hay valor preseleccionado, deshabilitar placeholder tras primer cambio
            select.addEventListener('change', () => {
                placeholderOption!.disabled = true;
            }, { once: true });
        }

        // 🔹 Inicializar Select2 si corresponde
        if (select.classList.contains('select2')) {
            const placeholderText = '    ' + select.dataset.placeholder || '';
            const allowClear = select.dataset.allowClear !== 'false'; // true por defecto

            $(select).select2({
                placeholder: placeholderText,
                allowClear: allowClear,
                theme: 'bootstrap4',
                width: '100%',
                multiple: select.multiple,
            });
        }
    });
}
