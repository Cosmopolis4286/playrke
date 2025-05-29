function handleRoundsConfig(arrRounds = []) {
    const pctValue = parseFloat(roundsPct.val()) || 0;
    const content = arrRounds.length
        ? generateRounds(arrRounds)
        : generateEmptyRounds(pctValue);

    $('#content-rounds-config').empty().html(content);
    bingoRounds = true;
}

function generateRounds(arr) {
    const lastRound = arr.length;
    const roundsPctVal = parseFloat(roundsPct.val()) || 0;

    return arr.map(({ round, pct }) => {
        const adjustedPct = (round === lastRound)
            ? (100 - roundsPctVal)
            : pct;

        return buildRoundHtml(round, adjustedPct);
    }).join('');
}

function generateEmptyRounds(pctValue) {
    const numRounds = parseInt($('#rounds').val()) || 0;

    if (numRounds === 0) return '';

    const valuePerRound = (numRounds > 1)
        ? (pctValue / (numRounds - 1)).toFixed(2)
        : pctValue.toFixed(2);

    const totalAssigned = (valuePerRound * (numRounds - 1)).toFixed(2);
    roundsPct.val(totalAssigned).trigger('input');

    return Array.from({ length: numRounds }, (_, i) => {
        const isLastRound = i === numRounds - 1;
        const roundPCT = isLastRound
            ? (100 - totalAssigned).toFixed(2)
            : valuePerRound;

        return buildRoundHtml(i + 1, roundPCT, isLastRound);
    }).join('');
}

function buildRoundHtml(roundNumber, roundPCT, isLastRound = false, selected = '') {
    // const optionsHtml = arrBingoPlays.map(
    //     ({ id, name }) => `
    //     <option value="${id}" ${id == selected ? 'selected' : ''}>
    //         ${name}
    //     </option>`
    // ).join('');

    const readonlyAttr = isLastRound ? 'readonly' : '';
    const hasClass = !isLastRound ? 'rounds-prize-value-input' : '';

    // const bingoSongs =
    //     `<div class="input-group-append">
    //         <select 
    //         class="form-control" 
    //         name="rounds_types[]" 
    //         aria-label="Seleccionar cantos"
    //         disabled>
    //             ${optionsHtml}
    //         </select>
    //     </div>`;

    return `
        <div class="form-group col-sm-6 col-md-4 mt-1">
            <label for="round-${roundNumber}">Ronda #${roundNumber}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <select 
                    class="form-control" 
                    name="rounds_types[]" 
                    aria-label="Seleccionar cantos">
                        <option value="PCT" selected>%</option>
                        <option value="USD">$</option>
                        <option value="OTHER">?</option>
                    </select>
                </div>
                <input 
                    type="text" 
                    class="form-control ${hasClass}" 
                    name="rounds_prize_value[]" 
                    id="round-${roundNumber}" 
                    aria-label="Ronda #${roundNumber}" 
                    value="${roundPCT}"
                    placeholder="%"
                    ${readonlyAttr}
                    required>              
            </div>
        </div>
    `;
}

function getRoundsConfig(room = '') {

    if (rounds.val() == 1 && $('#round-2').val() || !roundsPct.val()) {
        return;
    }

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
            $('#content-rounds-config').empty();
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
        $('#content-rounds-config').empty();
        $('.content-rounds-pct, #container-rounds-config').hide();
        roundsPct.prop('required', false).val('');
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

    getRoundsConfig();
});

$(document).on("input", ".rounds-prize-value-input", function () {
    const elementID = $(this).attr('id');
    const elements = ['.rounds-prize-value-input'];

    const inputValue = parseFloat(roundsPct.val()) || 0;
    const maxInput = parseFloat(roundsPct.attr('max')) || 20;

    const currentTotal = totalPct - inputValue;
    const availablePct = Math.max(0, 100 - currentTotal);
    const pctLimit = Math.min(availablePct, maxInput);

    validateInput(this, true, () => {
        const rTotal = handlePercentageAdjust(true, elementID, elements, pctLimit);
        roundsPct.val(rTotal);
    });
});

window.getRoundsConfig = getRoundsConfig;
