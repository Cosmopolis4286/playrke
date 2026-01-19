@extends('layouts.auth-layout')
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
                        @include('includes.games.bingo.report.my-winners-report-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
