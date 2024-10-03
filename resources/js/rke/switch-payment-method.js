function switchPaymentMethod(code, data = '') {
    switch (code) {
        case 'USDT':
            $("#payment-method-1").text(`Wallet Crypto`);
            break;
        case 'VE':
            $("#payment-subtitle").text('Pago Móvil:');
            $("#payment-method-1").text(`Banco: ${data.paymentMethod[0]}`);
            $("#payment-method-2").text(`Documento: ${data.paymentMethod[1]}`);
            $("#payment-method-3").text(`Celular: ${data.paymentMethod[2]}`);
            break;
        case 'BR':
            $("#payment-method-1").html(`<b>Pix: </b>${data.paymentMethod[0]}`);
            break;
        case 'CO':
            break;
        default:
            break;
    }
}