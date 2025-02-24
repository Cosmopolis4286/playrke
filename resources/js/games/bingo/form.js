// Validar Bote Valor Fijo (Debe estar entre 30 y 10000 con pasos de 0.05)
$(document).on("input", "#fixed-jackpot-value", function () {
    validateInput(this, 30, 10000);
});

// Validar Precio Base Carton USD (Debe estar entre 0.35 y 50 con pasos de 0.05)
$(document).on("input", "#ctn-price", function () {
    validateInput(this, 0.35, 50);
});

// Validar Tiempo de salida de fichas (Debe estar entre 3 y 10 con pasos de 1)
$(document).on("input", "#time-chip", function () {
    validateInput(this, 3, 10, false);
});

// Validar %Bote Seriales (Debe estar entre 2 y 35 con pasos de 0.05)
$(document).on("input", "#val-serials", function () {
    validateInput(this, 2, 35);
});

// Validar #Max. Seriales (Debe estar entre 3 y 25)
$(document).on("input", "#num-serials", function () {
    validateInput(this, 3, 25);
});

// Validar %Comercio (Debe estar entre 5 y 50 con pasos de 0.05)
$(document).on("input", "#room-commission", function () {
    validateInput(this, 5, 50);
});    