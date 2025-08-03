import { showToast } from '../../../helpers/toastNotify';

let coinsCtnPrice = false;

/**
 * Renderiza los inputs para configurar el precio por moneda.
 * @param arrCtnPrice Array con objetos de monedas y precios.
 */
export function handleCtnPriceForCoins(arrCtnPrice: Array<{ coin: string | number; isoCode: string; ctnPrice: number; valMax: number }>): void {
    const allowedCoinsIDs = (document.getElementById('allowed-coins') as HTMLSelectElement)?.value;
    const allowedCoinsArray = Array.isArray(allowedCoinsIDs) ? allowedCoinsIDs : [allowedCoinsIDs];

    const container = document.getElementById('content-ctn-price-config');
    if (!container) return;

    const dataCoins = arrCtnPrice
        .filter(elem => allowedCoinsArray.includes(String(elem.coin)))
        .map(elem => `
      <div class="col col-sm-6 col-md-4 mt-1">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">${elem.isoCode}</span>
          </div>
          <input 
            type="number" 
            class="form-control ctn-price-coins-input" 
            name="ctn_price_coins[${elem.coin}]" 
            id="ctn-price-coin-${elem.coin}" 
            aria-label="Price for ${elem.isoCode}" 
            value="${elem.ctnPrice}" 
            min="1" 
            max="${elem.valMax}" 
            step="0.05" />
        </div>
      </div>
    `)
        .join('');

    container.innerHTML = dataCoins;
    coinsCtnPrice = true;
}

/**
 * Muestra u oculta el contenedor de configuración de precios y carga datos si es necesario.
 */
export function toggleCtnPriceConfig(): void {
    const container = document.getElementById('container-ctn-price-config');
    if (!container) return;

    const allowedCoinsElem = document.getElementById('allowed-coins') as HTMLSelectElement | null;
    const allowedCoinsVal = allowedCoinsElem?.value || '';
    const allowedCoinsArray = Array.isArray(allowedCoinsVal) ? allowedCoinsVal : [allowedCoinsVal];

    if (allowedCoinsArray.length === 0) return;

    if (container.style.display === 'block') {
        container.style.display = 'none';
        return;
    }

    if (!coinsCtnPrice) {
        const content = document.getElementById('content-ctn-price-config');
        const spinner = document.getElementById('spinner-ctn-price-config');
        if (content) content.innerHTML = '';
        if (spinner) spinner.style.display = 'block';
        getCtnPriceCoin();
    }

    container.style.display = 'block';
}

/**
 * Solicita los precios por moneda desde el servidor y los muestra.
 * @param room Opcional, identificador de la sala.
 */
export function getCtnPriceCoin(
    room = '',
    creation: string,
    creatorType: string,
    creatorID: string
): void {
    const allowedCoinsElem = document.getElementById('allowed-coins') as HTMLSelectElement | null;
    const allowedCoinsVal = allowedCoinsElem?.value || '';
    const allowedCoinsArray = Array.isArray(allowedCoinsVal) ? allowedCoinsVal : [allowedCoinsVal];
    const container = document.getElementById('container-ctn-price-config');
    const content = document.getElementById('content-ctn-price-config');
    const spinner = document.getElementById('spinner-ctn-price-config');

    if (coinsCtnPrice && allowedCoinsArray.length === 0) {
        if (container) container.style.display = 'none';
        if (content) content.innerHTML = '';
        coinsCtnPrice = false;
        allowedCoinsElem?.focus();
        showToast('Campo obligatorio', 'warning');
        return;
    }

    fetch(window.route('bingo.getCtnPriceForCoin', { creation, creatorType, creatorID, room }))
        .then(response => response.json())
        .then(res => {
            handleCtnPriceForCoins(res.ctnPrice);
            if (res.hasPrice && container) container.style.display = 'block';
        })
        .catch(() => {
            // Manejo de error opcional
        })
        .finally(() => {
            if (spinner) spinner.style.display = 'none';
        });
}

/**
 * Valida y ajusta inputs para precios de monedas.
 * @param input El input HTML a validar.
 */
export function validateCtnPriceInput(input: HTMLInputElement): void {
    input.required = input.value.trim() !== '';
    if (input.required) {
        // Aquí llama a tu función validateInput que ya tienes implementada
        (window as any).validateInput?.(input, true);
    }
}

// --- Event listeners sin jQuery ---
document.addEventListener('click', event => {
    const target = event.target as HTMLElement;
    if (target?.id === 'btn-ctn-price-config') {
        toggleCtnPriceConfig();
    }
});

document.addEventListener('input', event => {
    const target = event.target as HTMLElement;
    if (target && target.classList.contains('ctn-price-coins-input') && target instanceof HTMLInputElement) {
        validateCtnPriceInput(target);
    }
});

// --- Exponer funciones globalmente ---
(window as any).getCtnPriceCoin = getCtnPriceCoin;
