function getNumericValue($el) {
    return parseFloat($el.val()) || 0;
}

function setSafeValue($el, value) {
    const max = parseFloat($el.attr('max')) || 100;
    $el.val(Math.min(value, max, 100).toFixed(2));
}

function handlePercentageAdjust(changedId) {
    const $elements = {
        'room-commn': $('#room-commn'),
        'pct-serials': $('#pct-serials'),
        'pct-bingo-plays': $('#pct-bingo-plays'),
    };

    const values = {
        'room-commn': getNumericValue($elements['room-commn']),
        'pct-serials': getNumericValue($elements['pct-serials']),
        'pct-bingo-plays': getNumericValue($elements['pct-bingo-plays']),
    };

    const total = values['room-commn'] + values['pct-serials'] + values['pct-bingo-plays'];

    if (total > 100) {
        const over = total - 100;
        const $changed = $elements[changedId];
        const newValue = getNumericValue($changed) - over;
        setSafeValue($changed, newValue);
    }
}

$(document).on("input", "#amount-added-jackpot, #ctn-price, #time-chip, #num-serials, #max-plays-ramdom", function () {
    const id = $(this).attr('id');
    validateInput(this, !['num-serials', 'time-chip', 'max-plays-ramdom'].includes(id));
});

$(document).on("input", ".prize_pool-pct", function () {
    validateInput(this, true, () => handlePercentageAdjust($(this).attr('id')));
});