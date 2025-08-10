/**
 * Cambia la información de métodos de pago en el DOM según el código de país o tipo.
 * 
 * @param code - Código del método o país (ej: 'USDT', 'VE', 'BR', 'CO')
 * @param data - Objeto opcional con datos necesarios para mostrar información detallada.
 *               Se espera que data.paymentMethod sea un array de strings.
 */
export function switchPaymentMethod(
    code: string,
    data: { paymentMethod?: string[] } = {}
): void {
    // Referencias a los elementos del DOM donde se mostrarán los datos
    const paymentMethod1 = document.getElementById('payment-method-1');
    const paymentMethod2 = document.getElementById('payment-method-2');
    const paymentMethod3 = document.getElementById('payment-method-3');
    const paymentSubtitle = document.getElementById('payment-subtitle');

    if (!paymentMethod1) return; // Si no existe el elemento principal, no continuar

    switch (code) {
        case 'USDT':
            // Método de pago Crypto Wallet, solo texto simple
            paymentMethod1.textContent = 'Wallet Crypto';
            // Limpiar otros campos si existen
            if (paymentSubtitle) paymentSubtitle.textContent = '';
            if (paymentMethod2) paymentMethod2.textContent = '';
            if (paymentMethod3) paymentMethod3.textContent = '';
            break;

        case 'VE':
            // Pago móvil con varios datos
            if (paymentSubtitle) paymentSubtitle.textContent = 'Pago Móvil:';
            if (data.paymentMethod && data.paymentMethod.length >= 3) {
                paymentMethod1.textContent = `Banco: ${data.paymentMethod[0]}`;
                paymentMethod2 && (paymentMethod2.textContent = `Documento: ${data.paymentMethod[1]}`);
                paymentMethod3 && (paymentMethod3.textContent = `Celular: ${data.paymentMethod[2]}`);
            }
            break;

        case 'BR':
            // Pix - puede usar innerHTML si quieres etiquetas html
            paymentMethod1.innerHTML = `<b>Pix: </b>${data.paymentMethod?.[0] ?? ''}`;
            if (paymentSubtitle) paymentSubtitle.textContent = '';
            if (paymentMethod2) paymentMethod2.textContent = '';
            if (paymentMethod3) paymentMethod3.textContent = '';
            break;

        case 'CO':
            // Caso pendiente o vacío - no hace nada por ahora
            break;

        default:
            // Por defecto no hace nada ni cambia el DOM
            break;
    }
}
