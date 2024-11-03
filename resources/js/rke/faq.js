// Función para manejar los clics de las preguntas y guías
function toggleContent(triggerSelector, targetSelector, content) {
    $(triggerSelector).click(function (e) {
        e.preventDefault();
        const isVisible = $(this).data('visible') || false;

        // Alterna entre mostrar u ocultar el contenido
        if (isVisible) {
            $(targetSelector).html("");
        } else {
            $(targetSelector).html(content);
        }

        // Actualiza el estado de visibilidad
        $(this).data('visible', !isVisible);
    });
}

// Definición de cada pregunta y guía con sus respectivos contenidos
toggleContent("#p1", "#r1", "Es una plataforma de videojuegos, donde puedes ganar dinero digital que recibes en criptomonedas Tether USDT, así como en tu moneda local (si existe disponibilidad).");
toggleContent("#p2", "#r2", "Es un juego de PlayRKE que consiste en una serie de 10 questions; donde cada una presenta cuatro posibles respuestas de selección simple (A·B·C·D). Sin embargo, solo una es la respuesta correcta.");
toggleContent("#p3", "#r3", "No necesariamente, ya que se habilitan demos o salas de juego gratis. No obstante, estas salas no le generan ganancias económicas a los usuarios.");
toggleContent("#p4", "#r4", "Debes unirte a salas de juego; en las que se requiera pagar un costo de inscripción. En caso de no poseer suficiente saldo, tendrás que depositar/recargar a tu cuenta PlayRKE.");
toggleContent("#p5", "#r5", "Sí, cualquier persona mayor de 18 años que puede participar, esto independientemente del país donde use nuestros servicios.");
toggleContent("#p6", "#r6", "El tiempo de apertura de nuevas salas de juego varía según la demanda. Las salas de juego están relacionadas con el aumento del índice de jugadores, es decir, mientras mayor sea la cantidad de usuarios, más salas serán creadas.");
toggleContent("#p7", "#r7", "La ganancia mínima es de aproximadamente un 750%, esto en base al costo de inscripción en una sala de juego. Además, cada juego dura menos de 10 minutos, es decir, puedes multiplicar tu dinero en poco tiempo.");
toggleContent("#p8", "#r8", "→ Transferencia Inmediata<br>→ Pago Móvil.<br>→ Red Blockchain y Binance Pay (USDT).");
toggleContent("#p8c", "#r8c", "→ Red Blockchain y Binance Pay (USDT).");
toggleContent("#p9", "#r9", "En moneda local (Bs), el mínimo de depósito es $0.70, recibidos y procesados a su valor en Bolívares, al tipo de cambio vigente. En criptomonedas, el mínimo de depósito es 2 USDT.");
toggleContent("#p9c", "#r9c", "En criptomonedas, el monto mínimo de depósito es 2&nbsp;USDT.");
toggleContent("#p10", "#r10", "Todas las solicitudes de depósito se procesan en un lapso no mayor a 45 minutos.");
toggleContent("#p11", "#r11", "El monto mínimo de retiro es $10 al valor de la moneda a retirar. Además, todos los retiros tienen un costo de comisión del 1.8% sobre el monto.");
toggleContent("#p12", "#r12", "Todo proceso de envío (retiro) de dinero, se efectuará en un lapso no mayor a 24 horas.");
toggleContent("#p13", "#r13", "<p><b>8.4.</b> Si el monto de depósito enviado por el usuario no coincide con el monto recibido en las cuentas bancarias asociadas a PlayRKE; el saldo será procesado como saldo congelado y cargado a su cuenta en base al valor del monto recibido.</p><p><b>8.5.</b> Al realizar un depósito/recarga inferior al monto mínimo estipulado; esta transacción será procesada y cargada su billetera como saldo congelado. </p><p><b>8.6.</b> El saldo congelado, solo podrá liberarse o estar disponible una vez se realice otra recarga igual o superior al monto mínimo estipulado. Las transferencias entre usuarios no cuentan como una recarga o depósito.</p>");

// Guias
toggleContent("#g1", "#f1", "<ol><li>Selecciona <b>Wallet</b>.</li><li>Selecciona la opción <b>Depósito</b>, luego escoge el método de depósito de tu preferencia.</li><li><b>Usa la información</b> expuesta en el formulario para el depósito, luego entra a tu wallet digital o la app de tu banco para realizar el pago.</li><li><b>Una vez realizado el pago</b>, rellena los campos del formulario con la información solicitada, es decir, los datos de tu depósito.</li><li><b>Revisa la información del formulario</b> y selecciona el botón Enviar.</li><li><b>Esperar</b> que la información enviada sea verificada, si su depósito es válido será aprobado.</li></ol>");
toggleContent("#g2", "#f2", "<ol><li>Dirígete a la sección de <b>Juegos</b></li><li>Selecciona el <b>tipo de moneda</b>.</li><li>Una vez en las salas de juego, <b>únete</b> a la opción de tu preferencia. Dependiendo el caso, es necesario tener saldo disponible para jugar/participar.</li></ol>");
toggleContent("#g3", "#f3", "<ol><li>Selecciona <b>Wallet</b>.</li><li>Selecciona la opción <b>Retirar</b>, luego escoge el método de retiro de tu preferencia.<br>Si aún no has cargado tu información de retiro, será obligatorio hacerlo para retirar.</li><li><b>Ingresa el monto a retirar</b> y selecciona el botón Procesar.</li></ol>");
toggleContent("#g4", "#f4", "Esta información será cargada próximamente.");