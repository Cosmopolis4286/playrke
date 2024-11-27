// function getMoneyToReceiveByCommerce(ctnId) {
    //     $.ajax({
    //         url: `{{ route('commerce.getInfoByCtnId', ['ctnId' => ':ctnId']) }}`.replace(':ctnId', ctnId),
    //         method: 'GET',
    //         dataType: 'json',
    //         success: function(data) {
    //             $('#text-payment-details').text('Informacion de contacto:');
    //             $('#sectPaymentMethod1').text(data.phone);
    //             $('#paymentModal').modal('show');
    //             console.log(data);
    //         },
    //         error: function(jqXHR, textStatus, errorThrown) {
    //             console.error('Error en la petición:', textStatus, errorThrown);
    //         }
    //     });
    // }

    // function getInfoByCtnId(ctnId) {
    //     $.ajax({
    //         url: `{{ route('commerce.getSellerInfoByCtn', ['ctnId' => ':ctnId']) }}`.replace(':ctnId', ctnId),
    //         method: 'GET',
    //         dataType: 'json',
    //         success: function(data) {
    //             $('#payment-method-1').text('Informacion de contacto:');
    //             $('#payment-method-2').text('Vendedor:' + ' ' + data.name);
    //             $('#payment-method-3').text('Telefono:' + ' ' + data.phone);

    //             $(`#modal-${name}`).modal('show');
    //         },
    //         error: function(jqXHR, textStatus, errorThrown) {
    //             console.error('Error en la petición:', textStatus, errorThrown);
    //         }
    //     });
    // }
