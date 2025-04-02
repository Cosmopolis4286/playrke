function handleTicketPriceForCoins(arrTicketPrice) {
    // Generar el contenido HTML usando map y join
    const dataCoins = arrTicketPrice.map((elem) =>
        `<div class="col col-sm-6 col-md-4 mt-1">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">${elem.isoCode}</span>
            </div>
            <input 
                type="text" 
                class="form-control" 
                name="ctn_price_coins[${elem.coin}]" 
                id="ticket-price-coin-${elem.coin}" 
                aria-label="Precio para ${elem.isoCode}" 
                value="${elem.ticketPrice}">
        </div>
    </div>`).join('');

    // Actualizar el contenido del contenedor
    $('#container-price-config').html(dataCoins);

    // Marcar que las monedas ya han sido procesadas
    arrCoinsTicketPrice = true;
}

$(document).on("click", "#btn-ticket-price-config", function () {
    const container = $("#container-ticket-price-config");
    // Alternar la clase y el valor del atributo 'data-active'
    const isActive = container.toggleClass("hide-elem").data("active") === 1 ? 0 : 1;
    container.data("active", isActive);

    if (isActive === 0 || arrCoinsTicketPrice) return;

    getTicketPriceCoin();
});

function getTicketPriceCoin(draw = '', action = '') {

    $('#container-price-config').html('');

    $.ajax({
        url: routeGetTicketPriceForCoin.replace(':1', creation).replace(':2', creatorType)
            .replace(':3', creatorID).replace(':4', draw),
        method: 'GET',
        dataType: 'json',
        beforeSend: function () {
            inCourse = true;
            $('#container-spinner').show();
        },
        success: function (res) {
            if (!action || res.hasPrice) {
                handleTicketPriceForCoins(res.ticketPrice);
                $("#container-ticket-price-config").removeClass("hide-elem").data("active", 1);
            }
        },
        error: function (error) { },
        complete: function () {
            $('#container-spinner').hide();
            inCourse = false;
        }
    });
}

window.getTicketPriceCoin = getTicketPriceCoin;