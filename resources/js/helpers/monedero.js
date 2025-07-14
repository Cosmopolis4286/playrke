function msg_webpay() {
    Toast.fire({
        icon: 'success',
        html: 'Redireccionando a WebPay'
    });
}

$("#saldosCongelado").hover(
    function () {
        $("#sC-usd").css("display", "block");
        $("#sC-bs").css("display", "none");
    },
    function () {
        $("#sC-usd").css("display", "none");
        $("#sC-bs").css("display", "block");
    }
);

$(".estatus_id").change(function (e) {
    $("#estatus1").val($(".estatus_id").val());
    $('#estatus').submit();
});

$(".inf_transk").click(function () {
    $("#orden_compra").html($(this).data('orden_compra'));
    $("#fecha_transk").html($(this).data('fecha_transk') +
        '&nbsp&nbsp' + $(this).data('hora_transk') + '&nbspUTC');
    $("#payment_type").html($(this).data('payment_type'));
    $("#amount_transk").html($(this).data('amount_transk'));
    if ($(this).data('num_cuotas') < 1) {
        cant_cuotas = 'Sin cuotas';
        val_cuotas = '---';
    } else {
        cant_cuotas = $(this).data('num_cuotas');
        val_cuotas = $(this).data('val_cuotas');
    }
    $("#num_cuotas").html(cant_cuotas);
    $("#val_cuotas").html(val_cuotas);
    $("#cod_autorize").html($(this).data('cod_autorize'));
    $("#card_number").html($(this).data('card_number'));
});

$("#show_decline").click(function () {
    $("#info_mtv").html($(this).data('motive'));
});
$(".transk_rhz").click(function () {
    console.log($(this).data('orden_compra'));
    $("#order_buy").html($(this).data('orden_compra'));
});
$(".dp_cold").click(function () {
    $("#motive_transk").html($(this).data('motive'));
});

var van_bt_transk = 0;
$("#bt_transk").click(function (e) {
    if (van_bt_transk == 0) {
        msg_webpay();
        $.ajax({
            url: "{{ route('transbank.createCLP') }}",
            headers: {
                'X-CSRF-TOKEN': $("#5K3_70K").val()
            },
            type: 'GET',
            dataType: 'json',
            data: {
                id_user: "<?php echo auth()->user()->id ?>",
                amount: $('#amount').val()
            },
            beforeSend: function () {
                van_bt_transk == 1;
                $('#bt_transk').prop("disabled", true);
            },
            success: function (data) {
                van_bt_transk == 0;
                $('#bt_transk').prop("disabled", false);
                window.open(data['url']);
            },
        });
    }
});

function hideShowDiv(val) {
    var pais = "<?php $pais ?>";
    if (val == 1) {
        if (pais == 234) {
            document.getElementById('hist_ves').style.display = 'block';
        } else if (pais == 43) {
            document.getElementById('hist_clp').style.display = 'block';
        }
        document.getElementById('hist_bpay').style.display = 'none';
    }
    if (val == 2) {
        if (pais == 234) {
            document.getElementById('hist_ves').style.display = 'none';
        } else if (pais == 43) {
            document.getElementById('hist_clp').style.display = 'none';
        }
        document.getElementById('hist_bpay').style.display = 'block';
    }
}
