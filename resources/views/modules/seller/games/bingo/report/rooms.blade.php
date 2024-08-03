@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
                @component('components.coin-tabs', [
                    'routeName' => 'seller.reportSalesRooms',
                    'dateFilter' => $dateFilter,
                    'coin' => $coinId,
                    'coins' => $coins,
                ])
                @endcomponent
                @component('components.date-filter-buttons', [
                    'routeName' => 'seller.reportSalesRooms',
                    'dateFilter' => $dateFilter,
                    'coin' => $coinId,
                ])
                @endcomponent
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
                                        <th>Banca</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roomsData as $data)
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Reporte"
                                                    href="{{ route('seller.reportSalesByRoom', [
                                                        'room' => $data['room']->encrypted,
                                                        'coin' => encrypt($coinId),
                                                        'dateFilter' => $dateFilter,
                                                    ]) }}">
                                                    {{ $data['room']->encrypted }}
                                                </a>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($data['room']->date)->format('d/m/y') }}</td>
                                            <td>{{ $data['room']->time }}h</td>
                                            <td>{{ $coinName }}</td>
                                            <td>{{ $data['calculatedCtns'] }}</td>
                                            <td>{{ $coinSym }} {{ $data['calculatedSale'] }}</td>
                                            <td>{{ $coinSym }} {{ $data['calculatedCom'] }}</td>
                                            <td>{{ $coinSym }} {{ $data['calculatedBanca'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><b>Total:</b></td>
                                        <td>{{ collect($roomsData)->sum('calculatedCtns') }}</td>
                                        <td>{{ $coinSym }} {{ $totalSale }}</td>
                                        <td>{{ $coinSym }} {{ $totalComision }}</td>
                                        <td>{{ $coinSym }} {{ $totalBanca }}</td>
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
