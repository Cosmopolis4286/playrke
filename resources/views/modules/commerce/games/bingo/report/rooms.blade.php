@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
                @component('components.coin-tabs', [
                    'coins' => $coins,
                    'routeName' => 'commerce.reportSalesRooms',
                    'dateFilter' => $dateFilter,
                    'coin' => $coinId,
                ])
                @endcomponent
                @component('components.date-filter-buttons', [
                    'routeName' => 'commerce.reportSalesRooms',
                    'dateFilter' => $dateFilter,
                    'coin' => $coinId,
                ])
                @endcomponent
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @component('components.game.bingo.report-table-bingo', [
                            'wallet' => $wallet,
                            'roomData' => $roomData,
                            'totals' => $totals,
                            'coinSym' => $coinSym,
                            'coinName' => $coinName,
                        ])
                        @endcomponent
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
