import { coins } from '@/data/index.js'; // Ahora arrCoins viene de AppData

// ==========================
//   Elementos del DOM
// ==========================
const coin = document.getElementById('coin') as HTMLSelectElement | null;
const coinText = document.getElementById('coin-text') as HTMLElement | null;
const containerCoin = document.getElementById('container-coin') as HTMLElement | null;
const containerOtherCoins = document.getElementById('container-other-coins') as HTMLElement | null;
const containerTimeZone = document.getElementById('container-time-zone') as HTMLElement | null;
const country = document.getElementById('country') as HTMLSelectElement | null;
const otherCoins = document.getElementById('other-coins') as HTMLSelectElement | null;

// ==========================
//   Funciones auxiliares
// ==========================
/** Limpia el select de monedas alternativas */
function clearOtherCoins(): void {
    if (otherCoins) otherCoins.innerHTML = '';
}

/**
 * Inserta las monedas alternativas en el select correspondiente.
 * - Excluye la moneda principal seleccionada.
 * - Ordena las monedas alfabéticamente por su símbolo.
 * - Marca como seleccionadas las monedas que se pasan en `selectedCoins`.
 *
 * @param selectedCoins - Array de IDs de monedas que deben estar preseleccionadas.
 */
export function insertOtherCoins(selectedCoins: (number | string)[] = []): void {
    // Si no existen los elementos DOM requeridos, salir de la función
    if (!otherCoins || !coin) return;

    // Limpiar cualquier opción previa en el select de monedas alternativas
    clearOtherCoins();

    // Obtener el ID de la moneda principal actualmente seleccionada
    const mainCoinId = Number(coin.value);

    // Filtrar monedas excluyendo la principal, ordenar por symbol y agregar al select
    coins
        .filter(c => c.id !== mainCoinId) // Excluir moneda principal
        .sort((a, b) => a.symbol.localeCompare(b.symbol, 'es', { sensitivity: 'base' })) // Ordenar alfabéticamente por símbolo
        .forEach(({ id, symbol, long_name }) => {
            const option = document.createElement('option'); // Crear opción del select
            option.value = String(id);                        // Valor de la opción
            option.textContent = `(${symbol}) ${long_name}`; // Texto visible en el select

            // Marcar como seleccionada si el ID está en selectedCoins
            if (selectedCoins.includes(id)) option.selected = true;

            // Agregar la opción al select
            otherCoins.appendChild(option);
        });

    // Asegurarse de mostrar el contenedor de monedas alternativas
    containerOtherCoins?.classList.remove('d-none');
}

/** Actualiza la moneda principal y el texto según el país seleccionado */
function updateMainCoinByCountry(selectedCountry: number): void {
    const matchingCoin = coins.find(c => c.country === selectedCountry);

    if (!matchingCoin || !coin || !coinText) return;

    coin.value = String(matchingCoin.id);
    coinText.textContent = `(${matchingCoin.symbol}) ${matchingCoin.short_name}`;

    containerCoin?.classList.remove('d-none');
    containerTimeZone?.classList.remove('d-none');
}

/** Resetea selección de moneda y oculta container de otras monedas */
function resetCoinSelection(): void {
    if (coin) coin.value = '';
    if (coinText) coinText.textContent = '';
    containerOtherCoins?.classList.add('d-none');
    containerCoin?.classList.add('d-none');
    containerTimeZone?.classList.add('d-none');
}

// ==========================
//   Inicialización
// ==========================
/** Inicializa el selector de moneda en función del país */
export function initCoinSelector(): void {
    if (!country) return;

    country.addEventListener('change', () => {
        const selectedCountry = Number(country.value);

        if (!selectedCountry) {
            resetCoinSelection();
            return;
        }

        updateMainCoinByCountry(selectedCountry);

        if (coins.length > 1) {
            insertOtherCoins();
        }
    });
}



// Ejecutar inicialización automáticamente si existe el select de país
initCoinSelector();
