
let symbolSale = coinSym;
let coinSale = player ? $('#coin').val() : '';
let ctnPriceSale = ctnPrice;
let numCtns = $("#num-ctns").val() ?? 1;
let available = true;

window.onfocus = function () {
    const url = route('bingo.getInfo', { id: encrypted, starTime: true });
    $.getJSON(url)
        .done((response) => {
            timeServ = response;
        })
        .fail((jqXHR, textStatus, errorThrown) => {
            console.error(`Request failed with status: ${textStatus}, Error: ${errorThrown}`);
        });
};

function toggleModalInfo() {
    $("#card-form-buy-ctns").toggleClass('hide-elem');
    $("#btn-buy-ctns, #game-clock").toggle();
}

function toggleSpinnerBtn() {
    $("#spinner-btn-buy-ctns-confirm").toggleClass('hide-elem');
}

function setAmount() {
    const formattedAmount = (amount).toFixed(2);

    if (creatorType === 'commerce') $("#sent-amount").text(`${symbolSale} ${formattedAmount}`);

    $("#total-amount").text(`${symbolSale} ${formattedAmount}`);
}

function getCommerceDetails(coinSale) {
    if (!available) return;
    const url = route('commerce.getPaymentCommerceDetails', { commerce: creatorID, coin: coinSale });
    $.getJSON(url)
        .done((response) => {
            const { commerce, paymentDetails, exchangeRate } = response;

            if (paymentDetails) {
                if (!player) ctnPriceSale = ctnPrice * exchangeRate;
                symbolSale = paymentDetails.coin;
                amount = ctnPriceSale * numCtns;

                $("#ctn-price-coin-symbol").text(symbolSale);
                $("#ctn-price-amount").text((amount).toFixed(2));

                setAmount();
                $("#commerce-name").text(commerce);
                switchPaymentMethod(paymentDetails.code, paymentDetails);
                $("#commerce-details").removeClass('hide-elem');
            } else {
                console.error('Error: Datos de pago no disponibles');
            }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
            console.error(`Error: ${textStatus}`, errorThrown);
            // Manejar el error adecuadamente
        });
}

function handleAmount() {
    $("#ctn-price-coin-symbol").text(symbolSale);
    $("#ctn-price-amount").text(ctnPrice);
}

$(document).on("change", "#coin", function () {
    coinSale = $(this).val();
    if (coinSale) getCommerceDetails(coinSale);
});

$(document).on("change", "#num-ctns", function () {
    numCtns = $(this).val();
    amount = ctnPriceSale * numCtns;
    setAmount();
});

$(document).on("click", "#btn-buy-ctns", function () {
    handleAmount();
    setAmount();
    toggleModalInfo();
});

$(document).on("click", "#btn-cancel-buy-ctns", function () {
    toggleModalInfo();
});

$(document).on("click", "#btn-buy-ctns-confirm", function () {
    if (creatorType === 'commerce') {

        if (!coinSale) {
            $('#coin').focus();
            return showToast('Selecione una moneda', 'warning');
        }

        if (!$("#payment-proof").val()) {
            $('#payment-proof').focus();
            return showToast('Cargue el Comprobante', 'warning');
        }

        if (!$("#purchase-code").val()) {
            $("#purchase-code").focus();
            return showToast('Ingrese el Código de Compra', 'warning');
        }
    }

    $.ajax({
        url: route('bingo.generateCarton'),
        headers: {
            'X-CSRF-TOKEN': $("#5K3_70K").val()
        },
        type: 'post',
        dataType: 'json',
        data: {
            room: encrypted,
            creator_type: creatorType,
            creator_id: creatorID,
            player: player,
            coin: coinSale,
            num_ctns: numCtns,
            purchase_code: $('#purchase-code').val() || ''
        },
        beforeSend: function () {
            toggleSpinnerBtn();
        },
        success: function (data) {

            showToast(data.message, data.type);

            if (data.prize_pool) {
                $("#prize-pool-amount").text(data.prize_pool);
                setTimeout(function () {
                    location.reload();
                }, 3000);
            }

            toggleSpinnerBtn();
            toggleModalInfo();
        },
        error: function () { }
    });
});

function adjustHeightBoxes() {
    let widthNumberBox = $('.number-box').first().width();
    $('.number-box').height(widthNumberBox);
}

let waitingTime = false;
let timeServ = Math.floor(Date.now() / 1000); // Asegura un valor numérico válido

function countdownt(gameStartTime) {
    if (isNaN(timeServ)) {
        console.error("Error: timeServ es NaN. Reiniciando...");
        timeServ = Math.floor(Date.now() / 1000);
    }
    if (isNaN(gameStartTime)) {
        console.error("Error: gameStartTime es NaN. No se puede continuar.");
        return;
    }

    // Incrementa el tiempo del servidor
    timeServ++;

    // Calcula la diferencia entre la fecha de destino y la fecha actual en segundos
    const distance = Math.floor(gameStartTime - timeServ);

    // Verifica si distance es NaN antes de continuar
    if (isNaN(distance)) {
        console.error("Error: distance es NaN. Deteniendo cuenta regresiva.");
        return;
    }

    // Calcula los días, horas, minutos y segundos restantes
    const days = Math.floor(distance / (60 * 60 * 24));
    const hours = Math.floor((distance % (60 * 60 * 24)) / (60 * 60));
    const minutes = Math.floor((distance % (60 * 60)) / 60);
    const seconds = Math.floor(distance % 60);

    // Muestra los resultados en los elementos correspondientes
    $('#days').text(days);
    $('#hours').text(hours);
    $('#minutes').text(minutes);
    $('#seconds').text(seconds);

    // Si la cuenta regresiva ha terminado, muestra un mensaje
    if (distance <= 0) {
        available = false;
        $('#btn-buy-ctns').prop('disabled', true);
        $('#game-clock').html("<small>¡La cuenta regresiva ha terminado!</small>");
        if (waitingTime) {
            clearInterval(waitingTime);
            setTimeout(() => location.reload(), 2500);
        }
    } else {
        if (days === 0) $('#cont-D').hide();
        $('#cont-H').toggle(hours !== 0);
        $('#cont-M').toggle(minutes !== 0);
    }
}

$(function () {

    if (roomStatus != 2) return;

    if (player && coinSale) {
        getCommerceDetails(coinSale);
    }

    adjustHeightBoxes();
    $('.container-carton').css('visibility', 'visible');
    $('#modal-info-draw-title').text(convertAndDisplay(gameStartTime));
    $("#modal-info-draw").modal('show');

    // Llama a la función cada vez que se redimensiona la ventana
    window.addEventListener("resize", () => {
        adjustHeightBoxes();
        if (roomStatus > 2 && $('#cont-ctn-award-winning').data('status')) {
            adjustHeightBoxesCtnAward();
        }
    });

    if (!isNaN(gameStartTime)) {
        waitingTime = setInterval(() => countdownt(gameStartTime), 1000);
    } else {
        console.error("Error: gameStartTime es inválido.");
    }
});

window.toggleModalInfo = toggleModalInfo;
window.toggleSpinnerBtn = toggleSpinnerBtn;