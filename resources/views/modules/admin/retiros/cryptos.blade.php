@extends('layouts.app_rke')
<title>PlayRKE - RT Pendientes</title>
@section('fnd-content')
<div class="content-wrapper">
@endsection
@section('content')
    <div class="card bg-rke">
        <div class="card-header">
            <h4 class="my-n2 text-center">Retiros Pendientes</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="table-responsive">
                        <table id="t_1" class="table table-striped table-bordered  nowrap"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>TC</th>
                                    <th>Usuario</th>
                                    <th>Transacción</th>
                                    <th>Monto</th>
                                    <th>Banco Receptor</th>
                                    <th>Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($esperas as $retiro)
                                    <tr>
                                        <td>{{ $retiro->fecha_retiro }}</td>
                                        <td>{{$retiro->retiroCommerceExchangeRate->CommerceExchangeRate_RKE}}</td>
                                        @foreach ($users as $user)
                                            @if ($user->id == $retiro->usuario_id)
                                                <td>{{ $user->username }}</td>
                                            @endif
                                        @endforeach
                                        <td>{{ $retiro->descripcion }}</td>
                                        <td>{{ $retiro->monto }}</td>


                                        @foreach ($bancos as $banco)
                                            @if ($banco->id == $retiro->banco_id)
                                                <td>{{ $banco->nombre }}</td>
                                            @endif
                                        @endforeach
                                        <th>
                                            <a href="{{route('retiro.aceptar', $retiro->id)}}">
                                                <svg class="icon icon-tabler icon-tabler-circle-check" width="28"
                                                    height="28" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00abfb"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="12" r="9" />
                                                    <path d="M9 12l2 2l4 -4" />
                                                </svg>
                                            </a>
                                            <a href="{{ url('retiro/rechazar/' . $retiro['id']) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-circle-x" width="28"
                                                    height="28" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ff2825"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="12" r="9" />
                                                    <path d="M10 10l4 4m0 -4l-4 4" />
                                                </svg>
                                            </a>
                                        </th>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
{{--DT Retiros Efectuados--}}
<div class="card bg-rke">
        <div class="card-header">
            <h4 class="my-n2 text-center">Retiros Efectuados</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="table-responsive">
                        <table id="t_1" class="table table-striped table-bordered  nowrap"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>TC</th>
                                    <th>Usuario</th>
                                    <th>Transacción</th>
                                    <th>Monto</th>
                                    <th>Banco R</th>
                                    <th>Referencia</th>
                                    <th>Banco E</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($retiros as $retiro)
                                    <tr>
                                        <td>{{ $retiro->fecha_retiro }}</td>
                                        <td>{{$retiro->retiroCommerceExchangeRate->CommerceExchangeRate_RKE}}</td>
                                        @foreach ($users as $user)
                                            @if ($user->id == $retiro->usuario_id)
                                                <td>{{ $user->username }}</td>
                                            @endif
                                        @endforeach
                                        <td>{{ $retiro->descripcion }}</td>
                                        <td>{{ $retiro->monto }}</td>
                                        <td>{{ $retiro->retiroBancoRE->nombre}}</td>
                                        <td>{{ $retiro->referencia }}</td>
                                        {{-- @foreach ($bancos as $banco)
                                            @if ($banco->id == $recarga->banco_id)
                                                <td>{{ $banco->nombre }}</td>
                                            @endif
                                        @endforeach --}}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')  
@endsection