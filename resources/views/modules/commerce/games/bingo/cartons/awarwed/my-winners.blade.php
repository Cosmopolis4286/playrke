@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
                @include('components.game.bingo.winners-ctns-tabs', [
                    'isCommerce' => true,
                    'countWinnersByCommerce' => $countWinnersByCommerce,
                    'countWinnersOtherCommerces' => $countWinnersOtherCommerces,
                ])
                @include('components.date-filter-buttons', [
                    'routeName' => 'commerce.getBingoWinnersByCommerce',
                    'dateFilter' => $dateFilter,
                ])
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        @include('components.game.bingo.my-winners-report-table', [
                            'winners' => $winners,
                            'paymentsOtherCommerces' => $paymentsOtherCommerces,
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
    <script src="{{ asset('js/components/switch-payment-method.js') }}"></script>
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
    <script src="{{ asset('js/components/view-player-ctns.js') }}"></script>
    <script src="{{ asset('js/components/payment-confirm-button.js') }}"></script>
@endsection
