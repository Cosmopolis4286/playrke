function handleRoundsConfig(arrRounds = []) {
    const pctValue = parseFloat(roundsPct.val()) || 0;
    const content = arrRounds.length
        ? generateRounds(arrRounds)
        : generateEmptyRounds(pctValue);

    $('#content-rounds-config').empty().html(content);
    bingoRounds = true;
}

function generateRounds(arr) {
    return arr.map(({ round, pct }) => {
        return buildRoundHtml(round, pct);
    }).join('');
}

function generateEmptyRounds(pctValue) {
    const numRounds = parseInt($('#rounds').val()) || 0;
    const valuePerRound = (numRounds > 0) ? (pctValue / numRounds).toFixed(2) : 0;

    return Array.from({ length: numRounds }, (_, i) => {
        const roundNumber = i + 1;
        return buildRoundHtml(roundNumber, valuePerRound);
    }).join('');
}

function buildRoundHtml(roundNumber, roundPCT = '', selected = '') {
    const optionsHtml = arrBingoPlays.map(
        ({ id, name }) => `
        <option value="${id}" ${id == selected ? 'selected' : ''}>
            ${name}
        </option>`
    ).join('');

    return `
        <div class="form-group col-sm-6 col-md-4 mt-1">
            <label for="round-${roundNumber}">Ronda #${roundNumber}</label>
            <div class="input-group">
                <input 
                    type="text" 
                    class="form-control rounds-pcts-input" 
                    name="rounds_pcts[]" 
                    id="round-${roundNumber}" 
                    aria-label="Ronda #${roundNumber}" 
                    value="${roundPCT}"
                    placeholder="%"
                    required>
                <div class="input-group-append">
                    <select 
                        class="form-control" 
                        name="rounds_types[]" 
                        aria-label="Seleccionar cantos"
                        disabled>
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
            if (res.length) handleRoundsConfig(res);
        },
        error: function () { },
        complete: function () {
            $('#spinner-rounds-config').hide();
        }
    });
}

rounds.on('change', function () {
    if ($(this).val() > 1) {
        if (!$('.content-rounds-pct').is(":visible")) {
            $('.content-rounds-pct').show();
            roundsPct.prop('required', true);
        } else {
            getRoundsConfig();
        }
    } else {
        $('.content-rounds-pct, #container-rounds-config').hide();
        roundsPct.val('').prop('required', false);
    }
});

roundsPct.on('change', function () {
    getRoundsConfig();
});

$(document).on("click", "#btn-rounds-config", function () {
    const pctValue = parseFloat(roundsPct.val());

    if (!pctValue || pctValue <= 0) {
        roundsPct.trigger('focus');
        showToast('Indica un porcentaje para las rondas', 'warning');
        return;
    }

    const container = $("#container-rounds-config");

    if (container.is(":visible")) {
        container.hide();
        return;
    }

    container.show();

    if (rounds.val() > 1 && !$('#round-2').val()) {
        getRoundsConfig();
    }
});

$(document).on("input", ".rounds-pcts-input", function () {
    const elementID = $(this).attr('id');
    const elements = ['.rounds-pcts-input'];

    const inputValue = parseFloat(roundsPct.val()) || 0;
    const maxInput = parseFloat(roundsPct.attr('max')) || 20;

    const currentTotal = totalPct - inputValue;
    const availablePct = Math.max(0, 100 - currentTotal);
    const pctLimit = Math.min(availablePct, maxInput);

    validateInput(this, true, () => {
        const rTotal = handlePercentageAdjust(true, elementID, elements, pctLimit);
        roundsPct.val(rTotal).trigger('change');
    });
});

window.getRoundsConfig = getRoundsConfig;
