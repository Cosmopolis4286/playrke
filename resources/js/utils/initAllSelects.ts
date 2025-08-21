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
 * - Determina el dataset según `data-source`.
 * - Limpia el contenido del select.
 * - Agrega placeholder si existe.
 * - Ordena las opciones por label.
 * - Marca opción seleccionada según `selectedValues`.
 * - Permite deshabilitar opciones con `disableFn`.
 *
 * Validaciones adicionales:
 * - Verifica que el dataset exista y tenga elementos.
 * - Comprueba que `data-value` y `data-label` estén definidos en el select.
 * - Evita errores si un item del dataset no tiene la propiedad indicada.
 *
 * @param selectedValues - Opcional: objeto { [selectId]: valorPreseleccionado }
 * @param disableFn - Opcional: función para deshabilitar opciones. Recibe (selectId, item) => boolean
 */
export function initAllSelects<T extends keyof DatasetMap>(
    selectedValues?: Partial<Record<string, string | number>>,
    disableFn?: (selectId: string, item: DatasetMap[T][number]) => boolean
): void {
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

        // Limpiar y agregar placeholder
        select.innerHTML = '';
        if (select.dataset.placeholder) {
            const ph = new Option(select.dataset.placeholder, '', true, false);
            ph.disabled = true;
            select.appendChild(ph);
        }

        // Agregar opciones ordenadas
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
    });
}
