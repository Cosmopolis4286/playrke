@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs')
                @include('includes.date-filter-buttons', ['routeWithCoin' => 'seller.reportSalesRooms'])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Sala</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Moneda</th>
                                        <th>#Ctn</th>
                                        <th>Venta</th>
                                        <th>%Com</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roomsData as $data)
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Reporte"
                                                    href="{{ route('seller.reportSalesByRoom', [
                                                        'room' => $data['room']->encrypted,
                                                        'coin' => encrypt($coin),
                                                    ]) }}">
                                                    {{ $data['room']->encrypted }}
                                                </a>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($data['room']->Config->date)->format('d/m/y') }}
                                            </td>
                                            <td>{{ $data['room']->Config->time }}h</td>
                                            <td>{{ $coinName }} ({{ $coinSym }})</td>
                                            <td>{{ $data['calculatedCtns'] }}</td>
                                            <td>{{ $coinSym }} {{ $data['calculatedSale'] }}</td>
                                            <td>{{ $coinSym }} {{ $data['calculatedCom'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if (count($roomsData))
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right"><b>Total:</b></td>
                                            <td>{{ collect($roomsData)->sum('calculatedCtns') }}</td>
                                            <td>{{ $coinSym }} {{ $totalSale }}</td>
                                            <td>{{ $coinSym }} {{ $totalComision }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
