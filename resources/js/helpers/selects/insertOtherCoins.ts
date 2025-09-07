import { getAppDataProperty } from '@/data/index'; // Leer dinámicamente de AppData
import { Currency } from '@/types/dataModels';

// ==========================
//   Constantes
// ==========================
const arrCurrencies = getAppDataProperty<Currency[]>('currencies', []);

// ==========================
//   Elementos del DOM
// ==========================
const currency = document.getElementById('currency') as HTMLSelectElement | null;
const currencyText = document.getElementById('currency-text') as HTMLElement | null;
const containerCurrency = document.getElementById('container-currency') as HTMLElement | null;
const containerOtherCurrencies = document.getElementById('container-other-currencies') as HTMLElement | null;
const containerTimeZone = document.getElementById('container-time-zone') as HTMLElement | null;
const country = document.getElementById('country') as HTMLSelectElement | null;
const otherCurrencies = document.getElementById('other-currencies') as HTMLSelectElement | null;

// ==========================
//   Funciones auxiliares
// ==========================
/** Limpia el select de monedas alternativas */
function clearOtherCurrencies(): void {
    otherCurrencies?.replaceChildren();
}

/**
 * Inserta las monedas alternativas en el select correspondiente.
 * - Excluye la moneda principal seleccionada.
 * - Ordena las monedas alfabéticamente por su símbolo.
 * - Marca como seleccionadas las monedas que se pasan en `selectedCurrencies`.
 *
 * @param selectedCurrencies - Array de IDs de monedas que deben estar preseleccionadas.
 */
export function insertOtherCurrencies(selectedCurrencies: (number | string)[] = []): void {
    if (!otherCurrencies || !currency) return;

    clearOtherCurrencies();

    const mainCurrencyId = Number(currency.value);

    arrCurrencies
        .filter(c => c.id !== mainCurrencyId)
        .sort((a, b) => a.symbol.localeCompare(b.symbol, 'es', { sensitivity: 'base' }))
        .forEach(({ id, symbol, long_name }) => {
            const option = document.createElement('option');
            option.value = String(id);
            option.textContent = `(${symbol}) ${long_name}`;
            if (selectedCurrencies.includes(id)) option.selected = true;
            otherCurrencies.appendChild(option);
        });

    containerOtherCurrencies?.classList.remove('d-none');
}

/** Actualiza la moneda principal y el texto según el país seleccionado */
function updateMainCurrencyByCountry(selectedCountry: number): void {
    const matchingCurrency = arrCurrencies.find(c => c.country === selectedCountry);
    if (!matchingCurrency || !currency || !currencyText) return;

    currency.value = String(matchingCurrency.id);
    currencyText.textContent = `(${matchingCurrency.symbol}) ${matchingCurrency.short_name}`;

    containerCurrency?.classList.remove('d-none');
    containerTimeZone?.classList.remove('d-none');
}

/** Resetea selección de moneda y oculta container de otras monedas */
function resetCurrencySelection(): void {
    if (currency) currency.value = '';
    if (currencyText) currencyText.textContent = '';
    containerOtherCurrencies?.classList.add('d-none');
    containerCurrency?.classList.add('d-none');
    containerTimeZone?.classList.add('d-none');
}

// ==========================
//   Inicialización
// ==========================
/** Inicializa el selector de moneda en función del país */
export function initCurrencySelector(): void {
    if (!country) return;

    containerOtherCurrencies?.classList.add('d-none');

    country.addEventListener('change', () => {
        const selectedCountry = Number(country.value);

        if (!selectedCountry) {
            resetCurrencySelection();
            return;
        }

        updateMainCurrencyByCountry(selectedCountry);

        if (arrCurrencies.length > 1) insertOtherCurrencies();
    });
}

// Ejecutar inicialización automáticamente si existe el select de país
initCurrencySelector();
