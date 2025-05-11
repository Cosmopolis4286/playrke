function handleCtnPriceForCoins(arrCtnPrice) {
    // Generar el contenido HTML usando map y join
    const dataCoins = arrCtnPrice.map((elem) =>
        `<div class="col col-sm-6 col-md-4 mt-1">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">${elem.isoCode}</span>
            </div>
            <input 
                type="text" 
                class="form-control" 
                name="ctn_price_coins[${elem.coin}]" 
                id="ctn-price-coin-${elem.coin}" 
                aria-label="Precio para ${elem.isoCode}" 
                value="${elem.ctnPrice}">
        </div>
    </div>`).join('');

    // Actualizar el contenido del contenedor
    $('#container-price-config').html(dataCoins);

    // Marcar que las monedas ya han sido procesadas
    coinsCtnPrice = true;
}

$(document).on("click", "#btn-ctn-price-config", function () {
    const container = $("#container-ctn-price-config");
    // Alternar la clase y el valor del atributo 'data-active'
    const isActive = container.toggleClass("hide-elem").data("active") === 1 ? 0 : 1;
    container.data("active", isActive);

    if (isActive === 0 || coinsCtnPrice) return;

    getCtnPriceCoin();
});

function getCtnPriceCoin(room = '', action = '') {

    $('#container-price-config').html('');

    $.ajax({
        url: route('bingo.getCtnPriceForCoin', { creation, creatorType, creatorID, room }),
        method: 'GET',
        dataType: 'json',
        beforeSend: function () {
            $('#container-spinner').show();
        },
        success: function (res) {
            if (!action || res.hasPrice) {
                handleCtnPriceForCoins(res.ctnPrice);
                $("#container-ctn-price-config").removeClass("hide-elem").data("active", 1);
            }
        },
        error: function (error) { },
        complete: function () {
            $('#container-spinner').hide();
        }
    });
}

window.getCtnPriceCoin = getCtnPriceCoin;