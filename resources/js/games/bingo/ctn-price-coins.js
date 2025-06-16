function handleCtnPriceForCoins(arrCtnPrice) {
    const allowedCoinsIDs = $("#allowed-coins").val();
    const dataCoins = arrCtnPrice
        .filter(elem => Array.isArray(allowedCoinsIDs) && allowedCoinsIDs.some(id => id == elem.coin))
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
                    aria-label="Precio para ${elem.isoCode}" 
                    value="${elem.ctnPrice}"
                    min="1"
                    max="${elem.valMax}"
                    step="0.05">
            </div>
        </div>
    `).join('');

    // Actualizar el contenido del contenedor
    $('#content-ctn-price-config').html(dataCoins);

    // Marcar que las monedas ya han sido procesadas
    coinsCtnPrice = true;
}

$(document).on("click", "#btn-ctn-price-config", function () {
    const container = $("#container-ctn-price-config");
    console.log('$("#allowed-coins").val()', $("#allowed-coins").val());

    if (($("#allowed-coins").val() || []).length === 0) return;

    if (container.is(":visible")) {
        container.hide();
        return;
    }

    if (!coinsCtnPrice) {
        $('#content-ctn-price-config').empty();
        $('#spinner-ctn-price-config').show();
        getCtnPriceCoin();
    }

    container.show();
});

function getCtnPriceCoin(room = '') {

    if (($("#allowed-coins").val() || []).length === 0) {
        $("#container-ctn-price-config").hide();
        $('#content-ctn-price-config').empty();
        coinsCtnPrice = false;
        $("#allowed-coins").focus();
        showToast('Campo obligatorio', 'warning');
        return;
    }

    $.ajax({
        url: route('bingo.getCtnPriceForCoin', { creation, creatorType, creatorID, room }),
        method: 'GET',
        dataType: 'json',
        success: function (res) {
            handleCtnPriceForCoins(res.ctnPrice);
            if (res.hasPrice) {
                $("#container-ctn-price-config").show();
            }
        },
        error: function (error) { },
        complete: function () {
            $('#spinner-ctn-price-config').hide();
        }
    });
}

$(document).on("input change", ".ctn-price-coins-input", function () {
    const $input = $(this);

    $input.prop('required', $input.val().trim() !== '');

    if ($input.prop('required')) {
        validateInput($input, true);
    }
});

window.getCtnPriceCoin = getCtnPriceCoin;