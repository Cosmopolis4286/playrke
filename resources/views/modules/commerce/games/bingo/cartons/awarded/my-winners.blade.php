@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.games.bingo.report.winners-ctns-tabs', [
                    'isCommerce' => true,
                    'countWinnersByCommerce' => $countWinnersByCommerce,
                    'countWinnersOtherCommerces' => $countWinnersOtherCommerces,
                ])
                @include('includes.date-filter-buttons')
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        <button type="button" id="btn-pending-payments" class="btn btn-sm btn-dark mt-2">
                            Pagos Pendientes</button>
                        @include('includes.games.bingo.report.my-winners-report-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-payment-info', [
        'name' => $name,
        'title' => 'Notificar Pago',
        'formId' => 'ctn-payment',
        'formRoute' => route('bingo.notifyPaymentPrizeWinningCtn'),
    ])
    @include('includes.games.bingo.layout.modal-other-bingo-rooms-pending-payments')
@endsection
@section('scripts')
    @vite(['resources/js/rke/switch-payment-method.js', 'resources/js/rke/payment-confirm-button.js', 'resources/js/games/bingo/includes/prize-ctns-pending-payments.js'])
    <script>
        const name = '{{ $name }}';

        $(document).on("click", ".notify-ctn-payment", function() {
            const data = $(this).data();
            $.ajax({
                url: route('commerce.getSellerInfoByCtn', data.ctn),
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    $("#payment-title").html(
                        `Vendedor: ${res.seller[0]}<br> Celular: ${res.seller[1]}`);
                    $("#sent-amount").text(`${data.coin_sym} ${data.amount}`);
                    $("#ctn").val(data.ctn);
                    $("#payment_type").val('CS');

                    if (res.data.lenght) {
                        switchPaymentMethod(data.code);
                        $("#btn-payment-confirm").prop('disabled', false);
                    } else {
                        $("#payment-subtitle")
                            .text('El vendedor no ha cargado los datos bancarios para esta moneda.');

                        $("#btn-payment-confirm").prop('disabled', true);
                    }

                    $(`#modal-${name}`).modal('show');
                },
                error: function(jqXHR, textStatus, errorThrown) {}
            });
        });
    </script>
@endsection
