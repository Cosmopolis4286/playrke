$(document).on("click", "#btn-payment-confirm", function () {
    if ($("#payment-proof").val()) {
        $("#form-ctn-payment").submit();
    } else {
        showToast('warning', 'Cargue el Comprobante');
    }
});