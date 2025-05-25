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
        'serials-pct': $('#serials-pct'),
        'rounds-pct': $('#rounds-pct'),
    };

    const values = {
        'room-commn': getNumericValue($elements['room-commn']),
        'serials-pct': getNumericValue($elements['serials-pct']),
        'rounds-pct': getNumericValue($elements['rounds-pct']),
    };

    const total = values['room-commn'] + values['serials-pct'] + values['rounds-pct'];

    if (total > 100) {
        const over = total - 100;
        const $changed = $elements[changedId];
        const newValue = getNumericValue($changed) - over;
        setSafeValue($changed, newValue);
    }
}

$(document).on("input", "#amount-added-prize-pool, #ctn-price, #time-chip, #num-serials", function () {
    const id = $(this).attr('id');
    validateInput(this, !['num-serials', 'time-chip', 'rounds'].includes(id));
});

$(document).on("input", ".prize_pool-pct", function () {
    validateInput(this, true, () => handlePercentageAdjust($(this).attr('id')));
});