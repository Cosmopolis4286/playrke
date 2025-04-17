@extends('layouts.app_rke')
@section('title', $title)

@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs')
                @include('includes.date-filter-buttons', ['routeWithCoin' => $routeWithCoin['route']])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @include('includes.games.bingo.report.report-table-bingo')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
