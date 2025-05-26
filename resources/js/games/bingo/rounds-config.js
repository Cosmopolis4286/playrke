const options = [
    ['1', 'Cruz larga roja'],
    ['2', '2'],
    ['3', 'Cruz corta azul']
];

function handleRoundsConfig(arrRounds = []) {
    const content = arrRounds.length
        ? generateRounds(arrRounds)
        : generateEmptyRounds();

    $('#content-rounds-config').html(content);
    bingoRounds = true;
}

function generateRounds(arr) {
    return arr.map(({ isoCode, coin, ctnPrice }, i) => {
        const roundNumber = i + 1;
        return buildRoundHtml(roundNumber, coin, isoCode);
    }).join('');
}

function generateEmptyRounds() {
    const numRounds = parseInt($('#rounds').val()) || 0;
    return Array.from({ length: numRounds }, (_, i) => {
        const roundNumber = i + 1;
        return buildRoundHtml(roundNumber);
    }).join('');
}

function buildRoundHtml(roundNumber, value = '', selected = '') {
    const optionsHtml = options.map(
        ([val, label]) => `
            <option value="${val}" ${val == selected ? 'selected' : ''}>
                ${label}
            </option>
        `
    ).join('');

    return `
        <div class="form-group col-sm-6 col-md-4 mt-1">
            <label for="round-${roundNumber}">Ronda #${roundNumber}</label>
            <div class="input-group">
                <input 
                    type="text" 
                    class="form-control" 
                    name="rounds_pcts[]" 
                    id="round-${roundNumber}" 
                    aria-label="Ronda #${roundNumber}" 
                    value="${value}"
                    placeholder="%">
                <div class="input-group-append">
                    <select 
                        class="form-control" 
                        name="rounds_types[]" 
                        aria-label="Seleccionar cantos">
                        ${optionsHtml}
                    </select>
                </div>
            </div>
        </div>
    `;
}


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

rounds.on('change', function () {
    if ($(this).val() > 1) {
        $('.content-rounds-pct').show();
        roundsPct.prop('required', true);
    } else {
        $('.content-rounds-pct, #container-rounds-config').hide();
        roundsPct.val('').prop('required', false);
    }
});

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

window.getRoundsConfig = getRoundsConfig;