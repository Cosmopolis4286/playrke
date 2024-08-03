@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
                @include('components.winners-ctns-tabs', [
                    'isCommerce' => false,
                    'countWinnersByCommerce' => $countWinnersByCommerce,
                    'countWinnersOtherCommerces' => $countWinnersOtherCommerces,
                ])
                @include('components.date-filter-buttons', [
                    'routeName' => 'commerce.getBingoOffWinnersByOtherCommerces',
                    'dateFilter' => $dateFilter,
                ])
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        @include('components.other-winners-report-table', [
                            'winnersData' => $winnersData,
                            'commerce' => $commerce,
                            'wallet' => $wallet,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
    @component('components.modal-payment-info', [
        'name' => $name,
        'title' => 'Notificar Pago',
        'formId' => 'ctn-payment',
        'formRoute' => route('commerce.notifyPaymentPrizeWinningCtn'),
    ])
    @endcomponent
    <input disabled type="hidden" id="rout-rke" value="{{ $routCtns }}">
@endsection
@section('scripts')
    <script>
        function getPaymentCommerceDetails(commerce, coin) {
            $.getJSON(`{{ asset('') }}commerce/getPaymentCommerceDetails/${commerce}/${coin}`, {
                    '_token': $("#token").val()
                })
                .done(data => {
                    if (!allNotifyCtn) {
                        $("#sent-amount").text(data.coin + ' ' + montSend);
                    } else {
                        $('#allNotify').show();
                        $('#sent-amount').text(coinName + ' ' + allPendingCtnSale);
                        $('#allNotifyCtns').val(1);
                    }
                    $("#nameCommerce").text(nameCommerce);
                    $("#sectPaymentMethod1").text(data.paymentMethod.chave_pix.value + ': ' +
                        data.paymentMethod.val_chave_pix);
                    $('#paymentModal').modal('show');
                })
                .fail((jqXHR, textStatus, errorThrown) => {});
        }

        function getMoneyToReceiveByCommerce(ctnId) {
            $.ajax({
                url: `{{ route('commerce.getInfoByCtnId', ['ctnId' => ':ctnId']) }}`.replace(':ctnId', ctnId),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#text-payment-details').text('Informacion de contacto:');
                    $('#sectPaymentMethod1').text(data.phone);
                    $('#paymentModal').modal('show');
                    console.log(data);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error en la petición:', textStatus, errorThrown);
                }
            });
        }

        function getInfoByCtnId(ctnId) {
            $.ajax({
                url: `{{ route('commerce.getInfoByCtnId', ['ctnId' => ':ctnId']) }}`.replace(':ctnId', ctnId),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#payment-method-1').text('Informacion de contacto:');
                    $('#payment-method-2').text('Vendedor:' + ' ' + data.name);
                    $('#payment-method-3').text('Telefono:' + ' ' + data.phone);

                    $(`#modal-${name}`).modal('show');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error en la petición:', textStatus, errorThrown);
                }
            });
        }

        $(document).on("click", ".notify-ctn-payment", function() {
            const data = $(this).data();
            const countCommerces = data.count_commerce;
            const ctnId = data.ctn;
            const texSendReceive = (data.payment_type == 1 && data.countCommerces > 1) ? 'Recibiste ' : 'Enviaste ';

            $('#id-ctn').val(ctnId);
            if (data.payment_type == 1) {
                const prizeAmount = data.prize_amount;

                $('#send-receive').text(texSendReceive);
                $('#sent-amount').text(data.coin_sym + ' ' + prizeAmount);
                $('#amount-sent').val(prizeAmount);

                if (countCommerces == 1) {
                    getInfoByCtnId(ctnId);
                } else {
                    getMoneyToReceiveByCommerce(ctnId); // obtener dinero a recibir de otras Comercializadora 
                }

            } else {
                $('#send-receive').text(texSendReceive);
                getPaymentCommerceDetails(data.commerce, data.id_coin);
            };
        });
    </script>
    <script src="{{ asset('js/components/view-player-ctns.js') }}"></script>
    <script src="{{ asset('js/components/payment-confirm-button.js') }}"></script>
@endsection
