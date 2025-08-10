import { route } from '@/app.js'; // Importamos funciones para manejar rutas Ziggy
import { showToastOptionBtns } from '../../../../helpers/toastNotify.js';
import { validateInput } from '../../../../helpers/validateInput.js';

// Usar listener para esperar que las rutas estén cargadas
window.addEventListener('ziggyReady', () => { }, { once: true });

// ==========================
//   Configuración inicial
// ==========================

// URL base para rutas de este módulo
const routeURL = (document.getElementById('route-url') as HTMLInputElement)?.value ?? '';

// Datos globales pasados desde PHP
const appDataScript = document.getElementById('app-data');
if (!appDataScript) throw new Error('No se encontró el script con datos de la app');

const appData = JSON.parse(appDataScript.textContent ?? '{}') as {
    countriesCommerce: Country[];
    timeZones: TimeZone[];
    coinsCommerce: Coin[];
};

// Tipos de datos
interface Country { id: number; name: string; }
interface TimeZone { id: number; name: string; }
interface Coin { id: number; symbol: string; long_name: string; short_name: string; country: number; }

// Datos procesados
const arrCountries = appData.countriesCommerce;
const arrTimeZones = appData.timeZones;
const coins = appData.coinsCommerce;

// Elementos clave del DOM
const coin = document.getElementById('coin') as HTMLSelectElement | null;
const coinText = document.getElementById('coin-text') as HTMLElement | null;
const country = document.getElementById('country') as HTMLSelectElement | null;
const otherCoins = document.getElementById('other-coins') as HTMLSelectElement | null;
const contOtherCoins = document.getElementById('cont-other-coins');

// Flag de estado
let update = false;

// ==========================
//   Funciones auxiliares
// ==========================

/**
 * Lista de campos requeridos para validación de formularios
 */
export const requiredFields: string[] = [
    'name', 'phone', 'commission', 'country', 'time-zone', 'coin', 'username', 'email'
];

/**
 * Casos especiales para acciones personalizadas con vendedores
 */
export const otherCasesMap: Record<string, (encryptedId: string) => void> = {
    '4': (encryptedId) => {
        showToastOptionBtns(
            '¿Deseas cambiar el status de este vendedor?',
            'question',
            'Sí, cambiar',
            route(`${routeURL}.change-status`, { id: encryptedId })
        );
    },
    '5': (encryptedId) => {
        showToastOptionBtns(
            'Generar nueva contraseña a este vendedor',
            'info',
            'Sí, generar',
            route(`${routeURL}.change-passw`, { id: encryptedId })
        );
    },
};

/**
 * Inserta las monedas alternativas en el select correspondiente.
 * Excluye la moneda principal seleccionada.
 */
function insertOtherCoins(selectedCoins: number[] = []): void {
    if (!otherCoins) return;

    otherCoins.innerHTML = '';
    const mainCoinId = coin ? Number(coin.value) : null;

    coins.forEach(item => {
        if (item.id === mainCoinId) return;

        const option = document.createElement('option');
        option.value = String(item.id);
        option.textContent = `(${item.symbol}) ${item.long_name}`;
        if (selectedCoins.includes(item.id)) option.selected = true;
        otherCoins.appendChild(option);
    });

    if (contOtherCoins) contOtherCoins.classList.remove('hide-elem');
}

/**
 * Inserta información en el formulario del modal según la acción (crear o editar)
 */
export function insertInfo(action: 'create' | 'edit', data: any = null): void {
    const usernameInput = document.getElementById('username') as HTMLInputElement;
    const emailInput = document.getElementById('email') as HTMLInputElement;
    const countrySelect = country;
    const timeZoneSelect = document.getElementById('time-zone') as HTMLSelectElement;
    const modal = document.getElementById(`modal-${routeURL}`);

    const elements = { usernameInput, emailInput, countrySelect, timeZoneSelect, modal };
    const missing = Object.entries(elements)
        .filter(([_, el]) => !el)
        .map(([name]) => name);

    if (missing.length) {
        console.error('Elementos DOM requeridos no encontrados:', missing.join(', '));
        return;
    }

    // Reset estado de edición
    usernameInput.readOnly = false;
    emailInput.readOnly = false;

    // Limpiar selects si es creación
    if (action === 'create') {
        countrySelect.innerHTML = `<option value="" disabled selected>Seleccionar:</option>`;
        timeZoneSelect.innerHTML = `<option value="" disabled selected>Seleccionar Zona:</option>`;
    }

    // Poblar países
    arrCountries.forEach(item => {
        const option = new Option(item.name, String(item.id), false, action === 'edit' && data?.seller?.country === item.id);
        countrySelect.appendChild(option);
    });

    // Poblar zonas horarias
    arrTimeZones.forEach(item => {
        const option = new Option(item.name, String(item.id), false, action === 'edit' && data?.user?.time_zone === item.id);
        timeZoneSelect.appendChild(option);
    });

    // Si es edición, rellenar datos
    if (action === 'edit' && data) {
        const { seller, user, other_coins: otherCoinsSelected = [] } = data;
        (document.getElementById('name') as HTMLInputElement).value = seller.name || '';
        (document.getElementById('phone') as HTMLInputElement).value = seller.phone || '';
        (document.getElementById('commission') as HTMLInputElement).value = seller.commission || '';

        countrySelect.dispatchEvent(new Event('change'));
        insertOtherCoins(otherCoinsSelected);

        usernameInput.value = user.username || '';
        emailInput.value = user.email || '';
        usernameInput.readOnly = true;
        emailInput.readOnly = true;

        update = true;
    } else {
        update = false;
    }

    // Mostrar modal con Bootstrap 4
    $(modal).modal('show');
}

// ==========================
//   Eventos del formulario
// ==========================

if (country && coin && coinText) {
    country.addEventListener('change', () => {
        const selectedCountry = Number(country.value);
        const matchingCoin = coins.find(c => c.country === selectedCountry);

        if (matchingCoin) {
            coinText.textContent = `(${matchingCoin.symbol}) ${matchingCoin.short_name}`;
            coin.value = String(matchingCoin.id);
        }

        if (coins.length > 1 && !update) {
            insertOtherCoins();
        }

        if (!selectedCountry) {
            coinText.textContent = '';
            coin.value = '';
            contOtherCoins?.classList.add('hide-elem');
        }
    });
}

// ==========================
//   Inicialización DOM
// ==========================

document.addEventListener('DOMContentLoaded', () => {
    if (typeof $ === 'function') {
        $('#phone').mask('00000000000');

        $('.select2').select2({
            theme: 'bootstrap4',
            width: 'style',
            placeholder: function () {
                return $(this).attr('placeholder') || '';
            },
            allowClear: function () {
                return Boolean($(this).data('allow-clear'));
            }
        });
    }

    document.addEventListener('input', (event) => {
        const input = event.target as HTMLInputElement;
        if (input.id === 'commission') {
            validateInput(input, true, 3, 35);
        }
    });
});
