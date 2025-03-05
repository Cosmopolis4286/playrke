@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.game.bingo.report.winners-ctns-tabs', [
                    'isCommerce' => true,
                    'countWinnersByCommerce' => $countWinnersByCommerce,
                    'countWinnersOtherCommerces' => $countWinnersOtherCommerces,
                ])
                @include('includes.date-filter-buttons', [
                    'routeWithCoin' => 'commerce.getBingoWinnersByCommerce',
                ])
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        @include('includes.game.bingo.report.my-winners-report-table')
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
@endsection
@section('scripts')
    @vite(['resources/js/rke/switch-payment-method.js', 'resources/js/rke/payment-confirm-button.js'])
    <script>
        const name = '{{ $name }}';

        $(document).on("click", ".notify-ctn-payment", function() {
            const data = $(this).data();
            $.ajax({
                url: `{{ route('commerce.getSellerInfoByCtn', ['ctn' => ':ctn']) }}`.replace(':ctn', data
                    .ctn),
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    $("#payment-title").html(
                        `Vendedor: ${res.seller[0]}<br> Celular: ${res.seller[1]}`);
                    $("#sent-amount").text(`${data.coin_sym} ${data.amount}`);
                    $("#ctn").val(data.ctn);

                    if (res.data.lenght) {
                        switchPaymentMethod(data.code);
                    } else {
                        $("#payment-subtitle").text(
                            'El vendedor no ha cargado los datos bancarios para esta moneda.');
                    }

                    $(`#modal-${name}`).modal('show');
                },
                error: function(jqXHR, textStatus, errorThrown) {}
            });
        });
    </script>
@endsection
