function toggleModalInfo() {
    $("#card-form-buy-ctns").toggleClass('hide-elem');
    $("#cont-btn-buy-ctns, #game-clock").toggle();
}

function toggleSpinnerBtn() {
    $("#spinner-btn-buy-ctns-confirm").toggleClass('hide-elem');
}

function setAmount(amount) {
    if (userType == 'commerce') $("#sent-amount").text(`${coinSym} ${amount}`);
    $("#total-amount").text(`${coinSym} ${amount}`);
}

$(document).on("click", "#cont-btn-buy-ctns", function () {
    if (!info) {
        $("#ctn-price-coin-symbol").text(coinSym);
        $("#ctn-price-amount").text(ctnPrice);

        if (userType == 'commerce') {
            $.getJSON(`{{ url('commerce/getPaymentCommerceDetails') }}/${userTypeVal}/${coinID}`, {
                '_token': "{{ csrf_token() }}"
            })
                .done(res => {
                    const data = res.data;

                    setAmount(amount);
                    $("#commerce-name").text(name);

                    if (true) {
                        switchPaymentMethod(data.code, data);
                        $("#commerce-details").removeClass('hide-elem');
                    }
                })
                .fail((jqXHR, textStatus, errorThrown) => {
                    console.error('Error:', textStatus, errorThrown);
                });
        } else {
            setAmount(amount);
        }
    }
    toggleModalInfo();
});

$(document).on("change", "#num-ctns", function () {
    amount = ctnPrice * $(this).val();
    setAmount(amount);
});

$(document).on("click", "#btn-cancel-buy-ctns", function () {
    toggleModalInfo();
});

$(document).on("click", "#btn-buy-ctns-confirm", function () {

    if (!$("#num-ctns").val()) {
        $("#num-ctns").val(1);
    }

    if (userType == 'commerce') {
        if (!$("#purchase-code").val()) {
            $("#purchase-code").focus();
            return showToast('Ingrese el Código de Compra', 'warning');
        }

        if (!$("#payment-proof").val()) {
            return showToast('Cargue el Comprobante', 'warning');
        }
    }

    $.ajax({
        url: "{{ route('bingocash.generateCarton') }}",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        type: 'post',
        dataType: 'json',
        data: {
            room: encrypted,
            user_type: userType,
            user_type_val: userTypeVal,
            player: player,
            coin: coinID,
            num_ctns: $("#num-ctns").val()
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
window.setAmount = setAmount;