function validateInput(input, min, max) {
    setTimeout(() => {
        let value = parseFloat($(input).val());

        if (value < min || value > max) {
            showToast(`El valor debe estar entre ${min} y ${max}`, 'info');
            value = min; // Restablecer al valor mínimo permitido
        }

        $(input).val(value.toFixed(2));
    }, 1500);
}

// Validar Bote Valor Fijo (Debe estar entre 50 y 10000 con pasos de 0.05)
$(document).on("input", "#fixed-jackpot-value", function () {
    validateInput(this, 50, 10000);
});

// Validar Precio Base Carton USD (Debe estar entre 0.35 y 50 con pasos de 0.05)
$(document).on("input", "#ctn-price", function () {
    validateInput(this, 0.35, 50);
});

// Validar %Bote Seriales (Debe estar entre 2 y 15 con pasos de 0.05)
$(document).on("input", "#val-serials", function () {
    validateInput(this, 2, 15);
});

// Validar #Max. Seriales (Debe estar entre 5 y 15)
$(document).on("input", "#num-serials", function () {
    validateInput(this, 5, 15);
});

// Validar %Comercio (Debe estar entre 10 y 50 con pasos de 0.05)
$(document).on("input", "#room-commission", function () {
    validateInput(this, 10, 50);
});    