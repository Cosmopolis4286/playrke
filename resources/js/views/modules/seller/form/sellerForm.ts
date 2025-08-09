import $ from 'jquery';
import 'jquery-mask-plugin';
import { route } from 'ziggy-js';
import { showToastOptionBtns } from '../../../../helpers/toastNotify.js';
import { validateInput } from '../../../../helpers/validateInput.js';

// Variables globales obtenidas desde el DOM o PHP
const routeURL = (document.getElementById('route') as HTMLInputElement | null)?.value ?? '';

interface Country {
    id: number;
    name: string;
}

interface TimeZone {
    id: number;
    name: string;
}

interface Coin {
    id: number;
    symbol: string;
    long_name: string;
    short_name: string;
    country: number;
}

const appDataScript = document.getElementById('app-data');
if (!appDataScript) throw new Error('No se encontró el script con datos de la app');

const appData = JSON.parse(appDataScript.textContent ?? '{}') as {
    countriesCommerce: Country[];
    timeZones: TimeZone[];
    coinsCommerce: Coin[];
};

const arrCountries = appData.countriesCommerce;
const arrTimeZones = appData.timeZones;
const coins = appData.coinsCommerce;

// Correcta asignación de tipos según elementos HTML reales:
const coin = document.getElementById('coin') as HTMLSelectElement | null;
const coinText = document.getElementById('coin-text') as HTMLElement | null;
const country = document.getElementById('country') as HTMLSelectElement | null; // Asumo es select, no input
const otherCoins = document.getElementById('other-coins') as HTMLSelectElement | null; // Asumo es select

// Flag externo para controlar si estamos en modo update
let update = false;

/**
 * Tipo función para los casos especiales con encryptedId
 */
type OtherCaseFn = (encryptedId: string) => void;

/**
 * Mapeo de acciones personalizadas para manejar casos especiales
 * donde la clave es el tipo de acción y el valor es la función a ejecutar.
 */
export const otherCasesMap: Record<string, OtherCaseFn> = {
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
            route(`${routeURL}.change-passw`, { id: encryptedId }) // corregido 'name' a routeURL
        );
    },
};

/**
 * Lista de campos requeridos para formularios
 */
export const requiredFields: string[] = [
    'name', 'phone', 'commission', 'country', 'time-zone', 'coin', 'username', 'email'
];

/**
 * Inserta las opciones de monedas alternativas excluyendo la moneda seleccionada.
 * @param selectedCoins - Array con IDs de monedas que deben aparecer seleccionadas
 */
function insertOtherCoins(selectedCoins: number[] = []): void {
    // Verifica que el contenedor exista para evitar errores
    if (!otherCoins) return;

    // Limpia todo el contenido actual dentro de 'otherCoins'
    otherCoins.innerHTML = '';

    // Obtiene el valor seleccionado del select 'coin' como número
    const coinValue = coin ? Number(coin.value) : null;

    // Recorre todas las monedas disponibles
    coins.forEach(item => {
        // Solo añade monedas que no sean la seleccionada actualmente
        if (item.id !== coinValue) {
            // Crea un nuevo elemento <option>
            const option = document.createElement('option');

            // Asigna el valor del option como string
            option.value = item.id.toString();

            // Asigna el texto visible dentro del option
            option.textContent = `(${item.symbol}) ${item.long_name}`;

            // Marca el option como seleccionado si está dentro de selectedCoins
            if (selectedCoins.includes(item.id)) {
                option.selected = true;
            }

            // Agrega el option al select 'otherCoins'
            otherCoins.appendChild(option);
        }
    });

    // Busca el contenedor que debe mostrarse cuando hay otras monedas
    const contOtherCoins = document.getElementById('cont-other-coins');

    // Si el contenedor existe, quita la clase que lo oculta
    if (contOtherCoins) {
        contOtherCoins.classList.remove('hide-elem');
    }
}

/**
 * Inserta información en el formulario/modal según la acción.
 * @param action - Tipo de acción ('create', 'edit', etc.)
 * @param data - Datos a cargar en el formulario (opcional)
 */
export function insertInfo(action: string, data: any = ''): void {
    // Obtener elementos DOM
    const usernameInput = document.getElementById('username') as HTMLInputElement | null;
    const emailInput = document.getElementById('email') as HTMLInputElement | null;
    const countrySelect = document.getElementById('country') as HTMLSelectElement | null;
    const timeZoneSelect = document.getElementById('time-zone') as HTMLSelectElement | null;
    const modal = document.getElementById(`modal-${routeURL}`) as HTMLElement | null; // uso routeURL para identificar modal

    if (!usernameInput || !emailInput || !countrySelect || !timeZoneSelect || !modal) {
        console.error('Elementos DOM requeridos no encontrados');
        return;
    }

    // Permitir editar username y email por defecto
    usernameInput.readOnly = false;
    emailInput.readOnly = false;

    // Si es acción crear, limpiar selects y poner opciones por defecto
    if (action === 'create') {
        countrySelect.innerHTML = `<option value="" disabled selected>Seleccionar:</option>`;
        timeZoneSelect.innerHTML = `<option value="" disabled selected>Seleccionar Zona:</option>`;
    }

    // Insertar países en el select, seleccionando el actual si acción es editar
    arrCountries.forEach(item => {
        const option = document.createElement('option');
        option.value = String(item.id);
        option.textContent = item.name;
        if (action === 'edit' && data?.seller?.country === item.id) option.selected = true;
        countrySelect.appendChild(option);
    });

    // Insertar zonas horarias en el select, seleccionando la actual si acción es editar
    arrTimeZones.forEach(item => {
        const option = document.createElement('option');
        option.value = String(item.id);
        option.textContent = item.name;
        if (action === 'edit' && data?.user?.time_zone === item.id) option.selected = true;
        timeZoneSelect.appendChild(option);
    });

    if (action === 'edit' && data) {
        const seller = data.seller;
        const user = data.user;
        const otherCoinsSelected: number[] = data.other_coins || [];

        // Rellenar campos con datos del vendedor
        (document.getElementById('name') as HTMLInputElement).value = seller.name || '';
        (document.getElementById('phone') as HTMLInputElement).value = seller.phone || '';
        (document.getElementById('commission') as HTMLInputElement).value = seller.commission || ''; // corregido 'commn' a 'commission'

        // Disparar evento change para actualizar select moneda
        countrySelect.dispatchEvent(new Event('change'));

        // Insertar monedas alternativas seleccionadas
        insertOtherCoins(otherCoinsSelected);

        // Poner username y email y bloquear edición
        usernameInput.value = user.username || '';
        emailInput.value = user.email || '';
        usernameInput.readOnly = true;
        emailInput.readOnly = true;

        update = true;
    } else {
        update = false;
    }

    // Mostrar modal con jQuery (Bootstrap 4 requiere jQuery para modales)
    $(modal).modal('show');
}

/**
 * Evento para actualizar la moneda principal y monedas alternativas
 * cuando se cambia el país seleccionado.
 */
if (country && coin && coinText) {
    country.addEventListener('change', () => {
        const selectedCountry = country.value;

        if (selectedCountry) {
            const selectedCountryNum = Number(selectedCountry);

            const matchingCoin = coins.find(item => item.country === selectedCountryNum);

            if (matchingCoin) {
                coinText.textContent = `(${matchingCoin.symbol}) ${matchingCoin.short_name}`;
                coin.value = matchingCoin.id.toString();
            }

            if (coins.length > 1 && !update) {
                insertOtherCoins();
            }
        } else {
            coinText.textContent = '';
            coin.value = '';
            const contOtherCoins = document.getElementById('cont-other-coins');
            if (contOtherCoins) {
                contOtherCoins.classList.add('hide-elem');
            }
        }
    });
}

/**
 * Código que se ejecuta cuando el DOM está cargado,
 * inicializando popovers, máscaras y select2 con jQuery.
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof $ === 'function') {
        try {
            $('[data-toggle="popover"]').popover({ html: true });
        } catch (error) {
            console.warn('Error inicializando popovers:', error);
        }

        $('#phone').mask('00000000000');

        $('.select2').each(function () {
            $(this).select2({
                theme: 'bootstrap4',
                width: 'style',
                placeholder: $(this).attr('placeholder') || '',
                allowClear: Boolean($(this).data('allow-clear')),
            });
        });
    }

    document.addEventListener('input', (event) => {
        const input = event.target as HTMLInputElement | null;
        if (input?.id === 'commission') {
            validateInput(input, true, 3, 35);
        }
    });
});
