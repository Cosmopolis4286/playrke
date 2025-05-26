function getNumericValue($el) {
    return parseFloat($el.val()) || 0;
}

function setSafeValue($el, value) {
    const max = parseFloat($el.attr('max')) || 100;
    $el.val(Math.min(value, max, 100).toFixed(2));
}

function handlePercentageAdjust(changedSelector, selectors = [], maxValue = 100) {
    const $elements = {};
    const values = {};

    // Mapear todos los elementos
    selectors.forEach(selector => {
        const isClass = selector.startsWith('.');
        const $el = isClass ? $('.' + selector.slice(1)) : $('#' + selector);
        $elements[selector] = $el;

        // Si es clase, sumar todos los elementos con esa clase
        const totalValue = isClass 
            ? $el.toArray().reduce((sum, el) => sum + getNumericValue($(el)), 0)
            : getNumericValue($el);

        values[selector] = totalValue;
    });

    const total = Object.values(values).reduce((acc, val) => acc + val, 0);

    if (total > maxValue) {
        const over = total - maxValue;

        const isChangedClass = changedSelector.startsWith('.');
        const $changed = isChangedClass 
            ? $('.' + changedSelector.slice(1)) 
            : $('#' + changedSelector);

        const currentValue = isChangedClass
            ? $changed.toArray().reduce((sum, el) => sum + getNumericValue($(el)), 0)
            : getNumericValue($changed);

        const newValue = currentValue - over;

        // Si es clase, ajustar proporcionalmente entre todos
        if (isChangedClass) {
            const count = $changed.length;
            const distribute = newValue / count;

            $changed.each((_, el) => {
                setSafeValue($(el), distribute);
            });
        } else {
            setSafeValue($changed, newValue);
        }
    }
}

// Limitar a solo números enteros (sin puntos, comas ni letras)
$(document).on('input', '#num-serials, #time-chip, #rounds', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Limitar a números decimales válidos (sin letras)
$(document).on('input', '#amount-added-prize-pool, #room-commn, #serials-pct, #rounds-pct, .rounds-pcts-input, #ctn-price', function () {
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

$(document).on("input", ".prize-pool-pct", function () {
    const elements = ['room-commn', 'serials-pct', 'rounds-pct'];
    validateInput(this, true, () => handlePercentageAdjust($(this).attr('id'), elements));
});

window.handlePercentageAdjust = handlePercentageAdjust; 