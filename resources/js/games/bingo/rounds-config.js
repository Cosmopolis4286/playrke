const rounds = $("#rounds");
const roundsPct = $('#rounds-pct');

function handleRoundsConfig(arrRounds = []) {
    const content = arrRounds.length
        ? generateRounds(arrRounds)
        : generateEmptyRounds();

    if (content) {
        $('#content-rounds-config').empty().html(content);
    }
}

function generateRounds(data) {
    const numRounds = parseInt(data.rounds) || 0;
    const pctValue = parseFloat(roundsPct.val()) || 0;

    return Array.from({ length: numRounds }, (_, i) => {
        const isLastRound = i === numRounds - 1;
        const prizeValue = isLastRound
            ? (100 - pctValue).toFixed(2)
            : data.prize_values[i];

        return buildRoundHtml({
            roundNumber: i + 1,
            prizeType: data.prize_types[i],
            playType: data.play_types[i],
            prizeValue,
            isLastRound
        });
    }).join('');
}

function generateEmptyRounds() {
    const numRounds = parseInt(rounds.val()) || 0;
    if (numRounds === 0) return '';

    const pctValue = parseFloat(roundsPct.val()) || 0;
    const isMultiple = numRounds > 1;

    const valuePerRound = isMultiple
        ? (pctValue / (numRounds - 1)).toFixed(2)
        : pctValue.toFixed(2);

    const totalAssigned = isMultiple
        ? (valuePerRound * (numRounds - 1)).toFixed(2)
        : valuePerRound;

    roundsPct.val(totalAssigned).trigger('input');

    return Array.from({ length: numRounds }, (_, i) => {
        const isLastRound = i === numRounds - 1;
        const prizeValue = isLastRound
            ? (100 - totalAssigned).toFixed(2)
            : valuePerRound;

        return buildRoundHtml({
            roundNumber: i + 1,
            prizeType: 'PCT',
            playType: 1, // patrón por defecto
            prizeValue,
            isLastRound
        });
    }).join('');
}

function buildRoundHtml({ roundNumber, prizeType, playType, prizeValue, isLastRound }) {
    const readonlyAttr = isLastRound ? 'readonly' : '';
    const inputClass = !isLastRound ? 'rounds-prize-value-input' : '';

    const selectBingoPatterns = `
        <select class="form-control" name="rounds_play_type[]" aria-label="Seleccionar cantos">
            ${arrBingoPatterns.map(item => `
                <option value="${item.id}" ${item.id == playType ? 'selected' : ''}>${item.name}</option>
            `).join('')}
        </select>
    `;

    return `
        <div class="form-group col-sm-6">
            <label for="round-${roundNumber}">Ronda #${roundNumber}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <select class="form-control" name="rounds_prize_type[]" required>
                        <option value="PCT" ${prizeType === 'PCT' ? 'selected' : ''}>%</option>
                        <option value="USD" ${prizeType === 'USD' ? 'selected' : ''}>$</option>
                        <option value="OTHER" ${prizeType === 'OTHER' ? 'selected' : ''}>?</option>
                    </select>
                </div>
                <input 
                    type="text"
                    class="form-control ${inputClass}"
                    name="rounds_prize_value[]"
                    id="round-${roundNumber}"
                    aria-label="Ronda #${roundNumber}"
                    value="${prizeValue}"
                    placeholder="%"
                    ${readonlyAttr}
                    required>
                <div class="input-group-append">
                    ${selectBingoPatterns}
                </div>
            </div>
        </div>
    `;
}

function getRoundsConfig(room = '') {
    const roundsValue = parseInt(rounds.val()) || 0;

    if (!room) {
        if ((roundsValue === 1 && $('#round-2').val()) || !roundsPct.val()) return;
        $('#spinner-rounds-config').hide();
        handleRoundsConfig();
        return;
    }

    $.ajax({
        url: route('bingo.getRoundsConfig', { room, type: creation }),
        method: 'GET',
        dataType: 'json',
        beforeSend() {
            $('#spinner-rounds-config').show();
            $('#content-rounds-config').empty();
            $("#container-rounds-config").show();
        },
        success(res) {
            if (res.rounds > 1) handleRoundsConfig(res);
        },
        complete() {
            $('#spinner-rounds-config').hide();
        }
    });
}

// Listeners
rounds.on('change', function () {
    const value = parseInt($(this).val()) || 0;

    if (value > 1) {
        $('.content-rounds-pct').show();
        roundsPct.prop('required', true);
        getRoundsConfig();
    } else {
        $('#content-rounds-config').empty();
        $('.content-rounds-pct, #container-rounds-config').hide();
        roundsPct.prop('required', false).val('');
    }
});

roundsPct.on('change', getRoundsConfig);

$(document).on("click", "#btn-rounds-config", function () {
    const pctValue = parseFloat(roundsPct.val());

    if (!pctValue || pctValue <= 0) {
        roundsPct.trigger('focus');
        showToast('Indica un porcentaje para las rondas', 'warning');
        return;
    }

    const container = $("#container-rounds-config");
    container.toggle();

    if (container.is(":visible")) {
        getRoundsConfig();
    }
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
