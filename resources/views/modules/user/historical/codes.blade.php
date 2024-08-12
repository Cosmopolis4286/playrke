@extends('layouts.app_rke')
<title>Códigos PlayRKE - Canjeados</title>
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="margin-left:7px; font-size:20px;">Mis Códigos PlayRKE</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                           <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Precio</th>
                                            <th>Código</th>
                                            <th>WIN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($codes as $code)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($code->fecha)->format('d/m/Y') }}</td>
                                                <td>
                                                    {{ $code->precio }}
                                                    @if ($code->moneda == 1)
                                                        USDT
                                                    @elseif ($code->moneda == 2)
                                                        Bs/$
                                                    @else
                                                        R$
                                                    @endif
                                                </td>
                                                <td>{{ $code->codigo }}</td>
                                                <td>
                                                    @if ($code->win == 1)
                                                        {{ $code->premio }}
                                                        @if ($code->moneda == 1)
                                                            USDT
                                                        @elseif ($code->moneda == 2)
                                                            Bs/$
                                                        @else
                                                            R$
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection