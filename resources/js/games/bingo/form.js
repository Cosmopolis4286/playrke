function getNumericValue($el) {
    return parseFloat($el.val()) || 0;
}

function setSafeValue($el, value) {
    const max = parseFloat($el.attr('max')) || 100;
    $el.val(Math.min(value, max, 100).toFixed(2));
}

function handlePercentageAdjust(rTotal = false, changedSelector, selectors = [], maxValue = 100) {
    const values = {};

    // Mapear y calcular los valores de cada selector
    selectors.forEach(selector => {
        const $el = selector.startsWith('.') ? $('.' + selector.slice(1)) : $('#' + selector);
        const totalValue = $el.toArray().reduce((sum, el) => sum + getNumericValue($(el)), 0);
        values[selector] = totalValue;
    });

    let total = Object.values(values).reduce((acc, val) => acc + val, 0);

    if (total > maxValue) {
        const over = total - maxValue;

        const $changed = changedSelector.startsWith('.')
            ? $('.' + changedSelector.slice(1))
            : $('#' + changedSelector);

        const currentValue = $changed.toArray().reduce((sum, el) => sum + getNumericValue($(el)), 0);
        const newValue = Math.max(0, currentValue - over);

        if (changedSelector.startsWith('.')) {
            const count = $changed.length;
            const distribute = newValue / count;

            $changed.each((_, el) => {
                setSafeValue($(el), distribute);
            });
        } else {
            setSafeValue($changed, newValue);
        }

        // Recalcular total con los valores actualizados
        total = selectors.reduce((sum, selector) => {
            const $el = selector.startsWith('.') ? $('.' + selector.slice(1)) : $('#' + selector);
            return sum + $el.toArray().reduce((acc, el) => acc + getNumericValue($(el)), 0);
        }, 0);
    }

    return rTotal ? total.toFixed(2) : undefined;
}

// Limitar a solo números enteros (sin puntos, comas ni letras)
$(document).on('input', '#num-serials, #time-chip, #rounds', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Limitar a números decimales válidos (sin letras)
$(document).on('input', '#amount-added-prize-pool, #room-commn, #serials-pct, #rounds-pct, .rounds-prize-value-input, .ctn-price-coins-input, #ctn-price', function () {
    this.value = this.value.replace(/,/g, '.'); // Opcional: convertir comas en puntos
    this.value = this.value
        .replace(/[^0-9.]/g, '')        // Solo números y punto
        .replace(/(\..*?)\..*/g, '$1'); // Solo un punto permitido
});

// Validación general para rango min-max
$(document).on("input", "#amount-added-prize-pool, #ctn-price, #time-chip, #rounds, #num-serials", function () {
    const id = $(this).attr('id');
    validateInput(this, !['num-serials', 'time-chip', 'rounds'].includes(id));
});

$(document).on("input change", ".prize-pool-pct", function () {
    let elements = ['room-commn', 'serials-pct'];

    if ($('#rounds').val() > 1) {
        elements.push('rounds-pct');
    }

    validateInput(this, true, (isValid) => {
        if (isValid) {
            const rTotal = handlePercentageAdjust(true, $(this).attr('id'), elements);
            totalPct = rTotal;
        }
    });
});

window.handlePercentageAdjust = handlePercentageAdjust; 