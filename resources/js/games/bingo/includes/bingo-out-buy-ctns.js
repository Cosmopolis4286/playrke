
let symbolSale = coinSym;
let coinSale = '';
let ctnPriceSale = ctnPrice;
let numCtns = $("#num-ctns").val() ?? 1;

function toggleModalInfo() {
    $("#card-form-buy-ctns").toggleClass('hide-elem');
    $("#cont-btn-buy-ctns, #game-clock").toggle();
}

function toggleSpinnerBtn() {
    $("#spinner-btn-buy-ctns-confirm").toggleClass('hide-elem');
}

function setAmount() {
    const formattedAmount = (amount).toFixed(2);

    if (userType === 'commerce') $("#sent-amount").text(`${symbolSale} ${formattedAmount}`);

    $("#total-amount").text(`${symbolSale} ${formattedAmount}`);
}

function getCommerceDetails(coinSale) {
    const url = commerceDetailsURL.replace(':commerce', userTypeVal).replace(':coin', coinSale);
    $.getJSON(url, { _token: $("#5K3_70K").val() })
        .done((response) => {
            const { commerce, paymentDetails, exchangeRate } = response;

            if (paymentDetails) {
                symbolSale = paymentDetails.coin;
                ctnPriceSale = ctnPrice * exchangeRate;
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

$(document).on("click", "#cont-btn-buy-ctns", function () {
    handleAmount();
    setAmount();
    toggleModalInfo();
});

$(document).on("click", "#btn-cancel-buy-ctns", function () {
    toggleModalInfo();
});

$(document).on("click", "#btn-buy-ctns-confirm", function () {
    if (userType === 'commerce') {

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
        url: generateCartonURL,
        headers: {
            'X-CSRF-TOKEN': $("#5K3_70K").val()
        },
        type: 'post',
        dataType: 'json',
        data: {
            room: encrypted,
            user_type: userType,
            user_type_val: userTypeVal,
            player: player,
            coin: coinSale,
            num_ctns: numCtns,
            purchase_code: $('#purchase-code').val() || ''
        },
        beforeSend: function () {
            toggleSpinnerBtn();
        },
        success: function (data) {

            showToast(data.message, data.msg_type);

            if (data.jackpot) {
                $("#jackpot-amount").text(data.jackpot);
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

function countdownt(startTime) {
    // Establece la fecha y hora de destino
    const targetDate = startTime;

    // Calcula la diferencia entre la fecha de destino y la fecha actual en segundos
    const distance = Math.floor((targetDate - timeServ));

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
        $('#game-clock').html("<small>¡La cuenta regresiva ha terminado!</small>");
        if (waitingTime) {
            clearInterval(waitingTime);
            setTimeout(function () {
                location.reload();
            }, 2500);
        }
    } else {
        if (days == 0) $('#cont-D').hide();
        $('#cont-H').toggle(hours !== 0);
        $('#cont-M').toggle(minutes !== 0);
    }
}

$(document).ready(function () {

    if (roomStatus != 2) return;

    adjustHeightBoxes();
    $('.container-carton').css('visibility', 'visible');

    // Llama a la función cada vez que se redimensiona la ventana
    $(window).resize(function () {
        adjustHeightBoxes();
        if (roomStatus > 2 && $('#cont-ctn-award-winning').data('status') == true) {
            adjustHeightBoxesCtnAward();
        }
    });

    $('.container-carton').css('visibility', 'visible');
    $('#modal-info-draw-title').text(convertAndDisplay(startTime));
    waitingTime = setInterval(() => {
        countdownt(startTime);
        timeServ++;
    }, 1000);
    $("#modal-info-draw").modal('show');

});

window.toggleModalInfo = toggleModalInfo;
window.toggleSpinnerBtn = toggleSpinnerBtn;