const otherCoins = document.getElementById('other-coins') as HTMLSelectElement | null;

/**
 * Inserta las monedas alternativas en el select correspondiente.
 * Excluye la moneda principal seleccionada.
 */

export function insertOtherCoins(selectedCoins: number[] = []): void {
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
