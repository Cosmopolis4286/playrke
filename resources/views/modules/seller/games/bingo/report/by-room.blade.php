@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
                @component('components.coin-tabs', [
                    'routeName' => 'seller.reportSalesByRoom',
                    'dateFilter' => $dateFilter,
                    'room' => $generateUniqueCodeed,
                    'coin' => $coinId,
                    'coins' => $coins,
                ])
                @endcomponent
                @component('components.date-filter-buttons', [
                    'routeName' => 'seller.reportSalesByRoom',
                    'dateFilter' => $dateFilter,
                    'room' => $generateUniqueCodeed,
                    'coin' => $coinId,
                    'coins' => $coins,
                ])
                @endcomponent
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-3">
                            <h5>Mis Participantes</h5>
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>Celular</th>
                                        <th>Moneda</th>
                                        <th>#CTNs</th>
                                        <th>Total</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($playersRoom as $player)
                                        <tr>
                                            <td>{{ $player->name }}</td>
                                            <td>{{ $player->lastname }}</td>
                                            <td>{{ $player->phone }}</td>
                                            <td>{{ $coin->long_name }} ({{ $coin->symbol }})</td>
                                            <td>{{ $player->CtnsLot->sum('ctns') }}</td>
                                            <td>{{ $player->Coin->symbol }} {{ $player->ctns_purchased }}</td>
                                            <td>
                                                <button class="btn btn-sm bg-primary show-ctns"
                                                    data-player_link="{{ $player->link }}">
                                                    <i class="mr-1 fa-solid fa-eye"></i>CTNs
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><b>Total:</b></td>
                                        <td>{{ $totalCtnsByRoom }}</td>
                                        <td>{{ $coin->symbol }} {{ $totalCtnsPurchased }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input disabled type="hidden" id="rout-rke" value="{{ $routCtns }}">
@endsection
@section('scripts')
    @vite('resources/js/games/bingo/components/view-player-ctns.js')
    <script>
        const commerce = '<?php echo $commerce; ?>'
    </script>
    {{-- <script src="{{ asset('js/components/view-player-ctns.js') }}"></script> --}}
@endsection
