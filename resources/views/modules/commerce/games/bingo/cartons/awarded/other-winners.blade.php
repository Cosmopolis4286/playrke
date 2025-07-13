@extends('layouts.layout-online')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.games.bingo.report.winners-ctns-tabs', [
                    'isCommerce' => false,
                    'countWinnersByCommerce' => $countWinnersByCommerce,
                    'countWinnersOtherCommerces' => $countWinnersOtherCommerces,
                ])
                @include('includes.date-filter-buttons')
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        @include('includes.games.bingo.report.other-winners-report-table')
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
    @include('includes.games.bingo.layouts.modal-other-bingo-rooms-pending-payments')
@endsection
@section('scripts')
@endsection
