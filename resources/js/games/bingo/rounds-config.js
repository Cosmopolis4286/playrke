function handleRoundsConfig(arrRounds = []) {
    let dataRounds = '';

    if (arrRounds.length) {
        dataRounds = arrRounds.map(({ isoCode, coin, ctnPrice }) => `
            <div class="col col-sm-6 col-md-4 mt-1">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">#${isoCode}</span>
                    </div>
                    <input 
                        type="text" 
                        class="form-control" 
                        name="ctn_price_coins[${coin}]" 
                        id="rounds-coin-${coin}" 
                        aria-label="Precio para ${isoCode}" 
                        value="${ctnPrice}">
                </div>
            </div>
        `).join('');
    } else {
        const numRounds = parseInt(rounds.val()) || 0;

        dataRounds = Array.from({ length: numRounds }, (_, index) => {
            const roundNumber = index + 1;
            return `
                <div class="col col-sm-6 col-md-4 mt-1">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">#${roundNumber}</span>
                        </div>
                        <input 
                            type="text" 
                            class="form-control" 
                            name="rounds_pcts[]" 
                            id="rounds-${roundNumber}" 
                            aria-label="Ronda #${roundNumber}" 
                            value="">
                    </div>
                </div>
            `;
        }).join('');
    }

    $('#content-rounds-config').html(dataRounds);
    bingoRounds = true;
}

$(document).on("click", "#btn-rounds-config", function () {

    if (!roundsPct.val()) {
        roundsPct.trigger('focus');
        showToast('Indica un porcentaje para las rondas', 'warning');
        return;
    } else {
        const container = $("#container-rounds-config");

        if (container.is(":visible")) {
            container.hide();
            return;
        }

        container.show();

        if (!bingoRounds && rounds.val() > 1) {
            getRoundsConfig();
        }
    }
});

function getRoundsConfig(room = '') {
    if (!room) {
        $('#spinner-rounds-config').hide();
        handleRoundsConfig();
        return;
    }
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