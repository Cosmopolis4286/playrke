@extends('layouts.app_rke')
<title>Sorteos PlayRKE</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-rke">
                    <div class="card-header">
                        <a href="{{ url('/home') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="margin-left:7px; font-size:20px;">Sorteos PlayRKE</span>
                    </div>
                    <div class="col-11 mt-2 mb-n1 alert alert-primary text-center" style="margin:0 auto;">
                        Usamos los servicios de terceros (AppSorteos), para dar validez de un proceso aleatorio y con
                        resultados transparentes.
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
                                                <th>Hora</th>
                                                <th>Participantes</th>
                                                <th>Ganadores</th>
                                                <th>Premios</th>
                                                <th>Certificado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($luck as $luck)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($luck->fecha)->format('d/m/Y') }}</td>
                                                    <td>{{ $luck->hora }}</td>
                                                    <td>{{ $luck->participants }} PJ</td>
                                                    <td>{{ $luck->winners }} PJ</td>
                                                    <td>${{ $luck->premios }} c/u</td>
                                                    <td><a href="{{ $luck->certificate }}">Ver certificado</a></td>
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