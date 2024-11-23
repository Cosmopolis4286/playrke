@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs', [
                    'coins' => $coins,
                    'routeWithCoin' => 'commerce.reportSalesRooms',
                    'dateFilter' => $dateFilter,
                    'coin' => $coin,
                ])
                @include('includes.date-filter-buttons', [
                    'routeWithCoin' => 'commerce.reportSalesRooms',
                    'dateFilter' => $dateFilter,
                    'coin' => $coin,
                ])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @include('includes.game.bingo.report.report-table-bingo', [
                            'wallet' => $wallet,
                            'roomData' => $roomData,
                            'totals' => $totals,
                            'coinSym' => $coinSym,
                            'coinName' => $coinName,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
