@extends('layouts.auth-layout')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.currency-tabs')
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
                                        <th>CTNs</th>
                                        <th>Venta</th>
                                        <th>%Com</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalCommn = 0;
                                    @endphp
                                    @foreach ($playersRoom as $player)
                                        @php
                                            $saleAmount = $player->CtnsLot->sum('sale_amount');
                                            $commn = $player->CtnsLot->sum('seller_commn');
                                            $totalCommn += $commn;
                                        @endphp
                                        <tr>
                                            <td>{{ $player->name }}</td>
                                            <td>{{ $player->lastname }}</td>
                                            <td>{{ $player->phone }}</td>
                                            <td>{{ $player->CtnsLot->sum('cards_purchased') }}</td>
                                            <td>{{ $currencyData->iso_code }} {{ number_format($saleAmount, 2, '.', ',') }}</td>
                                            <td>{{ $currencyData->iso_code }} {{ number_format($commn, 2, '.', ',') }}</td>
                                            <td>
                                                <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                                    href="{{ route('bingo.in-game', ['player' => $player->link]) }}">
                                                    <i class="fa-solid fa-eye"></i> CTNs
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right"><b>Total:</b></td>
                                        <td>{{ $totalsFormatted['cards_purchased'] }}</td>
                                        <td>{{ $currencyData->iso_code }} {{ $totalsFormatted['sales'] }}</td>
                                        <td colspan="2">
                                            {{ $currencyData->iso_code }} {{ number_format($totalCommn, 2, '.', ',') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
