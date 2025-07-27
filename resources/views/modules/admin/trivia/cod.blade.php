@extends('layouts.layout-online')
<title>Tickets Trivias - PlayRKE</title>
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
                        <span style="margin-left:7px; font-size:20px;">Tickets Trivia</span>
                        @if (Session::has('mensaje'))
                            <caption>
                                <div class="alert alert-info alert-dismissible" role="alert">
                                    <strong></strong> {{ Session::get('mensaje') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            </caption>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered  nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Trivia ID</th>
                                                <th>Ticket</th>
                                                <th>Estatus</th>
                                                <th>Usuario</th>
                                                <th>Opc.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($trivias as $trivia)
                                                <tr>
                                                    <td>Trivia</td>
                                                    <td><a href="#">{{ $trivia->tickets }}</a></td>
                                                    <td>Status</td>
                                                    <td>username de quien canjeo el ticket</td>
                                                    <td>
                                                        <a href="{{ url('trivia/edit/' . $trivia->id) }}"
                                                            class="btn bg-cyan"><i class="fas fa-edit"></i>
                                                        </a>
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
