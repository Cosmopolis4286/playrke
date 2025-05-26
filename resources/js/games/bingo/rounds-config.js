function handleRoundsConfig(arrRounds = []) {
    // Generar el contenido HTML usando map y join
    const dataCoins = arrRounds.map((elem) =>
        `<div class="col col-sm-6 col-md-4 mt-1">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">${elem.isoCode}</span>
            </div>
            <input 
                type="text" 
                class="form-control" 
                name="ctn_price_coins[${elem.coin}]" 
                id="rounds-coin-${elem.coin}" 
                aria-label="Precio para ${elem.isoCode}" 
                value="${elem.ctnPrice}">
        </div>
    </div>`).join('');

    // Actualizar el contenido del contenedor
    $('#content-rounds-config').html(dataCoins);

    // Marcar que las rondas ya han sido procesadas
    bingoRounds = true;
}

$(document).on("click", "#btn-rounds-config", function () {

    if (!$('#rounds-pct').val()) {
        $('#rounds-pct').trigger('focus');
        showToast('Indica un porcentaje para las rondas', 'warning');
        return;
    } else {
        const container = $("#container-rounds-config");

        if (container.is(":visible")) {
            container.hide();
            return;
        }

        container.show();

        if (!bingoRounds) {
            getRoundsConfig();
        }
    }
});

function getRoundsConfig(room = '') {
    $.ajax({
        url: route('bingo.getRoundsConfig', room),
        method: 'GET',
        dataType: 'json',
        beforeSend: function () {
            $('#spinner-rounds-config').show();
            $('#content-rounds-config').html('');
            $("#container-rounds-config").show();
        },
        success: function (res) {
            handleRoundsConfig(res.ctnPrice);
        },
        error: function (error) { },
        complete: function () {
            $('#spinner-rounds-config').hide();
        }
    });
}

window.getRoundsConfig = getRoundsConfig;