@extends('layouts.layout-online')
@section('title', $title)
@section('content')
    <div class="col">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.currency-tabs')
                @include('includes.date-filter-buttons')
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Sala</th>
                                        <th>Tipo</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Moneda</th>
                                        <th>CTNs</th>
                                        <th>Venta</th>
                                        <th>%Com</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roomsData as $room)
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Reporte"
                                                    href="{{ route('bingo.reportSalesByRoom', [
                                                        'room' => $room['encrypted'],
                                                        'currency' => encrypt($currency),
                                                    ]) }}">
                                                    {{ $room['encrypted'] }}
                                                </a>
                                            </td>
                                            <td>{{ $room['roomType'] }}</td>
                                            <td>{{ $room['date'] }}
                                            </td>
                                            <td>{{ $room['time'] }}h</td>
                                            <td>{{ $currencyData->name }} ({{ $currencyData->iso_code }})</td>
                                            <td>{{ $room['ctns'] }}</td>
                                            <td>{{ $currencyData->iso_code }} {{ $room['sales'] }}</td>
                                            <td>{{ $currencyData->iso_code }} {{ $room['sellerCommn'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if (!empty($roomsData))
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-right"><b>Total:</b></td>
                                            <td>{{ $totalsFormatted['ctns'] }}</td>
                                            <td>{{ $currencyData->iso_code }} {{ $totalsFormatted['sales'] }}</td>
                                            <td>{{ $currencyData->iso_code }} {{ $totalsFormatted['sellerCommn'] }}</td>
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
