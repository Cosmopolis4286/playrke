import { getAppDataProperty } from '@/data/index'; // Leer dinámicamente de AppData
import { Currency, TimeZone } from '@/types/dataModels';

// ==========================
//   Constantes
// ==========================
const arrCurrencies = getAppDataProperty<Currency[]>('currencies', []);
const arrTimeZones = getAppDataProperty<TimeZone[]>('timeZones', []);

// ==========================
//   Elementos del DOM
// ==========================
const currency = document.getElementById('currency') as HTMLSelectElement | null;
const containerCurrency = document.getElementById('container-currency') as HTMLElement | null;
const containerOtherCurrencies = document.getElementById('container-other-currencies') as HTMLElement | null;
const containerTimeZone = document.getElementById('container-time-zone') as HTMLElement | null;
const country = document.getElementById('country') as HTMLSelectElement | null;
const otherCurrencies = document.getElementById('other-currencies') as HTMLSelectElement | null;
const timeZoneSelect = document.getElementById('time-zone') as HTMLSelectElement | null;

// ==========================
//   Funciones auxiliares
// ==========================
/** Limpia el select de monedas alternativas */
function clearOtherCurrencies(): void {
    otherCurrencies?.replaceChildren();
}

/**
 * Inserta las monedas alternativas en el select correspondiente.
 * - Filtra monedas del mismo país que la moneda principal.
 * - Excluye la moneda principal seleccionada.
 * - Ordena las monedas alfabéticamente por su símbolo.
 * - Marca como seleccionadas las monedas que se pasan en `selectedCurrencies`.
 *
 * @param selectedCurrencies - Array de IDs de monedas que deben estar preseleccionadas.
 */
export function insertOtherCurrencies(selectedCurrencies: (number | string)[] = []): void {
    if (!otherCurrencies || !currency) return;

    clearOtherCurrencies();

    const mainCurrencyId = currency.value;

    // Obtener el country_id de la moneda principal seleccionada
    const mainCurrency = arrCurrencies.find(c => String(c.id) === String(mainCurrencyId));
    if (!mainCurrency) return;

    const countryId = mainCurrency.country_id;

    // Filtrar monedas: mismo país Y diferente a la principal
    arrCurrencies
        .filter(c => String(c.country_id) === String(countryId) && String(c.id) !== String(mainCurrencyId))
        .sort((a, b) => a.symbol.localeCompare(b.symbol, 'es', { sensitivity: 'base' }))
        .forEach(({ id, symbol, description }) => {
            const option = document.createElement('option');
            option.value = String(id);
            option.textContent = `(${symbol}) ${description}`;
            if (selectedCurrencies.some(sc => String(sc) === String(id))) option.selected = true;
            otherCurrencies.appendChild(option);
        });

    containerOtherCurrencies?.classList.remove('d-none');
}

/** Actualiza la moneda principal según el país seleccionado */
function updateMainCurrencyByCountry(selectedCountry: string): void {
    if (!currency) return;

    // Limpiar el select
    currency.innerHTML = '';

    // Agregar placeholder
    const placeholder = currency.dataset.placeholder;
    if (placeholder) {
        const placeholderOption = new Option(placeholder, '', false, false);
        placeholderOption.disabled = true;
        currency.appendChild(placeholderOption);
    }

    // Filtrar monedas por país seleccionado
    const currenciesInCountry = arrCurrencies
        .filter(c => String(c.country_id) === String(selectedCountry))
        .sort((a, b) => a.symbol.localeCompare(b.symbol, 'es', { sensitivity: 'base' }));

    if (currenciesInCountry.length === 0) {
        console.warn(`⚠️ No hay monedas para el país con ID ${selectedCountry}`);
        return;
    }

    // Agregar todas las monedas del país al select
    currenciesInCountry.forEach(({ id, symbol, description }) => {
        const option = new Option(
            `(${symbol}) ${description}`,
            String(id),
            false,
            false
        );
        currency.appendChild(option);
    });

    // Si solo hay una moneda, seleccionarla automáticamente
    if (currenciesInCountry.length === 1) {
        currency.value = String(currenciesInCountry[0].id);

        // Disparar el evento change para que se carguen las otras monedas
        currency.dispatchEvent(new Event('change'));
    }

    containerCurrency?.classList.remove('d-none');
    containerTimeZone?.classList.remove('d-none');
}

/**
 * Actualiza las zonas horarias filtradas por el país seleccionado
 * @param selectedCountry - ID del país seleccionado
 * @param selectedTimeZone - Zona horaria a preseleccionar (opcional)
 */
function updateTimeZonesByCountry(selectedCountry: string, selectedTimeZone?: string): void {
    if (!timeZoneSelect) return;

    // Limpiar opciones actuales
    timeZoneSelect.innerHTML = '';

    // Filtrar zonas horarias por país
    const filteredTimeZones = arrTimeZones.filter(tz => String(tz.country_id) === String(selectedCountry));

    if (filteredTimeZones.length === 0) {
        console.warn(`⚠️ No hay zonas horarias para el país con ID ${selectedCountry}`);
        return;
    }

    // Ordenar alfabéticamente por nombre
    const sortedTimeZones = filteredTimeZones.sort((a, b) =>
        a.name.localeCompare(b.name, 'es', { sensitivity: 'base' })
    );

    // Si hay más de una zona horaria, agregar placeholder
    // Si hay solo una, NO agregar placeholder (se seleccionará automáticamente)
    const placeholder = timeZoneSelect.dataset.placeholder;
    const hasMultipleTimeZones = sortedTimeZones.length > 1;

    if (hasMultipleTimeZones && placeholder) {
        const placeholderOption = new Option(placeholder, '', true, false);
        placeholderOption.disabled = true;
        timeZoneSelect.appendChild(placeholderOption);
    }

    // Agregar opciones de zonas horarias
    sortedTimeZones.forEach((tz, index) => {
        // Si hay solo una zona horaria, seleccionarla por defecto
        // Si hay un valor preseleccionado, usarlo
        // Si hay múltiples zonas y no hay preselección, ninguna estará seleccionada (placeholder activo)
        const shouldBeSelected = selectedTimeZone
            ? selectedTimeZone === tz.id
            : !hasMultipleTimeZones && index === 0;

        const option = new Option(
            `${tz.name} (${tz.utc})`,
            tz.id,
            shouldBeSelected,
            shouldBeSelected
        );
        timeZoneSelect.appendChild(option);
    });

    // Establecer el valor seleccionado en el select
    if (selectedTimeZone) {
        timeZoneSelect.value = selectedTimeZone;
    } else if (!hasMultipleTimeZones && sortedTimeZones.length === 1) {
        timeZoneSelect.value = sortedTimeZones[0].id;
    }
}

/** Resetea selección de moneda y zonas horarias, oculta containers */
function resetCurrencySelection(): void {
    // Resetear select de moneda
    if (currency) {
        currency.innerHTML = '';
        const placeholder = currency.dataset.placeholder;
        if (placeholder) {
            const placeholderOption = new Option(placeholder, '', true, false);
            placeholderOption.disabled = true;
            currency.appendChild(placeholderOption);
        }
    }

    // Resetear select de zonas horarias
    if (timeZoneSelect) {
        timeZoneSelect.innerHTML = '';
        const placeholder = timeZoneSelect.dataset.placeholder;
        if (placeholder) {
            const placeholderOption = new Option(placeholder, '', true, false);
            placeholderOption.disabled = true;
            timeZoneSelect.appendChild(placeholderOption);
        }
    }

    containerOtherCurrencies?.classList.add('d-none');
    containerCurrency?.classList.add('d-none');
    containerTimeZone?.classList.add('d-none');
}

// ==========================
//   Inicialización
// ==========================
let _currencySelectorInitialized = false;

/** Inicializa el selector de moneda y zona horaria en función del país */
export function initCurrencySelector(): void {
    if (_currencySelectorInitialized) {
        if (import.meta.env.DEV) {
            console.log('♻️ initCurrencySelector ya fue inicializado, usando instancia existente');
        }
        return;
    }

    if (!country) {
        if (import.meta.env.DEV) {
            console.warn('⚠️ No se encontró select de país (#country)');
        }
        return;
    }

    if (import.meta.env.DEV) {
        console.log('Inicializando selector de moneda y zona horaria por país', country);
    }

    // Ocultar containers inicialmente
    containerOtherCurrencies?.classList.add('d-none');
    containerCurrency?.classList.add('d-none');
    containerTimeZone?.classList.add('d-none');

    // Inicializar el placeholder del select de moneda
    if (currency) {
        currency.innerHTML = '';
        const placeholder = currency.dataset.placeholder;
        if (placeholder) {
            const placeholderOption = new Option(placeholder, '', true, false);
            placeholderOption.disabled = true;
            currency.appendChild(placeholderOption);
        }
    }

    // Inicializar el placeholder del select de zona horaria
    if (timeZoneSelect) {
        timeZoneSelect.innerHTML = '';
        const placeholder = timeZoneSelect.dataset.placeholder;
        if (placeholder) {
            const placeholderOption = new Option(placeholder, '', true, false);
            placeholderOption.disabled = true;
            timeZoneSelect.appendChild(placeholderOption);
        }
    }

    country.addEventListener('change', () => {
        const selectedCountry = country.value;

        if (!selectedCountry) {
            resetCurrencySelection();
            return;
        }

        // Actualizar moneda principal
        updateMainCurrencyByCountry(selectedCountry);

        // Actualizar zonas horarias filtradas por país
        updateTimeZonesByCountry(selectedCountry);

        // Insertar otras monedas del mismo país (excluyendo la principal)
        const currenciesInCountry = arrCurrencies.filter(c => String(c.country_id) === String(selectedCountry));
        if (currenciesInCountry.length > 1) {
            insertOtherCurrencies();
        } else {
            // Si solo hay una moneda en el país, ocultar el select de otras monedas
            containerOtherCurrencies?.classList.add('d-none');
        }
    });

    // Listener para cuando se selecciona una moneda principal manualmente
    currency?.addEventListener('change', () => {
        const selectedCountry = country?.value;
        if (!selectedCountry) return;

        // Insertar otras monedas del mismo país (excluyendo la principal)
        const currenciesInCountry = arrCurrencies.filter(c => String(c.country_id) === String(selectedCountry));
        if (currenciesInCountry.length > 1 && currency.value) {
            insertOtherCurrencies();
        }
    });

    _currencySelectorInitialized = true;
}

/**
 * Exportar función para actualizar zonas horarias manualmente
 * Útil cuando se necesita preseleccionar una zona horaria al editar
 */
export function updateTimeZones(countryId: string | number, selectedTimeZone?: string): void {
    updateTimeZonesByCountry(String(countryId), selectedTimeZone);
}

/**
 * Resetea el estado de inicialización (útil para testing)
 */
export function resetCurrencySelectorState(): void {
    _currencySelectorInitialized = false;
}
