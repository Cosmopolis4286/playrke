@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.game.bingo.report.winners-ctns-tabs', [
                    'isCommerce' => false,
                    'countWinnersByCommerce' => $countWinnersByCommerce,
                    'countWinnersOtherCommerces' => $countWinnersOtherCommerces,
                ])
                @include('includes.date-filter-buttons', [
                    'routeWithCoin' => 'commerce.getBingoWinnersByOtherCommerces',
                ])
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        @include('includes.game.bingo.report.other-winners-report-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-payment-info', [
        'name' => $name,
        'title' => 'Notificar Pago',
        'formId' => 'ctn-payment',
        'formRoute' => route('commerce.notifyPaymentPrizeWinningCtn'),
    ])
@endsection
@section('scripts')
    @vite('resources/js/rke/payment-confirm-button.js')
    <script>
        function getPaymentCommerceDetails(commerce, coin) {
            $.getJSON(`{{ asset('') }}commerce/getPaymentCommerceDetails/${commerce}/${coin}`, {
                    '_token': $("#5K3_70K").val()
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
@endsection
