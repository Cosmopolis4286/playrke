@extends('layouts.auth-layout')
<title>PlayRKE - Reporte de Ventas</title>
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="margin-left:7px; font-size:20px;">Reporte de Ventas</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-bordered my-2 w-100">
                                    style="width:100%">
                                    <thead>
                                        @php $totalSale = $totalComision = 0; @endphp
                                        <tr>
                                            <th>Sala</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Venta</th>
                                            <th>%Com</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rooms as $room)
                                            @php
                                                $roomSale = $roomSaleCom = 0;
                                            @endphp
                                            <tr>
                                                <td><a href="#">{{ $room->encrypted }}</a></td>
                                                <td>{{ \Carbon\Carbon::parse($room->date)->format('d/m/y') }}</td>
                                                <td>{{ $room->time }}</td>
                                                @foreach ($allctnsSold as $sold)
                                                    @if ($sold->room == $room->id)
                                                        @php
                                                            $roomSale += $sold->ctns * $room->card_price;
                                                        @endphp
                                                    @endif
                                                @endforeach
                                                @php
                                                    $roomSaleCom = ($roomSale * $sold->Seller->com) / 100;
                                                    $totalSale += $roomSale;
                                                    $totalComision += $roomSaleCom;
                                                @endphp
                                                <td>{{ $room->Currency->symbol }} {{ $roomSale }}</td>
                                                <td>{{ $room->Currency->symbol }} {{ $roomSaleCom }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td><small><b>Total Venta: {{ $room->Currency->symbol }}
                                                        {{ $totalSale }}</b></small></td>
                                            <td><small><b>Total %Com: {{ $room->Currency->symbol }}
                                                        {{ $totalComision }}</b></small></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
