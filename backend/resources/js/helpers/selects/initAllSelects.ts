// resources/js/utils/initSelects.ts
import { createDatasetGetters } from '@/utils/dataset';

// Generar getters automáticamente desde appData
const datasets = createDatasetGetters();

// Cache para evitar reinicializar selects que ya fueron procesados
const initializedSelects = new WeakSet<HTMLSelectElement>();

/**
 * Inicializa todos los selects con Select2 que no tienen data-source
 */
function initSelect2Only(): void {
    document.querySelectorAll<HTMLSelectElement>('select.select2').forEach(select => {
        // Si ya fue inicializado con Select2, skip
        if ($(select).data('select2')) return;

        // Si tiene data-source, será manejado por initAllSelects
        if (select.dataset.source) return;

        const placeholderText = select.dataset.placeholder || '';
        const allowClear = select.dataset.allowClear !== 'false';

        $(select).select2({
            placeholder: placeholderText,
            allowClear: allowClear,
            theme: 'bootstrap4',
            width: '100%',
            multiple: select.multiple,
        });

        if (import.meta.env.DEV) {
            console.log(`✅ Select2 inicializado para #${select.id}`);
        }
    });
}

/**
 * Inicializa todos los <select> que tengan el atributo `data-source`.
 *
 * Funciona tanto para selects nativos como para Select2.
 * Permite placeholders dinámicos, selección inicial y deshabilitación condicional.
 *
 * @param selectedValues - Opcional: objeto { [selectId]: valorPreseleccionado }
 * @param disableFn - Opcional: función para deshabilitar opciones. Recibe (selectId, item) => boolean
 * @param forceReinit - Opcional: forzar reinicialización de selects ya inicializados (default: false)
 */
export function initAllSelects(
    selectedValues?: Partial<Record<string, string | number>>,
    disableFn?: (selectId: string, item: any) => boolean,
    forceReinit: boolean = false
): void {
    // Recorremos todos los selects con data-source
    document.querySelectorAll<HTMLSelectElement>('select[data-source]').forEach(select => {
        // Si ya fue inicializado y no se fuerza reinicialización, skip
        if (!forceReinit && initializedSelects.has(select)) {
            // Si hay valores preseleccionados, actualizar sin reconstruir opciones
            if (selectedValues?.[select.id]) {
                select.value = String(selectedValues[select.id]);
                // Trigger change para Select2
                if (select.classList.contains('select2')) {
                    $(select).trigger('change');
                }
            }
            return;
        }
        const sourceKey = select.dataset.source as keyof typeof datasets;
        const dataset = datasets[sourceKey]?.() ?? [];

        if (!dataset.length) {
            console.warn(`⚠️ Dataset '${String(sourceKey)}' no existe o está vacío para el select #${select.id}.`);
            return;
        }

        const valueKey = select.dataset.value as keyof typeof dataset[number];
        const labelKey = select.dataset.label as keyof typeof dataset[number];

        if (!valueKey || !labelKey) {
            console.warn(`⚠️ Select #${select.id} requiere atributos 'data-value' y 'data-label'.`);
            return;
        }

        // Limpiar select y crear placeholder si existe
        select.innerHTML = '';
        let placeholderOption: HTMLOptionElement | null = null;
        if (select.dataset.placeholder) {
            placeholderOption = new Option(select.dataset.placeholder, '', true, false);
            select.appendChild(placeholderOption);
        }

        // Agregar opciones del dataset ordenadas por label
        [...dataset]
            .sort((a, b) => String(a[labelKey]).localeCompare(String(b[labelKey]), 'es', { sensitivity: 'base' }))
            .forEach(item => {
                const value = item[valueKey];
                const label = item[labelKey];

                if (value === undefined || label === undefined) {
                    console.warn(`⚠️ Item del dataset '${String(sourceKey)}' no tiene '${String(valueKey)}' o '${String(labelKey)}'.`);
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
            select.addEventListener('change', () => {
                placeholderOption!.disabled = true;
            }, { once: true });
        }

        // 🔹 Inicializar Select2 si corresponde
        if (select.classList.contains('select2')) {
            const placeholderText = '    ' + (select.dataset.placeholder || '');
            const allowClear = select.dataset.allowClear !== 'false'; // true por defecto

            $(select).select2({
                placeholder: placeholderText,
                allowClear: allowClear,
                theme: 'bootstrap4',
                width: '100%',
                multiple: select.multiple,
            });
        }

        // Marcar como inicializado
        initializedSelects.add(select);
    });

    // Inicializar Select2 para selects que no tienen data-source
    initSelect2Only();
}

/**
 * Limpia el cache de selects inicializados
 * Útil cuando se destruye un modal o se recarga dinámicamente contenido
 */
export function clearSelectsCache(): void {
    // WeakSet no tiene método clear, pero los elementos se limpian automáticamente cuando son eliminados del DOM
    if (import.meta.env.DEV) {
        console.log('♻️ Cache de selects se limpiará automáticamente con garbage collection');
    }
}
