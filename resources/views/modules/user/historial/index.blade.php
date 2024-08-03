@extends('layouts.app_rke')
<title>Historial de Jugadas - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="margin-left:7px; font-size:20px;">Historial de Jugadas</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                           <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Juego</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Moneda</th>
                                            <th>jackpot</th>
                                            <th>Crédito</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($hist_games as $hist)
                                            <tr>
                                                <td>{{ $hist->name_game }}</td>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($hist->date)->format('d/m/Y') }}
                                                </td>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($hist->time)->format('h:m A') }}
                                                </td>
                                                <td>{{ $hist->name }}</td>
                                                <td>{{ $hist->boat }}</td>
                                                <td>{{ $hist->gain }}</td>
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
