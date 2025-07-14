$(document).on("click", "#btn-payment-confirm", function () {
    if ($("#payment-proof").val()) {
        $("#form-ctn-payment").submit();
    } else {
        showToast('Cargue el Comprobante', 'warning');
    }
});